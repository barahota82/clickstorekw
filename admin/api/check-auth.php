<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('GET');

if (!is_admin_logged_in()) {
    json_response(false, ['message' => 'Unauthorized'], 401);
}

$user = admin_current_user_row();

if (!$user) {
    json_response(false, ['message' => 'Admin user not found'], 404);
}

json_response(true, [
    'user' => [
        'id'            => (int)$user['id'],
        'full_name'     => (string)$user['full_name'],
        'username'      => (string)$user['username'],
        'email'         => (string)($user['email'] ?? ''),
        'role_id'       => (int)$user['role_id'],
        'role_name'     => (string)$user['role_name'],
        'role_code'     => (string)$user['role_code'],
        'is_active'     => (int)$user['is_active'],
        'last_login_at' => (string)($user['last_login_at'] ?? ''),
    ],
    'permissions' => admin_frontend_permissions_payload(),
    'permission_codes' => admin_effective_permission_codes($user),
]);
