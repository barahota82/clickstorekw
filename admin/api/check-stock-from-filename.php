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

$devices = stock_catalog_limit_devices($devices, 4);

$pdo = db();

$brandsStmt = $pdo->query("
    SELECT id, category_id, name, slug
    FROM brands
    WHERE (is_active = 1 OR is_active IS NULL)
    ORDER BY LENGTH(name) DESC, id ASC
");
$brands = $brandsStmt ? $brandsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

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
    $expectedCategoryId = null;

    $lookupSource = normalize_filename_text($rawTitle . ' ' . $normalizedTitle);

    foreach ($brands as $brandRow) {
        $brandNameNormalized = normalize_filename_text((string)($brandRow['name'] ?? ''));
        $brandSlugNormalized = normalize_filename_text((string)($brandRow['slug'] ?? ''));

        if ($brandNameNormalized !== '' && str_starts_with($lookupSource, $brandNameNormalized . ' ')) {
            $brandGuess = (string)$brandRow['name'];
            $brandIdGuess = (int)$brandRow['id'];
            $expectedCategoryId = (int)$brandRow['category_id'];
            break;
        }

        if ($brandNameNormalized !== '' && $lookupSource === $brandNameNormalized) {
            $brandGuess = (string)$brandRow['name'];
            $brandIdGuess = (int)$brandRow['id'];
            $expectedCategoryId = (int)$brandRow['category_id'];
            break;
        }

        if ($brandSlugNormalized !== '' && str_starts_with($lookupSource, $brandSlugNormalized . ' ')) {
            $brandGuess = (string)$brandRow['name'];
            $brandIdGuess = (int)$brandRow['id'];
            $expectedCategoryId = (int)$brandRow['category_id'];
            break;
        }

        if ($brandSlugNormalized !== '' && $lookupSource === $brandSlugNormalized) {
            $brandGuess = (string)$brandRow['name'];
            $brandIdGuess = (int)$brandRow['id'];
            $expectedCategoryId = (int)$brandRow['category_id'];
            break;
        }
    }

    $existing = find_stock_catalog(
        $pdo,
        $normalizedTitle,
        $brandIdGuess > 0 ? $brandIdGuess : null,
        $expectedCategoryId,
        $storageValue,
        $ramValue,
        $networkValue
    );

    $row = [
        'device_index' => $deviceIndex,
        'raw_title' => $rawTitle,
        'normalized_title' => $normalizedTitle,
        'storage_value' => $storageValue,
        'ram_value' => $ramValue,
        'network_value' => $networkValue,
        'brand_guess' => $brandGuess,
        'expected_brand_name' => $brandGuess,
        'expected_brand_id' => $brandIdGuess > 0 ? $brandIdGuess : null,
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
            'expected_category_id' => $expectedCategoryId,
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
