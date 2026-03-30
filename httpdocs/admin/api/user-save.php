<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['message' => 'Invalid request method'], 405);
}

require_admin_auth_json();

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

$id = (int)($data['id'] ?? 0);
$fullName = trim((string)($data['full_name'] ?? ''));
$username = trim((string)($data['username'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');
$roleId = (int)($data['role_id'] ?? 0);
$isActive = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;

if ($fullName === '' || $username === '' || $email === '' || $roleId <= 0) {
    json_response(false, ['message' => 'Missing required fields'], 422);
}

$pdo = db();

if ($id > 0) {
    $check = $pdo->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
    $check->execute(['id' => $id]);
    if (!$check->fetch()) {
        json_response(false, ['message' => 'User not found'], 404);
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                full_name = :full_name,
                username = :username,
                email = :email,
                password_hash = :password_hash,
                role_id = :role_id,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'password_hash' => $hash,
            'role_id' => $roleId,
            'is_active' => $isActive,
            'id' => $id,
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET
                full_name = :full_name,
                username = :username,
                email = :email,
                role_id = :role_id,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'role_id' => $roleId,
            'is_active' => $isActive,
            'id' => $id,
        ]);
    }

    json_response(true, ['message' => 'تم تحديث المستخدم بنجاح']);
}

if ($password === '') {
    json_response(false, ['message' => 'Password is required for new user'], 422);
}

$checkUsername = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
$checkUsername->execute(['username' => $username]);
if ($checkUsername->fetch()) {
    json_response(false, ['message' => 'Username already exists'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (
        full_name,
        username,
        email,
        password_hash,
        role_id,
        is_active,
        last_login_at,
        created_at,
        updated_at
    ) VALUES (
        :full_name,
        :username,
        :email,
        :password_hash,
        :role_id,
        :is_active,
        NULL,
        NOW(),
        NOW()
    )
");

$stmt->execute([
    'full_name' => $fullName,
    'username' => $username,
    'email' => $email,
    'password_hash' => $hash,
    'role_id' => $roleId,
    'is_active' => $isActive,
]);

json_response(true, ['message' => 'تم إنشاء المستخدم بنجاح']);
