<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(
        array_merge(['ok' => $ok], $data),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$configPath = dirname(__DIR__) . '/config.php';

if (!file_exists($configPath)) {
    respond(false, [
        'message' => 'Config file not found',
        'expected_path' => $configPath
    ], 500);
}

$config = require $configPath;

$ADMIN_USERNAME = trim($config['admin_username'] ?? '');
$ADMIN_PASSWORD_HASH = trim($config['admin_password_hash'] ?? '');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if ($username === '' || $password === '') {
    respond(false, ['message' => 'اسم المستخدم وكلمة المرور مطلوبان.'], 400);
}

if ($ADMIN_USERNAME === '' || $ADMIN_PASSWORD_HASH === '') {
    respond(false, ['message' => 'Admin config is incomplete.'], 500);
}

if ($username !== $ADMIN_USERNAME || !password_verify($password, $ADMIN_PASSWORD_HASH)) {
    respond(false, ['message' => 'بيانات الدخول غير صحيحة.'], 401);
}

session_regenerate_id(true);
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = $ADMIN_USERNAME;

respond(true, ['message' => 'تم تسجيل الدخول بنجاح.']);
