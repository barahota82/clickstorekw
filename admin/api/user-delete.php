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

if ($id <= 0) {
    json_response(false, ['message' => 'Invalid user id'], 422);
}

if ($id === (int)$_SESSION['admin_user_id']) {
    json_response(false, ['message' => 'لا يمكن حذف المستخدم الحالي'], 422);
}

$pdo = db();

$stmt = $pdo->prepare("DELETE FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);

if ($stmt->rowCount() === 0) {
    json_response(false, ['message' => 'User not found'], 404);
}

json_response(true, ['message' => 'تم حذف المستخدم بنجاح']);
