<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/mailer.php';

require_post();

$mode = strtolower(trim((string)($_POST['mode'] ?? 'signup')));
$email = strtolower(trim((string)($_POST['email'] ?? '')));

if ($email === '') {
    json_response(false, ['message' => 'Email is required'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, ['message' => 'Invalid email address'], 422);
}

try {
    $pdo = db();

    if ($mode === 'signin') {
        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name,
                email,
                whatsapp_country_code,
                whatsapp_number,
                whatsapp_full,
                is_verified
            FROM customers
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if (!$customer || (int)($customer['is_verified'] ?? 0) !== 1) {
            json_response(false, [
                'message' => 'This email is not registered. Please create a new account first'
            ], 422);
        }

        $otp = (string) random_int(100000, 999999);
        $expires = date('Y-m-d H:i:s', time() + 600);

        smtp_send_mail(
            (string)$customer['email'],
            (string)$customer['full_name'],
            'Sign In Verification Code',
            "Code: {$otp}"
        );

        $_SESSION['pending_customer_auth'] = [
            'mode' => 'signin',
            'customer_id' => (int)$customer['id'],
            'full_name' => (string)$customer['full_name'],
            'email' => (string)$customer['email'],
            'whatsapp_country_code' => (string)($customer['whatsapp_country_code'] ?? ''),
            'whatsapp_number' => (string)($customer['whatsapp_number'] ?? ''),
            'whatsapp_full' => (string)($customer['whatsapp_full'] ?? ''),
            'otp_code' => $otp,
            'otp_expires_at' => $expires
        ];

        json_response(true, [
            'message' => 'Verification code sent to your email',
            'mode' => 'signin'
        ]);
    }

    if ($mode !== 'signup') {
        json_response(false, ['message' => 'Invalid mode'], 422);
    }

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $country = trim((string)($_POST['country_code'] ?? ''));
    $number = trim((string)($_POST['whatsapp'] ?? ''));

    if ($fullName === '' || $country === '' || $number === '') {
        json_response(false, ['message' => 'Full name, country code and WhatsApp number are required'], 422);
    }

    $numberDigits = preg_replace('/\D+/', '', $number);
    if ($numberDigits === '') {
        json_response(false, ['message' => 'Invalid WhatsApp number'], 422);
    }

    $checkStmt = $pdo->prepare("
        SELECT id, is_verified
        FROM customers
        WHERE email = ?
        LIMIT 1
    ");
    $checkStmt->execute([$email]);
    $existingCustomer = $checkStmt->fetch();

    if ($existingCustomer && (int)$existingCustomer['is_verified'] === 1) {
        json_response(false, [
            'message' => 'This email is already registered. Please use Sign In'
        ], 422);
    }

    $whatsappFull = $country . $numberDigits;
    $otp = (string) random_int(100000, 999999);
    $expires = date('Y-m-d H:i:s', time() + 600);

    smtp_send_mail($email, $fullName, 'Verification Code', "Code: {$otp}");

    $_SESSION['pending_customer_auth'] = [
        'mode' => 'signup',
        'full_name' => $fullName,
        'email' => $email,
        'whatsapp_country_code' => $country,
        'whatsapp_number' => $numberDigits,
        'whatsapp_full' => $whatsappFull,
        'otp_code' => $otp,
        'otp_expires_at' => $expires
    ];

    json_response(true, [
        'message' => 'Verification code sent to your email',
        'mode' => 'signup'
    ]);
} catch (Throwable $e) {
    json_response(false, [
        'message' => 'Failed to send verification code',
        'error' => $e->getMessage()
    ], 500);
}
