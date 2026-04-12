<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

if (!function_exists('admin_brand_slugify')) {
    function admin_brand_slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = preg_replace('/-+/', '-', (string)$value);
        return trim((string)$value, '-');
    }
}

require_method('POST');
require_admin_auth_json();
admin_require_permission_json('manage_brand_ordering', 'ليس لديك صلاحية إضافة براند جديد.');

$data = get_request_json();

$categoryId = (int)($data['category_id'] ?? 0);
$name = trim((string)($data['name'] ?? ''));
$displayName = trim((string)($data['display_name'] ?? ''));
$sortOrder = max(1, (int)($data['sort_order'] ?? 9999));
$isActive = isset($data['is_active']) ? (int)!!$data['is_active'] : 1;

if ($categoryId <= 0) {
    json_response(false, ['message' => 'Category is required'], 422);
}

if ($name === '') {
    json_response(false, ['message' => 'Brand name is required'], 422);
}

if ($displayName === '') {
    $displayName = $name;
}

$slug = admin_brand_slugify($name);
if ($slug === '') {
    json_response(false, ['message' => 'Invalid brand slug'], 422);
}

$pdo = db();

$categoryStmt = $pdo->prepare("SELECT id, display_name FROM categories WHERE id = ? LIMIT 1");
$categoryStmt->execute([$categoryId]);
$category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    json_response(false, ['message' => 'Category not found'], 404);
}

$checkStmt = $pdo->prepare("
    SELECT id
    FROM brands
    WHERE category_id = ?
      AND slug = ?
    LIMIT 1
");
$checkStmt->execute([$categoryId, $slug]);
if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
    json_response(false, ['message' => 'Brand already exists inside this category'], 409);
}

$insertStmt = $pdo->prepare("
    INSERT INTO brands
    (
        category_id,
        name,
        display_name,
        slug,
        sort_order,
        is_active,
        created_at,
        updated_at
    )
    VALUES
    (
        :category_id,
        :name,
        :display_name,
        :slug,
        :sort_order,
        :is_active,
        NOW(),
        NOW()
    )
");

$insertStmt->execute([
    'category_id' => $categoryId,
    'name' => $name,
    'display_name' => $displayName,
    'slug' => $slug,
    'sort_order' => $sortOrder,
    'is_active' => $isActive,
]);

$brandId = (int)$pdo->lastInsertId();

admin_activity_log(
    'add_brand',
    'brands',
    'brand',
    $brandId,
    'Added brand | category_id: ' . $categoryId . ' | name: ' . $name
);

json_response(true, [
    'message' => 'تمت إضافة البراند بنجاح',
    'brand' => [
        'id' => $brandId,
        'category_id' => $categoryId,
        'name' => $name,
        'display_name' => $displayName,
        'slug' => $slug,
        'sort_order' => $sortOrder,
        'is_active' => (bool)$isActive,
    ],
]);
