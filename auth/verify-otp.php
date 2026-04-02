<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

require_post();

$email = strtolower(trim($_POST['email'] ?? ''));
$otp   = trim($_POST['otp'] ?? '');

json_response(true, [
    'step' => 'A',
    'email' => $email,
    'otp' => $otp
]);
