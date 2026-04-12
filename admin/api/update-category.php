<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once dirname(__DIR__) . '/helpers/categories_sync.php';

require_method('POST');
require_admin_auth_json();
admin_require_permission_json('manage_brand_ordering', 'ليس لديك صلاحية تعديل الفئة.');

$data = get_request_json();

$categoryId = (int)($data['id'] ?? 0);
$nameEn = trim((string)($data['name_en'] ?? ''));
$namePh = trim((string)($data['name_ph'] ?? ''));
$nameHi = trim((string)($data['name_hi'] ?? ''));
$sortOrder = max(1, (int)($data['sort_order'] ?? 9999));
$navOrder = max(1, (int)($data['nav_order'] ?? $sortOrder));
$visible = isset($data['visible']) ? (int)!!$data['visible'] : 1;
$isActive = isset($data['is_active']) ? (int)!!$data['is_active'] : 1;

if ($categoryId <= 0) {
    json_response(false, ['message' => 'Category is required'], 422);
}

if ($nameEn === '') {
    json_response(false, ['message' => 'Name EN is required'], 422);
}

if ($namePh === '') {
    $namePh = $nameEn;
}

if ($nameHi === '') {
    $nameHi = $nameEn;
}

$pdo = db();

$checkStmt = $pdo->prepare("SELECT id, slug FROM categories WHERE id = ? LIMIT 1");
$checkStmt->execute([$categoryId]);
$current = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    json_response(false, ['message' => 'Category not found'], 404);
}

$updateStmt = $pdo->prepare("
    UPDATE categories
    SET
        display_name = :display_name,
        name_en = :name_en,
        name_ph = :name_ph,
        name_hi = :name_hi,
        sort_order = :sort_order,
        is_active = :is_active,
        visible = :visible,
        nav_order = :nav_order,
        updated_at = NOW()
    WHERE id = :id
    LIMIT 1
");

$updateStmt->execute([
    'display_name' => $nameEn,
    'name_en' => $nameEn,
    'name_ph' => $namePh,
    'name_hi' => $nameHi,
    'sort_order' => $sortOrder,
    'is_active' => $isActive,
    'visible' => $visible,
    'nav_order' => $navOrder,
    'id' => $categoryId,
]);

generate_categories_json();

admin_activity_log(
    'update_category',
    'categories',
    'category',
    $categoryId,
    'Updated category | slug: ' . (string)$current['slug'] . ' | name_en: ' . $nameEn
);

json_response(true, ['message' => 'تم تحديث الفئة بنجاح']);
