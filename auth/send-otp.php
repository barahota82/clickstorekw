<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/mailer.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, [
        'message' => 'Invalid request method.'
    ], 405);
}

$fullName = trim($_POST['full_name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$email    = strtolower(trim($_POST['email'] ?? ''));

if ($fullName === '' || $phone === '' || $email === '') {
    json_response(false, [
        'message' => 'Please fill in all fields.'
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, [
        'message' => 'Invalid email address.'
    ], 422);
}

$otp = (string) random_int(100000, 999999);
$expiresAt = date('Y-m-d H:i:s', time() + 600);

$pdo = db();

$stmt = $pdo->prepare("
    SELECT id
    FROM customers
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    $customerId = (int) $existing['id'];

    $stmt = $pdo->prepare("
        UPDATE customers
        SET full_name = ?,
            phone = ?,
            otp_code = ?,
            otp_expires_at = ?,
            is_verified = 0,
            email_verified_at = NULL,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $fullName,
        $phone,
        $otp,
        $expiresAt,
        $customerId
    ]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO customers
        (full_name, phone, whatsapp, email, otp_code, otp_expires_at, is_verified, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
    ");
    $stmt->execute([
        $fullName,
        $phone,
        $phone,
        $email,
        $otp,
        $expiresAt
    ]);

    $customerId = (int) $pdo->lastInsertId();
}

$subject = 'Your verification code - Click Store KW';
$body = "Hello {$fullName},\n\nYour verification code is: {$otp}\n\nThis code expires in 10 minutes.\n\nClick Store KW";

smtp_send_mail($email, $fullName, $subject, $body);

$_SESSION['pending_customer_id'] = $customerId;
$_SESSION['pending_customer_email'] = $email;

json_response(true, [
    'message' => 'Verification code sent successfully.'
]);
