<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
        'message' => 'Name, phone, and email are required.'
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        'ok' => false,
        'message' => 'Invalid email address.'
    ], 422);
}

if (!preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) {
    json_response([
        'ok' => false,
        'message' => 'Invalid phone number.'
    ], 422);
}

$conn = db();

$stmt = $conn->prepare("SELECT id, full_name, phone, email, is_verified FROM customers WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

$otp = generate_otp(6);
$expiresAt = date('Y-m-d H:i:s', time() + 10 * 60);

if ($customer) {
    $customerId = (int)$customer['id'];

    $stmt = $conn->prepare("
        UPDATE customers
        SET full_name = ?, phone = ?, otp_code = ?, otp_expires_at = ?, is_verified = 0, email_verified_at = NULL, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param('ssssi', $fullName, $phone, $otp, $expiresAt, $customerId);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("
        INSERT INTO customers (full_name, phone, email, otp_code, otp_expires_at, is_verified, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $stmt->bind_param('sssss', $fullName, $phone, $email, $otp, $expiresAt);
    $stmt->execute();
    $customerId = $stmt->insert_id;
    $stmt->close();
}

$subject = 'Your Click Store KW verification code';
$body = "Hello {$fullName},

Your verification code is: {$otp}

This code will expire in 10 minutes.

If you did not request this code, please ignore this message.

Click Store KW";

try {
    smtp_send_mail($email, $fullName, $subject, $body);

    $_SESSION['pending_customer_email'] = $email;

    json_response([
        'ok' => true,
        'message' => 'Verification code sent successfully.',
        'email' => $email
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'message' => 'Failed to send verification email.',
        'error' => $e->getMessage()
    ], 500);
}
