<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once dirname(__DIR__) . '/helpers/categories_sync.php';

if (!function_exists('admin_category_slugify')) {
    function admin_category_slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = preg_replace('/-+/', '-', (string)$value);
        return trim((string)$value, '-');
    }
}

require_method('POST');
require_admin_auth_json();
admin_require_permission_json('manage_brand_ordering', 'ليس لديك صلاحية إضافة فئة جديدة.');

$data = get_request_json();

$nameEn = trim((string)($data['name_en'] ?? ''));
$namePh = trim((string)($data['name_ph'] ?? ''));
$nameHi = trim((string)($data['name_hi'] ?? ''));
$sortOrder = max(1, (int)($data['sort_order'] ?? 9999));
$navOrder = max(1, (int)($data['nav_order'] ?? $sortOrder));
$visible = isset($data['visible']) ? (int)!!$data['visible'] : 1;
$isActive = isset($data['is_active']) ? (int)!!$data['is_active'] : 1;

if ($nameEn === '') {
    json_response(false, ['message' => 'Name EN is required'], 422);
}

$slug = admin_category_slugify($nameEn);
if ($slug === '') {
    json_response(false, ['message' => 'Invalid category slug'], 422);
}

if ($namePh === '') {
    $namePh = $nameEn;
}

if ($nameHi === '') {
    $nameHi = $nameEn;
}

$pdo = db();

$checkStmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
$checkStmt->execute([$slug]);
if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
    json_response(false, ['message' => 'Category already exists'], 409);
}

$insertStmt = $pdo->prepare("
    INSERT INTO categories
    (
        name,
        slug,
        display_name,
        name_en,
        name_ph,
        name_hi,
        sort_order,
        is_active,
        visible,
        nav_order,
        created_at,
        updated_at
    )
    VALUES
    (
        :name,
        :slug,
        :display_name,
        :name_en,
        :name_ph,
        :name_hi,
        :sort_order,
        :is_active,
        :visible,
        :nav_order,
        NOW(),
        NOW()
    )
");

$insertStmt->execute([
    'name' => $slug,
    'slug' => $slug,
    'display_name' => $nameEn,
    'name_en' => $nameEn,
    'name_ph' => $namePh,
    'name_hi' => $nameHi,
    'sort_order' => $sortOrder,
    'is_active' => $isActive,
    'visible' => $visible,
    'nav_order' => $navOrder,
]);

$categoryId = (int)$pdo->lastInsertId();

generate_categories_json();

admin_activity_log(
    'add_category',
    'categories',
    'category',
    $categoryId,
    'Added category | slug: ' . $slug . ' | name_en: ' . $nameEn
);

$selectStmt = $pdo->prepare("
    SELECT
        id,
        name,
        slug,
        display_name,
        name_en,
        name_ph,
        name_hi,
        sort_order,
        is_active,
        visible,
        nav_order,
        created_at,
        updated_at
    FROM categories
    WHERE id = ?
    LIMIT 1
");
$selectStmt->execute([$categoryId]);
$row = $selectStmt->fetch(PDO::FETCH_ASSOC) ?: [];

json_response(true, [
    'message' => 'تمت إضافة الفئة بنجاح',
    'category' => [
        'id' => (int)($row['id'] ?? $categoryId),
        'name' => (string)($row['name'] ?? $slug),
        'slug' => (string)($row['slug'] ?? $slug),
        'display_name' => (string)($row['display_name'] ?? $nameEn),
        'name_en' => (string)($row['name_en'] ?? $nameEn),
        'name_ph' => (string)($row['name_ph'] ?? $namePh),
        'name_hi' => (string)($row['name_hi'] ?? $nameHi),
        'sort_order' => (int)($row['sort_order'] ?? $sortOrder),
        'is_active' => (bool)($row['is_active'] ?? $isActive),
        'visible' => (bool)($row['visible'] ?? $visible),
        'nav_order' => (int)($row['nav_order'] ?? $navOrder),
    ],
]);
