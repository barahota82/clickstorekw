<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['message' => 'Invalid request'], 405);
}

$fullName = trim($_POST['full_name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$email    = strtolower(trim($_POST['email'] ?? ''));

if ($fullName === '' || $phone === '' || $email === '') {
    json_response(false, ['message' => 'Fill all fields'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, ['message' => 'Invalid email'], 422);
}

$otp = random_int(100000, 999999);
$expires = date('Y-m-d H:i:s', time() + 600);

$pdo = db();

// check existing
$stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $stmt = $pdo->prepare("
        UPDATE customers SET
        full_name=?,
        phone=?,
        otp_code=?,
        otp_expires_at=?,
        is_verified=0,
        email_verified_at=NULL
        WHERE id=?
    ");
    $stmt->execute([$fullName, $phone, $otp, $expires, $user['id']]);
    $customerId = $user['id'];
} else {
    $stmt = $pdo->prepare("
        INSERT INTO customers
        (full_name, phone, whatsapp, email, otp_code, otp_expires_at, is_verified, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
    ");
    $stmt->execute([$fullName, $phone, $phone, $email, $otp, $expires]);
    $customerId = $pdo->lastInsertId();
}

// ✅ أهم تعديل هنا
try {
    smtp_send_mail(
        $email,
        $fullName,
        'Your verification code',
        "Code: $otp"
    );
} catch (Throwable $e) {
    json_response(false, [
        'message' => 'MAIL ERROR: ' . $e->getMessage()
    ], 500);
}

// session
$_SESSION['pending_customer_id'] = $customerId;

json_response(true, [
    'message' => 'Code sent successfully'
]);
