<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';

require_post();

$email = strtolower(clean_string($_POST['email'] ?? ''));
$otp   = clean_string($_POST['otp'] ?? '');

if ($email === '' || $otp === '') {
    json_response([
        'ok' => false,
        'message' => 'Email and code are required.'
    ], 422);
}

$conn = db();

$stmt = $conn->prepare("
    SELECT id, full_name, phone, email, otp_code, otp_expires_at
    FROM customers
    WHERE email = ?
    LIMIT 1
");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    json_response([
        'ok' => false,
        'message' => 'Customer not found.'
    ], 404);
}

if ((string)$customer['otp_code'] !== $otp) {
    json_response([
        'ok' => false,
        'message' => 'Invalid verification code.'
    ], 422);
}

if (empty($customer['otp_expires_at']) || strtotime((string)$customer['otp_expires_at']) < time()) {
    json_response([
        'ok' => false,
        'message' => 'Verification code expired.'
    ], 422);
}

$customerId = (int)$customer['id'];

$stmt = $conn->prepare("
    UPDATE customers
    SET is_verified = 1,
        email_verified_at = CURRENT_TIMESTAMP,
        otp_code = NULL,
        otp_expires_at = NULL,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
");
$stmt->bind_param('i', $customerId);
$stmt->execute();
$stmt->close();

$_SESSION['customer_auth'] = [
    'id' => $customerId,
    'full_name' => (string)$customer['full_name'],
    'phone' => (string)$customer['phone'],
    'email' => (string)$customer['email'],
    'is_verified' => 1
];

json_response([
    'ok' => true,
    'message' => 'Verification completed successfully.',
    'customer' => $_SESSION['customer_auth']
]);
