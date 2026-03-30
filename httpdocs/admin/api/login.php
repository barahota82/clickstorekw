<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['message' => 'Invalid request method'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

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
            u.password_hash,
            u.is_active,
            r.name AS role_name
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

    $_SESSION['admin_user_id'] = (int)$user['id'];
    $_SESSION['admin_full_name'] = (string)$user['full_name'];
    $_SESSION['admin_username'] = (string)$user['username'];
    $_SESSION['admin_role_name'] = (string)($user['role_name'] ?? 'Unknown');

    json_response(true, [
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => [
            'id' => (int)$user['id'],
            'full_name' => (string)$user['full_name'],
            'username' => (string)$user['username'],
            'role_name' => (string)($user['role_name'] ?? 'Unknown'),
        ]
    ]);
} catch (Throwable $e) {
    json_response(false, ['message' => 'حدث خطأ في تسجيل الدخول'], 500);
}
