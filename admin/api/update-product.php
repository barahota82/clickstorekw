<?php
require_once '../../config.php';
require_once '../helpers/products_sync.php';

require_admin_auth_json();

$data = get_request_json();

$id = (int)$data['id'];

$pdo = db();

$stmt = $pdo->prepare("
    UPDATE products
    SET 
        title = ?,
        image_path = ?,
        down_payment = ?,
        monthly_amount = ?,
        duration_months = ?,
        is_available = ?,
        is_hot_offer = ?,
        product_order = ?
    WHERE id = ?
");

$stmt->execute([
    $data['title'],
    $data['image_path'],
    (float)$data['down_payment'],
    (float)$data['monthly_amount'],
    (int)$data['duration_months'],
    $data['is_available'] ? 1 : 0,
    $data['is_hot_offer'] ? 1 : 0,
    (int)$data['product_order'],
    $id
]);

// نعرف الكاتيجوري لتحديث JSON
$stmt2 = $pdo->prepare("SELECT category_id FROM products WHERE id = ?");
$stmt2->execute([$id]);
$cat = $stmt2->fetch();

if ($cat) {
    generate_products_json_for_category((int)$cat['category_id']);
}

json_response(true, ['message' => 'Updated']);
