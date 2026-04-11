<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once dirname(__DIR__) . '/helpers/products_sync.php';
require_once __DIR__ . '/link-product-stock.php';

if (!function_exists('update_product_slugify')) {
    function update_product_slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\.[^.]+$/', '', $value);
        $value = str_replace(['_', '+'], ' ', $value);
        $value = str_replace('.', ' ', $value);
        $value = preg_replace('/[^a-z0-9\-\s]+/', ' ', (string)$value);
        $value = preg_replace('/\s+/', '-', (string)$value);
        $value = preg_replace('/-+/', '-', (string)$value);
        $value = trim((string)$value, '-');

        return $value;
    }
}

if (!function_exists('update_product_make_dir')) {
    function update_product_make_dir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('Failed to create directory: ' . $dir);
        }
    }
}

if (!function_exists('update_product_fetch_category_brand')) {
    function update_product_fetch_category_brand(PDO $pdo, int $categoryId, int $brandId): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                c.id AS category_id,
                c.display_name AS category_name,
                c.slug AS category_slug,
                b.id AS brand_id,
                b.name AS brand_name,
                b.slug AS brand_slug
            FROM categories c
            INNER JOIN brands b ON b.category_id = c.id
            WHERE c.id = :category_id
              AND b.id = :brand_id
            LIMIT 1
        ");
        $stmt->execute([
            'category_id' => $categoryId,
            'brand_id' => $brandId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException('Invalid category or brand');
        }

        return $row;
    }
}

if (!function_exists('update_product_json_payload')) {
    function update_product_json_payload(array $data): array
    {
        return [
            'slug' => (string)$data['slug'],
            'title' => (string)$data['title'],
            'category' => (string)$data['category_slug'],
            'brand' => (string)$data['brand_name'],
            'devices_count' => (int)$data['devices_count'],
            'image' => (string)$data['image_path'],
            'down_payment' => (float)$data['down_payment'],
            'monthly' => (float)$data['monthly_amount'],
            'duration' => (int)$data['duration_months'],
            'available' => (bool)$data['is_available'],
            'hot_offer' => (bool)$data['is_hot_offer']
        ];
    }
}

