<?php
require_once '../../config.php';
require_admin_auth_json();

$data = get_request_json();

$id = (int)$data['id'];

$pdo = db();

$stmt = $pdo->prepare("
    UPDATE brands
    SET 
        name = ?,
        display_name = ?,
        sort_order = ?,
        is_active = ?
    WHERE id = ?
");

$stmt->execute([
    $data['name'],
    $data['display_name'],
    (int)$data['sort_order'],
    $data['is_active'] ? 1 : 0,
    $id
]);

json_response(true, ['message' => 'Updated']);
