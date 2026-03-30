<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, ['message' => 'Invalid request method'], 405);
}

require_admin_auth_json();

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    json_response(false, ['message' => 'Invalid product id'], 422);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT
        p.*,
        c.display_name AS category_name,
        b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    WHERE p.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    json_response(false, ['message' => 'Product not found'], 404);
}

$links = $pdo->prepare("
    SELECT
        psl.id,
        psl.device_index,
        psl.source_type,
        psl.extracted_name,
        sc.id AS stock_catalog_id,
        sc.title AS stock_title,
        sc.normalized_title,
        sc.storage_value,
        sc.ram_value,
        sc.network_value
    FROM product_stock_links psl
    INNER JOIN stock_catalog sc ON sc.id = psl.stock_catalog_id
    WHERE psl.product_id = :product_id
    ORDER BY psl.device_index ASC
");
$links->execute(['product_id' => $productId]);

json_response(true, [
    'product' => $product,
    'stock_links' => $links->fetchAll(),
]);
