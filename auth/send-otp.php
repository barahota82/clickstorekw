<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/mailer.php';

require_post();

$fullName = trim($_POST['full_name'] ?? '');
$email    = strtolower(trim($_POST['email'] ?? ''));
$country  = trim($_POST['country_code'] ?? '');
$number   = trim($_POST['whatsapp'] ?? '');

if ($fullName === '' || $email === '' || $country === '' || $number === '') {
    json_response(false, ['message' => 'Fill all fields'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, ['message' => 'Invalid email address'], 422);
}

$numberDigits = preg_replace('/\D+/', '', $number);
if ($numberDigits === '') {
    json_response(false, ['message' => 'Invalid WhatsApp number'], 422);
}

$whatsappFull = $country . $numberDigits;
$otp = (string) random_int(100000, 999999);
$expires = date('Y-m-d H:i:s', time() + 600);

try {
    $_SESSION['pending_customer_registration'] = [
        'full_name' => $fullName,
        'email' => $email,
        'whatsapp_country_code' => $country,
        'whatsapp_number' => $numberDigits,
        'whatsapp_full' => $whatsappFull,
    ];

    $_SESSION['pending_customer_email'] = $email;
    $_SESSION['pending_customer_otp'] = $otp;
    $_SESSION['pending_customer_otp_expires_at'] = $expires;
    unset($_SESSION['pending_customer_id']);

    smtp_send_mail($email, $fullName, 'Verification Code', "Code: {$otp}");

    json_response(true, [
        'message' => 'Code sent successfully'
    ]);
} catch (Throwable $e) {
    unset($_SESSION['pending_customer_registration']);
    unset($_SESSION['pending_customer_email']);
    unset($_SESSION['pending_customer_otp']);
    unset($_SESSION['pending_customer_otp_expires_at']);
    unset($_SESSION['pending_customer_id']);

    json_response(false, [
        'message' => 'Failed to send verification code',
        'error' => $e->getMessage()
    ], 500);
}
