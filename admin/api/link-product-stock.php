<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';

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

if (!function_exists('stock_review_load_active_brands')) {
    function stock_review_load_active_brands(PDO $pdo): array
    {
        static $cache = [];

        $key = spl_object_hash($pdo);
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $stmt = $pdo->query("
            SELECT id, category_id, name, slug
            FROM brands
            WHERE (is_active = 1 OR is_active IS NULL)
            ORDER BY id ASC
        ");

        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $cache[$key] = is_array($rows) ? $rows : [];

        return $cache[$key];
    }
}

if (!function_exists('stock_review_match_brand_candidates')) {
    function stock_review_match_brand_candidates(array $brands, string $brandTokenNormalized): array
    {
        if ($brandTokenNormalized === '') {
            return [];
        }

        $matches = [];

        foreach ($brands as $brandRow) {
            $dbBrandName = trim((string)($brandRow['name'] ?? ''));
            $dbBrandSlug = trim((string)($brandRow['slug'] ?? ''));

            $dbBrandNameNormalized = stock_review_normalize_brand_compare($dbBrandName);
            $dbBrandSlugNormalized = stock_review_normalize_brand_compare($dbBrandSlug);

            if (
                $brandTokenNormalized === $dbBrandNameNormalized ||
                $brandTokenNormalized === $dbBrandSlugNormalized
            ) {
                $matches[] = $brandRow;
            }
        }

        return $matches;
    }
}

if (!function_exists('stock_review_try_existing_from_candidates')) {
    function stock_review_try_existing_from_candidates(
        PDO $pdo,
        array $candidates,
        string $normalizedTitle,
        ?string $storageValue,
        ?string $ramValue,
        ?string $networkValue
    ): array {
        foreach ($candidates as $candidate) {
            $candidateBrandId = (int)($candidate['id'] ?? 0);
            $candidateCategoryId = (int)($candidate['category_id'] ?? 0);

            if ($candidateBrandId <= 0) {
                continue;
            }

            $existing = find_stock_catalog(
                $pdo,
                $normalizedTitle,
                $candidateBrandId,
                $candidateCategoryId > 0 ? $candidateCategoryId : null,
                $storageValue,
                $ramValue,
                $networkValue
            );

            if ($existing) {
                return [
                    'candidate' => $candidate,
                    'existing' => $existing,
                ];
            }
        }

        foreach ($candidates as $candidate) {
            $candidateBrandId = (int)($candidate['id'] ?? 0);
            if ($candidateBrandId <= 0) {
                continue;
            }

            $existing = find_stock_catalog(
                $pdo,
                $normalizedTitle,
                $candidateBrandId,
                null,
                $storageValue,
                $ramValue,
                $networkValue
            );

            if ($existing) {
                return [
                    'candidate' => $candidate,
                    'existing' => $existing,
                ];
            }
        }

        return [
            'candidate' => null,
            'existing' => null,
        ];
    }
}

if (!function_exists('stock_review_resolve_device_context')) {
    function stock_review_resolve_device_context(
        PDO $pdo,
        array $brands,
        string $rawTitle,
        string $normalizedTitle,
        ?string $storageValue = null,
        ?string $ramValue = null,
        ?string $networkValue = null,
        ?int $preferredBrandId = null,
        ?int $preferredCategoryId = null,
        bool $allowPreferredForThisDevice = false
    ): array {
        $brandToken = stock_review_extract_brand_token($rawTitle);
        $brandTokenNormalized = stock_review_normalize_brand_compare($brandToken);

        $brandGuess = '';
        $brandIdGuess = 0;
        $expectedCategoryId = null;
        $pickedCandidate = null;
        $existing = null;

        $candidates = stock_review_match_brand_candidates($brands, $brandTokenNormalized);

        if ($allowPreferredForThisDevice && !empty($candidates) && $preferredBrandId !== null && $preferredBrandId > 0) {
            foreach ($candidates as $candidate) {
                if ((int)($candidate['id'] ?? 0) === $preferredBrandId) {
                    $pickedCandidate = $candidate;
                    break;
                }
            }
        }

        if ($pickedCandidate === null && $allowPreferredForThisDevice && !empty($candidates) && $preferredCategoryId !== null && $preferredCategoryId > 0) {
            foreach ($candidates as $candidate) {
                if ((int)($candidate['category_id'] ?? 0) === $preferredCategoryId) {
                    $pickedCandidate = $candidate;
                    break;
                }
            }
        }

        if ($pickedCandidate === null && !empty($candidates)) {
            $existingMatch = stock_review_try_existing_from_candidates(
                $pdo,
                $candidates,
                $normalizedTitle,
                $storageValue,
                $ramValue,
                $networkValue
            );

            if (is_array($existingMatch['candidate'] ?? null)) {
                $pickedCandidate = $existingMatch['candidate'];
            }

            if (is_array($existingMatch['existing'] ?? null)) {
                $existing = $existingMatch['existing'];
            }
        }

        if ($pickedCandidate === null && !empty($candidates)) {
            $pickedCandidate = $candidates[0];
        }

        if ($pickedCandidate !== null) {
            $brandGuess = (string)($pickedCandidate['name'] ?? '');
            $brandIdGuess = (int)($pickedCandidate['id'] ?? 0);
            $expectedCategoryId = (int)($pickedCandidate['category_id'] ?? 0) ?: null;
        } elseif ($brandToken !== '') {
            $brandGuess = $brandToken;
        }

        if ($existing === null && $brandIdGuess > 0) {
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

        if ($existing === null && $brandIdGuess > 0) {
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

        if ($existing === null) {
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

        if ($existing) {
            if ($brandGuess === '') {
                $brandGuess = (string)($existing['brand_name'] ?? '');
            }

            if ($brandIdGuess <= 0) {
                $brandIdGuess = (int)($existing['brand_id'] ?? 0);
            }

            if ($expectedCategoryId === null && isset($existing['category_id'])) {
                $expectedCategoryId = (int)$existing['category_id'];
            }
        }

        return [
            'brand_guess' => $brandGuess !== '' ? $brandGuess : null,
            'expected_brand_name' => $brandGuess !== '' ? $brandGuess : null,
            'expected_brand_id' => $brandIdGuess > 0 ? $brandIdGuess : null,
            'expected_category_id' => $expectedCategoryId,
            'existing' => $existing,
        ];
    }
}

if (!function_exists('review_stock_from_filename')) {
    function review_stock_from_filename(
        PDO $pdo,
        string $filename,
        ?int $preferredBrandId = null,
        ?int $preferredCategoryId = null
    ): array {
        $devices = parse_devices_from_filename($filename);

        if (!$devices) {
            $fallbackTitle = pathinfo($filename, PATHINFO_FILENAME);

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
        $brands = stock_review_load_active_brands($pdo);

        $linked = [];
        $missing = [];
        $allDevices = [];

        foreach ($devices as $index => $device) {
            $deviceIndex = (int)($device['device_index'] ?? ($index + 1));
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

            $context = stock_review_resolve_device_context(
                $pdo,
                $brands,
                $rawTitle,
                $normalizedTitle,
                $storageValue,
                $ramValue,
                $networkValue,
                $deviceIndex === 1 ? $preferredBrandId : null,
                $deviceIndex === 1 ? $preferredCategoryId : null,
                $deviceIndex === 1
            );

            $baseRow = [
                'device_index' => $deviceIndex,
                'raw_title' => $rawTitle,
                'normalized_title' => $normalizedTitle,
                'storage_value' => $storageValue,
                'ram_value' => $ramValue,
                'network_value' => $networkValue,
                'brand_guess' => $context['brand_guess'],
                'expected_brand_name' => $context['expected_brand_name'],
                'expected_brand_id' => $context['expected_brand_id'],
            ];

            $existing = $context['existing'] ?? null;

            if (is_array($existing)) {
                $linkedRow = array_merge($baseRow, [
                    'exists' => true,
                    'stock_catalog_id' => (int)$existing['id'],
                    'stock_title' => (string)$existing['title'],
                    'category_id' => (int)$existing['category_id'],
                    'category_name' => (string)($existing['category_name'] ?? ''),
                    'brand_id' => (int)$existing['brand_id'],
                    'brand_name' => (string)($existing['brand_name'] ?? ''),
                    'expected_category_id' => (int)($context['expected_category_id'] ?? $existing['category_id']),
                    'is_added' => true,
                ]);

                $linked[] = $linkedRow;
                $allDevices[] = $linkedRow;
            } else {
                $missingRow = array_merge($baseRow, [
                    'exists' => false,
                    'expected_category_id' => $context['expected_category_id'],
                    'is_added' => false,
                ]);

                $missing[] = $missingRow;
                $allDevices[] = $missingRow;
            }
        }

        return [
            'filename' => $filename,
            'devices_count' => count($allDevices),
            'devices' => $allDevices,
            'linked' => $linked,
            'missing' => $missing,
            'linked_count' => count($linked),
            'missing_count' => count($missing),
        ];
    }
}

function link_product_to_stock(PDO $pdo, int $productId, int $brandId, int $categoryId, string $originalFilename): array
{
    $review = review_stock_from_filename(
        $pdo,
        $originalFilename,
        $brandId > 0 ? $brandId : null,
        $categoryId > 0 ? $categoryId : null
    );

    if ($productId <= 0) {
        return $review;
    }

    $deleteExisting = $pdo->prepare("
        DELETE FROM product_stock_links
        WHERE product_id = :product_id
    ");
    $deleteExisting->execute([
        'product_id' => $productId,
    ]);

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
            :source_type,
            :extracted_name,
            NOW()
        )
    ");

    foreach (($review['linked'] ?? []) as $item) {
        $stockCatalogId = (int)($item['stock_catalog_id'] ?? 0);
        $deviceIndex = (int)($item['device_index'] ?? 0);

        if ($stockCatalogId <= 0 || $deviceIndex <= 0) {
            continue;
        }

        $insert->execute([
            'product_id' => $productId,
            'stock_catalog_id' => $stockCatalogId,
            'device_index' => $deviceIndex,
            'source_type' => 'filename',
            'extracted_name' => trim((string)($item['raw_title'] ?? $item['stock_title'] ?? '')),
        ]);
    }

    return $review;
}
