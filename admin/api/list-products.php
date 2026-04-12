<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('products_edit', 'ليس لديك صلاحية لعرض المنتجات.');

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
        COALESCE(NULLIF(b.display_name, ''), b.name) AS brand_name
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    INNER JOIN brands b ON b.id = p.brand_id
    WHERE p.category_id = :category_id
      AND p.brand_id = :brand_id
      AND p.is_active = 1
    ORDER BY p.product_order ASC, p.id DESC
");
$stmt->execute([
    'category_id' => $categoryId,
    'brand_id' => $brandId,
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$products = array_map(static function (array $row): array {
    $down = (float)($row['down_payment'] ?? 0);
    $monthly = (float)($row['monthly_amount'] ?? 0);
    $duration = (int)($row['duration_months'] ?? 0);

    return [
        'id' => (int)$row['id'],
        'category_id' => (int)$row['category_id'],
        'brand_id' => (int)$row['brand_id'],
        'title' => (string)$row['title'],
        'slug' => (string)($row['slug'] ?? ''),
        'sku' => (string)($row['sku'] ?? ''),
        'devices_count' => (int)($row['devices_count'] ?? 1),
        'image_path' => (string)($row['image_path'] ?? ''),
        'down_payment' => $down,
        'monthly_amount' => $monthly,
        'duration_months' => $duration,
        'is_available' => (bool)($row['is_available'] ?? false),
        'is_hot_offer' => (bool)($row['is_hot_offer'] ?? false),
        'product_order' => (int)($row['product_order'] ?? 9999),
        'is_active' => (bool)($row['is_active'] ?? false),
        'category_name' => (string)($row['category_name'] ?? ''),
        'brand_name' => (string)($row['brand_name'] ?? ''),
        'price_logic' => $down . ' KD Down / ' . $monthly . ' KD Monthly / ' . $duration . ' Months',
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
    ];
}, $rows);

json_response(true, ['products' => $products]);