if (!function_exists('update_product_build_stock_filename')) {
    function update_product_build_stock_filename(PDO $pdo, array $product, ?string $uploadedImageName = null): string
    {
        if ($uploadedImageName !== null && trim($uploadedImageName) !== '') {
            return trim($uploadedImageName);
        }

        $linksStmt = $pdo->prepare("
            SELECT extracted_name
            FROM product_stock_links
            WHERE product_id = :product_id
            ORDER BY device_index ASC, id ASC
        ");
        $linksStmt->execute([
            'product_id' => (int)$product['id']
        ]);
        $names = $linksStmt->fetchAll(PDO::FETCH_COLUMN);

        $names = array_values(array_filter(array_map(static function ($item): string {
            return trim((string)$item);
        }, is_array($names) ? $names : [])));

        if (!empty($names)) {
            return implode(' + ', $names) . '.jpg';
        }

        $existingImagePath = trim((string)($product['image_path'] ?? ''));
        if ($existingImagePath !== '') {
            return basename($existingImagePath);
        }

        return trim((string)($product['title'] ?? 'product')) . '.jpg';
    }
}

require_post();
require_admin_auth_json();
admin_require_permission_json('products_edit', 'ليس لديك صلاحية لتعديل المنتج');

$productId = (int)($_POST['id'] ?? 0);
$title = trim((string)($_POST['title'] ?? ''));
$categoryId = (int)($_POST['category_id'] ?? 0);
$brandId = (int)($_POST['brand_id'] ?? 0);
$devicesCount = max(1, min(4, (int)($_POST['devices_count'] ?? 1)));
$durationMonths = max(1, (int)($_POST['duration_months'] ?? 1));
$downPayment = (float)($_POST['down_payment'] ?? 0);
$monthlyAmount = (float)($_POST['monthly_amount'] ?? 0);
$isAvailable = isset($_POST['is_available']) ? (int)((string)$_POST['is_available'] === '1') : 1;
$isHotOffer = isset($_POST['is_hot_offer']) ? (int)((string)$_POST['is_hot_offer'] === '1') : 0;

if ($productId <= 0 || $title === '' || $categoryId <= 0 || $brandId <= 0) {
    json_response(false, ['message' => 'Missing required fields'], 422);
}

$pdo = db();

$productStmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = :id
    LIMIT 1
");
$productStmt->execute(['id' => $productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    json_response(false, ['message' => 'Product not found'], 404);
}

$oldCategoryId = (int)$product['category_id'];
$oldImagePath = (string)($product['image_path'] ?? '');
$oldJsonPath = (string)($product['json_file_path'] ?? '');

$uploadedImageName = null;
$newRelativeImagePath = $oldImagePath;
$newAbsoluteImagePath = '';
$newRelativeJsonPath = $oldJsonPath;
$newAbsoluteJsonPath = '';
$newUploadedAbsoluteImage = '';

try {
    $categoryBrand = update_product_fetch_category_brand($pdo, $categoryId, $brandId);

    $categorySlug = update_product_slugify((string)$categoryBrand['category_slug']);
    $brandSlug = update_product_slugify((string)$categoryBrand['brand_slug']);
    $brandName = (string)$categoryBrand['brand_name'];

    if ($categorySlug === '' || $brandSlug === '') {
        throw new RuntimeException('Invalid category slug or brand slug');
    }

    $slug = trim((string)($product['slug'] ?? ''));
    if ($slug === '') {
        $slug = update_product_slugify($title);
        if ($slug === '') {
            $slug = 'product-' . $productId;
        }
    }

    if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $image = $_FILES['image'];

        if (($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            json_response(false, ['message' => 'Image upload failed'], 422);
        }

        $uploadedImageName = (string)($image['name'] ?? '');
        $ext = strtolower((string)pathinfo($uploadedImageName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExtensions, true)) {
            json_response(false, ['message' => 'Unsupported image type'], 422);
        }

        $imageDir = dirname(__DIR__, 2) . '/images/products/' . $categorySlug . '/' . $brandSlug . '/';
        update_product_make_dir($imageDir);

        $newAbsoluteImagePath = $imageDir . $slug . '.' . $ext;
        $newRelativeImagePath = '/images/products/' . $categorySlug . '/' . $brandSlug . '/' . $slug . '.' . $ext;

        if (!move_uploaded_file((string)$image['tmp_name'], $newAbsoluteImagePath)) {
            json_response(false, ['message' => 'Failed to save uploaded image'], 500);
        }

        $newUploadedAbsoluteImage = $newAbsoluteImagePath;
    }

    $jsonDir = dirname(__DIR__, 2) . '/products/' . $categorySlug . '/' . $brandSlug . '/';
    update_product_make_dir($jsonDir);

    $newAbsoluteJsonPath = $jsonDir . $slug . '.json';
    $newRelativeJsonPath = '/products/' . $categorySlug . '/' . $brandSlug . '/' . $slug . '.json';

    $stockFilename = update_product_build_stock_filename($pdo, $product, $uploadedImageName);

    $jsonPayload = update_product_json_payload([
        'slug' => $slug,
        'title' => $title,
        'category_slug' => $categorySlug,
        'brand_name' => $brandName,
        'devices_count' => $devicesCount,
        'image_path' => $newRelativeImagePath,
        'down_payment' => $downPayment,
        'monthly_amount' => $monthlyAmount,
        'duration_months' => $durationMonths,
        'is_available' => $isAvailable,
        'is_hot_offer' => $isHotOffer,
    ]);

    $jsonEncoded = json_encode(
        $jsonPayload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );

    if ($jsonEncoded === false) {
        throw new RuntimeException('Failed to encode JSON file');
    }

    if (file_put_contents($newAbsoluteJsonPath, $jsonEncoded) === false) {
        throw new RuntimeException('Failed to write JSON file');
    }

    $pdo->beginTransaction();

    $adminUserId = function_exists('admin_current_user_id') && admin_current_user_id() > 0
        ? admin_current_user_id()
        : ((int)($_SESSION['admin_user_id'] ?? 0) ?: null);

    $updateStmt = $pdo->prepare("
        UPDATE products
        SET
            category_id = :category_id,
            brand_id = :brand_id,
            title = :title,
            devices_count = :devices_count,
            image_path = :image_path,
            down_payment = :down_payment,
            monthly_amount = :monthly_amount,
            duration_months = :duration_months,
            is_available = :is_available,
            is_hot_offer = :is_hot_offer,
            json_file_path = :json_file_path,
            updated_by = :updated_by,
            updated_at = NOW()
        WHERE id = :id
        LIMIT 1
    ");

    $updateStmt->execute([
        'category_id' => $categoryId,
        'brand_id' => $brandId,
        'title' => $title,
        'devices_count' => $devicesCount,
        'image_path' => $newRelativeImagePath,
        'down_payment' => $downPayment,
        'monthly_amount' => $monthlyAmount,
        'duration_months' => $durationMonths,
        'is_available' => $isAvailable,
        'is_hot_offer' => $isHotOffer,
        'json_file_path' => $newRelativeJsonPath,
        'updated_by' => $adminUserId,
        'id' => $productId
    ]);

    $updateMedia = $pdo->prepare("
        UPDATE product_media
        SET file_path = :file_path
        WHERE product_id = :product_id
          AND is_primary = 1
    ");
    $updateMedia->execute([
        'file_path' => $newRelativeImagePath,
        'product_id' => $productId
    ]);

    if ($isHotOffer === 1) {
        $checkHot = $pdo->prepare("SELECT id FROM hot_offers WHERE product_id = :product_id LIMIT 1");
        $checkHot->execute(['product_id' => $productId]);
        $existingHot = $checkHot->fetch(PDO::FETCH_ASSOC);

        if ($existingHot) {
            $enableHot = $pdo->prepare("
                UPDATE hot_offers
                SET is_active = 1, updated_at = NOW()
                WHERE product_id = :product_id
            ");
            $enableHot->execute(['product_id' => $productId]);
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
            SET is_active = 0, updated_at = NOW()
            WHERE product_id = :product_id
        ");
        $disableHot->execute(['product_id' => $productId]);
    }

    $stockLinkResult = link_product_to_stock($pdo, $productId, $brandId, $categoryId, $stockFilename);

    $pdo->commit();

    generate_products_json_for_category($categoryId);
    if ($oldCategoryId !== $categoryId) {
        generate_products_json_for_category($oldCategoryId);
    }

    if ($oldJsonPath !== '' && $oldJsonPath !== $newRelativeJsonPath) {
        $oldJsonAbsolute = dirname(__DIR__, 2) . $oldJsonPath;
        if (is_file($oldJsonAbsolute)) {
            @unlink($oldJsonAbsolute);
        }
    }

    if ($newUploadedAbsoluteImage !== '' && $oldImagePath !== '' && $oldImagePath !== $newRelativeImagePath) {
        $oldImageAbsolute = dirname(__DIR__, 2) . $oldImagePath;
        if (is_file($oldImageAbsolute)) {
            @unlink($oldImageAbsolute);
        }
    }

    if (function_exists('admin_activity_log')) {
        admin_activity_log(
            'update_product',
            'products',
            'product',
            $productId,
            'Updated product | title: ' . $title . ' | id: ' . $productId
        );
    }

    $linkedCount = is_array($stockLinkResult['linked'] ?? null) ? count($stockLinkResult['linked']) : 0;
    $missingCount = is_array($stockLinkResult['missing'] ?? null) ? count($stockLinkResult['missing']) : 0;

    $message = 'تم تحديث المنتج بنجاح';

    if ($linkedCount > 0 && $missingCount === 0) {
        $message = 'تم تحديث المنتج وربط جميع الأجهزة الموجودة بالمخزن';
    } elseif ($linkedCount > 0 && $missingCount > 0) {
        $message = 'تم تحديث المنتج وربط الأجهزة الموجودة، ويوجد أجهزة تحتاج إضافة يدوية للمخزن';
    } elseif ($linkedCount === 0 && $missingCount > 0) {
        $message = 'تم تحديث المنتج، لكن ما زالت هناك أجهزة غير مضافة بالمخزن';
    }

    json_response(true, [
        'message' => $message,
        'product_id' => $productId,
        'slug' => $slug,
        'image_path' => $newRelativeImagePath,
        'json_file_path' => $newRelativeJsonPath,
        'stock_review' => [
            'devices_count' => (int)($stockLinkResult['devices_count'] ?? $devicesCount),
            'linked' => $stockLinkResult['linked'] ?? [],
            'missing' => $stockLinkResult['missing'] ?? [],
            'linked_count' => $linkedCount,
            'missing_count' => $missingCount,
        ]
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($newUploadedAbsoluteImage !== '' && is_file($newUploadedAbsoluteImage)) {
        @unlink($newUploadedAbsoluteImage);
    }

    if ($newAbsoluteJsonPath !== '' && is_file($newAbsoluteJsonPath)) {
        @unlink($newAbsoluteJsonPath);
    }

    json_response(false, [
        'message' => 'فشل تحديث المنتج',
        'error' => $e->getMessage()
    ], 500);
}
