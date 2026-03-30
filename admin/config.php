<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kuwait');

const DB_HOST = 'localhost';
const DB_NAME = 'click_db';
const DB_USER = 'clickst1_click_user';
const DB_PASS = 'ضع_هنا_كلمة_مرور_قاعدة_البيانات';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}

function json_response(bool $ok, array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        array_merge(['ok' => $ok], $data),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_user_id']);
}

function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
        header('Location: /admin/index.php');
        exit;
    }
}
