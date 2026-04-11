<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('POST');
require_admin_auth_json();
admin_require_permission_json('manage_permissions', 'ليس لديك صلاحية تعديل صلاحيات المستخدمين.');

$data = get_request_json();

$userId = (int)($data['user_id'] ?? 0);
$permissionCodes = $data['permission_codes'] ?? [];

if ($userId <= 0) {
    json_response(false, ['message' => 'User is required'], 422);
}

if (!is_array($permissionCodes)) {
    json_response(false, ['message' => 'permission_codes must be an array'], 422);
}

$permissionCodes = array_values(array_unique(array_filter(array_map(static function ($value): string {
    return trim((string)$value);
}, $permissionCodes))));

$pdo = db();

$userStmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    json_response(false, ['message' => 'User not found'], 404);
}

$permissionIds = [];
if ($permissionCodes) {
    $placeholders = implode(',', array_fill(0, count($permissionCodes), '?'));
    $permStmt = $pdo->prepare("
        SELECT id, code
        FROM permissions
        WHERE code IN ($placeholders)
    ");
    $permStmt->execute($permissionCodes);
    $permRows = $permStmt->fetchAll(PDO::FETCH_ASSOC);

    $foundCodes = array_map(static fn(array $row): string => (string)$row['code'], $permRows);
    $missingCodes = array_values(array_diff($permissionCodes, $foundCodes));

    if ($missingCodes) {
        json_response(false, [
            'message' => 'Unknown permission codes',
            'missing_codes' => $missingCodes
        ], 422);
    }

    $permissionIds = array_map(static fn(array $row): int => (int)$row['id'], $permRows);
}

$pdo->beginTransaction();

try {
    $deleteStmt = $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?");
    $deleteStmt->execute([$userId]);

    if ($permissionIds) {
        $insertStmt = $pdo->prepare("
            INSERT INTO user_permissions (user_id, permission_id)
            VALUES (?, ?)
        ");

        foreach ($permissionIds as $permissionId) {
            $insertStmt->execute([$userId, $permissionId]);
        }
    }

    $pdo->commit();

    if (function_exists('admin_activity_log')) {
        admin_activity_log(
            'save_user_permissions',
            'users',
            'user',
            $userId,
            'Updated direct user permissions | username: ' . (string)$user['username']
        );
    }

    json_response(true, ['message' => 'User permissions updated successfully']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to update user permissions',
        'error' => $e->getMessage()
    ], 500);
}
