<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['customer_auth']) || !is_array($_SESSION['customer_auth'])) {
    json_response(true, [
        'logged_in' => false
    ]);
}

json_response(true, [
    'logged_in' => true,
    'customer' => $_SESSION['customer_auth']
]);
