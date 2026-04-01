<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

require_post();

$fullName = clean_string($_POST['full_name'] ?? '');
$phone    = clean_string($_POST['phone'] ?? '');
$email    = strtolower(clean_string($_POST['email'] ?? ''));

if ($fullName === '' || $phone === '' || $email === '') {
    json_response([
        'ok' => false,
        'message' => 'Please fill in all fields.'
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'ok' => false,
        'message' => 'Invalid email address.'
    ], 422);
}

$otp = generate_otp(6);
$expiresAt = date('Y-m-d H:i:s', time() + 10 * 60);

$conn = db();

$stmt = $conn->prepare("
    SELECT id
    FROM customers
    WHERE email = ?
    LIMIT 1
");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$stmt->close();

if ($existing) {
    $customerId = (int) $existing['id'];

    $stmt = $conn->prepare("
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
    $stmt->bind_param('ssssi', $fullName, $phone, $otp, $expiresAt, $customerId);
    $stmt->execute();
    $stmt->close();
} else {
    $whatsapp = $phone;

    $stmt = $conn->prepare("
        INSERT INTO customers
        (full_name, phone, whatsapp, email, otp_code, otp_expires_at, is_verified, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
    ");
    $stmt->bind_param('ssssss', $fullName, $phone, $whatsapp, $email, $otp, $expiresAt);
    $stmt->execute();
    $customerId = $stmt->insert_id;
    $stmt->close();
}

$subject = 'Your verification code - Click Store KW';
$body = "Hello {$fullName},\n\nYour verification code is: {$otp}\n\nThis code expires in 10 minutes.\n\nClick Store KW";

smtp_send_mail($email, $fullName, $subject, $body);

$_SESSION['pending_customer_id'] = $customerId;
$_SESSION['pending_customer_email'] = $email;

json_response([
    'ok' => true,
    'message' => 'Verification code sent successfully.'
]);
