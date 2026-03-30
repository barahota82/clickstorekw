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

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET time_zone = '+03:00'");
    $pdo->exec("SET NAMES utf8mb4");

    return $pdo;
}

function json_response(bool $ok, array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_user_id']) && (int)$_SESSION['admin_user_id'] > 0;
}

function require_admin_auth_json(): void
{
    if (!is_admin_logged_in()) {
        json_response(false, ['message' => 'Unauthorized'], 401);
    }
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
