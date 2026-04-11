<?php
require_once '../../config.php';
require_once '../helpers/hot_offers_sync.php';

require_admin_auth_json();

$data = get_request_json();

$product_id = (int)($data['product_id'] ?? 0);
$enabled = !empty($data['enabled']) ? 1 : 0;

if ($product_id <= 0) {
    json_response(false, ['message' => 'Product ID is required'], 422);
}

$pdo = db();

$productStmt = $pdo->prepare("
    SELECT id
    FROM products
    WHERE id = ?
    LIMIT 1
");
$productStmt->execute([$product_id]);
$product = $productStmt->fetch();

if (!$product) {
    json_response(false, ['message' => 'Product not found'], 404);
}

$existsStmt = $pdo->prepare("
    SELECT id
    FROM hot_offers
    WHERE product_id = ?
    LIMIT 1
");
$existsStmt->execute([$product_id]);
$existing = $existsStmt->fetch();

if ($enabled === 1) {
    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE hot_offers
            SET is_active = 1, updated_at = NOW()
            WHERE product_id = ?
        ");
        $stmt->execute([$product_id]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hot_offers (product_id, sort_order, is_active)
            VALUES (?, 9999, 1)
        ");
        $stmt->execute([$product_id]);
    }
} else {
    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE hot_offers
            SET is_active = 0, updated_at = NOW()
            WHERE product_id = ?
        ");
        $stmt->execute([$product_id]);
    }
}

sync_hot_offer_flags_for_products([$product_id]);
regenerate_products_json_for_products([$product_id]);

json_response(true, ['message' => 'Hot offer updated']);
