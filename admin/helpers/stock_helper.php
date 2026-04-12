<?php
declare(strict_types=1);

function normalize_stock_title(string $text): string
{
    $text = strtolower($text);
    $text = str_replace(['_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', (string)$text);
    return trim((string)$text);
}

function make_stock_slug(string $title): string
{
    $slug = strtolower($title);
    $slug = str_replace(['_', '+'], ' ', $slug);
    $slug = str_replace('.', ' ', $slug);
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

function stock_value_or_null(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);
    return $value === '' ? null : $value;
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
    $storageValue = stock_value_or_null($storageValue);
    $ramValue = stock_value_or_null($ramValue);
    $networkValue = stock_value_or_null($networkValue);

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
            sc.is_active,
            c.display_name AS category_name,
            b.name AS brand_name
        FROM stock_catalog sc
        LEFT JOIN categories c ON c.id = sc.category_id
        LEFT JOIN brands b ON b.id = sc.brand_id
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

    if ($storageValue !== null) {
        $sql .= " AND sc.storage_value = :storage_value";
        $params['storage_value'] = $storageValue;
    } else {
        $sql .= " AND sc.storage_value IS NULL";
    }

    if ($ramValue !== null) {
        $sql .= " AND sc.ram_value = :ram_value";
        $params['ram_value'] = $ramValue;
    } else {
        $sql .= " AND sc.ram_value IS NULL";
    }

    if ($networkValue !== null) {
        $sql .= " AND sc.network_value = :network_value";
        $params['network_value'] = $networkValue;
    } else {
        $sql .= " AND sc.network_value IS NULL";
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
    $storageValue = stock_value_or_null($storageValue);
    $ramValue = stock_value_or_null($ramValue);
    $networkValue = stock_value_or_null($networkValue);

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

        if (!$check->fetch(PDO::FETCH_ASSOC)) {
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

function get_stock_review_from_devices(PDO $pdo, array $devices, ?int $brandId = null, ?int $categoryId = null): array
{
    $devices = stock_catalog_limit_devices($devices, 4);
    $linked = [];
    $missing = [];
    $all = [];

    foreach ($devices as $index => $device) {
        $deviceIndex = (int)($device['device_index'] ?? ($index + 1));
        $rawTitle = trim((string)($device['raw_title'] ?? ''));
        $normalizedTitle = normalize_stock_title((string)($device['normalized_title'] ?? $rawTitle));
        $storageValue = stock_value_or_null($device['storage_value'] ?? null);
        $ramValue = stock_value_or_null($device['ram_value'] ?? null);
        $networkValue = stock_value_or_null($device['network_value'] ?? null);

        if ($rawTitle === '' && $normalizedTitle === '') {
            continue;
        }

        $stockRow = find_stock_catalog(
            $pdo,
            $normalizedTitle,
            $brandId,
            $categoryId,
            $storageValue,
            $ramValue,
            $networkValue
        );

        $baseRow = [
            'device_index' => $deviceIndex,
            'raw_title' => $rawTitle,
            'normalized_title' => $normalizedTitle,
            'storage_value' => $storageValue,
            'ram_value' => $ramValue,
            'network_value' => $networkValue,
        ];

        if ($stockRow) {
            $row = array_merge($baseRow, [
                'is_added' => true,
                'stock_catalog_id' => (int)$stockRow['id'],
                'stock_title' => (string)$stockRow['title'],
                'category_id' => (int)$stockRow['category_id'],
                'category_name' => (string)($stockRow['category_name'] ?? ''),
                'brand_id' => (int)$stockRow['brand_id'],
                'brand_name' => (string)($stockRow['brand_name'] ?? ''),
            ]);

            $linked[] = $row;
            $all[] = $row;
        } else {
            $row = array_merge($baseRow, [
                'is_added' => false,
                'expected_category_id' => $categoryId,
                'expected_brand_id' => $brandId,
            ]);

            $missing[] = $row;
            $all[] = $row;
        }
    }

    return [
        'devices_count' => count($all),
        'devices' => $all,
        'linked' => $linked,
        'missing' => $missing,
        'linked_count' => count($linked),
        'missing_count' => count($missing),
    ];
}


if (!function_exists('get_stock_item_by_product_id')) {
    function get_stock_item_by_product_id(PDO $pdo, int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT
                id,
                product_id,
                sku,
                qty_on_hand,
                reserved_qty,
                available_qty,
                reorder_level,
                is_active,
                created_at,
                updated_at
            FROM stock_items
            WHERE product_id = ?
            LIMIT 1
        ");
        $stmt->execute([$productId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('get_stock_item_by_id')) {
    function get_stock_item_by_id(PDO $pdo, int $stockItemId): ?array
    {
        if ($stockItemId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT
                id,
                product_id,
                sku,
                qty_on_hand,
                reserved_qty,
                available_qty,
                reorder_level,
                is_active,
                created_at,
                updated_at
            FROM stock_items
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$stockItemId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('recalculate_stock_item_available_qty')) {
    function recalculate_stock_item_available_qty(PDO $pdo, int $stockItemId): void
    {
        if ($stockItemId <= 0) {
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE stock_items
            SET
                available_qty = CASE
                    WHEN (qty_on_hand - reserved_qty) < 0 THEN 0
                    ELSE (qty_on_hand - reserved_qty)
                END,
                updated_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$stockItemId]);
    }
}

if (!function_exists('ensure_stock_item_for_product')) {
    function ensure_stock_item_for_product(
        PDO $pdo,
        int $productId,
        ?string $sku = null,
        ?int $reorderLevel = null
    ): array {
        if ($productId <= 0) {
            throw new RuntimeException('Product ID is required to create stock item');
        }

        $productStmt = $pdo->prepare("
            SELECT
                id,
                sku,
                is_active
            FROM products
            WHERE id = ?
            LIMIT 1
        ");
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new RuntimeException('Product not found while ensuring stock item');
        }

        $resolvedSku = trim((string)($sku ?? $product['sku'] ?? ''));
        if ($resolvedSku === '') {
            $resolvedSku = 'PRODUCT_' . $productId;
        }

        $existing = get_stock_item_by_product_id($pdo, $productId);

        if ($existing) {
            $duplicateSkuStmt = $pdo->prepare("
                SELECT id
                FROM stock_items
                WHERE sku = ?
                  AND product_id <> ?
                LIMIT 1
            ");
            $duplicateSkuStmt->execute([$resolvedSku, $productId]);

            if ($duplicateSkuStmt->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('Another stock item already uses the same SKU');
            }

            $fields = [];
            $params = [];

            if ((string)($existing['sku'] ?? '') !== $resolvedSku) {
                $fields[] = 'sku = ?';
                $params[] = $resolvedSku;
            }

            if ($reorderLevel !== null && (int)($existing['reorder_level'] ?? 0) !== max(0, $reorderLevel)) {
                $fields[] = 'reorder_level = ?';
                $params[] = max(0, $reorderLevel);
            }

            $targetActive = (int)($product['is_active'] ?? 1) === 1 ? 1 : 0;
            if ((int)($existing['is_active'] ?? 1) !== $targetActive) {
                $fields[] = 'is_active = ?';
                $params[] = $targetActive;
            }

            if ($fields) {
                $params[] = $productId;

                $updateStmt = $pdo->prepare("
                    UPDATE stock_items
                    SET " . implode(', ', $fields) . ", updated_at = NOW()
                    WHERE product_id = ?
                    LIMIT 1
                ");
                $updateStmt->execute($params);
            }

            $stockItemId = (int)$existing['id'];
            recalculate_stock_item_available_qty($pdo, $stockItemId);

            return get_stock_item_by_id($pdo, $stockItemId) ?: $existing;
        }

        $duplicateSkuStmt = $pdo->prepare("
            SELECT id
            FROM stock_items
            WHERE sku = ?
            LIMIT 1
        ");
        $duplicateSkuStmt->execute([$resolvedSku]);

        if ($duplicateSkuStmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Another stock item already uses the same SKU');
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO stock_items
            (
                product_id,
                sku,
                qty_on_hand,
                reserved_qty,
                available_qty,
                reorder_level,
                is_active,
                created_at,
                updated_at
            ) VALUES
            (
                :product_id,
                :sku,
                0,
                0,
                0,
                :reorder_level,
                :is_active,
                NOW(),
                NOW()
            )
        ");
        $insertStmt->execute([
            'product_id' => $productId,
            'sku' => $resolvedSku,
            'reorder_level' => max(0, (int)($reorderLevel ?? 0)),
            'is_active' => (int)($product['is_active'] ?? 1) === 1 ? 1 : 0,
        ]);

        $stockItemId = (int)$pdo->lastInsertId();
        recalculate_stock_item_available_qty($pdo, $stockItemId);

        return get_stock_item_by_id($pdo, $stockItemId) ?: [
            'id' => $stockItemId,
            'product_id' => $productId,
            'sku' => $resolvedSku,
            'qty_on_hand' => 0,
            'reserved_qty' => 0,
            'available_qty' => 0,
            'reorder_level' => max(0, (int)($reorderLevel ?? 0)),
            'is_active' => (int)($product['is_active'] ?? 1) === 1 ? 1 : 0,
        ];
    }
}
