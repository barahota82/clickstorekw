<?php
require_once '../../config.php';
require_once '../helpers/hot_offers_sync.php';

require_admin_auth_json();

$data = get_request_json();
$items = $data['items'] ?? [];

if (!is_array($items) || count($items) === 0) {
    json_response(false, ['message' => 'No items provided'], 422);
}

$pdo = db();
$productIds = [];

$pdo->beginTransaction();

try {
    foreach ($items as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $sort_order = (int)($item['sort_order'] ?? 9999);

        if ($product_id <= 0) {
            continue;
        }

        $productIds[] = $product_id;

        $checkStmt = $pdo->prepare("
            SELECT id
            FROM hot_offers
            WHERE product_id = ?
            LIMIT 1
        ");
        $checkStmt->execute([$product_id]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("
                UPDATE hot_offers
                SET sort_order = ?, is_active = 1, updated_at = NOW()
                WHERE product_id = ?
            ");
            $stmt->execute([$sort_order, $product_id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO hot_offers (product_id, sort_order, is_active)
                VALUES (?, ?, 1)
            ");
            $stmt->execute([$product_id, $sort_order]);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to update hot offer order',
        'error' => $e->getMessage()
    ], 500);
}

sync_hot_offer_flags_for_products($productIds);
regenerate_products_json_for_products($productIds);

json_response(true, ['message' => 'Hot offer order updated']);
