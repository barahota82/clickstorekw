<?php
declare(strict_types=1);

require_once __DIR__ . '/product_storage_helper.php';

if (!function_exists('products_sync_slugify')) {
    function products_sync_slugify(string $value): string
    {
        return product_storage_slugify($value);
    }
}

if (!function_exists('products_sync_ensure_dir')) {
    function products_sync_ensure_dir(string $dir): void
    {
        product_storage_ensure_dir($dir);
    }
}

if (!function_exists('products_sync_build_item')) {
    function products_sync_build_item(array $row, string $categorySlug): array
    {
        $slug = trim((string)($row['slug'] ?? ''));

        if ($slug === '') {
            $slug = products_sync_slugify((string)($row['title'] ?? ''));
        }

        return [
            'slug' => $slug,
            'title' => (string)($row['title'] ?? ''),
            'category' => $categorySlug,
            'brand' => (string)($row['brand_name'] ?? ''),
            'devices_count' => (int)($row['devices_count'] ?? 1),
            'image' => (string)($row['image_path'] ?? ''),
            'down_payment' => (float)($row['down_payment'] ?? 0),
            'monthly' => (float)($row['monthly_amount'] ?? 0),
            'duration' => (int)($row['duration_months'] ?? 0),
            'available' => (bool)($row['is_available'] ?? false),
            'hot_offer' => (bool)($row['is_hot_offer'] ?? false),
        ];
    }
}

if (!function_exists('generate_products_json_for_category')) {
    function generate_products_json_for_category(int $categoryId): void
    {
        $pdo = db();

        $categoryStmt = $pdo->prepare("
            SELECT id, slug
            FROM categories
            WHERE id = ?
            LIMIT 1
        ");
        $categoryStmt->execute([$categoryId]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            return;
        }

        $categorySlug = products_sync_slugify((string)$category['slug']);
        if ($categorySlug === '') {
            return;
        }

        $categoryPaths = product_storage_ensure_category_structure($categorySlug);

        $productsStmt = $pdo->prepare("
            SELECT
                p.id,
                p.title,
                p.slug,
                p.devices_count,
                p.image_path,
                p.down_payment,
                p.monthly_amount,
                p.duration_months,
                p.is_available,
                p.is_hot_offer,
                b.name AS brand_name
            FROM products p
            INNER JOIN brands b ON b.id = p.brand_id
            WHERE p.category_id = ?
              AND p.is_active = 1
            ORDER BY p.product_order ASC, p.id DESC
        ");
        $productsStmt->execute([$categoryId]);
        $rows = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = products_sync_build_item($row, $categorySlug);
        }

        product_storage_write_json_file($categoryPaths['category_data_json_abs'], $items);
    }
}
