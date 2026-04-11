<?php
require_once '../../config.php';
require_admin_auth_json();

$file = '../../data/categories.json';

if (!file_exists($file)) {
    echo json_encode([]);
    exit;
}

$data = json_decode(file_get_contents($file), true);

echo json_encode($data);
