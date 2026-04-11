<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';
require_once __DIR__ . '/../helpers/products_sync.php';

require_post();
require_admin_auth_json();
admin_require_permission_json('products.delete', 'ليس لديك صلاحية لحذف المنتج');

$data = get_request_json();
$productId = (int)($data['id'] ?? 0);

if ($productId <= 0) {
    json_response(false, ['message' => 'Invalid product id'], 422);
}

$pdo = db();

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            sku,
            category_id,
            is_active
        FROM products
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        json_response(false, ['message' => 'Product not found'], 404);
    }

    if ((int)$product['is_active'] === 0) {
        json_response(false, ['message' => 'Product already deleted'], 422);
    }

    $categoryId = (int)$product['category_id'];

    $pdo->beginTransaction();

    $adminUserId = function_exists('admin_current_user_id') && admin_current_user_id() > 0
        ? admin_current_user_id()
        : ((int)($_SESSION['admin_user_id'] ?? 0) ?: null);

    $updateProduct = $pdo->prepare("
        UPDATE products
        SET
            is_active = 0,
            is_available = 0,
            is_hot_offer = 0,
            updated_by = :updated_by,
            updated_at = NOW()
        WHERE id = :id
        LIMIT 1
    ");
    $updateProduct->execute([
        'id' => $productId,
        'updated_by' => $adminUserId,
    ]);

    $disableHotOffer = $pdo->prepare("
        UPDATE hot_offers
        SET
            is_active = 0,
            updated_at = NOW()
        WHERE product_id = :product_id
    ");
    $disableHotOffer->execute([
        'product_id' => $productId
    ]);

    $pdo->commit();

    generate_products_json_for_category($categoryId);

    if (function_exists('admin_activity_log')) {
        admin_activity_log(
            'delete_product',
            'products',
            'product',
            $productId,
            'Soft deleted product | title: ' . (string)$product['title'] . ' | sku: ' . (string)$product['sku']
        );
    }

    json_response(true, [
        'message' => 'تم حذف المنتج منطقيًا بنجاح',
        'product' => [
            'id' => (int)$product['id'],
            'title' => (string)$product['title'],
            'sku' => (string)$product['sku'],
            'is_active' => 0,
            'is_available' => 0
        ]
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'فشل حذف المنتج',
        'error' => $e->getMessage()
    ], 500);
}
