<?php
require_once '../../config.php';
require_once '../helpers/products_sync.php';

require_admin_auth_json();

$data = get_request_json();

$title = trim($data['title'] ?? '');
$category_id = (int)$data['category_id'];
$brand_id = (int)$data['brand_id'];

if (!$title) {
    json_response(false, ['message' => 'Title required'], 422);
}

$slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
$sku = strtoupper(substr(md5($slug . time()), 0, 10));

$pdo = db();

$stmt = $pdo->prepare("
    INSERT INTO products
    (
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
        is_active
    )
    VALUES (?, ?, ?, ?, ?, 1, '', 0, 0, 1, 1, 0, 9999, 1)
");

$stmt->execute([
    $category_id,
    $brand_id,
    $title,
    $slug,
    $sku
]);

generate_products_json_for_category($category_id);

json_response(true, ['message' => 'Product added']);
