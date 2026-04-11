<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('view_users', 'ليس لديك صلاحية عرض المستخدمين والصلاحيات.');

$pdo = db();

$search = trim((string)($_GET['search'] ?? ''));

$userWhere = [];
$userParams = [];

if ($search !== '') {
    $userWhere[] = "(
        u.username LIKE :search
        OR u.full_name LIKE :search
        OR u.email LIKE :search
        OR r.name LIKE :search
    )";
    $userParams['search'] = '%' . $search . '%';
}

$userSql = "
    SELECT
        u.id,
        u.username,
        u.full_name,
        u.email,
        u.role_id,
        u.is_active,
        u.last_login_at,
        u.created_at,
        u.updated_at,
        r.name AS role_name
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
";

if ($userWhere) {
    $userSql .= " WHERE " . implode(' AND ', $userWhere);
}

$userSql .= " ORDER BY u.id DESC";

$userStmt = $pdo->prepare($userSql);
$userStmt->execute($userParams);
$userRows = $userStmt->fetchAll(PDO::FETCH_ASSOC);

$roleStmt = $pdo->query("
    SELECT
        id,
        name,
        display_name,
        is_active,
        created_at,
        updated_at
    FROM roles
    ORDER BY id ASC
");
$roleRows = $roleStmt ? $roleStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$permissionStmt = $pdo->query("
    SELECT
        id,
        code,
        display_name,
        group_name,
        is_active,
        created_at,
        updated_at
    FROM permissions
    ORDER BY group_name ASC, id ASC
");
$permissionRows = $permissionStmt ? $permissionStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$userIds = array_map(static fn(array $row): int => (int)$row['id'], $userRows);
$roleIds = array_map(static fn(array $row): int => (int)$row['id'], $roleRows);

$userPermissionsMap = [];
if ($userIds) {
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $pdo->prepare("
        SELECT
            up.user_id,
            p.code
        FROM user_permissions up
        INNER JOIN permissions p ON p.id = up.permission_id
        WHERE up.user_id IN ($placeholders)
    ");
    $stmt->execute($userIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $uid = (int)$row['user_id'];
        if (!isset($userPermissionsMap[$uid])) {
            $userPermissionsMap[$uid] = [];
        }
        $userPermissionsMap[$uid][] = (string)$row['code'];
    }
}

$rolePermissionsMap = [];
if ($roleIds) {
    $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
    $stmt = $pdo->prepare("
        SELECT
            rp.role_id,
            p.code
        FROM role_permissions rp
        INNER JOIN permissions p ON p.id = rp.permission_id
        WHERE rp.role_id IN ($placeholders)
    ");
    $stmt->execute($roleIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $rid = (int)$row['role_id'];
        if (!isset($rolePermissionsMap[$rid])) {
            $rolePermissionsMap[$rid] = [];
        }
        $rolePermissionsMap[$rid][] = (string)$row['code'];
    }
}

$users = array_map(static function (array $row) use ($userPermissionsMap): array {
    $userId = (int)$row['id'];

    return [
        'id' => $userId,
        'username' => (string)$row['username'],
        'full_name' => (string)($row['full_name'] ?? ''),
        'email' => (string)($row['email'] ?? ''),
        'role_id' => $row['role_id'] !== null ? (int)$row['role_id'] : null,
        'role_name' => (string)($row['role_name'] ?? ''),
        'is_active' => (bool)$row['is_active'],
        'last_login_at' => (string)($row['last_login_at'] ?? ''),
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
        'direct_permission_codes' => $userPermissionsMap[$userId] ?? []
    ];
}, $userRows);

$roles = array_map(static function (array $row) use ($rolePermissionsMap): array {
    $roleId = (int)$row['id'];

    return [
        'id' => $roleId,
        'name' => (string)$row['name'],
        'display_name' => (string)($row['display_name'] ?: $row['name']),
        'is_active' => (bool)$row['is_active'],
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
        'permission_codes' => $rolePermissionsMap[$roleId] ?? []
    ];
}, $roleRows);

$permissions = array_map(static function (array $row): array {
    return [
        'id' => (int)$row['id'],
        'code' => (string)$row['code'],
        'display_name' => (string)($row['display_name'] ?: $row['code']),
        'group_name' => (string)($row['group_name'] ?: 'general'),
        'is_active' => (bool)$row['is_active'],
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? '')
    ];
}, $permissionRows);

json_response(true, [
    'users' => $users,
    'roles' => $roles,
    'permissions' => $permissions
]);
