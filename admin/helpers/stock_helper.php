<?php

require_once __DIR__ . '/../config.php';

function find_or_create_stock($device) {
    $pdo = db();

    // هل موجود؟
    $stmt = $pdo->prepare("SELECT id FROM stock_catalog WHERE normalized_title = ?");
    $stmt->execute([$device['normalized']]);
    $found = $stmt->fetch();

    if ($found) {
        return $found['id'];
    }

    // إضافة جديد
    $stmt = $pdo->prepare("
        INSERT INTO stock_catalog 
        (title, slug, normalized_title, storage_value, ram_value, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $slug = str_replace(' ', '-', $device['normalized']);

    $stmt->execute([
        $device['title'],
        $slug,
        $device['normalized'],
        $device['storage'],
        $device['ram']
    ]);

    return $pdo->lastInsertId();
}
