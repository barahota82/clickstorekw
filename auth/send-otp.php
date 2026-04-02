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
$expiresTs = time() + 600;
$expires = date('Y-m-d H:i:s', $expiresTs);

try {
    $payload = [
        'full_name' => $fullName,
        'email' => $email,
        'whatsapp_country_code' => $country,
        'whatsapp_number' => $numberDigits,
        'whatsapp_full' => $whatsappFull,
        'otp' => $otp,
        'expires_ts' => $expiresTs,
    ];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        throw new RuntimeException('Failed to encode verification payload');
    }

    $secret = hash('sha256', DB_HOST . '|' . DB_NAME . '|' . DB_USER . '|' . __FILE__);
    $signature = hash_hmac('sha256', $jsonPayload, $secret);
    $verificationToken = base64_encode($jsonPayload) . '.' . $signature;

    smtp_send_mail($email, $fullName, 'Verification Code', "Code: {$otp}");

    json_response(true, [
        'message' => 'Code sent successfully',
        'verification_token' => $verificationToken,
        'expires_at' => $expires
    ]);
} catch (Throwable $e) {
    json_response(false, [
        'message' => 'Failed to send verification code',
        'error' => $e->getMessage()
    ], 500);
}
