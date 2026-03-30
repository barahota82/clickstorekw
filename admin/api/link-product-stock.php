<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';

function link_product_to_stock(PDO $pdo, int $productId, int $brandId, int $categoryId, string $originalFilename): void
{
    $devices = parse_devices_from_filename($originalFilename);

    if (!$devices) {
        $devices = [[
            'device_index' => 1,
            'raw_title' => pathinfo($originalFilename, PATHINFO_FILENAME),
            'normalized_title' => normalize_stock_title(pathinfo($originalFilename, PATHINFO_FILENAME)),
            'storage_value' => null,
            'ram_value' => null,
            'network_value' => null,
        ]];
    }

    foreach ($devices as $device) {
        $stockId = find_or_create_stock_catalog(
            $pdo,
            $brandId,
            $categoryId,
            (string)$device['raw_title'],
            (string)$device['normalized_title'],
            $device['storage_value'],
            $device['ram_value'],
            $device['network_value']
        );

        $insert = $pdo->prepare("
            INSERT INTO product_stock_links (
                product_id,
                stock_catalog_id,
                device_index,
                source_type,
                extracted_name,
                created_at
            ) VALUES (
                :product_id,
                :stock_catalog_id,
                :device_index,
                'filename',
                :extracted_name,
                NOW()
            )
        ");

        $insert->execute([
            'product_id' => $productId,
            'stock_catalog_id' => $stockId,
            'device_index' => (int)$device['device_index'],
            'extracted_name' => (string)$device['raw_title'],
        ]);
    }
}
