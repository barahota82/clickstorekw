<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';
require_once __DIR__ . '/../helpers/products_sync.php';

require_method('POST');
require_admin_auth_json();
admin_require_permission_json('manage_product_ordering', 'ليس لديك صلاحية تعديل ترتيب المنتجات.');

$data = get_request_json();
$categoryId = (int)($data['category_id'] ?? 0);
$brandId = (int)($data['brand_id'] ?? 0);
$items = $data['items'] ?? [];

if ($categoryId <= 0) {
    json_response(false, ['message' => 'Category is required'], 422);
}

if (!is_array($items) || count($items) === 0) {
    json_response(false, ['message' => 'No product items provided'], 422);
}

$pdo = db();

$categoryStmt = $pdo->prepare("
    SELECT id
    FROM categories
    WHERE id = ?
    LIMIT 1
");
$categoryStmt->execute([$categoryId]);

if (!$categoryStmt->fetch()) {
    json_response(false, ['message' => 'Category not found'], 404);
}

if ($brandId > 0) {
    $brandStmt = $pdo->prepare("
        SELECT id
        FROM brands
        WHERE id = ? AND category_id = ?
        LIMIT 1
    ");
    $brandStmt->execute([$brandId, $categoryId]);

    if (!$brandStmt->fetch()) {
        json_response(false, ['message' => 'Brand not found in selected category'], 404);
    }
}

$pdo->beginTransaction();

try {
    if ($brandId > 0) {
        $updateStmt = $pdo->prepare("
            UPDATE products
            SET product_order = ?, updated_at = NOW()
            WHERE id = ? AND category_id = ? AND brand_id = ?
        ");
    } else {
        $updateStmt = $pdo->prepare("
            UPDATE products
            SET product_order = ?, updated_at = NOW()
            WHERE id = ? AND category_id = ?
        ");
    }

    foreach ($items as $item) {
        $productId = (int)($item['id'] ?? 0);
        $sortOrder = (int)($item['product_order'] ?? 9999);

        if ($productId <= 0) {
            continue;
        }

        if ($brandId > 0) {
            $updateStmt->execute([$sortOrder, $productId, $categoryId, $brandId]);
        } else {
            $updateStmt->execute([$sortOrder, $productId, $categoryId]);
        }
    }

    $pdo->commit();

    generate_products_json_for_category($categoryId);

    admin_activity_log(
        'save_product_ordering',
        'product_ordering',
        'category',
        $categoryId,
        'Saved product ordering for category id: ' . $categoryId . ($brandId > 0 ? ' | brand id: ' . $brandId : '')
    );

    json_response(true, ['message' => 'Product ordering saved successfully']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to save product ordering',
        'error' => $e->getMessage()
    ], 500);
}
