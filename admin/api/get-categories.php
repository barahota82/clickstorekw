<?php
require_once '../../config.php';
require_admin_auth_json();

$pdo = db();

$stmt = $pdo->query("
    SELECT 
        id,
        slug,
        visible,
        nav_order,
        name_en,
        name_ph,
        name_hi,
        display_name
    FROM categories
    ORDER BY nav_order ASC, id ASC
");

$rows = $stmt->fetchAll();

$data = [];

foreach ($rows as $row) {
    $data[] = [
        'id' => (int)$row['id'],
        'slug' => $row['slug'],
        'visible' => (bool)$row['visible'],
        'nav_order' => (int)$row['nav_order'],
        'name_en' => $row['name_en'] ?: $row['display_name'],
        'name_ph' => $row['name_ph'] ?: $row['display_name'],
        'name_hi' => $row['name_hi'] ?: $row['display_name']
    ];
}

json_response(true, ['categories' => $data]);
