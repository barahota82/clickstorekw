<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once __DIR__ . '/link-product-stock.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['message' => 'Invalid request method'], 405);
}

if (!is_admin_logged_in()) {
    json_response(false, ['message' => 'Unauthorized'], 401);
}

try {
    $title = trim((string)($_POST['title'] ?? ''));
    $category_id = (int)($_POST['category_id'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $sku = trim((string)($_POST['sku'] ?? ''));
    $devices_count = (int)($_POST['devices_count'] ?? 1);
    $down_payment = (float)($_POST['down_payment'] ?? 0);
    $monthly_amount = (float)($_POST['monthly_amount'] ?? 0);
    $duration_months = (int)($_POST['duration_months'] ?? 1);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $is_hot_offer = isset($_POST['is_hot_offer']) ? 1 : 0;

    if ($title === '') {
        json_response(false, ['message' => 'اسم المنتج مطلوب'], 422);
    }

    if ($category_id <= 0) {
        json_response(false, ['message' => 'اختر الفئة'], 422);
    }

    if ($brand_id <= 0) {
        json_response(false, ['message' => 'اختر البراند'], 422);
    }

    if ($sku === '') {
        json_response(false, ['message' => 'SKU مطلوب'], 422);
    }

    if (!isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
        json_response(false, ['message' => 'صورة المنتج مطلوبة'], 422);
    }

    $image = $_FILES['image'];

    if ($image['error'] !== UPLOAD_ERR_OK) {
        json_response(false, ['message' => 'فشل رفع الصورة'], 422);
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $image['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMime[$mime])) {
        json_response(false, ['message' => 'نوع الصورة غير مدعوم'], 422);
    }

    $pdo = db();

    $checkSku = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
    $checkSku->execute(['sku' => $sku]);
    if ($checkSku->fetch()) {
        json_response(false, ['message' => 'SKU مستخدم من قبل'], 409);
    }

    $slugBase = strtolower(trim($title));
    $slugBase = preg_replace('/[^a-z0-9\-\s]+/i', '', $slugBase);
    $slugBase = preg_replace('/\s+/', '-', $slugBase);
    $slugBase = trim($slugBase, '-');
    if ($slugBase === '') {
        $slugBase = 'product';
    }

    $slug = $slugBase;
    $counter = 2;

    while (true) {
        $checkSlug = $pdo->prepare("
            SELECT id FROM products
            WHERE brand_id = :brand_id AND slug = :slug
            LIMIT 1
        ");
        $checkSlug->execute([
            'brand_id' => $brand_id,
            'slug' => $slug
        ]);

        if (!$checkSlug->fetch()) {
            break;
        }

        $slug = $slugBase . '-' . $counter;
        $counter++;
    }

    $uploadDir = dirname(__DIR__, 2) . '/images/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $extension = $allowedMime[$mime];
    $safeFilenameBase = strtolower(trim(pathinfo($image['name'], PATHINFO_FILENAME)));
    $safeFilenameBase = preg_replace('/[^a-z0-9\-\s\+]+/i', '', $safeFilenameBase);
    $safeFilenameBase = preg_replace('/\s+/', '-', $safeFilenameBase);
    $safeFilenameBase = trim($safeFilenameBase, '-');

    if ($safeFilenameBase === '') {
        $safeFilenameBase = $slug;
    }

    $finalFilename = $safeFilenameBase . '-' . time() . '.' . $extension;
    $absolutePath = $uploadDir . $finalFilename;
    $relativePath = '/images/products/' . $finalFilename;

    if (!move_uploaded_file($image['tmp_name'], $absolutePath)) {
        json_response(false, ['message' => 'تعذر حفظ الصورة على السيرفر'], 500);
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare("
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

    $insert->execute([
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
        'updated_by' => (int)$_SESSION['admin_user_id']
    ]);

    $productId = (int)$pdo->lastInsertId();

    $media = $pdo->prepare("
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
    $media->execute([
        'product_id' => $productId,
        'file_path' => $relativePath
    ]);

    if ($is_hot_offer === 1) {
        $hot = $pdo->prepare("
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
        $hot->execute(['product_id' => $productId]);
    }

    // ربط المنتج بالمخزن اعتمادًا على اسم الملف
    link_product_to_stock($productId, $image['name']);

    $pdo->commit();

    json_response(true, [
        'message' => 'تم حفظ المنتج بنجاح',
        'product_id' => $productId,
        'image_path' => $relativePath
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'فشل حفظ المنتج',
        'error' => $e->getMessage()
    ], 500);
}
