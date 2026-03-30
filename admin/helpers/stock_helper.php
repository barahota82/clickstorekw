<?php
declare(strict_types=1);

function normalize_stock_title(string $text): string
{
    $text = strtolower($text);
    $text = str_replace(['_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim((string)$text);
}

function make_stock_slug(string $title): string
{
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\-\s]+/i', '', $slug);
    $slug = preg_replace('/\s+/', '-', (string)$slug);
    return trim((string)$slug, '-');
}

function find_or_create_stock_catalog(
    PDO $pdo,
    int $brandId,
    int $categoryId,
    string $title,
    string $normalizedTitle,
    ?string $storageValue = null,
    ?string $ramValue = null,
    ?string $networkValue = null
): int {
    $query = $pdo->prepare("
        SELECT id
        FROM stock_catalog
        WHERE normalized_title = :normalized_title
        LIMIT 1
    ");
    $query->execute([
        'normalized_title' => $normalizedTitle
    ]);

    $existing = $query->fetch();
    if ($existing) {
        return (int)$existing['id'];
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
