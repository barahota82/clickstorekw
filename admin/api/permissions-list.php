<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('users_view', 'You do not have permission to view permissions');

$pdo = db();

$stmt = $pdo->query("
    SELECT
        id,
        name,
        code,
        module,
        created_at
    FROM permissions
    ORDER BY module ASC, id ASC
");

$permissions = [];
$grouped = [];

foreach ($stmt->fetchAll() as $row) {
    $item = [
        'id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'code' => (string)$row['code'],
        'module' => (string)$row['module'],
        'created_at' => (string)$row['created_at'],
    ];

    $permissions[] = $item;
    $grouped[$item['module']][] = $item;
}

json_response(true, [
    'permissions' => $permissions,
    'grouped' => $grouped,
]);
