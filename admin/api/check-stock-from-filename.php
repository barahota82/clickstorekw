<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

require_post();
require_admin_auth_json();
admin_require_permission_json('stock_manage', 'ليس لديك صلاحية لفحص المخزن');

$data = get_request_json();
$filename = trim((string)($data['filename'] ?? ''));

if ($filename === '') {
    json_response(false, ['message' => 'Filename is required'], 422);
}

$devices = parse_devices_from_filename($filename);

if (!$devices) {
    $baseName = pathinfo($filename, PATHINFO_FILENAME);

    $devices = [[
        'device_index' => 1,
        'raw_title' => $baseName,
        'normalized_title' => normalize_stock_title($baseName),
        'storage_value' => null,
        'ram_value' => null,
        'network_value' => null,
    ]];
}

$devices = array_slice($devices, 0, 4);

$pdo = db();

$linked = [];
$missing = [];
$allDevices = [];

foreach ($devices as $device) {
    $deviceIndex = (int)($device['device_index'] ?? 0);
    $rawTitle = trim((string)($device['raw_title'] ?? ''));
    $normalizedTitle = trim((string)($device['normalized_title'] ?? ''));

    if ($rawTitle === '' && $normalizedTitle === '') {
        continue;
    }

    if ($normalizedTitle === '') {
        $normalizedTitle = normalize_stock_title($rawTitle);
    }

    $storageValue = isset($device['storage_value']) && $device['storage_value'] !== ''
        ? (string)$device['storage_value']
        : null;

    $ramValue = isset($device['ram_value']) && $device['ram_value'] !== ''
        ? (string)$device['ram_value']
        : null;

    $networkValue = isset($device['network_value']) && $device['network_value'] !== ''
        ? (string)$device['network_value']
        : null;

    $brandGuess = '';
    $brandIdGuess = 0;

    $brandMap = [
        'samsung'  => 'Samsung',
        'apple'    => 'Apple',
        'iphone'   => 'Apple',
        'honor'    => 'Honor',
        'xiaomi'   => 'Xiaomi',
        'redmi'    => 'Redmi',
        'oppo'     => 'Oppo',
        'vivo'     => 'Vivo',
        'realme'   => 'Realme',
        'huawei'   => 'Huawei',
        'oneplus'  => 'OnePlus',
        'nokia'    => 'Nokia',
        'google'   => 'Google',
        'pixel'    => 'Google',
        'motorola' => 'Motorola',
        'tecno'    => 'Tecno',
        'infinix'  => 'Infinix',
        'lenovo'   => 'Lenovo',
        'asus'     => 'Asus',
        'acer'     => 'Acer',
        'hp'       => 'HP',
        'dell'     => 'Dell',
    ];

    $lookupSource = strtolower($rawTitle . ' ' . $normalizedTitle);

    foreach ($brandMap as $needle => $brandName) {
        if (str_contains($lookupSource, $needle)) {
            $brandGuess = $brandName;
            break;
        }
    }

    if ($brandGuess !== '') {
        $brandStmt = $pdo->prepare("
            SELECT id, category_id, name
            FROM brands
            WHERE LOWER(name) = LOWER(:name)
            ORDER BY id ASC
            LIMIT 1
        ");
        $brandStmt->execute(['name' => $brandGuess]);
        $brandRow = $brandStmt->fetch(PDO::FETCH_ASSOC);

        if ($brandRow) {
            $brandIdGuess = (int)$brandRow['id'];
        }
    }

    $stmt = $pdo->prepare("
        SELECT
            sc.id,
            sc.category_id,
            sc.brand_id,
            sc.title,
            sc.slug,
            sc.normalized_title,
            sc.storage_value,
            sc.ram_value,
            sc.network_value,
            c.display_name AS category_name,
            b.name AS brand_name
        FROM stock_catalog sc
        LEFT JOIN categories c ON c.id = sc.category_id
        LEFT JOIN brands b ON b.id = sc.brand_id
        WHERE sc.normalized_title = :normalized_title
        LIMIT 1
    ");
    $stmt->execute([
        'normalized_title' => $normalizedTitle
    ]);

    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $row = [
        'device_index' => $deviceIndex,
        'raw_title' => $rawTitle,
        'normalized_title' => $normalizedTitle,
        'storage_value' => $storageValue,
        'ram_value' => $ramValue,
        'network_value' => $networkValue,
        'brand_guess' => $brandGuess,
        'expected_brand_name' => $brandGuess,
        'expected_brand_id' => $brandIdGuess,
    ];

    if ($existing) {
        $linkedRow = array_merge($row, [
            'exists' => true,
            'stock_catalog_id' => (int)$existing['id'],
            'stock_title' => (string)$existing['title'],
            'category_id' => (int)$existing['category_id'],
            'category_name' => (string)($existing['category_name'] ?? ''),
            'brand_id' => (int)$existing['brand_id'],
            'brand_name' => (string)($existing['brand_name'] ?? ''),
        ]);

        $linked[] = $linkedRow;
        $allDevices[] = $linkedRow;
    } else {
        $missingRow = array_merge($row, [
            'exists' => false,
            'expected_category_id' => null,
        ]);

        $missing[] = $missingRow;
        $allDevices[] = $missingRow;
    }
}

json_response(true, [
    'filename' => $filename,
    'devices_count' => count($allDevices),
    'devices' => $allDevices,
    'linked' => $linked,
    'missing' => $missing
]);
