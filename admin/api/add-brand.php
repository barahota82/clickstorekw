<?php
require_once '../../config.php';
require_admin_auth_json();

$data = get_request_json();

$category_id = (int)$data['category_id'];
$name = trim($data['name']);

if (!$name) {
    json_response(false, ['message' => 'Name required'], 422);
}

$slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));

$pdo = db();

$stmt = $pdo->prepare("
    INSERT INTO brands (category_id, name, display_name, slug, sort_order, is_active)
    VALUES (?, ?, ?, ?, 9999, 1)
");

$stmt->execute([$category_id, $name, $name, $slug]);

json_response(true, ['message' => 'Brand added']);
