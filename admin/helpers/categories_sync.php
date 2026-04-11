<?php
require_once __DIR__ . '/../../config.php';

function generate_categories_json()
{
    $pdo = db();

    $stmt = $pdo->query("
        SELECT 
            slug,
            visible,
            nav_order,
            name_en,
            name_ph,
            name_hi,
            display_name
        FROM categories
        WHERE is_active = 1
        ORDER BY nav_order ASC, id ASC
    ");

    $data = [];

    foreach ($stmt->fetchAll() as $row) {
        $data[] = [
            'slug' => $row['slug'],
            'visible' => (bool)$row['visible'],
            'nav_order' => (int)$row['nav_order'],
            'names' => [
                'en' => $row['name_en'] ?: $row['display_name'],
                'ph' => $row['name_ph'] ?: $row['display_name'],
                'hi' => $row['name_hi'] ?: $row['display_name'],
            ]
        ];
    }

    file_put_contents(
        __DIR__ . '/../../data/categories.json',
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}
