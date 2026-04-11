<?php
require_once __DIR__ . '/../../config.php';

function generate_products_json_for_category(int $category_id): void
{
    $pdo = db();

    // اسم الكاتيجوري (slug)
    $catStmt = $pdo->prepare("SELECT slug FROM categories WHERE id = ? LIMIT 1");
    $catStmt->execute([$category_id]);
    $cat = $catStmt->fetch();

    if (!$cat) return;

    $category_slug = $cat['slug'];

    // كل المنتجات الفعّالة داخل الكاتيجوري
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
            p.product_order,
            b.name AS brand_name
        FROM products p
        INNER JOIN brands b ON b.id = p.brand_id
        WHERE p.category_id = ?
          AND p.is_active = 1
        ORDER BY p.product_order ASC, p.id ASC
    ");
    $stmt->execute([$category_id]);

    $rows = $stmt->fetchAll();

    $data = [];

    foreach ($rows as $row) {
        $data[] = [
            'slug' => $row['slug'],
            'title' => $row['title'],
            'category' => $category_slug,
            'brand' => $row['brand_name'],
            'devices_count' => (int)$row['devices_count'],
            'image' => $row['image_path'],
            'down_payment' => (float)$row['down_payment'],
            'monthly' => (float)$row['monthly_amount'],
            'duration' => (int)$row['duration_months'],
            'available' => (bool)$row['is_available'],
            'hot_offer' => (bool)$row['is_hot_offer'],
            'product_order' => (int)$row['product_order']
        ];
    }

    $dir = __DIR__ . "/../../products/{$category_slug}";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents(
        "{$dir}/data.json",
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}
