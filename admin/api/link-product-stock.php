<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';

function link_product_to_stock(PDO $pdo, int $productId, int $brandId, int $categoryId, string $originalFilename): array
{
    $devices = parse_devices_from_filename($originalFilename);

    if (!$devices) {
        $fallbackTitle = pathinfo($originalFilename, PATHINFO_FILENAME);

        $devices = [[
            'device_index' => 1,
            'raw_title' => $fallbackTitle,
            'normalized_title' => normalize_stock_title($fallbackTitle),
            'storage_value' => null,
            'ram_value' => null,
            'network_value' => null,
        ]];
    }

    $devices = stock_catalog_limit_devices($devices, 4);

    $deleteExisting = $pdo->prepare("
        DELETE FROM product_stock_links
        WHERE product_id = :product_id
    ");
    $deleteExisting->execute([
        'product_id' => $productId,
    ]);

    $linked = [];
    $missing = [];

    foreach ($devices as $device) {
        $deviceIndex = (int)($device['device_index'] ?? 0);
        $rawTitle = trim((string)($device['raw_title'] ?? ''));
        $normalizedTitle = normalize_stock_title((string)($device['normalized_title'] ?? $rawTitle));
        $storageValue = isset($device['storage_value']) && $device['storage_value'] !== null && $device['storage_value'] !== ''
            ? (string)$device['storage_value']
            : null;
        $ramValue = isset($device['ram_value']) && $device['ram_value'] !== null && $device['ram_value'] !== ''
            ? (string)$device['ram_value']
            : null;
        $networkValue = isset($device['network_value']) && $device['network_value'] !== null && $device['network_value'] !== ''
            ? (string)$device['network_value']
            : null;

        if ($deviceIndex <= 0) {
            $deviceIndex = count($linked) + count($missing) + 1;
        }

        $stockRow = find_stock_catalog(
            $pdo,
            $normalizedTitle,
            $brandId > 0 ? $brandId : null,
            $categoryId > 0 ? $categoryId : null,
            $storageValue,
            $ramValue,
            $networkValue
        );

        if ($stockRow) {
            $checkExistingLink = $pdo->prepare("
                SELECT id
                FROM product_stock_links
                WHERE product_id = :product_id
                  AND stock_catalog_id = :stock_catalog_id
                  AND device_index = :device_index
                LIMIT 1
            ");
            $checkExistingLink->execute([
                'product_id' => $productId,
                'stock_catalog_id' => (int)$stockRow['id'],
                'device_index' => $deviceIndex,
            ]);

            if (!$checkExistingLink->fetch(PDO::FETCH_ASSOC)) {
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
                    'stock_catalog_id' => (int)$stockRow['id'],
                    'device_index' => $deviceIndex,
                    'extracted_name' => $rawTitle,
                ]);
            }

            $linked[] = [
                'device_index' => $deviceIndex,
                'raw_title' => $rawTitle,
                'normalized_title' => $normalizedTitle,
                'storage_value' => $storageValue,
                'ram_value' => $ramValue,
                'network_value' => $networkValue,
                'stock_catalog_id' => (int)$stockRow['id'],
                'stock_title' => (string)($stockRow['title'] ?? ''),
                'category_id' => (int)($stockRow['category_id'] ?? 0),
                'brand_id' => (int)($stockRow['brand_id'] ?? 0),
                'is_added' => true,
            ];
        } else {
            $missing[] = [
                'device_index' => $deviceIndex,
                'raw_title' => $rawTitle,
                'normalized_title' => $normalizedTitle,
                'storage_value' => $storageValue,
                'ram_value' => $ramValue,
                'network_value' => $networkValue,
                'expected_brand_id' => $brandId > 0 ? $brandId : null,
                'expected_category_id' => $categoryId > 0 ? $categoryId : null,
                'is_added' => false,
            ];
        }
    }

    return [
        'linked' => $linked,
        'missing' => $missing,
        'devices_count' => count($devices),
    ];
}
