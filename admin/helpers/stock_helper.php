<?php
declare(strict_types=1);

function normalize_stock_title(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('/\.[^.]+$/', '', $text);
    $text = str_replace(['_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', (string)$text);
    return trim((string)$text);
}

function make_stock_slug(string $title): string
{
    $slug = strtolower($title);
    $slug = preg_replace('/\.[^.]+$/', '', $slug);
    $slug = str_replace(['_', '+'], ' ', $slug);
    $slug = preg_replace('/[^a-z0-9\-\s]+/i', '', (string)$slug);
    $slug = preg_replace('/\s+/', '-', (string)$slug);
    $slug = preg_replace('/-+/', '-', (string)$slug);
    return trim((string)$slug, '-');
}

function stock_catalog_limit_devices(array $devices, int $max = 4): array
{
    if ($max < 1) {
        $max = 1;
    }

    return array_slice(array_values($devices), 0, $max);
}

function find_stock_catalog(
    PDO $pdo,
    string $normalizedTitle,
    ?int $brandId = null,
    ?int $categoryId = null,
    ?string $storageValue = null,
    ?string $ramValue = null,
    ?string $networkValue = null
): ?array {
    $normalizedTitle = normalize_stock_title($normalizedTitle);

    if ($normalizedTitle === '') {
        return null;
    }

    $sql = "
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
            sc.is_active
        FROM stock_catalog sc
        WHERE sc.normalized_title = :normalized_title
    ";

    $params = [
        'normalized_title' => $normalizedTitle,
    ];

    if ($brandId !== null && $brandId > 0) {
        $sql .= " AND sc.brand_id = :brand_id";
        $params['brand_id'] = $brandId;
    }

    if ($categoryId !== null && $categoryId > 0) {
        $sql .= " AND sc.category_id = :category_id";
        $params['category_id'] = $categoryId;
    }

    if ($storageValue !== null && $storageValue !== '') {
        $sql .= " AND (sc.storage_value = :storage_value OR sc.storage_value IS NULL)";
        $params['storage_value'] = $storageValue;
    }

    if ($ramValue !== null && $ramValue !== '') {
        $sql .= " AND (sc.ram_value = :ram_value OR sc.ram_value IS NULL)";
        $params['ram_value'] = $ramValue;
    }

    if ($networkValue !== null && $networkValue !== '') {
        $sql .= " AND (sc.network_value = :network_value OR sc.network_value IS NULL)";
        $params['network_value'] = $networkValue;
    }

    $sql .= " ORDER BY sc.id ASC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function find_stock_catalog_id(
    PDO $pdo,
    string $normalizedTitle,
    ?int $brandId = null,
    ?int $categoryId = null,
    ?string $storageValue = null,
    ?string $ramValue = null,
    ?string $networkValue = null
): ?int {
    $row = find_stock_catalog(
        $pdo,
        $normalizedTitle,
        $brandId,
        $categoryId,
        $storageValue,
        $ramValue,
        $networkValue
    );

    return $row ? (int)$row['id'] : null;
}

function create_stock_catalog(
    PDO $pdo,
    int $brandId,
    int $categoryId,
    string $title,
    string $normalizedTitle,
    ?string $storageValue = null,
    ?string $ramValue = null,
    ?string $networkValue = null
): int {
    $title = trim($title);
    $normalizedTitle = normalize_stock_title($normalizedTitle);

    if ($title === '' || $normalizedTitle === '') {
        throw new RuntimeException('Invalid stock title');
    }

    if ($brandId <= 0 || $categoryId <= 0) {
        throw new RuntimeException('Brand and category are required');
    }

    $existingId = find_stock_catalog_id(
        $pdo,
        $normalizedTitle,
        $brandId,
        $categoryId,
        $storageValue,
        $ramValue,
        $networkValue
    );

    if ($existingId !== null) {
        return $existingId;
    }

    $slugBase = make_stock_slug($normalizedTitle);
    if ($slugBase === '') {
        $slugBase = 'stock-item';
    }

    $slug = $slugBase;
    $counter = 2;

    while (true) {
        $check = $pdo->prepare("SELECT id FROM stock_catalog WHERE slug = :slug LIMIT 1");
        $check->execute(['slug' => $slug]);

        if (!$check->fetch()) {
            break;
        }

        $slug = $slugBase . '-' . $counter;
        $counter++;
    }

    $insert = $pdo->prepare("
        INSERT INTO stock_catalog (
            category_id,
            brand_id,
            title,
            slug,
            normalized_title,
            storage_value,
            ram_value,
            network_value,
            sku,
            is_active,
            created_at,
            updated_at
        ) VALUES (
            :category_id,
            :brand_id,
            :title,
            :slug,
            :normalized_title,
            :storage_value,
            :ram_value,
            :network_value,
            NULL,
            1,
            NOW(),
            NOW()
        )
    ");

    $insert->execute([
        'category_id' => $categoryId,
        'brand_id' => $brandId,
        'title' => $title,
        'slug' => $slug,
        'normalized_title' => $normalizedTitle,
        'storage_value' => $storageValue,
        'ram_value' => $ramValue,
        'network_value' => $networkValue,
    ]);

    return (int)$pdo->lastInsertId();
}

function get_stock_review_from_devices(PDO $pdo, array $devices, ?int $brandId = null): array
{
    $devices = stock_catalog_limit_devices($devices, 4);
    $result = [];

    foreach ($devices as $device) {
        $rawTitle = trim((string)($device['raw_title'] ?? ''));
        $normalizedTitle = normalize_stock_title((string)($device['normalized_title'] ?? $rawTitle));
        $storageValue = isset($device['storage_value']) ? (string)$device['storage_value'] : null;
        $ramValue = isset($device['ram_value']) ? (string)$device['ram_value'] : null;
        $networkValue = isset($device['network_value']) ? (string)$device['network_value'] : null;

        $stockRow = find_stock_catalog(
            $pdo,
            $normalizedTitle,
            $brandId,
            null,
            $storageValue,
            $ramValue,
            $networkValue
        );

        $result[] = [
            'device_index' => (int)($device['device_index'] ?? 0),
            'raw_title' => $rawTitle,
            'normalized_title' => $normalizedTitle,
            'storage_value' => $storageValue,
            'ram_value' => $ramValue,
            'network_value' => $networkValue,
            'is_added' => $stockRow !== null,
            'stock_catalog_id' => $stockRow ? (int)$stockRow['id'] : null,
            'stock_category_id' => $stockRow ? (int)$stockRow['category_id'] : null,
            'stock_brand_id' => $stockRow ? (int)$stockRow['brand_id'] : null,
            'stock_title' => $stockRow ? (string)$stockRow['title'] : '',
        ];
    }

    return $result;
}
