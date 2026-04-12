<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/github_sync_helper.php';

function generate_categories_json(): void
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
            'slug' => (string)$row['slug'],
            'visible' => (bool)$row['visible'],
            'nav_order' => (int)$row['nav_order'],
            'names' => [
                'en' => (string)($row['name_en'] ?: $row['display_name']),
                'ph' => (string)($row['name_ph'] ?: $row['display_name']),
                'hi' => (string)($row['name_hi'] ?: $row['display_name']),
            ]
        ];
    }

    $target = __DIR__ . '/../../data/categories.json';
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($encoded === false) {
        throw new RuntimeException('Failed to encode categories.json');
    }

    if (file_put_contents($target, $encoded) === false) {
        throw new RuntimeException('Failed to write categories.json');
    }

    github_sync_upsert_local_file(
        '/data/categories.json',
        $target,
        'Sync categories.json'
    );
}
