<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('view_brand_ordering', 'ليس لديك صلاحية عرض البراندات.');

$categoryId = (int)($_GET['category_id'] ?? 0);

if ($categoryId <= 0) {
    json_response(true, ['brands' => []]);
}

$pdo = db();

$categoryStmt = $pdo->prepare("
    SELECT id, display_name, slug
    FROM categories
    WHERE id = ?
    LIMIT 1
");
$categoryStmt->execute([$categoryId]);
$category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    json_response(false, ['message' => 'Category not found'], 404);
}

$stmt = $pdo->prepare("
    SELECT
        id,
        category_id,
        name,
        display_name,
        slug,
        sort_order,
        is_active,
        created_at,
        updated_at
    FROM brands
    WHERE category_id = ?
    ORDER BY sort_order ASC, id ASC
");
$stmt->execute([$categoryId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$brands = array_map(static function (array $row): array {
    return [
        'id' => (int)$row['id'],
        'category_id' => (int)$row['category_id'],
        'name' => (string)($row['name'] ?? ''),
        'display_name' => (string)($row['display_name'] ?? $row['name'] ?? ''),
        'slug' => (string)($row['slug'] ?? ''),
        'sort_order' => (int)($row['sort_order'] ?? 9999),
        'is_active' => (bool)($row['is_active'] ?? true),
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
    ];
}, $rows);

json_response(true, [
    'category' => [
        'id' => (int)$category['id'],
        'display_name' => (string)$category['display_name'],
        'slug' => (string)$category['slug'],
    ],
    'brands' => $brands,
]);
