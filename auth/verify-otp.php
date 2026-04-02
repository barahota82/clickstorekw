<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

try {
    require_post();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $otp   = trim($_POST['otp'] ?? '');
    $verificationToken = trim($_POST['verification_token'] ?? '');

    if ($email === '' || $otp === '' || $verificationToken === '') {
        json_response(false, ['message' => 'Email, code and verification token are required'], 422);
    }

    $parts = explode('.', $verificationToken, 2);
    if (count($parts) !== 2) {
        json_response(false, ['message' => 'Invalid verification token'], 422);
    }

    [$encodedPayload, $providedSignature] = $parts;

    $jsonPayload = base64_decode($encodedPayload, true);
    if ($jsonPayload === false || $jsonPayload === '') {
        json_response(false, ['message' => 'Invalid verification token'], 422);
    }

    $secret = hash('sha256', DB_HOST . '|' . DB_NAME . '|' . DB_USER . '|' . __FILE__);
    $expectedSignature = hash_hmac('sha256', $jsonPayload, $secret);

    if (!hash_equals($expectedSignature, $providedSignature)) {
        json_response(false, ['message' => 'Invalid verification token'], 422);
    }

    $payload = json_decode($jsonPayload, true);
    if (!is_array($payload)) {
        json_response(false, ['message' => 'Invalid verification token'], 422);
    }

    $tokenEmail = strtolower(trim((string)($payload['email'] ?? '')));
    $tokenOtp = trim((string)($payload['otp'] ?? ''));
    $fullName = trim((string)($payload['full_name'] ?? ''));
    $countryCode = trim((string)($payload['whatsapp_country_code'] ?? ''));
    $whatsappNumber = trim((string)($payload['whatsapp_number'] ?? ''));
    $whatsappFull = trim((string)($payload['whatsapp_full'] ?? ''));
    $expiresTs = (int)($payload['expires_ts'] ?? 0);

    if (
        $tokenEmail === '' ||
        $tokenOtp === '' ||
        $fullName === '' ||
        $countryCode === '' ||
        $whatsappNumber === '' ||
        $whatsappFull === '' ||
        $expiresTs <= 0
    ) {
        json_response(false, ['message' => 'Invalid verification token'], 422);
    }

    if ($tokenEmail !== $email) {
        json_response(false, ['message' => 'Email does not match verification token'], 422);
    }

    if (!hash_equals($tokenOtp, $otp)) {
        json_response(false, ['message' => 'Invalid code'], 422);
    }

    if ($expiresTs < time()) {
        json_response(false, ['message' => 'Code expired'], 422);
    }

    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            id,
            email,
            full_name,
            whatsapp_full,
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

    $_SESSION['customer_auth'] = [
        'id' => $customerId,
        'email' => $email,
        'full_name' => $fullName,
        'whatsapp_full' => $whatsappFull
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
