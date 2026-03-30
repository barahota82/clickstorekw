<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/filename_parser.php';
require_once __DIR__ . '/../helpers/stock_helper.php';

function link_product_to_stock($product_id, $filename) {

    $devices = extract_devices_from_filename($filename);

    $pdo = db();

    $index = 1;

    foreach ($devices as $device) {

        $stock_id = find_or_create_stock($device);

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO product_stock_links
            (product_id, stock_catalog_id, device_index, source_type, extracted_name)
            VALUES (?, ?, ?, 'filename', ?)
        ");

        $stmt->execute([
            $product_id,
            $stock_id,
            $index,
            $device['title']
        ]);

        $index++;
    }
}
