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
    json_response(false, ['message' => 'ليس لديك صلاحية لتعديل المنتج'], 403);
}

$productId = (int)($_POST['id'] ?? 0);
$title = trim((string)($_POST['title'] ?? ''));
$downPayment = (float)($_POST['down_payment'] ?? 0);
$monthlyAmount = (float)($_POST['monthly_amount'] ?? 0);
$durationMonths = max(1, (int)($_POST['duration_months'] ?? 1));
$isHotOffer = isset($_POST['is_hot_offer']) ? 1 : 0;
$isAvailable = isset($_POST['is_available']) ? 1 : 0;

if ($productId <= 0) {
    json_response(false, ['message' => 'Invalid product id'], 422);
}

if ($title === '') {
    json_response(false, ['message' => 'Title is required'], 422);
}

$pdo = db();

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            slug,
            sku,
            image_path,
            is_hot_offer,
            is_active
        FROM products
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        json_response(false, ['message' => 'Product not found'], 404);
    }

    if ((int)$product['is_active'] !== 1) {
        json_response(false, ['message' => 'Cannot update inactive product'], 422);
    }

    $newImagePath = (string)($product['image_path'] ?? '');
    $newAbsolutePath = '';
    $oldAbsolutePath = dirname(__DIR__, 2) . $newImagePath;

    if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
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
        $newAbsolutePath = $uploadDir . $filename;
        $newImagePath = '/images/products/' . $filename;

        if (!move_uploaded_file((string)$image['tmp_name'], $newAbsolutePath)) {
            json_response(false, ['message' => 'Failed to save uploaded image'], 500);
        }
    }

    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\-\s]+/i', '', $slug);
    $slug = preg_replace('/\s+/', '-', (string)$slug);
    $slug = trim((string)$slug, '-');
    if ($slug === '') {
        $slug = 'product-' . $productId;
    }

    $pdo->beginTransaction();

    $update = $pdo->prepare("
        UPDATE products
        SET
            title = :title,
            slug = :slug,
            image_path = :image_path,
            down_payment = :down_payment,
            monthly_amount = :monthly_amount,
            duration_months = :duration_months,
            is_available = :is_available,
            is_hot_offer = :is_hot_offer,
            updated_by = :updated_by,
            updated_at = NOW()
        WHERE id = :id
        LIMIT 1
    ");
    $update->execute([
        'id' => $productId,
        'title' => $title,
        'slug' => $slug,
        'image_path' => $newImagePath,
        'down_payment' => $downPayment,
        'monthly_amount' => $monthlyAmount,
        'duration_months' => $durationMonths,
        'is_available' => $isAvailable,
        'is_hot_offer' => $isHotOffer,
        'updated_by' => (int)($_SESSION['admin_user_id'] ?? 0),
    ]);

    if ($newImagePath !== (string)$product['image_path']) {
        if (admin_table_exists($pdo, 'product_media')) {
            $deleteOldPrimary = $pdo->prepare("
                UPDATE product_media
                SET is_primary = 0
                WHERE product_id = :product_id
            ");
            $deleteOldPrimary->execute([
                'product_id' => $productId
            ]);

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
                'file_path' => $newImagePath
            ]);
        }
    }

    if (admin_table_exists($pdo, 'hot_offers')) {
        $hotStmt = $pdo->prepare("
            SELECT id
            FROM hot_offers
            WHERE product_id = :product_id
            LIMIT 1
        ");
        $hotStmt->execute(['product_id' => $productId]);
        $hot = $hotStmt->fetch();

        if ($isHotOffer === 1) {
            if ($hot) {
                $updateHot = $pdo->prepare("
                    UPDATE hot_offers
                    SET
                        is_active = 1,
                        updated_at = NOW()
                    WHERE product_id = :product_id
                ");
                $updateHot->execute(['product_id' => $productId]);
            } else {
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
        } else {
            $disableHot = $pdo->prepare("
                UPDATE hot_offers
                SET
                    is_active = 0,
                    updated_at = NOW()
                WHERE product_id = :product_id
            ");
            $disableHot->execute(['product_id' => $productId]);
        }
    }

    $pdo->commit();

    if ($newAbsolutePath !== '' && is_file($oldAbsolutePath) && str_starts_with((string)$product['image_path'], '/images/products/')) {
        @unlink($oldAbsolutePath);
    }

    json_response(true, [
        'message' => 'تم تحديث المنتج بنجاح',
        'product' => [
            'id' => $productId,
            'title' => $title,
            'slug' => $slug,
            'image_path' => $newImagePath,
            'down_payment' => $downPayment,
            'monthly_amount' => $monthlyAmount,
            'duration_months' => $durationMonths,
            'is_available' => $isAvailable,
            'is_hot_offer' => $isHotOffer
        ]
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($newAbsolutePath !== '' && is_file($newAbsolutePath)) {
        @unlink($newAbsolutePath);
    }

    json_response(false, [
        'message' => 'فشل تحديث المنتج',
        'error' => $e->getMessage()
    ], 500);
}
