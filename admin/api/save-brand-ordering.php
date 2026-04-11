<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('POST');
require_admin_auth_json();
admin_require_permission_json('manage_brand_ordering', 'ليس لديك صلاحية تعديل ترتيب البراندات.');

$data = get_request_json();
$categoryId = (int)($data['category_id'] ?? 0);
$items = $data['items'] ?? [];

if ($categoryId <= 0) {
    json_response(false, ['message' => 'Category is required'], 422);
}

if (!is_array($items) || count($items) === 0) {
    json_response(false, ['message' => 'No brand items provided'], 422);
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

$pdo->beginTransaction();

try {
    $updateStmt = $pdo->prepare("
        UPDATE brands
        SET sort_order = ?, updated_at = NOW()
        WHERE id = ? AND category_id = ?
    ");

    foreach ($items as $item) {
        $brandId = (int)($item['id'] ?? 0);
        $sortOrder = (int)($item['sort_order'] ?? 9999);

        if ($brandId <= 0) {
            continue;
        }

        $updateStmt->execute([$sortOrder, $brandId, $categoryId]);
    }

    $pdo->commit();

    admin_activity_log(
        'save_brand_ordering',
        'brand_ordering',
        'category',
        $categoryId,
        'Saved brand ordering for category id: ' . $categoryId
    );

    json_response(true, ['message' => 'Brand ordering saved successfully']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to save brand ordering',
        'error' => $e->getMessage()
    ], 500);
}
