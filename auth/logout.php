<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config.php';

/* ===== CLEAR SESSION ===== */
unset($_SESSION['customer_auth']);
unset($_SESSION['pending_customer_email']);
unset($_SESSION['pending_customer_id']);

/* ===== RESPONSE ===== */
json_response(true, [
    'message' => 'Logged out successfully.'
]);
