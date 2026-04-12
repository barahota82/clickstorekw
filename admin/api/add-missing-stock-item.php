<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

if (!function_exists('add_missing_stock_item_link_product')) {
    function add_missing_stock_item_link_product(
        PDO $pdo,
        int $productId,
        int $stockCatalogId,
        int $deviceIndex,
        string $extractedName
    ): bool {
        if ($productId <= 0 || $stockCatalogId <= 0 || $deviceIndex <= 0) {
            return false;
        }

        $productStmt = $pdo->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
        $productStmt->execute([$productId]);

        if (!$productStmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Product not found for stock linking');
        }

        $deleteStmt = $pdo->prepare("
            DELETE FROM product_stock_links
            WHERE product_id = :product_id
              AND device_index = :device_index
        ");
        $deleteStmt->execute([
            'product_id' => $productId,
            'device_index' => $deviceIndex,
        ]);

        $insertStmt = $pdo->prepare("
            INSERT INTO product_stock_links (
                product_id,
                stock_catalog_id,
                device_index,
                source_type,
                extracted_name,
                created_at
            ) VALUES (
                :product_id,
                :stock_catalog_id,
                :device_index,
                'manual',
                :extracted_name,
                NOW()
            )
        ");
        $insertStmt->execute([
            'product_id' => $productId,
            'stock_catalog_id' => $stockCatalogId,
            'device_index' => $deviceIndex,
            'extracted_name' => trim($extractedName),
        ]);

        return true;
    }
}

require_post();
require_admin_auth_json();
admin_require_permission_json('stock_manage', 'ليس لديك صلاحية لإضافة عنصر إلى المخزن');

$data = get_request_json();

$rawTitle = trim((string)($data['raw_title'] ?? ''));
$normalizedTitle = normalize_stock_title((string)($data['normalized_title'] ?? $rawTitle));
$categoryId = (int)($data['category_id'] ?? 0);
$brandId = (int)($data['brand_id'] ?? 0);
$productId = (int)($data['product_id'] ?? 0);
$deviceIndex = (int)($data['device_index'] ?? 0);
$extractedName = trim((string)($data['extracted_name'] ?? $rawTitle));

$storageValue = isset($data['storage_value']) && $data['storage_value'] !== ''
    ? trim((string)$data['storage_value'])
    : null;

$ramValue = isset($data['ram_value']) && $data['ram_value'] !== ''
    ? trim((string)$data['ram_value'])
    : null;

$networkValue = isset($data['network_value']) && $data['network_value'] !== ''
    ? trim((string)$data['network_value'])
    : null;

if ($rawTitle === '' || $normalizedTitle === '') {
    json_response(false, ['message' => 'اسم الجهاز مطلوب'], 422);
}

if ($categoryId <= 0) {
    json_response(false, ['message' => 'Category is required'], 422);
}

if ($brandId <= 0) {
    json_response(false, ['message' => 'Brand is required'], 422);
}

$pdo = db();

try {
    $categoryStmt = $pdo->prepare("
        SELECT id, display_name, slug
        FROM categories
        WHERE id = :id
        LIMIT 1
    ");
    $categoryStmt->execute(['id' => $categoryId]);
    $categoryRow = $categoryStmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoryRow) {
        json_response(false, ['message' => 'Invalid category'], 404);
    }

    $brandStmt = $pdo->prepare("
        SELECT id, category_id, name, slug
        FROM brands
        WHERE id = :id
        LIMIT 1
    ");
    $brandStmt->execute(['id' => $brandId]);
    $brandRow = $brandStmt->fetch(PDO::FETCH_ASSOC);

    if (!$brandRow) {
        json_response(false, ['message' => 'Invalid brand'], 404);
    }

    if ((int)$brandRow['category_id'] !== $categoryId) {
        json_response(false, ['message' => 'Selected brand does not belong to selected category'], 422);
    }

    $pdo->beginTransaction();

    $existing = find_stock_catalog(
        $pdo,
        $normalizedTitle,
        $brandId,
        $categoryId,
        $storageValue,
        $ramValue,
        $networkValue
    );

    $stockCatalogId = 0;
    $isExisting = false;

    if ($existing) {
        $stockCatalogId = (int)$existing['id'];
        $isExisting = true;
    } else {
        $stockCatalogId = create_stock_catalog(
            $pdo,
            $brandId,
            $categoryId,
            $rawTitle,
            $normalizedTitle,
            $storageValue,
            $ramValue,
            $networkValue
        );

        admin_activity_log(
            'create_stock_item',
            'stock',
            'stock_catalog',
            $stockCatalogId,
            'Created stock item manually from add-missing-stock-item API | title: ' . $rawTitle
        );
    }

    $linkedToProduct = add_missing_stock_item_link_product(
        $pdo,
        $productId,
        $stockCatalogId,
        $deviceIndex,
        $extractedName
    );

    $inventoryStockItem = null;
    if ($productId > 0) {
        $inventoryStockItem = ensure_stock_item_for_product($pdo, $productId, null, null);
    }

    if ($linkedToProduct) {
        admin_activity_log(
            'link_product_stock_manual',
            'stock',
            'product',
            $productId,
            'Linked product to stock item manually | product_id: ' . $productId . ' | stock_catalog_id: ' . $stockCatalogId . ' | device_index: ' . $deviceIndex
        );
    }

    $pdo->commit();

    $created = find_stock_catalog(
        $pdo,
        $normalizedTitle,
        $brandId,
        $categoryId,
        $storageValue,
        $ramValue,
        $networkValue
    );

    $finalItem = [
        'id' => $created ? (int)$created['id'] : $stockCatalogId,
        'title' => $created ? (string)$created['title'] : $rawTitle,
        'normalized_title' => $created ? (string)$created['normalized_title'] : $normalizedTitle,
        'category_id' => $created ? (int)$created['category_id'] : $categoryId,
        'category_name' => (string)$categoryRow['display_name'],
        'brand_id' => $created ? (int)$created['brand_id'] : $brandId,
        'brand_name' => (string)$brandRow['name'],
        'storage_value' => $created && $created['storage_value'] !== null ? (string)$created['storage_value'] : $storageValue,
        'ram_value' => $created && $created['ram_value'] !== null ? (string)$created['ram_value'] : $ramValue,
        'network_value' => $created && $created['network_value'] !== null ? (string)$created['network_value'] : $networkValue,
        'is_existing' => $isExisting,
    ];

    $message = $isExisting
        ? 'العنصر موجود بالفعل في المخزن'
        : 'تمت إضافة الجهاز إلى المخزن بنجاح';

    if ($linkedToProduct) {
        $message .= ' وتم ربطه بالمنتج الحالي';
    }

    json_response(true, [
        'message' => $message,
        'stock_item' => $finalItem,
        'inventory_stock_item' => $inventoryStockItem,
        'linked_to_product' => $linkedToProduct,
        'product_id' => $linkedToProduct ? $productId : null,
        'device_index' => $linkedToProduct ? $deviceIndex : null,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'فشل إضافة الجهاز إلى المخزن',
        'error' => $e->getMessage()
    ], 500);
}
