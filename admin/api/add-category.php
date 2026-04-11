<?php
require_once '../../config.php';
require_admin_auth_json();

$data = get_request_json();

$name_en = trim($data['name_en']);
$name_ph = trim($data['name_ph']);
$name_hi = trim($data['name_hi']);

if (!$name_en) {
    json_response(['error' => 'Name EN required'], 400);
}

$slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name_en));

$file = '../../data/categories.json';
$categories = file_exists($file)
    ? json_decode(file_get_contents($file), true)
    : [];

foreach ($categories as $cat) {
    if ($cat['slug'] === $slug) {
        json_response(['error' => 'Category exists'], 400);
    }
}

$categories[] = [
    'slug' => $slug,
    'name' => [
        'en' => $name_en,
        'ph' => $name_ph,
        'hi' => $name_hi
    ]
];

file_put_contents($file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

json_response(['success' => true]);
