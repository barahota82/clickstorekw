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

    $pending = $_SESSION['pending_customer_auth'] ?? null;

    if (!is_array($pending)) {
        json_response(false, ['message' => 'No pending verification request found'], 422);
    }

    $pendingEmail = strtolower(trim((string)($pending['email'] ?? '')));
    if ($pendingEmail === '' || $pendingEmail !== $email) {
        json_response(false, ['message' => 'Email does not match pending verification'], 422);
    }

    $sessionOtp = trim((string)($pending['otp_code'] ?? ''));
    if ($sessionOtp === '' || $sessionOtp !== $otp) {
        json_response(false, ['message' => 'Invalid code'], 422);
    }

    $expiresAt = (string)($pending['otp_expires_at'] ?? '');
    if ($expiresAt !== '') {
        $expiresTs = strtotime($expiresAt);
        if ($expiresTs !== false && $expiresTs < time()) {
            unset($_SESSION['pending_customer_auth']);
            json_response(false, ['message' => 'Code expired'], 422);
        }
    }

    $fullName = trim((string)($pending['full_name'] ?? ''));
    $phone = trim((string)($pending['phone'] ?? ''));
    $whatsappCountryCode = trim((string)($pending['whatsapp_country_code'] ?? ''));
    $whatsappNumber = trim((string)($pending['whatsapp_number'] ?? ''));
    $whatsappFull = trim((string)($pending['whatsapp_full'] ?? ''));
    $whatsapp = trim((string)($pending['whatsapp'] ?? ''));

    if (
        $fullName === '' ||
        $phone === '' ||
        $whatsappCountryCode === '' ||
        $whatsappNumber === '' ||
        $whatsappFull === ''
    ) {
        json_response(false, ['message' => 'Pending registration data is incomplete'], 422);
    }

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
        $update = $pdo->prepare("
            UPDATE customers
            SET
                full_name = ?,
                phone = ?,
                whatsapp_country_code = ?,
                whatsapp_number = ?,
                whatsapp_full = ?,
                whatsapp = ?,
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
            $phone,
            $whatsappCountryCode,
            $whatsappNumber,
            $whatsappFull,
            $whatsapp,
            (int)$existing['id']
        ]);

        $customerId = (int)$existing['id'];
    } else {
        $insert = $pdo->prepare("
            INSERT INTO customers
            (
                full_name,
                phone,
                whatsapp_country_code,
                whatsapp_number,
                whatsapp_full,
                whatsapp,
                email,
                otp_code,
                otp_expires_at,
                is_verified,
                installment_approved,
                email_verified_at,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, 1, 0, NOW(), NOW(), NOW())
        ");

        $insert->execute([
            $fullName,
            $phone,
            $whatsappCountryCode,
            $whatsappNumber,
            $whatsappFull,
            $whatsapp,
            $email
        ]);

        $customerId = (int)$pdo->lastInsertId();
    }

    $_SESSION['customer_auth'] = [
        'id' => $customerId,
        'email' => $email,
        'full_name' => $fullName,
        'whatsapp_full' => $whatsappFull
    ];

    unset($_SESSION['pending_customer_auth']);

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
