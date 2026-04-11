<?php
require_once '../../config.php';
require_once '../helpers/categories_sync.php';

require_admin_auth_json();

$data = get_request_json();

$id = (int)$data['id'];

$pdo = db();

$stmt = $pdo->prepare("
    UPDATE categories
    SET 
        name_en = ?,
        name_ph = ?,
        name_hi = ?,
        visible = ?,
        nav_order = ?
    WHERE id = ?
");

$stmt->execute([
    $data['name_en'],
    $data['name_ph'],
    $data['name_hi'],
    $data['visible'] ? 1 : 0,
    (int)$data['nav_order'],
    $id
]);

generate_categories_json();

json_response(true, ['message' => 'Updated']);
