<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once __DIR__ . '/link-product-stock.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['message' => 'Invalid request method'], 405);
}

require_admin_auth_json();

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
