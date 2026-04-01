<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

unset($_SESSION['customer_auth']);
unset($_SESSION['pending_customer_email']);

json_response([
    'ok' => true,
    'message' => 'Logged out successfully.'
]);
