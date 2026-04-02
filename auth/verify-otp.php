<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

require_post();

$email = strtolower(trim($_POST['email'] ?? ''));
$otp   = trim($_POST['otp'] ?? '');

if ($email === '' || $otp === '') {
    json_response(false, ['message' => 'Email and code are required'], 422);
}

$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    json_response(false, ['message' => 'Customer not found'], 404);
}

if ((string)$user['otp_code'] !== $otp) {
    json_response(false, ['message' => 'Invalid code'], 422);
}

if (!empty($user['otp_expires_at']) && strtotime((string)$user['otp_expires_at']) < time()) {
    json_response(false, ['message' => 'Code expired'], 422);
}

$stmt = $pdo->prepare("
    UPDATE customers SET
        is_verified = 1,
        otp_code = NULL,
        otp_expires_at = NULL,
        email_verified_at = NOW(),
        updated_at = NOW()
    WHERE id = ?
");
$stmt->execute([(int)$user['id']]);

$_SESSION['customer_auth'] = [
    'id' => (int)$user['id'],
    'email' => (string)$user['email'],
    'full_name' => (string)$user['full_name'],
    'whatsapp_full' => (string)($user['whatsapp_full'] ?? '')
];

json_response(true, [
    'message' => 'Verification completed',
    'customer' => $_SESSION['customer_auth']
]);


