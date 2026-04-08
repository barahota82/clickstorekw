<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

require_post();
require_admin_auth_json();
admin_require_permission_json('stock_manage', 'ليس لديك صلاحية لفحص المخزن');

if (!function_exists('stock_review_extract_brand_token')) {
    function stock_review_extract_brand_token(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $value) ?: [];
        return trim((string)($parts[0] ?? ''));
    }
}

if (!function_exists('stock_review_normalize_brand_compare')) {
    function stock_review_normalize_brand_compare(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['_', '.'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', (string)$value);
        return trim((string)$value);
    }
}

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
    ORDER BY id ASC
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

    /*
    |--------------------------------------------------------------------------
    | Brand Extraction Rule
    |--------------------------------------------------------------------------
    | اسم البراند = أول كلمة قبل أول مسافة
    | سواء كانت تحتوي - أو لا
    | مثال:
    | m-horse hero ... => brand = m-horse
    | s-color ultra ... => brand = s-color
    |--------------------------------------------------------------------------
    */
    $brandToken = stock_review_extract_brand_token($rawTitle);
    $brandTokenNormalized = stock_review_normalize_brand_compare($brandToken);

    if ($brandTokenNormalized !== '') {
        foreach ($brands as $brandRow) {
            $dbBrandName = trim((string)($brandRow['name'] ?? ''));
            $dbBrandSlug = trim((string)($brandRow['slug'] ?? ''));

            $dbBrandNameNormalized = stock_review_normalize_brand_compare($dbBrandName);
            $dbBrandSlugNormalized = stock_review_normalize_brand_compare($dbBrandSlug);

            if (
                $brandTokenNormalized === $dbBrandNameNormalized ||
                $brandTokenNormalized === $dbBrandSlugNormalized
            ) {
                $brandGuess = $dbBrandName;
                $brandIdGuess = (int)($brandRow['id'] ?? 0);
                $expectedCategoryId = (int)($brandRow['category_id'] ?? 0);
                break;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | لو البراند غير موجود في قاعدة البيانات
    |--------------------------------------------------------------------------
    | نظل نعرض أول كلمة كما هي كـ brand guess
    | حتى لو كان براند جديد أول مرة
    |--------------------------------------------------------------------------
    */
    if ($brandGuess === '' && $brandToken !== '') {
        $brandGuess = $brandToken;
    }

    $existing = null;

    if ($brandIdGuess > 0 && $expectedCategoryId !== null) {
        $existing = find_stock_catalog(
            $pdo,
            $normalizedTitle,
            $brandIdGuess,
            $expectedCategoryId,
            $storageValue,
            $ramValue,
            $networkValue
        );
    }

    if (!$existing && $brandIdGuess > 0) {
        $existing = find_stock_catalog(
            $pdo,
            $normalizedTitle,
            $brandIdGuess,
            null,
            $storageValue,
            $ramValue,
            $networkValue
        );
    }

    if (!$existing) {
        $existing = find_stock_catalog(
            $pdo,
            $normalizedTitle,
            null,
            null,
            $storageValue,
            $ramValue,
            $networkValue
        );
    }

    if ($existing && $expectedCategoryId === null && isset($existing['category_id'])) {
        $expectedCategoryId = (int)$existing['category_id'];
    }

    $row = [
        'device_index' => $deviceIndex,
        'raw_title' => $rawTitle,
        'normalized_title' => $normalizedTitle,
        'storage_value' => $storageValue,
        'ram_value' => $ramValue,
        'network_value' => $networkValue,
        'brand_guess' => $brandGuess,
        'expected_brand_name' => $brandGuess !== '' ? $brandGuess : null,
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
