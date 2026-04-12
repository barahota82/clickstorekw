<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once dirname(__DIR__) . '/helpers/products_sync.php';
require_once dirname(__DIR__) . '/helpers/product_storage_helper.php';
require_once __DIR__ . '/link-product-stock.php';

if (!function_exists('save_product_json_payload')) {
    function save_product_json_payload(array $data): array
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

require_post();
require_admin_auth_json();
admin_require_permission_json('products_edit', 'ليس لديك صلاحية لحفظ المنتج');

$title = trim((string)($_POST['title'] ?? ''));
$categoryId = (int)($_POST['category_id'] ?? 0);
$brandId = (int)($_POST['brand_id'] ?? 0);
$devicesCount = max(1, min(4, (int)($_POST['devices_count'] ?? 1)));
$durationMonths = max(1, (int)($_POST['duration_months'] ?? 1));
$downPayment = (float)($_POST['down_payment'] ?? 0);
$monthlyAmount = (float)($_POST['monthly_amount'] ?? 0);
$isAvailable = isset($_POST['is_available']) ? (int)((string)$_POST['is_available'] === '1') : 1;
$isHotOffer = isset($_POST['is_hot_offer']) ? (int)((string)$_POST['is_hot_offer'] === '1') : 0;

if ($title === '' || $categoryId <= 0 || $brandId <= 0) {
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
$uploadedExt = strtolower((string)pathinfo((string)($image['name'] ?? ''), PATHINFO_EXTENSION));

if (!in_array($uploadedExt, $allowedExtensions, true)) {
    json_response(false, ['message' => 'Unsupported image type'], 422);
}

$originalImageName = (string)($image['name'] ?? '');
$slug = product_storage_slugify($originalImageName);

if ($slug === '') {
    $slug = product_storage_slugify($title);
}

if ($slug === '') {
    $slug = 'product-' . time();
}

$sku = strtoupper(str_replace('-', '_', $slug));

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
$relativeImagePath = '';
$categoryDataJsonRel = '';
$productId = 0;
$stockLinkResult = [
    'devices_count' => $devicesCount,
    'linked' => [],
    'missing' => [],
];

try {
    $categoryBrand = product_storage_fetch_category_brand($pdo, $categoryId, $brandId);

    $categorySlug = product_storage_slugify((string)$categoryBrand['category_slug']);
    $brandSlug = product_storage_slugify((string)$categoryBrand['brand_slug']);
    $brandName = trim((string)($categoryBrand['brand_name'] ?? ''));

    if ($categorySlug === '' || $brandSlug === '' || $brandName === '') {
        throw new RuntimeException('Invalid category or brand data');
    }

    $paths = product_storage_build_paths($categorySlug, $brandSlug, $slug);
    $absoluteImagePath = (string)$paths['image_abs'];
    $relativeImagePath = (string)$paths['image_rel'];
    $categoryDataJsonRel = (string)$paths['category_data_json_rel'];

    if (file_exists($absoluteImagePath)) {
        json_response(false, ['message' => 'An image with the same slug already exists'], 409);
    }

    $checkSku = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
    $checkSku->execute(['sku' => $sku]);

    if ($checkSku->fetch(PDO::FETCH_ASSOC)) {
        json_response(false, ['message' => 'SKU already exists'], 409);
    }

    $checkSlug = $pdo->prepare("SELECT id FROM products WHERE slug = :slug LIMIT 1");
    $checkSlug->execute(['slug' => $slug]);

    if ($checkSlug->fetch(PDO::FETCH_ASSOC)) {
        json_response(false, ['message' => 'Slug already exists'], 409);
    }

    product_storage_convert_to_webp((string)$image['tmp_name'], $absoluteImagePath, true, 85);

    $pdo->beginTransaction();

    $adminUserId = function_exists('admin_current_user_id') && admin_current_user_id() > 0
        ? admin_current_user_id()
        : ((int)($_SESSION['admin_user_id'] ?? 0) ?: null);

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
            :product_order,
            :json_file_path,
            1,
            :created_by,
            :updated_by,
            NOW(),
            NOW()
        )
    ");

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
        'product_order' => 9999,
        'json_file_path' => $categoryDataJsonRel,
        'created_by' => $adminUserId,
        'updated_by' => $adminUserId,
    ]);

    $productId = (int)$pdo->lastInsertId();

    $inventoryStockItem = ensure_stock_item_for_product($pdo, $productId, $sku, null);

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

    $stockLinkResult = link_product_to_stock($pdo, $productId, $brandId, $categoryId, $originalImageName);

    generate_products_json_for_category($categoryId);

    $pdo->commit();

    if (function_exists('admin_activity_log')) {
        admin_activity_log(
            'create_product',
            'products',
            'product',
            $productId,
            'Created product | title: ' . $title . ' | slug: ' . $slug . ' | sku: ' . $sku
        );
    }

    $savedJson = save_product_json_payload([
        'slug' => $slug,
        'title' => $title,
        'category_slug' => $categorySlug,
        'brand_name' => $brandName,
        'devices_count' => $devicesCount,
        'down_payment' => $downPayment,
        'monthly_amount' => $monthlyAmount,
        'duration_months' => $durationMonths,
        'is_available' => $isAvailable,
        'is_hot_offer' => $isHotOffer,
        'image_path' => $relativeImagePath,
    ]);

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
        'json_file_path' => $categoryDataJsonRel,
        'saved_json' => $savedJson,
        'inventory_stock_item' => $inventoryStockItem,
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

    product_storage_remove_file_if_exists($absoluteImagePath);

    try {
        if ($categoryId > 0) {
            generate_products_json_for_category($categoryId);
        }
    } catch (Throwable $restoreError) {
    }

    json_response(false, [
        'message' => 'فشل حفظ المنتج',
        'error' => $e->getMessage()
    ], 500);
}
