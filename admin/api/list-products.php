<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('products.edit', 'ليس لديك صلاحية لعرض المنتجات');

$categoryId = (int)($_GET['category_id'] ?? 0);
$brandId = (int)($_GET['brand_id'] ?? 0);

if ($categoryId <= 0 || $brandId <= 0) {
    json_response(false, ['message' => 'Category and brand are required'], 422);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.category_id,
        p.brand_id,
        p.title,
        p.slug,
        p.sku,
        p.devices_count,
        p.image_path,
        p.down_payment,
        p.monthly_amount,
        p.duration_months,
        p.is_available,
        p.is_hot_offer,
        p.product_order,
        p.is_active,
        p.created_at,
        p.updated_at,
        c.display_name AS category_name,
        b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    WHERE p.category_id = :category_id
      AND p.brand_id = :brand_id
      AND p.is_active = 1
    ORDER BY p.product_order ASC, p.id DESC
");
$stmt->execute([
    'category_id' => $categoryId,
    'brand_id' => $brandId
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = array_map(static function (array $row): array {
    return [
        'id' => (int)$row['id'],
        'category_id' => (int)$row['category_id'],
        'brand_id' => (int)$row['brand_id'],
        'title' => (string)$row['title'],
        'slug' => (string)($row['slug'] ?? ''),
        'sku' => (string)($row['sku'] ?? ''),
        'devices_count' => (int)($row['devices_count'] ?? 1),
        'image_path' => (string)($row['image_path'] ?? ''),
        'down_payment' => (float)($row['down_payment'] ?? 0),
        'monthly_amount' => (float)($row['monthly_amount'] ?? 0),
        'duration_months' => (int)($row['duration_months'] ?? 0),
        'is_available' => (int)($row['is_available'] ?? 0),
        'is_hot_offer' => (int)($row['is_hot_offer'] ?? 0),
        'product_order' => (int)($row['product_order'] ?? 9999),
        'is_active' => (int)($row['is_active'] ?? 0),
        'category_name' => (string)($row['category_name'] ?? ''),
        'brand_name' => (string)($row['brand_name'] ?? ''),
        'price_logic' => trim(
            ((float)($row['down_payment'] ?? 0)) . ' KD Down / ' .
            ((float)($row['monthly_amount'] ?? 0)) . ' KD Monthly / ' .
            ((int)($row['duration_months'] ?? 0)) . ' Months'
        ),
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? '')
    ];
}, $rows ?: []);

json_response(true, [
    'products' => $products,
]);
