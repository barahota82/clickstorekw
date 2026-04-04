<?php
declare(strict_types=1);

$env = require __DIR__ . '/.env.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kuwait');

/*
|--------------------------------------------------------------------------
| Load Sensitive Config
|--------------------------------------------------------------------------
*/
$envPath = __DIR__ . '/.env.php';

if (!file_exists($envPath)) {
    die('Missing .env.php configuration file');
}

$env = require $envPath;

/*
|--------------------------------------------------------------------------
| Database Config
|--------------------------------------------------------------------------
*/
const DB_HOST = 'localhost';
const DB_NAME = 'click_db';

define('DB_USER', $env['DB_USER'] ?? '');
define('DB_PASS', $env['DB_PASS'] ?? '');

/*
|--------------------------------------------------------------------------
| PDO Connection
|--------------------------------------------------------------------------
*/
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
    echo json_encode(
        array_merge(['ok' => $ok], $data),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function require_method(string $method): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($method)) {
        json_response(false, ['message' => 'Invalid request method'], 405);
    }
}

function require_post(): void
{
    require_method('POST');
}

function get_request_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
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

function get_setting(string $key, ?string $default = null): ?string
{
    static $cache = [];

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT setting_value
        FROM settings
        WHERE setting_key = :setting_key
        LIMIT 1
    ");
    $stmt->execute(['setting_key' => $key]);
    $row = $stmt->fetch();

    $cache[$key] = $row ? (string)$row['setting_value'] : $default;
    return $cache[$key];
}

function get_setting_bool(string $key, bool $default = false): bool
{
    $value = get_setting($key, $default ? '1' : '0');

    if ($value === null) {
        return $default;
    }

    $normalized = strtolower(trim($value));

    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

function current_customer_id(): int
{
    return isset($_SESSION['customer_auth']['id'])
        ? (int)$_SESSION['customer_auth']['id']
        : 0;
}

function require_customer_auth_json(): void
{
    if (current_customer_id() <= 0) {
        json_response(false, ['message' => 'Login required'], 401);
    }
}

function parse_money_to_decimal($value): float
{
    if (is_numeric($value)) {
        return round((float)$value, 3);
    }

    $raw = trim((string)$value);
    if ($raw === '') {
        return 0.0;
    }

    if (preg_match('/[\d.]+/', $raw, $m)) {
        return round((float)$m[0], 3);
    }

    return 0.0;
}

function generate_order_number(): string
{
    $now = new DateTime('now', new DateTimeZone('Asia/Kuwait'));
    return 'CLK-' . $now->format('Ymd-His') . '-' . random_int(100, 999);
}
