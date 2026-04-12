<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once __DIR__ . '/link-product-stock.php';

if (!function_exists('products_manager_detect_ext_from_path')) {
    function products_manager_detect_ext_from_path(string $path): string
    {
        $ext = strtolower(trim((string)pathinfo($path, PATHINFO_EXTENSION)));
        return $ext !== '' ? $ext : 'jpg';
    }
}

if (!function_exists('products_manager_build_review_filename')) {
    function products_manager_build_review_filename(array $product, array $linkRows): string
    {
        $title = trim((string)($product['title'] ?? ''));
        $imagePath = trim((string)($product['image_path'] ?? ''));
        $ext = products_manager_detect_ext_from_path($imagePath);

        $normalizedTitle = preg_replace('/\s*\/\s*/u', ' + ', $title);
        $normalizedTitle = preg_replace('/\s+/u', ' ', (string)$normalizedTitle);
        $normalizedTitle = trim((string)$normalizedTitle);

        if ($normalizedTitle !== '' && str_contains($normalizedTitle, '+')) {
            return $normalizedTitle . '.' . $ext;
        }

        $names = [];
        foreach ($linkRows as $row) {
            $name = trim((string)($row['extracted_name'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        $names = array_values(array_unique($names));

        if (count($names) >= 2) {
            return implode(' + ', $names) . '.' . $ext;
        }

        if ($imagePath !== '') {
            return basename($imagePath);
        }

        if (!empty($names)) {
            return $names[0] . '.' . $ext;
        }

        if ($normalizedTitle !== '') {
            return $normalizedTitle . '.' . $ext;
        }

        return 'product-' . (int)($product['id'] ?? 0) . '.' . $ext;
    }
}

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('products_edit', 'ليس لديك صلاحية لعرض بيانات المنتج.');

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    json_response(false, ['message' => 'Invalid product id'], 422);
}

$pdo = db();

$productStmt = $pdo->prepare("
    SELECT
        p.*,
        c.display_name AS category_name,
        COALESCE(NULLIF(b.display_name, ''), b.name) AS brand_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    WHERE p.id = :id
    LIMIT 1
");
$productStmt->execute(['id' => $productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    json_response(false, ['message' => 'Product not found'], 404);
}

$linksStmt = $pdo->prepare("
    SELECT
        psl.id,
        psl.device_index,
        psl.source_type,
        psl.extracted_name,
        sc.id AS stock_catalog_id,
        sc.category_id,
        sc.brand_id,
        sc.title AS stock_title,
        sc.normalized_title,
        sc.storage_value,
        sc.ram_value,
        sc.network_value,
        c.display_name AS category_name,
        COALESCE(NULLIF(b.display_name, ''), b.name) AS brand_name
    FROM product_stock_links psl
    INNER JOIN stock_catalog sc ON sc.id = psl.stock_catalog_id
    LEFT JOIN categories c ON c.id = sc.category_id
    LEFT JOIN brands b ON b.id = sc.brand_id
    WHERE psl.product_id = :product_id
    ORDER BY psl.device_index ASC, psl.id ASC
");
$linksStmt->execute(['product_id' => $productId]);
$linkRows = $linksStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$reviewFilename = products_manager_build_review_filename($product, $linkRows);
$review = review_stock_from_filename(
    $pdo,
    $reviewFilename,
    (int)($product['brand_id'] ?? 0) ?: null,
    (int)($product['category_id'] ?? 0) ?: null
);

$linkByDevice = [];
foreach ($linkRows as $row) {
    $deviceIndex = (int)($row['device_index'] ?? 0);
    if ($deviceIndex <= 0 || isset($linkByDevice[$deviceIndex])) {
        continue;
    }
    $linkByDevice[$deviceIndex] = $row;
}

$seenDeviceIndexes = [];
$linked = [];

foreach (($review['linked'] ?? []) as $item) {
    $deviceIndex = (int)($item['device_index'] ?? 0);
    $currentLink = $linkByDevice[$deviceIndex] ?? null;

    if ($deviceIndex > 0) {
        $seenDeviceIndexes[$deviceIndex] = true;
    }

    if ($currentLink) {
        $item['product_linked'] = true;
        $item['link_id'] = (int)($currentLink['id'] ?? 0);
        $item['source_type'] = (string)($currentLink['source_type'] ?? 'filename');
        $item['extracted_name'] = (string)($currentLink['extracted_name'] ?? '');
        $item['raw_title'] = (string)($item['raw_title'] ?? $currentLink['extracted_name'] ?? $item['stock_title'] ?? '');
    } else {
        $item['product_linked'] = false;
        $item['source_type'] = 'filename';
    }

    $linked[] = $item;
}

foreach ($linkRows as $row) {
    $deviceIndex = (int)($row['device_index'] ?? 0);
    if ($deviceIndex > 0 && isset($seenDeviceIndexes[$deviceIndex])) {
        continue;
    }

    $linked[] = [
        'device_index' => $deviceIndex > 0 ? $deviceIndex : count($linked) + 1,
        'raw_title' => (string)($row['extracted_name'] ?? $row['stock_title'] ?? ''),
        'normalized_title' => (string)($row['normalized_title'] ?? ''),
        'storage_value' => $row['storage_value'] !== null ? (string)$row['storage_value'] : null,
        'ram_value' => $row['ram_value'] !== null ? (string)$row['ram_value'] : null,
        'network_value' => $row['network_value'] !== null ? (string)$row['network_value'] : null,
        'brand_id' => (int)($row['brand_id'] ?? 0),
        'brand_name' => (string)($row['brand_name'] ?? ''),
        'category_id' => (int)($row['category_id'] ?? 0),
        'category_name' => (string)($row['category_name'] ?? ''),
        'stock_catalog_id' => (int)($row['stock_catalog_id'] ?? 0),
        'stock_title' => (string)($row['stock_title'] ?? ''),
        'product_linked' => true,
        'source_type' => (string)($row['source_type'] ?? 'manual'),
        'extracted_name' => (string)($row['extracted_name'] ?? ''),
        'is_added' => true,
    ];
}

usort($linked, static function (array $a, array $b): int {
    return (int)($a['device_index'] ?? 0) <=> (int)($b['device_index'] ?? 0);
});

$review['product_id'] = (int)$product['id'];
$review['linked'] = $linked;
$review['linked_count'] = count($linked);

json_response(true, [
    'product' => [
        'id' => (int)$product['id'],
        'category_id' => (int)$product['category_id'],
        'brand_id' => (int)$product['brand_id'],
        'title' => (string)$product['title'],
        'slug' => (string)($product['slug'] ?? ''),
        'sku' => (string)($product['sku'] ?? ''),
        'devices_count' => (int)($product['devices_count'] ?? 1),
        'image_path' => (string)($product['image_path'] ?? ''),
        'down_payment' => (float)($product['down_payment'] ?? 0),
        'monthly_amount' => (float)($product['monthly_amount'] ?? 0),
        'duration_months' => (int)($product['duration_months'] ?? 1),
        'is_available' => (bool)($product['is_available'] ?? false),
        'is_hot_offer' => (bool)($product['is_hot_offer'] ?? false),
        'product_order' => (int)($product['product_order'] ?? 9999),
        'json_file_path' => (string)($product['json_file_path'] ?? ''),
        'is_active' => (bool)($product['is_active'] ?? false),
        'category_name' => (string)($product['category_name'] ?? ''),
        'brand_name' => (string)($product['brand_name'] ?? ''),
    ],
    'review_source_filename' => $reviewFilename,
    'stock_links' => $linkRows,
    'stock_review' => $review,
]);
