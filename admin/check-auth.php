<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/helpers/permissions_helper.php';

$authorized = false;

if (is_admin_logged_in()) {
    $user = admin_current_user_row();
    $authorized = $user && (int)($user['is_active'] ?? 0) === 1;
}

if ($authorized) {
    return;
}

unset(
    $_SESSION['admin_user_id'],
    $_SESSION['admin_full_name'],
    $_SESSION['admin_username'],
    $_SESSION['admin_role_name'],
    $_SESSION['admin_role_id'],
    $_SESSION['admin_permissions']
);

http_response_code(401);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>Redirecting...</title>
</head>
<body>
<script>
(function () {
  var target = '/admin/';
  try {
    if (window.top && window.top !== window.self) {
      window.top.location.href = target;
      return;
    }
  } catch (e) {}
  window.location.href = target;
})();
</script>
<noscript>
  <meta http-equiv="refresh" content="0;url=/admin/">
  <p>يتم تحويلك إلى صفحة تسجيل الدخول...</p>
</noscript>
</body>
</html>
