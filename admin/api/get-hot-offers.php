<?php
require_once '../../config.php';
require_admin_auth_json();

$category_id = (int)($_GET['category_id'] ?? 0);

$pdo = db();

$sql = "
    SELECT
        p.id,
        p.title,
        p.slug,
        p.image_path,
        p.monthly_amount,
        p.duration_months,
        p.down_payment,
        p.product_order,
        p.is_available,
        p.is_active,
        c.id AS category_id,
        c.display_name AS category_name,
        b.display_name AS brand_name,
        h.id AS hot_offer_id,
        h.sort_order AS hot_sort_order,
        h.is_active AS hot_is_active
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    INNER JOIN brands b ON b.id = p.brand_id
    LEFT JOIN hot_offers h ON h.product_id = p.id
    WHERE p.is_active = 1
";

$params = [];

if ($category_id > 0) {
    $sql .= " AND p.category_id = ? ";
    $params[] = $category_id;
}

$sql .= " ORDER BY c.sort_order ASC, b.sort_order ASC, p.product_order ASC, p.id ASC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll();

$products = [];

foreach ($rows as $row) {
    $products[] = [
        'id' => (int)$row['id'],
        'title' => (string)$row['title'],
        'slug' => (string)$row['slug'],
        'image_path' => (string)$row['image_path'],
        'monthly_amount' => (float)$row['monthly_amount'],
        'duration_months' => (int)$row['duration_months'],
        'down_payment' => (float)$row['down_payment'],
        'product_order' => (int)$row['product_order'],
        'is_available' => (bool)$row['is_available'],
        'category_id' => (int)$row['category_id'],
        'category_name' => (string)$row['category_name'],
        'brand_name' => (string)$row['brand_name'],
        'is_hot_offer' => !empty($row['hot_offer_id']) && (int)$row['hot_is_active'] === 1,
        'hot_sort_order' => $row['hot_sort_order'] !== null ? (int)$row['hot_sort_order'] : 9999
    ];
}

json_response(true, ['products' => $products]);
