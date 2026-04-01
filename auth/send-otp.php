<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

echo json_encode([
    'ok' => true,
    'message' => 'send-otp.php loaded successfully'
], JSON_UNESCAPED_UNICODE);
exit;
