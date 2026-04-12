<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once dirname(__DIR__) . '/helpers/products_sync.php';

require_method('POST');
require_admin_auth_json();
admin_require_permission_json('manage_brand_ordering', 'ليس لديك صلاحية تعديل البراند.');

$data = get_request_json();

$brandId = (int)($data['id'] ?? 0);
$name = trim((string)($data['name'] ?? ''));
$displayName = trim((string)($data['display_name'] ?? ''));
$sortOrder = max(1, (int)($data['sort_order'] ?? 9999));
$isActive = isset($data['is_active']) ? (int)!!$data['is_active'] : 1;

if ($brandId <= 0) {
    json_response(false, ['message' => 'Brand is required'], 422);
}

if ($name === '') {
    json_response(false, ['message' => 'Brand name is required'], 422);
}

if ($displayName === '') {
    $displayName = $name;
}

$pdo = db();

$brandStmt = $pdo->prepare("
    SELECT id, category_id, slug, name
    FROM brands
    WHERE id = ?
    LIMIT 1
");
$brandStmt->execute([$brandId]);
$current = $brandStmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    json_response(false, ['message' => 'Brand not found'], 404);
}

$duplicateStmt = $pdo->prepare("
    SELECT id
    FROM brands
    WHERE category_id = ?
      AND name = ?
      AND id <> ?
    LIMIT 1
");
$duplicateStmt->execute([(int)$current['category_id'], $name, $brandId]);

if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
    json_response(false, ['message' => 'Another brand with the same name already exists in this category'], 409);
}

$updateStmt = $pdo->prepare("
    UPDATE brands
    SET
        name = :name,
        display_name = :display_name,
        sort_order = :sort_order,
        is_active = :is_active,
        updated_at = NOW()
    WHERE id = :id
    LIMIT 1
");

$updateStmt->execute([
    'name' => $name,
    'display_name' => $displayName,
    'sort_order' => $sortOrder,
    'is_active' => $isActive,
    'id' => $brandId,
]);

generate_products_json_for_category((int)$current['category_id']);

admin_activity_log(
    'update_brand',
    'brands',
    'brand',
    $brandId,
    'Updated brand | category_id: ' . (int)$current['category_id'] . ' | old_name: ' . (string)$current['name'] . ' | new_name: ' . $name
);

json_response(true, ['message' => 'تم تحديث البراند بنجاح']);
