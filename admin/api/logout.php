<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['message' => 'Invalid request method'], 405);
}

/*
========================================
  CLEAR ADMIN SESSION KEYS (ADDED ONLY)
========================================
*/
unset(
    $_SESSION['admin_user_id'],
    $_SESSION['admin_full_name'],
    $_SESSION['admin_username'],
    $_SESSION['admin_role_name'],
    $_SESSION['admin_role_id'],
    $_SESSION['admin_permissions']
);

/*
========================================
  ORIGINAL LOGIC (UNCHANGED)
========================================
*/
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        (bool)$params['secure'],
        (bool)$params['httponly']
    );
}

session_destroy();

json_response(true, ['message' => 'تم تسجيل الخروج بنجاح']);
