<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

if (!function_exists('admin_normalize_permission_key')) {
    function admin_normalize_permission_key(string $value): string
    {
        return strtolower(trim($value));
    }
}

if (!function_exists('admin_is_full_access_role')) {
    function admin_is_full_access_role(string $roleName): bool
    {
        $roleName = strtolower(trim($roleName));
        return in_array($roleName, ['admin', 'super_admin', 'super admin'], true);
    }
}

if (!function_exists('admin_table_exists')) {
    function admin_table_exists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
        ");
        $stmt->execute(['table_name' => $tableName]);
        return (int)$stmt->fetchColumn() > 0;
    }
}

if (!function_exists('admin_get_table_columns')) {
    function admin_get_table_columns(PDO $pdo, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT COLUMN_NAME
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
        ");
        $stmt->execute(['table_name' => $tableName]);

        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return is_array($columns) ? $columns : [];
    }
}

if (!function_exists('admin_pick_existing_column')) {
    function admin_pick_existing_column(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }
        return null;
    }
}

if (!function_exists('admin_permissions_from_database')) {
    function admin_permissions_from_database(PDO $pdo, int $roleId): array
    {
        if ($roleId <= 0) {
            return [];
        }

        if (
            !admin_table_exists($pdo, 'permissions') ||
            !admin_table_exists($pdo, 'role_permissions')
        ) {
            return [];
        }

        $permissionsColumns = admin_get_table_columns($pdo, 'permissions');
        $rolePermissionsColumns = admin_get_table_columns($pdo, 'role_permissions');

        $permissionIdColumn = admin_pick_existing_column($permissionsColumns, ['id', 'permission_id']);
        $permissionKeyColumn = admin_pick_existing_column($permissionsColumns, ['permission_key', 'slug', 'code', 'name']);

        $rpRoleIdColumn = admin_pick_existing_column($rolePermissionsColumns, ['role_id', 'roles_id']);
        $rpPermissionIdColumn = admin_pick_existing_column($rolePermissionsColumns, ['permission_id', 'permissions_id']);

        if (!$permissionIdColumn || !$permissionKeyColumn || !$rpRoleIdColumn || !$rpPermissionIdColumn) {
            return [];
        }

        $sql = "
            SELECT p.`{$permissionKeyColumn}` AS permission_key
            FROM `role_permissions` rp
            INNER JOIN `permissions` p
                ON p.`{$permissionIdColumn}` = rp.`{$rpPermissionIdColumn}`
            WHERE rp.`{$rpRoleIdColumn}` = :role_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['role_id' => $roleId]);

        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_unique(array_filter(array_map(
            static fn($item) => admin_normalize_permission_key((string)$item),
            is_array($rows) ? $rows : []
        ))));
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['message' => 'Invalid request method'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    json_response(false, ['message' => 'اسم المستخدم وكلمة المرور مطلوبان'], 422);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.full_name,
            u.username,
            u.password_hash,
            u.is_active,
            u.role_id,
            r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.username = :username
        LIMIT 1
    ");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(false, ['message' => 'بيانات الدخول غير صحيحة'], 401);
    }

    if ((int)$user['is_active'] !== 1) {
        json_response(false, ['message' => 'هذا المستخدم غير مفعل'], 403);
    }

    if (!password_verify($password, (string)$user['password_hash'])) {
        json_response(false, ['message' => 'بيانات الدخول غير صحيحة'], 401);
    }

    $roleId = (int)($user['role_id'] ?? 0);
    $roleName = (string)($user['role_name'] ?? 'Unknown');
    $permissions = [];

    if (admin_is_full_access_role($roleName)) {
        $permissions = ['admin.full_access'];
    } else {
        $permissions = admin_permissions_from_database($pdo, $roleId);
    }

    $_SESSION['admin_user_id'] = (int)$user['id'];
    $_SESSION['admin_full_name'] = (string)$user['full_name'];
    $_SESSION['admin_username'] = (string)$user['username'];
    $_SESSION['admin_role_name'] = $roleName;
    $_SESSION['admin_role_id'] = $roleId;
    $_SESSION['admin_permissions'] = $permissions;

    json_response(true, [
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => [
            'id' => (int)$user['id'],
            'full_name' => (string)$user['full_name'],
            'username' => (string)$user['username'],
            'role_name' => $roleName,
            'role_id' => $roleId,
        ],
        'permissions' => $permissions
    ]);
} catch (Throwable $e) {
    json_response(false, ['message' => 'حدث خطأ في تسجيل الدخول'], 500);
}
