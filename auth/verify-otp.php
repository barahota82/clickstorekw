<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, [
        'message' => 'Invalid request method.'
    ], 405);
}

$email = strtolower(trim($_POST['email'] ?? ''));
$otp   = trim($_POST['otp'] ?? '');

if ($email === '' || $otp === '') {
    json_response(false, [
        'message' => 'Email and code are required.'
    ], 422);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT id, full_name, phone, email, otp_code, otp_expires_at
    FROM customers
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$customer = $stmt->fetch();

if (!$customer) {
    json_response(false, [
        'message' => 'Customer not found.'
    ], 404);
}

if ((string)$customer['otp_code'] !== $otp) {
    json_response(false, [
        'message' => 'Invalid verification code.'
    ], 422);
}

if (empty($customer['otp_expires_at']) || strtotime((string)$customer['otp_expires_at']) < time()) {
    json_response(false, [
        'message' => 'Verification code expired.'
    ], 422);
}

$customerId = (int)$customer['id'];

$stmt = $pdo->prepare("
    UPDATE customers
    SET is_verified = 1,
        email_verified_at = NOW(),
        otp_code = NULL,
        otp_expires_at = NULL,
        updated_at = NOW()
    WHERE id = ?
");
$stmt->execute([$customerId]);

$_SESSION['customer_auth'] = [
    'id' => $customerId,
    'full_name' => (string)$customer['full_name'],
    'phone' => (string)$customer['phone'],
    'email' => (string)$customer['email'],
    'is_verified' => 1
];

json_response(true, [
    'message' => 'Verification completed successfully.',
    'customer' => $_SESSION['customer_auth']
]);
