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
    json_response(false, ['message' => 'Full name, WhatsApp number, country code, and email are required'], 422);
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
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, email, is_verified
        FROM customers
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $existingCustomer = $stmt->fetch();

    $_SESSION['pending_customer_auth'] = [
        'mode' => $existingCustomer ? 'signin' : 'signup',
        'full_name' => $fullName,
        'email' => $email,
        'whatsapp_country_code' => $country,
        'whatsapp_number' => $numberDigits,
        'whatsapp_full' => $whatsappFull,
        'otp_code' => $otp,
        'otp_expires_at' => $expires
    ];

    smtp_send_mail(
        $email,
        $fullName,
        'Verification Code',
        "Your verification code is: {$otp}\n\nThis code expires in 10 minutes."
    );

    json_response(true, [
        'message' => 'Verification code sent to your email',
        'mode' => $existingCustomer ? 'signin' : 'signup'
    ]);
} catch (Throwable $e) {
    json_response(false, [
        'message' => 'Failed to send verification code',
        'error' => $e->getMessage()
    ], 500);
}
