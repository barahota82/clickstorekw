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

$pdo = db();

try {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("
            UPDATE customers SET
                full_name = ?,
                phone = ?,
                whatsapp_country_code = ?,
                whatsapp_number = ?,
                whatsapp_full = ?,
                whatsapp = ?,
                otp_code = ?,
                otp_expires_at = ?,
                is_verified = 0,
                email_verified_at = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $fullName,
            $numberDigits,
            $country,
            $numberDigits,
            $whatsappFull,
            $numberDigits,
            $otp,
            $expires,
            (int)$user['id']
        ]);

        $customerId = (int)$user['id'];
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO customers
            (
                full_name,
                phone,
                whatsapp_country_code,
                whatsapp_number,
                whatsapp_full,
                whatsapp,
                email,
                otp_code,
                otp_expires_at,
                is_verified,
                installment_approved,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW(), NOW())
        ");

        $stmt->execute([
            $fullName,
            $numberDigits,
            $country,
            $numberDigits,
            $whatsappFull,
            $numberDigits,
            $email,
            $otp,
            $expires
        ]);

        $customerId = (int)$pdo->lastInsertId();
    }

    smtp_send_mail($email, $fullName, 'Verification Code', "Code: {$otp}");

    $_SESSION['pending_customer_id'] = $customerId;
    $_SESSION['pending_customer_email'] = $email;

    json_response(true, [
        'message' => 'Code sent successfully'
    ]);
} catch (Throwable $e) {
    json_response(false, [
        'message' => 'OTP send failed',
        'error' => $e->getMessage()
    ], 500);
}
