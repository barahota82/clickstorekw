<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once __DIR__ . '/link-product-stock.php';

if (!function_exists('save_product_slugify')) {
    function save_product_slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['_', '+'], ' ', $value);
        $value = preg_replace('/\.[^.]+$/', '', $value);
        $value = preg_replace('/[^a-z0-9.\-\s]+/', ' ', (string)$value);
        $value = preg_replace('/\s+/', '-', (string)$value);
        $value = preg_replace('/-+/', '-', (string)$value);
        $value = trim((string)$value, '-');

        return $value;
    }
}

if (!function_exists('save_product_make_dir')) {
    function save_product_make_dir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('Failed to create directory: ' . $dir);
        }
    }
}

if (!function_exists('save_product_fetch_category_brand')) {
    function save_product_fetch_category_brand(PDO $pdo, int $categoryId, int $brandId): array
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

if (!function_exists('save_product_json_payload')) {
    function save_product_json_payload(array $data): array
    {
        return [
            'title' => $data['title'],
            'category' => $data['category_slug'],
            'brand' => $data['brand_name'],
            'devices_count' => (int)$data['devices_count'],
            'image' => $data['image_path'],
            'down_payment' => (float)$data['down_payment'],
            'monthly' => (float)$data['monthly_amount'],
            'duration' => (int)$data['duration_months'],
            'available' => (bool)$data['is_available'],
            'hot_offer' => (bool)$data['is_hot_offer'],
            'brand_priority' => 1,
            'priority' => 1
        ];
    }
}

require_post();
require_admin_auth_json();
admin_require_permission_json('products_edit', 'ليس لديك صلاحية لحفظ المنتج');

$title = trim((string)($_POST['title'] ?? ''));
$stockDisplayName = trim((string)($_POST['stock_display_name'] ?? $title));
$categoryId = (int)($_POST['category_id'] ?? 0);
$brandId = (int)($_POST['brand_id'] ?? 0);
$devicesCount = max(1, min(4, (int)($_POST['devices_count'] ?? 1)));
$durationMonths = max(1, (int)($_POST['duration_months'] ?? 1));
$downPayment = (float)($_POST['down_payment'] ?? 0);
$monthlyAmount = (float)($_POST['monthly_amount'] ?? 0);
$isAvailable = isset($_POST['is_available']) ? (int)((string)$_POST['is_available'] === '1') : 1;
$isHotOffer = isset($_POST['is_hot_offer']) ? (int)((string)$_POST['is_hot_offer'] === '1') : 0;

if ($title === '' || $stockDisplayName === '' || $categoryId <= 0 || $brandId <= 0) {
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

$originalImageName = (string)($image['name'] ?? '');
$slugBase = save_product_slugify($originalImageName);

if ($slugBase === '') {
    $slugBase = 'product-' . time();
}

$parsedDevices = parse_devices_from_filename($originalImageName);
$parsedDevices = stock_catalog_limit_devices(is_array($parsedDevices) ? $parsedDevices : [], 4);

if (empty($parsedDevices)) {
    $fallbackTitle = pathinfo($originalImageName, PATHINFO_FILENAME);
    $parsedDevices = [[
        'device_index' => 1,
        'raw_title' => $fallbackTitle,
        'normalized_title' => normalize_stock_title($fallbackTitle),
        'storage_value' => null,
        'ram_value' => null,
        'network_value' => null,
    ]];
}

$devicesCount = min(4, max(1, count($parsedDevices)));

$pdo = db();

$absoluteImagePath = '';
$absoluteJsonPath = '';

try {
    $categoryBrand = save_product_fetch_category_brand($pdo, $categoryId, $brandId);

    $categorySlug = save_product_slugify((string)$categoryBrand['category_slug']);
    $brandSlug = save_product_slugify((string)$categoryBrand['brand_slug']);
    $categoryName = (string)$categoryBrand['category_name'];
    $brandName = (string)$categoryBrand['brand_name'];

    if ($categorySlug === '' || $brandSlug === '') {
        throw new RuntimeException('Invalid category slug or brand slug');
    }

    $slug = $slugBase;
    $sku = strtoupper(str_replace('-', '_', $slug));

    $imageDir = dirname(__DIR__, 2) . '/images/products/' . $categorySlug . '/' . $brandSlug . '/';
    $jsonDir = dirname(__DIR__, 2) . '/products/' . $categorySlug . '/' . $brandSlug . '/';

    save_product_make_dir($imageDir);
    save_product_make_dir($jsonDir);

    $absoluteImagePath = $imageDir . $slug . '.' . $ext;
    $relativeImagePath = '/images/products/' . $categorySlug . '/' . $brandSlug . '/' . $slug . '.' . $ext;

    $absoluteJsonPath = $jsonDir . $slug . '.json';
    $relativeJsonPath = '/products/' . $categorySlug . '/' . $brandSlug . '/' . $slug . '.json';

    if (file_exists($absoluteImagePath) || file_exists($absoluteJsonPath)) {
        json_response(false, ['message' => 'A product file with the same slug already exists'], 409);
    }

    if (!move_uploaded_file((string)$image['tmp_name'], $absoluteImagePath)) {
        json_response(false, ['message' => 'Failed to save uploaded image'], 500);
    }

    $checkSku = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
    $checkSku->execute(['sku' => $sku]);

    if ($checkSku->fetch()) {
        @unlink($absoluteImagePath);
        json_response(false, ['message' => 'SKU already exists'], 409);
    }

    $checkSlug = $pdo->prepare("SELECT id FROM products WHERE slug = :slug LIMIT 1");
    $checkSlug->execute(['slug' => $slug]);

    if ($checkSlug->fetch()) {
        @unlink($absoluteImagePath);
        json_response(false, ['message' => 'Slug already exists'], 409);
    }

    $jsonPayload = save_product_json_payload([
        'title' => $title,
        'category_slug' => $categorySlug,
        'category_name' => $categoryName,
        'brand_slug' => $brandSlug,
        'brand_name' => $brandName,
        'devices_count' => $devicesCount,
        'down_payment' => $downPayment,
        'monthly_amount' => $monthlyAmount,
        'duration_months' => $durationMonths,
        'is_available' => $isAvailable,
        'is_hot_offer' => $isHotOffer,
        'image_path' => $relativeImagePath,
    ]);

    $jsonEncoded = json_encode(
        $jsonPayload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );

    if ($jsonEncoded === false) {
        @unlink($absoluteImagePath);
        json_response(false, ['message' => 'Failed to encode JSON file'], 500);
    }

    if (file_put_contents($absoluteJsonPath, $jsonEncoded) === false) {
        @unlink($absoluteImagePath);
        json_response(false, ['message' => 'Failed to write JSON file'], 500);
    }

    $pdo->beginTransaction();

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
            :json_file_path,
            1,
            :created_by,
            :updated_by,
            NOW(),
            NOW()
        )
    ");

    $adminUserId = admin_current_user_id() > 0 ? admin_current_user_id() : null;

    $insertProduct->execute([
        'category_id' => $categoryId,
        'brand_id' => $brandId,
        'title' => $title,
        'slug' => $slug,
        'sku' => $sku,
        'devices_count' => $devicesCount,
        'image_path' => $relativeImagePath,
        'down_payment' => $downPayment,
        'monthly_amount' => $monthlyAmount,
        'duration_months' => $durationMonths,
        'is_available' => $isAvailable,
        'is_hot_offer' => $isHotOffer,
        'json_file_path' => $relativeJsonPath,
        'created_by' => $adminUserId,
        'updated_by' => $adminUserId,
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
        'file_path' => $relativeImagePath,
    ]);

    if ($isHotOffer === 1) {
        $checkHot = $pdo->prepare("SELECT id FROM hot_offers WHERE product_id = :product_id LIMIT 1");
        $checkHot->execute(['product_id' => $productId]);

        if (!$checkHot->fetch()) {
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
    }

    $stockLinkResult = link_product_to_stock($pdo, $productId, $brandId, $categoryId, $originalImageName);

    $pdo->commit();

    admin_activity_log(
        'create_product',
        'products',
        'product',
        $productId,
        'Created product | title: ' . $title . ' | slug: ' . $slug . ' | sku: ' . $sku
    );

    $linkedCount = is_array($stockLinkResult['linked'] ?? null) ? count($stockLinkResult['linked']) : 0;
    $missingCount = is_array($stockLinkResult['missing'] ?? null) ? count($stockLinkResult['missing']) : 0;

    $message = 'تم حفظ المنتج بنجاح';

    if ($linkedCount > 0 && $missingCount === 0) {
        $message = 'تم حفظ المنتج بنجاح وربط جميع الأجهزة الموجودة بالمخزن';
    } elseif ($linkedCount > 0 && $missingCount > 0) {
        $message = 'تم حفظ المنتج وربط الأجهزة الموجودة، ويوجد أجهزة غير مضافة بالمخزن تحتاج إضافة يدوية';
    } elseif ($linkedCount === 0 && $missingCount > 0) {
        $message = 'تم حفظ المنتج، لكن لم يتم ربطه بالمخزن بعد لأن الأجهزة غير مضافة';
    }

    json_response(true, [
        'message' => $message,
        'product_id' => $productId,
        'slug' => $slug,
        'sku' => $sku,
        'image_path' => $relativeImagePath,
        'json_file_path' => $relativeJsonPath,
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

    if ($absoluteImagePath !== '' && file_exists($absoluteImagePath)) {
        @unlink($absoluteImagePath);
    }

    if ($absoluteJsonPath !== '' && file_exists($absoluteJsonPath)) {
        @unlink($absoluteJsonPath);
    }

    json_response(false, [
        'message' => 'فشل حفظ المنتج',
        'error' => $e->getMessage()
    ], 500);
}
