<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('users_view', 'You do not have permission to view roles');

$pdo = db();

$stmt = $pdo->query("
    SELECT
        id,
        name,
        code,
        description,
        created_at,
        updated_at
    FROM roles
    ORDER BY id ASC
");

$roles = [];
foreach ($stmt->fetchAll() as $row) {
    $roleId = (int)$row['id'];

    $roles[] = [
        'id' => $roleId,
        'name' => (string)$row['name'],
        'code' => (string)$row['code'],
        'description' => (string)($row['description'] ?? ''),
        'permission_codes' => admin_role_permission_codes($roleId),
        'created_at' => (string)$row['created_at'],
        'updated_at' => (string)$row['updated_at'],
    ];
}

json_response(true, ['roles' => $roles]);
