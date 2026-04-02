<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

try {
    require_post();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $otp   = trim($_POST['otp'] ?? '');

    if ($email === '' || $otp === '') {
        json_response(false, ['message' => 'Email and code are required'], 422);
    }

    $pendingRegistration = $_SESSION['pending_customer_registration'] ?? null;
$pendingEmail = strtolower(trim((string)($_SESSION['pending_customer_email'] ?? '')));
$pendingOtp = trim((string)($_SESSION['pending_customer_otp'] ?? ''));
$pendingExpiresAt = (string)($_SESSION['pending_customer_otp_expires_at'] ?? '');

if (!is_array($pendingRegistration)) {
    json_response(false, ['message' => 'Customer not found'], 404);
}

if ($pendingEmail === '' || $pendingEmail !== $email) {
    json_response(false, ['message' => 'Customer not found'], 404);
}

if ($pendingOtp === '' || $pendingOtp !== $otp) {
    json_response(false, ['message' => 'Invalid code'], 422);
}

if ($pendingExpiresAt !== '') {
    $expiresTs = strtotime($pendingExpiresAt);
    if ($expiresTs !== false && $expiresTs < time()) {
        json_response(false, ['message' => 'Code expired'], 422);
    }
}

$fullName = trim((string)($pendingRegistration['full_name'] ?? ''));
$countryCode = trim((string)($pendingRegistration['whatsapp_country_code'] ?? ''));
$whatsappNumber = trim((string)($pendingRegistration['whatsapp_number'] ?? ''));
$whatsappFull = trim((string)($pendingRegistration['whatsapp_full'] ?? ''));

if ($fullName === '' || $countryCode === '' || $whatsappNumber === '' || $whatsappFull === '') {
    json_response(false, ['message' => 'Customer not found'], 404);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT
        id,
        email,
        full_name,
        whatsapp_full,
        otp_code,
        otp_expires_at,
        is_verified
    FROM customers
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && (int)($user['is_verified'] ?? 0) === 1) {
    $_SESSION['customer_auth'] = [
        'id' => (int)$user['id'],
        'email' => (string)$user['email'],
        'full_name' => (string)$user['full_name'],
        'whatsapp_full' => (string)($user['whatsapp_full'] ?? '')
    ];

    unset($_SESSION['pending_customer_registration']);
    unset($_SESSION['pending_customer_email']);
    unset($_SESSION['pending_customer_otp']);
    unset($_SESSION['pending_customer_otp_expires_at']);

    json_response(true, [
        'message' => 'Account already verified',
        'customer' => $_SESSION['customer_auth']
    ]);
}

    if ($user) {
    $update = $pdo->prepare("
        UPDATE customers
        SET
            full_name = ?,
            whatsapp_country_code = ?,
            whatsapp_number = ?,
            whatsapp_full = ?,
            otp_code = NULL,
            otp_expires_at = NULL,
            is_verified = 1,
            email_verified_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");

    $update->execute([
        $fullName,
        $countryCode,
        $whatsappNumber,
        $whatsappFull,
        (int)$user['id']
    ]);

    $customerId = (int)$user['id'];
} else {
    $insert = $pdo->prepare("
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
            email_verified_at,
            created_at,
            updated_at
        )
        VALUES (?, ?, ?, ?, ?, NULL, NULL, 1, 0, NOW(), NOW(), NOW())
    ");

    $insert->execute([
        $fullName,
        $countryCode,
        $whatsappNumber,
        $whatsappFull,
        $email
    ]);

    $customerId = (int)$pdo->lastInsertId();
}

$_SESSION['pending_customer_id'] = $customerId;

$_SESSION['customer_auth'] = [
    'id' => $customerId,
    'email' => $email,
    'full_name' => $fullName,
    'whatsapp_full' => $whatsappFull
];

unset($_SESSION['pending_customer_registration']);
unset($_SESSION['pending_customer_email']);
unset($_SESSION['pending_customer_otp']);
unset($_SESSION['pending_customer_otp_expires_at']);

json_response(true, [
    'message' => 'Verification completed',
    'customer' => $_SESSION['customer_auth']
]);
    
} catch (Throwable $e) {
    json_response(false, [
        'message' => 'Verification failed',
        'error' => $e->getMessage()
    ], 500);
}
