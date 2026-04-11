<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('POST');
require_admin_auth_json();
admin_require_permission_json('manage_users', 'ليس لديك صلاحية تعديل المستخدمين.');

$data = get_request_json();

$userId = (int)($data['user_id'] ?? 0);
$roleId = (int)($data['role_id'] ?? 0);
$isActive = isset($data['is_active']) ? (int)!!$data['is_active'] : null;

if ($userId <= 0) {
    json_response(false, ['message' => 'User is required'], 422);
}

$pdo = db();

$userStmt = $pdo->prepare("SELECT id, username, role_id, is_active FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    json_response(false, ['message' => 'User not found'], 404);
}

if ($roleId > 0) {
    $roleStmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ? LIMIT 1");
    $roleStmt->execute([$roleId]);
    $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        json_response(false, ['message' => 'Role not found'], 404);
    }
}

$fields = [];
$params = [];

if ($roleId > 0) {
    $fields[] = "role_id = ?";
    $params[] = $roleId;
}

if ($isActive !== null) {
    $fields[] = "is_active = ?";
    $params[] = $isActive;
}

if (!$fields) {
    json_response(false, ['message' => 'No changes provided'], 422);
}

$fields[] = "updated_at = NOW()";
$params[] = $userId;

$sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

if (function_exists('admin_activity_log')) {
    admin_activity_log(
        'save_user_role',
        'users',
        'user',
        $userId,
        'Updated user role/status | username: ' . (string)$user['username']
    );
}

json_response(true, ['message' => 'User updated successfully']);
