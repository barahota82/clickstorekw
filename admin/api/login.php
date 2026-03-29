<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'Admin@12345';

if ($username === '' || $password === '') {
    respond(false, ['message' => 'اسم المستخدم وكلمة المرور مطلوبان.'], 400);
}

if ($username !== $ADMIN_USERNAME || $password !== $ADMIN_PASSWORD) {
    respond(false, ['message' => 'بيانات الدخول غير صحيحة.'], 401);
}

session_regenerate_id(true);
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = $ADMIN_USERNAME;

respond(true, ['message' => 'تم تسجيل الدخول بنجاح.']);
