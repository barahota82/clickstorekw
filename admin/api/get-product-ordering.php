<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('manage_product_ordering', 'ليس لديك صلاحية عرض ترتيب المنتجات.');

$categoryId = (int)($_GET['category_id'] ?? 0);
$brandId = (int)($_GET['brand_id'] ?? 0);

if ($categoryId <= 0) {
    json_response(false, ['message' => 'Category is required'], 422);
}

$pdo = db();

$categoryStmt = $pdo->prepare("
    SELECT id, display_name, slug
    FROM categories
    WHERE id = ?
    LIMIT 1
");
$categoryStmt->execute([$categoryId]);
$category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    json_response(false, ['message' => 'Category not found'], 404);
}

$brand = null;
if ($brandId > 0) {
    $brandStmt = $pdo->prepare("
        SELECT id, category_id, name, display_name, slug
        FROM brands
        WHERE id = ? AND category_id = ?
        LIMIT 1
    ");
    $brandStmt->execute([$brandId, $categoryId]);
    $brand = $brandStmt->fetch(PDO::FETCH_ASSOC);

    if (!$brand) {
        json_response(false, ['message' => 'Brand not found in selected category'], 404);
    }
}

$sql = "
    SELECT
        p.id,
        p.category_id,
        p.brand_id,
        p.title,
        p.slug,
        p.sku,
        p.image_path,
        p.down_payment,
        p.monthly_amount,
        p.duration_months,
        p.is_available,
        p.is_hot_offer,
        p.product_order,
        p.is_active,
        b.display_name AS brand_display_name,
        b.name AS brand_name
    FROM products p
    INNER JOIN brands b ON b.id = p.brand_id
    WHERE p.category_id = ?
";

$params = [$categoryId];

if ($brandId > 0) {
    $sql .= " AND p.brand_id = ? ";
    $params[] = $brandId;
}

$sql .= " ORDER BY p.product_order ASC, p.id ASC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mapped = array_map(static function (array $row): array {
    return [
        'id' => (int)$row['id'],
        'category_id' => (int)$row['category_id'],
        'brand_id' => (int)$row['brand_id'],
        'title' => (string)$row['title'],
        'slug' => (string)$row['slug'],
        'sku' => (string)$row['sku'],
        'image_path' => (string)$row['image_path'],
        'down_payment' => (float)$row['down_payment'],
        'monthly_amount' => (float)$row['monthly_amount'],
        'duration_months' => (int)$row['duration_months'],
        'is_available' => (bool)$row['is_available'],
        'is_hot_offer' => (bool)$row['is_hot_offer'],
        'product_order' => (int)$row['product_order'],
        'is_active' => (bool)$row['is_active'],
        'brand_name' => (string)($row['brand_display_name'] ?: $row['brand_name'])
    ];
}, $products);

json_response(true, [
    'category' => [
        'id' => (int)$category['id'],
        'display_name' => (string)$category['display_name'],
        'slug' => (string)$category['slug']
    ],
    'brand' => $brand ? [
        'id' => (int)$brand['id'],
        'display_name' => (string)($brand['display_name'] ?: $brand['name']),
        'slug' => (string)$brand['slug']
    ] : null,
    'products' => $mapped
]);
