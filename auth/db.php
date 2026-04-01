<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli
{
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $conn = new mysqli(
        'localhost',
        'click_user',
        'Admin@Hem@3282',
        'click_db'
    );

    $conn->set_charset('utf8mb4');
    return $conn;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_response([
            'ok' => false,
            'message' => 'Invalid request method.'
        ], 405);
    }
}

function clean_string(?string $value): string
{
    return trim((string)$value);
}

function generate_otp(int $length = 6): string
{
    $min = 10 ** ($length - 1);
    $max = (10 ** $length) - 1;
    return (string) random_int($min, $max);
}
