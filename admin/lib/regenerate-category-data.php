<?php
require_once __DIR__ . '/../config.php';

function regenerate_category_data($category)
{
    $products_dir = __DIR__ . "/../../products/$category/";
    $data_file = $products_dir . "data.json";

    if (!is_dir($products_dir)) {
        return false;
    }

    $files = glob($products_dir . "*.json");
    $products = [];

    foreach ($files as $file) {
        if (basename($file) === 'data.json') continue;

        $json = json_decode(file_get_contents($file), true);
        if (!$json) continue;

        if (isset($json['deleted']) && $json['deleted'] === true) continue;
        if (isset($json['available']) && $json['available'] == false) continue;

        $products[] = $json;
    }

    // ترتيب حسب product_order
    usort($products, function ($a, $b) {
        return ($a['product_order'] ?? 0) <=> ($b['product_order'] ?? 0);
    });

    // Lock أثناء الكتابة
    $fp = fopen($data_file, 'c+');
    if (!$fp) return false;

    flock($fp, LOCK_EX);

    ftruncate($fp, 0);
    fwrite($fp, json_encode([
        'category' => $category,
        'count' => count($products),
        'products' => $products
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}
