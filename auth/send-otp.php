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
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, is_verified
        FROM customers
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $existingCustomer = $stmt->fetch();

    if ($existingCustomer && (int)$existingCustomer['is_verified'] === 1) {
        json_response(false, ['message' => 'This email is already registered and verified'], 422);
    }

    smtp_send_mail($email, $fullName, 'Verification Code', "Code: {$otp}");

    $_SESSION['pending_customer_auth'] = [
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $numberDigits,
        'whatsapp_country_code' => $country,
        'whatsapp_number' => $numberDigits,
        'whatsapp_full' => $whatsappFull,
        'whatsapp' => $numberDigits,
        'otp_code' => $otp,
        'otp_expires_at' => $expires
    ];

    json_response(true, [
        'message' => 'Code sent successfully'
    ]);
} catch (Throwable $e) {
    json_response(false, [
        'message' => 'Failed to send verification code',
        'error' => $e->getMessage()
    ], 500);
}
