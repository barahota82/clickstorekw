<?php
require_once '../../config.php';
require_once '../helpers/categories_sync.php';

require_admin_auth_json();

$data = get_request_json();

$name_en = trim($data['name_en'] ?? '');

if (!$name_en) {
    json_response(false, ['message' => 'Name required'], 422);
}

$slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name_en));

$pdo = db();

$check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
$check->execute([$slug]);

if ($check->fetch()) {
    json_response(false, ['message' => 'Category exists'], 409);
}

$stmt = $pdo->prepare("
    INSERT INTO categories
    (name, slug, display_name, is_active, visible, nav_order, name_en)
    VALUES (?, ?, ?, 1, 1, 9999, ?)
");

$stmt->execute([$slug, $slug, $name_en, $name_en]);

generate_categories_json();

json_response(true, ['message' => 'Category added']);
