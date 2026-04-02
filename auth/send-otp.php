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
    json_response(false, ['message' => 'Invalid email'], 422);
}

$numberDigits = preg_replace('/\D+/', '', $number);
$whatsappFull = $country . $numberDigits;

$otp = (string) random_int(100000, 999999);
$expires = date('Y-m-d H:i:s', time() + 600);

$pdo = db();

$stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

try {

    if ($user) {
        // تحديث بدون phone و whatsapp
        $stmt = $pdo->prepare("
            UPDATE customers SET
                full_name = ?,
                whatsapp_country_code = ?,
                whatsapp_number = ?,
                whatsapp_full = ?,
                otp_code = ?,
                otp_expires_at = ?,
                is_verified = 0,
                email_verified_at = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $fullName,
            $country,
            $numberDigits,
            $whatsappFull,
            $otp,
            $expires,
            (int)$user['id']
        ]);

    } else {
        // إدخال جديد
        $stmt = $pdo->prepare("
            INSERT INTO customers
            (
                full_name,
                whatsapp_country_code,
                whatsapp_number,
                whatsapp_full,
                email,
                otp_code,
                otp_expires_at,
                is_verified,
                installment_approved,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, NOW(), NOW())
        ");

        $stmt->execute([
            $fullName,
            $country,
            $numberDigits,
            $whatsappFull,
            $email,
            $otp,
            $expires
        ]);
    }

    smtp_send_mail($email, $fullName, 'Verification Code', "Code: {$otp}");

    json_response(true, ['message' => 'Code sent']);

} catch (Throwable $e) {
    json_response(false, [
        'message' => 'Server error',
        'error' => $e->getMessage()
    ], 500);
}
