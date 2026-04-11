<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';
require_once __DIR__ . '/../helpers/stock_helper.php';

require_method('POST');
require_admin_auth_json();
admin_require_permission_json('manage_stock', 'ليس لديك صلاحية إضافة عنصر إلى stock catalog.');

$data = get_request_json();

$title = trim((string)($data['title'] ?? ''));
$categoryId = (int)($data['category_id'] ?? 0);
$brandId = (int)($data['brand_id'] ?? 0);
$storageValue = trim((string)($data['storage_value'] ?? ''));
$ramValue = trim((string)($data['ram_value'] ?? ''));
$networkValue = trim((string)($data['network_value'] ?? ''));

if ($title === '') {
    json_response(false, ['message' => 'Title is required'], 422);
}

if ($categoryId <= 0) {
    json_response(false, ['message' => 'Category is required'], 422);
}

if ($brandId <= 0) {
    json_response(false, ['message' => 'Brand is required'], 422);
}

$pdo = db();

$categoryStmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? LIMIT 1");
$categoryStmt->execute([$categoryId]);
if (!$categoryStmt->fetch(PDO::FETCH_ASSOC)) {
    json_response(false, ['message' => 'Category not found'], 404);
}

$brandStmt = $pdo->prepare("SELECT id FROM brands WHERE id = ? AND category_id = ? LIMIT 1");
$brandStmt->execute([$brandId, $categoryId]);
if (!$brandStmt->fetch(PDO::FETCH_ASSOC)) {
    json_response(false, ['message' => 'Brand not found in selected category'], 404);
}

$normalizedTitle = normalize_stock_title($title);

try {
    $stockCatalogId = create_stock_catalog(
        $pdo,
        $brandId,
        $categoryId,
        $title,
        $normalizedTitle,
        $storageValue !== '' ? $storageValue : null,
        $ramValue !== '' ? $ramValue : null,
        $networkValue !== '' ? $networkValue : null
    );

    $itemStmt = $pdo->prepare("
        SELECT
            sc.id,
            sc.category_id,
            sc.brand_id,
            sc.title,
            sc.slug,
            sc.normalized_title,
            sc.storage_value,
            sc.ram_value,
            sc.network_value,
            sc.sku,
            sc.is_active,
            c.display_name AS category_name,
            b.name AS brand_name
        FROM stock_catalog sc
        LEFT JOIN categories c ON c.id = sc.category_id
        LEFT JOIN brands b ON b.id = sc.brand_id
        WHERE sc.id = ?
        LIMIT 1
    ");
    $itemStmt->execute([$stockCatalogId]);
    $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

    if (function_exists('admin_activity_log')) {
        admin_activity_log(
            'save_stock_catalog_item',
            'stock',
            'stock_catalog',
            $stockCatalogId,
            'Saved stock catalog item | title: ' . $title
        );
    }

    json_response(true, [
        'message' => 'Stock catalog item saved successfully',
        'item' => [
            'id' => (int)$item['id'],
            'category_id' => isset($item['category_id']) ? (int)$item['category_id'] : null,
            'brand_id' => isset($item['brand_id']) ? (int)$item['brand_id'] : null,
            'title' => (string)$item['title'],
            'slug' => (string)$item['slug'],
            'normalized_title' => (string)$item['normalized_title'],
            'storage_value' => $item['storage_value'] !== null ? (string)$item['storage_value'] : null,
            'ram_value' => $item['ram_value'] !== null ? (string)$item['ram_value'] : null,
            'network_value' => $item['network_value'] !== null ? (string)$item['network_value'] : null,
            'sku' => $item['sku'] !== null ? (string)$item['sku'] : null,
            'is_active' => (bool)$item['is_active'],
            'category_name' => (string)($item['category_name'] ?? ''),
            'brand_name' => (string)($item['brand_name'] ?? '')
        ]
    ]);
} catch (Throwable $e) {
    json_response(false, [
        'message' => 'Failed to save stock catalog item',
        'error' => $e->getMessage()
    ], 500);
}
