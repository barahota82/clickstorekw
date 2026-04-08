<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';

require_post();
require_admin_auth_json();
admin_require_permission_json('stock_manage', 'ليس لديك صلاحية لفحص المخزن');

if (!function_exists('stock_review_normalize_brand_text')) {
    function stock_review_normalize_brand_text(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['_', '-', '.'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', (string)$value);
        return trim((string)$value);
    }
}

if (!function_exists('stock_review_brand_match_score')) {
    function stock_review_brand_match_score(string $lookupSource, string $brandNameNormalized, string $brandSlugNormalized, int $brandCategoryId, int $selectedCategoryId): int
    {
        $score = 0;

        if ($brandNameNormalized !== '') {
            if ($lookupSource === $brandNameNormalized) {
                $score += 3000 + strlen($brandNameNormalized);
            } elseif (str_starts_with($lookupSource, $brandNameNormalized . ' ')) {
                $score += 2000 + strlen($brandNameNormalized);
            } elseif (str_contains($lookupSource, ' ' . $brandNameNormalized . ' ')) {
                $score += 1000 + strlen($brandNameNormalized);
            }
        }

        if ($brandSlugNormalized !== '') {
            if ($lookupSource === $brandSlugNormalized) {
                $score = max($score, 2900 + strlen($brandSlugNormalized));
            } elseif (str_starts_with($lookupSource, $brandSlugNormalized . ' ')) {
                $score = max($score, 1900 + strlen($brandSlugNormalized));
            } elseif (str_contains($lookupSource, ' ' . $brandSlugNormalized . ' ')) {
                $score = max($score, 900 + strlen($brandSlugNormalized));
            }
        }

        if ($score > 0 && $selectedCategoryId > 0 && $brandCategoryId === $selectedCategoryId) {
            $score += 5000;
        }

        return $score;
    }
}

$data = get_request_json();
$filename = trim((string)($data['filename'] ?? ''));
$selectedCategoryId = (int)($data['category_id'] ?? 0);

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

    $lookupSource = stock_review_normalize_brand_text($rawTitle . ' ' . $normalizedTitle);

    $bestBrandRow = null;
    $bestScore = 0;

    foreach ($brands as $brandRow) {
        $brandName = trim((string)($brandRow['name'] ?? ''));
        $brandSlug = trim((string)($brandRow['slug'] ?? ''));

        $brandNameNormalized = stock_review_normalize_brand_text($brandName);
        $brandSlugNormalized = stock_review_normalize_brand_text($brandSlug);
        $brandCategoryId = (int)($brandRow['category_id'] ?? 0);

        $score = stock_review_brand_match_score(
            $lookupSource,
            $brandNameNormalized,
            $brandSlugNormalized,
            $brandCategoryId,
            $selectedCategoryId
        );

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestBrandRow = $brandRow;
        }
    }

    if ($bestBrandRow) {
        $brandGuess = (string)($bestBrandRow['name'] ?? '');
        $brandIdGuess = (int)($bestBrandRow['id'] ?? 0);
        $expectedCategoryId = (int)($bestBrandRow['category_id'] ?? 0);
    }

    if ($selectedCategoryId > 0) {
        $expectedCategoryId = $selectedCategoryId;
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

    if (!$existing) {
        $existing = find_stock_catalog(
            $pdo,
            $normalizedTitle,
            $brandIdGuess > 0 ? $brandIdGuess : null,
            null,
            $storageValue,
            $ramValue,
            $networkValue
        );
    }

    if (!$existing && $selectedCategoryId > 0) {
        $existing = find_stock_catalog(
            $pdo,
            $normalizedTitle,
            null,
            $selectedCategoryId,
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
