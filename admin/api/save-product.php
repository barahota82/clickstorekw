<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once __DIR__ . '/link-product-stock.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    json_response(false, ['message' => 'ليس لديك صلاحية لحفظ المنتج'], 403);
}

$title = trim((string)($_POST['title'] ?? ''));
$sku = trim((string)($_POST['sku'] ?? ''));
$category_id = (int)($_POST['category_id'] ?? 0);
$brand_id = (int)($_POST['brand_id'] ?? 0);
$devices_count = max(1, (int)($_POST['devices_count'] ?? 1));
$duration_months = max(1, (int)($_POST['duration_months'] ?? 1));
$down_payment = (float)($_POST['down_payment'] ?? 0);
$monthly_amount = (float)($_POST['monthly_amount'] ?? 0);
$is_available = isset($_POST['is_available']) ? 1 : 0;
$is_hot_offer = isset($_POST['is_hot_offer']) ? 1 : 0;

if ($title === '' || $sku === '' || $category_id <= 0 || $brand_id <= 0) {
    json_response(false, ['message' => 'Missing required fields'], 422);
}

if (!isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    json_response(false, ['message' => 'Image is required'], 422);
}

$image = $_FILES['image'];

if (($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    json_response(false, ['message' => 'Image upload failed'], 422);
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$ext = strtolower((string)pathinfo((string)$image['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions, true)) {
    json_response(false, ['message' => 'Unsupported image type'], 422);
}

$uploadDir = dirname(__DIR__, 2) . '/images/products/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
    json_response(false, ['message' => 'Failed to create upload directory'], 500);
}

$filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$absolutePath = $uploadDir . $filename;
$relativePath = '/images/products/' . $filename;

if (!move_uploaded_file((string)$image['tmp_name'], $absolutePath)) {
    json_response(false, ['message' => 'Failed to save uploaded image'], 500);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $checkSku = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
    $checkSku->execute(['sku' => $sku]);
    if ($checkSku->fetch()) {
        $pdo->rollBack();
        @unlink($absolutePath);
        json_response(false, ['message' => 'SKU already exists'], 409);
    }

    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\-\s]+/i', '', $slug);
    $slug = preg_replace('/\s+/', '-', (string)$slug);
    $slug = trim((string)$slug, '-');
    if ($slug === '') {
        $slug = 'product-' . time();
    }

    $insertProduct = $pdo->prepare("
        INSERT INTO products (
            category_id,
            brand_id,
            title,
            slug,
            sku,
            devices_count,
            image_path,
            down_payment,
            monthly_amount,
            duration_months,
            is_available,
            is_hot_offer,
            product_order,
            json_file_path,
            is_active,
            created_by,
            updated_by,
            created_at,
            updated_at
        ) VALUES (
            :category_id,
            :brand_id,
            :title,
            :slug,
            :sku,
            :devices_count,
            :image_path,
            :down_payment,
            :monthly_amount,
            :duration_months,
            :is_available,
            :is_hot_offer,
            9999,
            NULL,
            1,
            :created_by,
            :updated_by,
            NOW(),
            NOW()
        )
    ");

    $insertProduct->execute([
        'category_id' => $category_id,
        'brand_id' => $brand_id,
        'title' => $title,
        'slug' => $slug,
        'sku' => $sku,
        'devices_count' => $devices_count,
        'image_path' => $relativePath,
        'down_payment' => $down_payment,
        'monthly_amount' => $monthly_amount,
        'duration_months' => $duration_months,
        'is_available' => $is_available,
        'is_hot_offer' => $is_hot_offer,
        'created_by' => (int)$_SESSION['admin_user_id'],
        'updated_by' => (int)$_SESSION['admin_user_id'],
    ]);

    $productId = (int)$pdo->lastInsertId();

    $insertMedia = $pdo->prepare("
        INSERT INTO product_media (
            product_id,
            file_path,
            sort_order,
            is_primary,
            created_at
        ) VALUES (
            :product_id,
            :file_path,
            1,
            1,
            NOW()
        )
    ");
    $insertMedia->execute([
        'product_id' => $productId,
        'file_path' => $relativePath,
    ]);

    if ($is_hot_offer === 1) {
        $insertHot = $pdo->prepare("
            INSERT INTO hot_offers (
                product_id,
                sort_order,
                is_active,
                created_at,
                updated_at
            ) VALUES (
                :product_id,
                9999,
                1,
                NOW(),
                NOW()
            )
        ");
        $insertHot->execute(['product_id' => $productId]);
    }

    link_product_to_stock($pdo, $productId, $brand_id, $category_id, (string)$image['name']);

    $pdo->commit();

    json_response(true, [
        'message' => 'تم حفظ المنتج بنجاح وربطه بالمخزن',
        'product_id' => $productId,
        'image_path' => $relativePath
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    @unlink($absolutePath);

    json_response(false, [
        'message' => 'فشل حفظ المنتج',
        'error' => $e->getMessage()
    ], 500);
}
