<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

if (!function_exists('admin_normalize_permission_key')) {
    function admin_normalize_permission_key(string $value): string
    {
        return strtolower(trim($value));
    }
}

if (!function_exists('admin_permissions_from_session')) {
    function admin_permissions_from_session(): array
    {
        $sessionCandidates = [
            $_SESSION['admin_permissions'] ?? null,
            $_SESSION['permissions'] ?? null,
            $_SESSION['admin_user_permissions'] ?? null,
        ];

        foreach ($sessionCandidates as $candidate) {
            if (is_array($candidate)) {
                return array_values(array_unique(array_filter(array_map(
                    static fn($item) => admin_normalize_permission_key((string)$item),
                    $candidate
                ))));
            }
        }

        return [];
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
    function admin_permissions_from_database(int $roleId): array
    {
        if ($roleId <= 0) {
            return [];
        }

        try {
            $pdo = db();

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
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('admin_resolve_permissions')) {
    function admin_resolve_permissions(): array
    {
        $roleName = (string)($_SESSION['admin_role_name'] ?? '');
        $roleId = (int)($_SESSION['admin_role_id'] ?? 0);

        $permissions = admin_permissions_from_session();

        if (!empty($permissions)) {
            return $permissions;
        }

        if (admin_is_full_access_role($roleName)) {
            return ['admin.full_access'];
        }

        if ($roleId > 0) {
            $permissions = admin_permissions_from_database($roleId);
            if (!empty($permissions)) {
                return $permissions;
            }
        }

        return [];
    }
}

if (!function_exists('admin_has_any_permission')) {
    function admin_has_any_permission(array $requiredPermissions): bool
    {
        $granted = admin_resolve_permissions();

        if (in_array('admin.full_access', $granted, true)) {
            return true;
        }

        $grantedMap = array_fill_keys($granted, true);

        foreach ($requiredPermissions as $permission) {
            $permission = admin_normalize_permission_key((string)$permission);
            if ($permission !== '' && isset($grantedMap[$permission])) {
                return true;
            }
        }

        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, ['message' => 'Invalid request method'], 405);
}

require_admin_auth_json();

$requiredPermissions = [
    'products.edit',
    'edit_products',
    'manage_products',
    'admin.full_access'
];

if (!admin_has_any_permission($requiredPermissions)) {
    json_response(false, ['message' => 'ليس لديك صلاحية لعرض المنتجات'], 403);
}

$categoryId = (int)($_GET['category_id'] ?? 0);
$brandId = (int)($_GET['brand_id'] ?? 0);

if ($categoryId <= 0 || $brandId <= 0) {
    json_response(false, ['message' => 'Category and brand are required'], 422);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.category_id,
        p.brand_id,
        p.title,
        p.slug,
        p.sku,
        p.devices_count,
        p.image_path,
        p.down_payment,
        p.monthly_amount,
        p.duration_months,
        p.is_available,
        p.is_hot_offer,
        p.product_order,
        p.is_active,
        p.created_at,
        p.updated_at,
        c.display_name AS category_name,
        b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    WHERE p.category_id = :category_id
      AND p.brand_id = :brand_id
      AND p.is_active = 1
    ORDER BY p.product_order ASC, p.id DESC
");
$stmt->execute([
    'category_id' => $categoryId,
    'brand_id' => $brandId
]);

$rows = $stmt->fetchAll();

$products = array_map(static function (array $row): array {
    return [
        'id' => (int)$row['id'],
        'category_id' => (int)$row['category_id'],
        'brand_id' => (int)$row['brand_id'],
        'title' => (string)$row['title'],
        'slug' => (string)($row['slug'] ?? ''),
        'sku' => (string)($row['sku'] ?? ''),
        'devices_count' => (int)($row['devices_count'] ?? 1),
        'image_path' => (string)($row['image_path'] ?? ''),
        'down_payment' => (float)($row['down_payment'] ?? 0),
        'monthly_amount' => (float)($row['monthly_amount'] ?? 0),
        'duration_months' => (int)($row['duration_months'] ?? 0),
        'is_available' => (int)($row['is_available'] ?? 0),
        'is_hot_offer' => (int)($row['is_hot_offer'] ?? 0),
        'product_order' => (int)($row['product_order'] ?? 9999),
        'is_active' => (int)($row['is_active'] ?? 0),
        'category_name' => (string)($row['category_name'] ?? ''),
        'brand_name' => (string)($row['brand_name'] ?? ''),
        'price_logic' => trim(
            ((float)($row['down_payment'] ?? 0)) . ' KD Down / ' .
            ((float)($row['monthly_amount'] ?? 0)) . ' KD Monthly / ' .
            ((int)($row['duration_months'] ?? 0)) . ' Months'
        ),
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? '')
    ];
}, $rows ?: []);

json_response(true, [
    'products' => $products,
]);
