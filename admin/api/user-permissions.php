<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_admin_auth_json();
admin_require_permission_json('users_manage', 'You do not have permission to manage user permissions');

$pdo = db();

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $userId = (int)($_GET['user_id'] ?? 0);

    if ($userId <= 0) {
        json_response(false, ['message' => 'User ID is required'], 422);
    }

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.full_name,
            u.username,
            u.email,
            u.role_id,
            r.name AS role_name,
            r.code AS role_code
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(false, ['message' => 'User not found'], 404);
    }

    $rawStmt = $pdo->prepare("
        SELECT
            up.permission_id,
            up.is_allowed,
            p.code,
            p.name,
            p.module
        FROM user_permissions up
        INNER JOIN permissions p ON p.id = up.permission_id
        WHERE up.user_id = ?
        ORDER BY p.module ASC, p.id ASC
    ");
    $rawStmt->execute([$userId]);

    $directPermissions = [];
    foreach ($rawStmt->fetchAll() as $row) {
        $directPermissions[] = [
            'permission_id' => (int)$row['permission_id'],
            'code' => (string)$row['code'],
            'name' => (string)$row['name'],
            'module' => (string)$row['module'],
            'is_allowed' => (int)$row['is_allowed'],
        ];
    }

    $effective = admin_effective_permission_codes([
        'id' => (int)$user['id'],
        'role_id' => (int)$user['role_id'],
        'role_code' => (string)$user['role_code'],
    ]);

    json_response(true, [
        'user' => [
            'id' => (int)$user['id'],
            'full_name' => (string)$user['full_name'],
            'username' => (string)$user['username'],
            'email' => (string)($user['email'] ?? ''),
            'role_id' => (int)$user['role_id'],
            'role_name' => (string)$user['role_name'],
            'role_code' => (string)$user['role_code'],
        ],
        'direct_permissions' => $directPermissions,
        'effective_permission_codes' => $effective,
        'role_permission_codes' => admin_role_permission_codes((int)$user['role_id']),
    ]);
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $data = get_request_json();

    $userId = (int)($data['user_id'] ?? 0);
    $permissions = $data['permissions'] ?? null;

    if ($userId <= 0) {
        json_response(false, ['message' => 'User ID is required'], 422);
    }

    if (!is_array($permissions)) {
        json_response(false, ['message' => 'Permissions payload must be an array'], 422);
    }

    $userStmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ? LIMIT 1");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        json_response(false, ['message' => 'User not found'], 404);
    }

    if ((int)$user['id'] === admin_current_user_id()) {
        json_response(false, ['message' => 'You cannot edit your own direct permissions from this action'], 422);
    }

    $allStmt = $pdo->query("SELECT id, code FROM permissions");
    $permissionIdByCode = [];
    foreach ($allStmt->fetchAll() as $row) {
        $permissionIdByCode[(string)$row['code']] = (int)$row['id'];
    }

    try {
        $pdo->beginTransaction();

        $deleteStmt = $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?");
        $deleteStmt->execute([$userId]);

        $insertStmt = $pdo->prepare("
            INSERT INTO user_permissions
            (
                user_id,
                permission_id,
                is_allowed,
                created_at
            )
            VALUES
            (
                :user_id,
                :permission_id,
                :is_allowed,
                NOW()
            )
        ");

        foreach ($permissions as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = trim((string)($row['code'] ?? ''));
            $isAllowed = isset($row['is_allowed']) ? (int)$row['is_allowed'] : 1;

            if ($code === '' || !isset($permissionIdByCode[$code])) {
                continue;
            }

            $insertStmt->execute([
                'user_id' => $userId,
                'permission_id' => $permissionIdByCode[$code],
                'is_allowed' => $isAllowed === 1 ? 1 : 0,
            ]);
        }

        $pdo->commit();

        admin_activity_log(
            'update_user_permissions',
            'users',
            'user',
            $userId,
            'Updated direct permissions for user: ' . (string)$user['username']
        );

        json_response(true, [
            'message' => 'User permissions saved successfully'
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        json_response(false, [
            'message' => 'Failed to save user permissions',
            'error' => $e->getMessage()
        ], 500);
    }
}

json_response(false, ['message' => 'Invalid request method'], 405);
