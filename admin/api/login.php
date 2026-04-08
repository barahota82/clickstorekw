<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_post();

$data = get_request_json();

$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    json_response(false, ['message' => 'اسم المستخدم وكلمة المرور مطلوبان'], 422);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.full_name,
            u.username,
            u.email,
            u.password_hash,
            u.is_active,
            u.role_id,
            u.last_login_at,
            r.name AS role_name,
            r.code AS role_code
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.username = :username
        LIMIT 1
    ");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(false, ['message' => 'بيانات الدخول غير صحيحة'], 401);
    }

    if ((int)$user['is_active'] !== 1) {
        json_response(false, ['message' => 'هذا المستخدم غير مفعل'], 403);
    }

    if (!password_verify($password, (string)$user['password_hash'])) {
        json_response(false, ['message' => 'بيانات الدخول غير صحيحة'], 401);
    }

    session_regenerate_id(true);

    $_SESSION['admin_user_id'] = (int)$user['id'];
    $_SESSION['admin_full_name'] = (string)$user['full_name'];
    $_SESSION['admin_username'] = (string)$user['username'];
    $_SESSION['admin_role_name'] = (string)($user['role_name'] ?? '');
    $_SESSION['admin_role_id'] = (int)($user['role_id'] ?? 0);

    $effectivePermissionCodes = admin_effective_permission_codes([
        'id' => (int)$user['id'],
        'full_name' => (string)$user['full_name'],
        'username' => (string)$user['username'],
        'email' => (string)($user['email'] ?? ''),
        'role_id' => (int)($user['role_id'] ?? 0),
        'role_name' => (string)($user['role_name'] ?? ''),
        'role_code' => (string)($user['role_code'] ?? ''),
        'is_active' => (int)($user['is_active'] ?? 0),
        'last_login_at' => (string)($user['last_login_at'] ?? ''),
    ]);

    $_SESSION['admin_permissions'] = $effectivePermissionCodes;

    $updateLastLogin = $pdo->prepare("
        UPDATE users
        SET last_login_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    $updateLastLogin->execute([(int)$user['id']]);

    admin_activity_log(
        'login',
        'auth',
        'user',
        (int)$user['id'],
        'Admin login successful'
    );

    json_response(true, [
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => [
            'id' => (int)$user['id'],
            'full_name' => (string)$user['full_name'],
            'username' => (string)$user['username'],
            'email' => (string)($user['email'] ?? ''),
            'role_name' => (string)($user['role_name'] ?? ''),
            'role_code' => (string)($user['role_code'] ?? ''),
            'role_id' => (int)($user['role_id'] ?? 0),
        ],
        'permissions' => admin_frontend_permissions_payload(),
        'permission_codes' => $effectivePermissionCodes
    ]);
} catch (Throwable $e) {
    json_response(false, ['message' => 'حدث خطأ في تسجيل الدخول'], 500);
}
