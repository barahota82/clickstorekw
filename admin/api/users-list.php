<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, ['message' => 'Invalid request method'], 405);
}

require_admin_auth_json();

$pdo = db();

$users = $pdo->query("
    SELECT
        u.id,
        u.full_name,
        u.username,
        u.email,
        u.role_id,
        u.is_active,
        u.last_login_at,
        u.created_at,
        u.updated_at,
        r.name AS role_name
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    ORDER BY u.id DESC
")->fetchAll();

json_response(true, ['users' => $users]);
