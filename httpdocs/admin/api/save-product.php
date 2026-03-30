<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// تحقق تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

// تحقق method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request method']);
    exit;
}

// استقبال البيانات
$title = trim($_POST['title'] ?? '');
$sku = trim($_POST['sku'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);
$brand_id = (int)($_POST['brand_id'] ?? 0);
$devices_count = (int)($_POST['devices_count'] ?? 1);
$duration_months = (int)($_POST['duration_months'] ?? 0);
$down_payment = (float)($_POST['down_payment'] ?? 0);
$monthly_amount = (float)($_POST['monthly_amount'] ?? 0);
$is_available = isset($_POST['is_available']) ? 1 : 0;
$is_hot_offer = isset($_POST['is_hot_offer']) ? 1 : 0;

// تحقق أساسي
if (!$title || !$sku || !$category_id || !$brand_id || !isset($_FILES['image'])) {
    echo json_encode(['ok' => false, 'message' => 'Missing required fields']);
    exit;
}

// رفع الصورة
$uploadDir = __DIR__ . '/../../images/products/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$filename = time() . '_' . uniqid() . '.' . $ext;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
    echo json_encode(['ok' => false, 'message' => 'Image upload failed']);
    exit;
}

// تحليل الاسم (بديل OCR)
function normalize_title($text) {
    $text = strtolower($text);
    $text = str_replace(['-', '_', '+'], ' ', $text);
    $text = preg_replace('/[^a-z0-9 ]/', '', $text);
    return trim(preg_replace('/\s+/', ' ', $text));
}

// تقسيم الأجهزة لو أكثر من جهاز
function extract_devices_from_filename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $parts = explode('+', $name);
    return array_map('trim', $parts);
}

try {
    $pdo = db();

    // إضافة المنتج
    $stmt = $pdo->prepare("
        INSERT INTO products
        (title, sku, category_id, brand_id, devices_count, duration_months, down_payment, monthly_amount, is_available, is_hot_offer, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $title,
        $sku,
        $category_id,
        $brand_id,
        $devices_count,
        $duration_months,
        $down_payment,
        $monthly_amount,
        $is_available,
        $is_hot_offer
    ]);

    $product_id = $pdo->lastInsertId();

    // استخراج الأجهزة
    $devices = extract_devices_from_filename($_FILES['image']['name']);

    $added_devices = [];

    foreach ($devices as $device) {

        $normalized = normalize_title($device);

        // هل موجود في stock_catalog؟
        $stmt = $pdo->prepare("
            SELECT id FROM stock_catalog WHERE normalized_title = ?
        ");
        $stmt->execute([$normalized]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stock_id = $existing['id'];
        } else {
            // إضافة جديد
            $stmt = $pdo->prepare("
                INSERT INTO stock_catalog (title, normalized_title, is_active, created_at)
                VALUES (?, ?, 1, NOW())
            ");
            $stmt->execute([$device, $normalized]);
            $stock_id = $pdo->lastInsertId();
        }

        // ربط المنتج بالمخزن
        $stmt = $pdo->prepare("
            INSERT INTO product_stock_links (product_id, stock_catalog_id, device_index)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$product_id, $stock_id, 0]);

        $added_devices[] = $device;
    }

    echo json_encode([
        'ok' => true,
        'message' => "تم حفظ المنتج وربط الأجهزة:\n" . implode("\n", $added_devices)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
