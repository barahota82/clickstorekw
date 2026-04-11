<?php
require_once '../../config.php';
require_admin_auth_json();

$data = get_request_json();

$slug = $data['slug'];

$file = '../../data/categories.json';
$categories = json_decode(file_get_contents($file), true);

foreach ($categories as &$cat) {
    if ($cat['slug'] === $slug) {
        $cat['name']['en'] = $data['name_en'];
        $cat['name']['ph'] = $data['name_ph'];
        $cat['name']['hi'] = $data['name_hi'];
        break;
    }
}

file_put_contents($file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

json_response(['success' => true]);
