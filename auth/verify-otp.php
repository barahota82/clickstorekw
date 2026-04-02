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

    if (!$user) {
        json_response(false, ['message' => 'Customer not found'], 404);
    }

    if ((int)($user['is_verified'] ?? 0) === 1) {
        $_SESSION['customer_auth'] = [
            'id' => (int)$user['id'],
            'email' => (string)$user['email'],
            'full_name' => (string)$user['full_name'],
            'whatsapp_full' => (string)($user['whatsapp_full'] ?? '')
        ];

        json_response(true, [
            'message' => 'Account already verified',
            'customer' => $_SESSION['customer_auth']
        ]);
    }

    $dbOtp = trim((string)($user['otp_code'] ?? ''));
    if ($dbOtp === '' || $dbOtp !== $otp) {
        json_response(false, ['message' => 'Invalid code'], 422);
    }

    $expiresAt = (string)($user['otp_expires_at'] ?? '');
    if ($expiresAt !== '') {
        $expiresTs = strtotime($expiresAt);
        if ($expiresTs !== false && $expiresTs < time()) {
            json_response(false, ['message' => 'Code expired'], 422);
        }
    }

    $update = $pdo->prepare("
        UPDATE customers
        SET
            is_verified = 1,
            otp_code = NULL,
            otp_expires_at = NULL,
            email_verified_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    $update->execute([(int)$user['id']]);

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
} catch (Throwable $e) {
    json_response(false, [
        'message' => 'Verification failed',
        'error' => $e->getMessage()
    ], 500);
}
