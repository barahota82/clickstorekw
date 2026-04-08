<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

require_post();
require_admin_auth_json();
admin_require_permission_json('stock_manage', 'ليس لديك صلاحية لإضافة عنصر إلى المخزن');

$data = get_request_json();

$rawTitle = trim((string)($data['raw_title'] ?? ''));
$normalizedTitle = normalize_stock_title((string)($data['normalized_title'] ?? $rawTitle));
$categoryId = (int)($data['category_id'] ?? 0);
$brandId = (int)($data['brand_id'] ?? 0);

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

    $existing = find_stock_catalog(
        $pdo,
        $normalizedTitle,
        $brandId,
        $categoryId,
        $storageValue,
        $ramValue,
        $networkValue
    );

    if ($existing) {
        json_response(true, [
            'message' => 'العنصر موجود بالفعل في المخزن',
            'stock_item' => [
                'id' => (int)$existing['id'],
                'title' => (string)$existing['title'],
                'normalized_title' => (string)$existing['normalized_title'],
                'category_id' => (int)$existing['category_id'],
                'brand_id' => (int)$existing['brand_id'],
                'storage_value' => $existing['storage_value'] !== null ? (string)$existing['storage_value'] : null,
                'ram_value' => $existing['ram_value'] !== null ? (string)$existing['ram_value'] : null,
                'network_value' => $existing['network_value'] !== null ? (string)$existing['network_value'] : null,
                'is_existing' => true
            ]
        ]);
    }

    $pdo->beginTransaction();

    $stockId = create_stock_catalog(
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
        $stockId,
        'Created stock item manually from add-missing-stock-item API | title: ' . $rawTitle
    );

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

    json_response(true, [
        'message' => 'تمت إضافة الجهاز إلى المخزن بنجاح',
        'stock_item' => [
            'id' => $created ? (int)$created['id'] : $stockId,
            'title' => $created ? (string)$created['title'] : $rawTitle,
            'normalized_title' => $created ? (string)$created['normalized_title'] : $normalizedTitle,
            'category_id' => $created ? (int)$created['category_id'] : $categoryId,
            'brand_id' => $created ? (int)$created['brand_id'] : $brandId,
            'storage_value' => $created && $created['storage_value'] !== null ? (string)$created['storage_value'] : $storageValue,
            'ram_value' => $created && $created['ram_value'] !== null ? (string)$created['ram_value'] : $ramValue,
            'network_value' => $created && $created['network_value'] !== null ? (string)$created['network_value'] : $networkValue,
            'is_existing' => false
        ]
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
