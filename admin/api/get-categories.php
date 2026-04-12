<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('view_brand_ordering', 'ليس لديك صلاحية عرض الفئات والبراندات.');

$pdo = db();

$stmt = $pdo->query("
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
    ORDER BY nav_order ASC, sort_order ASC, id ASC
");

$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$categories = array_map(static function (array $row): array {
    return [
        'id' => (int)$row['id'],
        'name' => (string)($row['name'] ?? ''),
        'slug' => (string)($row['slug'] ?? ''),
        'display_name' => (string)($row['display_name'] ?? $row['name_en'] ?? ''),
        'name_en' => (string)($row['name_en'] ?? $row['display_name'] ?? ''),
        'name_ph' => (string)($row['name_ph'] ?? $row['display_name'] ?? ''),
        'name_hi' => (string)($row['name_hi'] ?? $row['display_name'] ?? ''),
        'sort_order' => (int)($row['sort_order'] ?? 9999),
        'is_active' => (bool)($row['is_active'] ?? true),
        'visible' => (bool)($row['visible'] ?? true),
        'nav_order' => (int)($row['nav_order'] ?? 9999),
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
    ];
}, $rows);

json_response(true, ['categories' => $categories]);
