<?php
header('Content-Type: application/json; charset=utf-8');

function respond($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$category = $_GET['category'] ?? '';
$file = $_GET['file'] ?? '';

$allowedCategories = ['phones', 'tablets', 'laptops', 'accessories'];

if (!in_array($category, $allowedCategories, true)) {
    respond(false, ['message' => 'Invalid category'], 400);
}

if ($file === '' || strpos($file, '..') !== false || !str_ends_with($file, '.json')) {
    respond(false, ['message' => 'Invalid file name'], 400);
}

$basePath = dirname(__DIR__, 2);
$productPath = $basePath . "/products/{$category}/{$file}";

if (!file_exists($productPath)) {
    respond(false, ['message' => 'Product file not found'], 404);
}

$content = file_get_contents($productPath);
if ($content === false) {
    respond(false, ['message' => 'Failed to read product file'], 500);
}

$data = json_decode($content, true);
if (!is_array($data)) {
    respond(false, ['message' => 'Invalid JSON in product file'], 500);
}

respond(true, [
    'product' => $data,
    'category' => $category,
    'file' => $file
]);
