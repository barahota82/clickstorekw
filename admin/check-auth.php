<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/helpers/permissions_helper.php';

if (!is_admin_logged_in()) {
    header('Location: /admin/');
    exit;
}

$adminPageUser = admin_current_user_row();

if (!$adminPageUser || (int)($adminPageUser['is_active'] ?? 0) !== 1) {
    unset(
        $_SESSION['admin_user_id'],
        $_SESSION['admin_full_name'],
        $_SESSION['admin_username'],
        $_SESSION['admin_role_name'],
        $_SESSION['admin_role_id'],
        $_SESSION['admin_permissions']
    );

    header('Location: /admin/');
    exit;
}
