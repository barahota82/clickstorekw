<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'message' => 'Unauthorized'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'ok' => true,
    'username' => $_SESSION['admin_username'] ?? 'admin'
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
