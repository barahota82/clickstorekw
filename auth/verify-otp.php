<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config.php';

require_post();

$email = strtolower(trim($_POST['email'] ?? ''));
$otp   = trim($_POST['otp'] ?? '');

$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || $user['otp_code'] != $otp) {
    json_response(false, ['message' => 'Invalid code'], 422);
}

$stmt = $pdo->prepare("
    UPDATE customers SET
    is_verified=1,
    otp_code=NULL,
    otp_expires_at=NULL
    WHERE id=?
");
$stmt->execute([$user['id']]);

$_SESSION['customer_auth'] = [
    'id' => $user['id'],
    'email' => $user['email'],
    'full_name' => $user['full_name']
];

json_response(true, [
    'message' => 'Verification completed',
    'customer' => $_SESSION['customer_auth']
]);
