<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once dirname(__DIR__) . '/helpers/categories_sync.php';
require_once dirname(__DIR__) . '/helpers/products_sync.php';
require_once dirname(__DIR__) . '/helpers/product_storage_helper.php';
require_once dirname(__DIR__) . '/helpers/github_sync_helper.php';

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

github_sync_reset_report();

$pdo = db();

$checkStmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
$checkStmt->execute([$slug]);

if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
    json_response(false, ['message' => 'Category already exists'], 409);
}

try {
    $pdo->beginTransaction();

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

    $categoryPaths = product_storage_ensure_category_structure($slug);

    $pdo->commit();

    generate_categories_json();
    generate_products_json_for_category($categoryId);

    $gitkeepAbs = $categoryPaths['images_category_dir_abs'] . '/.gitkeep';
    if (!is_file($gitkeepAbs)) {
        file_put_contents($gitkeepAbs, "\n");
    }

    github_sync_upsert_local_file(
        '/images/' . $slug . '/.gitkeep',
        $gitkeepAbs,
        'Create category image folder placeholder: ' . $slug
    );

    $githubSyncReport = github_sync_get_report();

    if (function_exists('admin_activity_log')) {
        admin_activity_log(
            'add_category',
            'categories',
            'category',
            $categoryId,
            'Added category | slug: ' . $slug . ' | name_en: ' . $nameEn
        );
    }

    $responseMessage = 'تمت إضافة الفئة بنجاح';
    if (!empty($githubSyncReport['has_errors'])) {
        $responseMessage .= '، لكن بعض ملفات GitHub لم تتم مزامنتها';
    }

    json_response(true, [
        'message' => $responseMessage,
        'category' => [
            'id' => $categoryId,
            'slug' => $slug,
            'display_name' => $nameEn,
            'name_en' => $nameEn,
            'name_ph' => $namePh,
            'name_hi' => $nameHi,
            'sort_order' => $sortOrder,
            'visible' => (bool)$visible,
            'nav_order' => $navOrder,
            'image_category_dir' => $categoryPaths['images_category_dir_rel'],
            'products_category_dir' => $categoryPaths['products_category_dir_rel'],
            'products_category_data_json' => $categoryPaths['category_data_json_rel'],
        ],
        'github_sync' => $githubSyncReport,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to add category',
        'error' => $e->getMessage(),
    ], 500);
}
