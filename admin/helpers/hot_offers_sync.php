<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/products_sync.php';

function sync_hot_offer_flags_for_products(array $productIds = []): void
{
    $pdo = db();

    if (!empty($productIds)) {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $resetStmt = $pdo->prepare("
            UPDATE products
            SET is_hot_offer = 0
            WHERE id IN ($placeholders)
        ");
        $resetStmt->execute($productIds);

        $activeStmt = $pdo->prepare("
            UPDATE products p
            INNER JOIN hot_offers h ON h.product_id = p.id
            SET p.is_hot_offer = 1
            WHERE h.is_active = 1
              AND p.id IN ($placeholders)
        ");
        $activeStmt->execute($productIds);

        return;
    }

    $pdo->exec("UPDATE products SET is_hot_offer = 0");

    $pdo->exec("
        UPDATE products p
        INNER JOIN hot_offers h ON h.product_id = p.id
        SET p.is_hot_offer = 1
        WHERE h.is_active = 1
    ");
}

function regenerate_products_json_for_products(array $productIds = []): void
{
    if (empty($productIds)) {
        return;
    }

    $pdo = db();

    $productIds = array_values(array_unique(array_map('intval', $productIds)));
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $stmt = $pdo->prepare("
        SELECT DISTINCT category_id
        FROM products
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($productIds);

    $categories = $stmt->fetchAll();

    foreach ($categories as $row) {
        generate_products_json_for_category((int)$row['category_id']);
    }
}
