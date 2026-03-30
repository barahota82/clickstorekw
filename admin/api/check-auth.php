<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

if (!is_admin_logged_in()) {
    json_response(false, ['message' => 'Unauthorized'], 401);
}

json_response(true, [
    'user' => [
        'id' => (int)($_SESSION['admin_user_id'] ?? 0),
        'full_name' => (string)($_SESSION['admin_full_name'] ?? ''),
        'username' => (string)($_SESSION['admin_username'] ?? ''),
        'role_id' => (int)($_SESSION['admin_role_id'] ?? 0),
        'role_name' => (string)($_SESSION['admin_role_name'] ?? ''),
        'role_code' => (string)($_SESSION['admin_role_code'] ?? '')
    ]
]);
