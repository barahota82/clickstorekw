<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    respond(false, ['message' => 'Unauthorized'], 401);
}

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    respond(false, ['message' => 'Empty request body'], 400);
}

$input = json_decode($raw, true);
if (!is_array($input)) {
    respond(false, ['message' => 'Invalid JSON body'], 400);
}

$category = $input['category'] ?? '';
$file = $input['file'] ?? '';
$product = $input['product'] ?? null;

$allowedCategories = ['phones', 'tablets', 'laptops', 'accessories'];

if (!in_array($category, $allowedCategories, true)) {
    respond(false, ['message' => 'Invalid category'], 400);
}

if ($file === '' || strpos($file, '..') !== false || !str_ends_with($file, '.json')) {
    respond(false, ['message' => 'Invalid file name'], 400);
}

if (!is_array($product)) {
    respond(false, ['message' => 'Invalid product payload'], 400);
}

$basePath = dirname(__DIR__, 2);
$productPath = $basePath . "/products/{$category}/{$file}";

if (!file_exists($productPath)) {
    respond(false, ['message' => 'Product file not found'], 404);
}

$cleanProduct = [
    'title' => (string)($product['title'] ?? ''),
    'category' => (string)($product['category'] ?? $category),
    'brand' => (string)($product['brand'] ?? ''),
    'devices_count' => (int)($product['devices_count'] ?? 1),
    'image' => (string)($product['image'] ?? ''),
    'down_payment' => (string)($product['down_payment'] ?? ''),
    'monthly' => (string)($product['monthly'] ?? ''),
    'duration' => (string)($product['duration'] ?? ''),
    'available' => (bool)($product['available'] ?? false),
    'hot_offer' => (bool)($product['hot_offer'] ?? false),
    'brand_priority' => isset($product['brand_priority']) ? (int)$product['brand_priority'] : 9999,
    'priority' => isset($product['priority']) ? (int)$product['priority'] : 9999,
];

if ($cleanProduct['title'] === '') {
    respond(false, ['message' => 'Title is required'], 400);
}

if ($cleanProduct['brand'] === '') {
    respond(false, ['message' => 'Brand is required'], 400);
}

if ($cleanProduct['devices_count'] < 1) {
    $cleanProduct['devices_count'] = 1;
}

$json = json_encode($cleanProduct, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false) {
    respond(false, ['message' => 'Failed to encode product JSON'], 500);
}

$result = file_put_contents($productPath, $json . PHP_EOL, LOCK_EX);

if ($result === false) {
    respond(false, ['message' => 'Failed to save product file'], 500);
}

respond(true, [
    'message' => 'Product saved successfully',
    'path' => "products/{$category}/{$file}"
]);
