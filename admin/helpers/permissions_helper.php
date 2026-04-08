<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

function admin_current_user_id(): int
{
    return isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : 0;
}

function admin_current_user_row(): ?array
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }

    $loaded = true;

    $userId = admin_current_user_id();
    if ($userId <= 0) {
        $cached = null;
        return null;
    }

    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.full_name,
            u.username,
            u.email,
            u.role_id,
            u.is_active,
            u.last_login_at,
            r.name AS role_name,
            r.code AS role_code
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    $cached = $row ?: null;
    return $cached;
}

function admin_permission_alias_map(): array
{
    return [
        // Frontend tab/action aliases used in admin/index.php
        'ocr_view'            => ['view_ocr', 'manage_ocr'],
        'products_edit'       => ['edit_products', 'manage_hot_offers', 'create_products'],
        'products_delete'     => ['delete_products'],
        'hot_offers_order'    => ['view_hot_offers', 'manage_hot_offers'],
        'brands_order'        => ['view_brand_ordering', 'manage_brand_ordering'],
        'products_order'      => ['view_product_ordering', 'manage_product_ordering'],
        'stock_manage'        => ['view_stock', 'manage_stock', 'manage_stock_movements'],
        'orders_view'         => ['view_orders', 'manage_orders', 'change_order_status', 'view_analytics'],
        'orders_manage'       => ['manage_orders', 'change_order_status'],
        'users_view'          => ['view_users', 'manage_users', 'view_roles', 'manage_roles', 'view_permissions', 'manage_permissions'],
        'users_manage'        => ['manage_users', 'manage_roles', 'manage_permissions'],
        'admin.full_access'   => ['*'],

        // Optional direct action aliases
        'order.approve'       => ['change_order_status', 'manage_orders'],
        'order.reject'        => ['change_order_status', 'manage_orders'],
        'order.on_the_way'    => ['change_order_status', 'manage_orders'],
        'order.deliver'       => ['change_order_status', 'manage_orders'],
        'order.pending'       => ['change_order_status', 'manage_orders'],
        'order.cancel'        => ['change_order_status', 'manage_orders'],

        'reports_view'        => ['view_analytics'],
        'settings_view'       => ['view_settings', 'manage_settings'],
        'settings_manage'     => ['manage_settings'],
    ];
}

function admin_role_permission_codes(int $roleId): array
{
    static $cache = [];

    if (isset($cache[$roleId])) {
        return $cache[$roleId];
    }

    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT p.code
        FROM role_permissions rp
        INNER JOIN permissions p ON p.id = rp.permission_id
        WHERE rp.role_id = ?
    ");
    $stmt->execute([$roleId]);

    $codes = [];
    foreach ($stmt->fetchAll() as $row) {
        $code = trim((string)($row['code'] ?? ''));
        if ($code !== '') {
            $codes[$code] = true;
        }
    }

    $cache[$roleId] = array_keys($codes);
    return $cache[$roleId];
}

function admin_user_permission_overrides(int $userId): array
{
    static $cache = [];

    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            p.code,
            up.is_allowed
        FROM user_permissions up
        INNER JOIN permissions p ON p.id = up.permission_id
        WHERE up.user_id = ?
    ");
    $stmt->execute([$userId]);

    $allowed = [];
    $denied = [];

    foreach ($stmt->fetchAll() as $row) {
        $code = trim((string)($row['code'] ?? ''));
        if ($code === '') {
            continue;
        }

        if ((int)$row['is_allowed'] === 1) {
            $allowed[$code] = true;
            unset($denied[$code]);
        } else {
            $denied[$code] = true;
            unset($allowed[$code]);
        }
    }

    $cache[$userId] = [
        'allowed' => array_keys($allowed),
        'denied'  => array_keys($denied),
    ];

    return $cache[$userId];
}

function admin_effective_permission_codes(?array $user = null): array
{
    static $cache = null;

    if ($user === null && $cache !== null) {
        return $cache;
    }

    $user = $user ?? admin_current_user_row();
    if (!$user) {
        return [];
    }

    $roleCode = trim((string)($user['role_code'] ?? ''));
    if ($roleCode === 'super_admin') {
        $all = admin_all_permission_codes();
        $all[] = '*';
        if ($user === admin_current_user_row()) {
            $cache = $all;
        }
        return $all;
    }

    $roleId = (int)($user['role_id'] ?? 0);
    $userId = (int)($user['id'] ?? 0);

    $effective = [];
    foreach (admin_role_permission_codes($roleId) as $code) {
        $effective[$code] = true;
    }

    $overrides = admin_user_permission_overrides($userId);

    foreach ($overrides['allowed'] as $code) {
        $effective[$code] = true;
    }

    foreach ($overrides['denied'] as $code) {
        unset($effective[$code]);
    }

    $result = array_keys($effective);

    if ($user === admin_current_user_row()) {
        $cache = $result;
    }

    return $result;
}

function admin_all_permission_codes(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $pdo = db();
    $stmt = $pdo->query("SELECT code FROM permissions ORDER BY id ASC");

    $codes = [];
    foreach ($stmt->fetchAll() as $row) {
        $code = trim((string)($row['code'] ?? ''));
        if ($code !== '') {
            $codes[] = $code;
        }
    }

    $cache = $codes;
    return $cache;
}

function admin_has_permission(string $permissionCode): bool
{
    $permissionCode = trim($permissionCode);
    if ($permissionCode === '') {
        return false;
    }

    $user = admin_current_user_row();
    if (!$user) {
        return false;
    }

    $effective = admin_effective_permission_codes($user);
    $effectiveMap = array_fill_keys($effective, true);

    if (isset($effectiveMap['*'])) {
        return true;
    }

    if (isset($effectiveMap[$permissionCode])) {
        return true;
    }

    $aliases = admin_permission_alias_map();
    if (isset($aliases[$permissionCode])) {
        foreach ($aliases[$permissionCode] as $mappedCode) {
            if ($mappedCode === '*') {
                return true;
            }
            if (isset($effectiveMap[$mappedCode])) {
                return true;
            }
        }
    }

    return false;
}

function admin_require_permission_json(string $permissionCode, string $message = 'Forbidden'): void
{
    require_admin_auth_json();

    if (!admin_has_permission($permissionCode)) {
        json_response(false, ['message' => $message], 403);
    }
}

function admin_frontend_permissions_payload(): array
{
    $aliases = array_keys(admin_permission_alias_map());
    $payload = [];

    foreach ($aliases as $alias) {
        $payload[$alias] = admin_has_permission($alias);
    }

    foreach (admin_all_permission_codes() as $code) {
        $payload[$code] = admin_has_permission($code);
    }

    return $payload;
}

function admin_activity_log(
    string $action,
    string $module,
    ?string $referenceType = null,
    ?int $referenceId = null,
    ?string $details = null
): void {
    try {
        $pdo = db();

        $stmt = $pdo->prepare("
            INSERT INTO activity_logs
            (
                user_id,
                action,
                module,
                reference_type,
                reference_id,
                details,
                ip_address,
                created_at
            )
            VALUES
            (
                :user_id,
                :action,
                :module,
                :reference_type,
                :reference_id,
                :details,
                :ip_address,
                NOW()
            )
        ");

        $stmt->execute([
            'user_id'        => admin_current_user_id() > 0 ? admin_current_user_id() : null,
            'action'         => $action,
            'module'         => $module,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'details'        => $details,
            'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        // Ignore logging failures to avoid breaking core actions
    }
}
