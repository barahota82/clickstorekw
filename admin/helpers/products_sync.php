<?php
declare(strict_types=1);

function generate_products_json_for_category(int $categoryId): void
{
    $pdo = db();

    // 🔹 Get category
    $catStmt = $pdo->prepare("
        SELECT id, slug
        FROM categories
        WHERE id = ?
        LIMIT 1
    ");
    $catStmt->execute([$categoryId]);
    $category = $catStmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        return;
    }

    $categorySlug = strtolower(trim((string)$category['slug']));

    // 🔹 Get products (ACTIVE ONLY)
    $stmt = $pdo->prepare("
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
    $stmt->execute([$categoryId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products = [];

    foreach ($rows as $row) {

        $products[] = [
            'slug' => (string)$row['slug'],
            'title' => (string)$row['title'],
            'category' => $categorySlug,
            'brand' => (string)$row['brand_name'],
            'devices_count' => (int)$row['devices_count'],
            'image' => (string)$row['image_path'],
            'down_payment' => (float)$row['down_payment'],
            'monthly' => (float)$row['monthly_amount'],
            'duration' => (int)$row['duration_months'],
            'available' => (bool)$row['is_available'],
            'hot_offer' => (bool)$row['is_hot_offer']
        ];
    }

    // 🔹 Path
    $filePath = dirname(__DIR__, 2) . '/products/' . $categorySlug . '/data.json';

    if (!is_dir(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }

    file_put_contents(
        $filePath,
        json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    );
}
