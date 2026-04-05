<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

if (!function_exists('admin_normalize_permission_key')) {
    function admin_normalize_permission_key(string $value): string
    {
        return strtolower(trim($value));
    }
}

if (!function_exists('admin_permissions_from_session')) {
    function admin_permissions_from_session(): array
    {
        $sessionCandidates = [
            $_SESSION['admin_permissions'] ?? null,
            $_SESSION['permissions'] ?? null,
            $_SESSION['admin_user_permissions'] ?? null,
        ];

        foreach ($sessionCandidates as $candidate) {
            if (is_array($candidate)) {
                return array_values(array_unique(array_filter(array_map(
                    static fn($item) => admin_normalize_permission_key((string)$item),
                    $candidate
                ))));
            }
        }

        return [];
    }
}

if (!function_exists('admin_is_full_access_role')) {
    function admin_is_full_access_role(string $roleName): bool
    {
        $roleName = strtolower(trim($roleName));
        return in_array($roleName, ['admin', 'super_admin', 'super admin'], true);
    }
}

if (!function_exists('admin_table_exists')) {
    function admin_table_exists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
        ");
        $stmt->execute(['table_name' => $tableName]);
        return (int)$stmt->fetchColumn() > 0;
    }
}

if (!function_exists('admin_get_table_columns')) {
    function admin_get_table_columns(PDO $pdo, string $tableName): array
    {
        $stmt = $pdo->prepare("
            SELECT COLUMN_NAME
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table_name
        ");
        $stmt->execute(['table_name' => $tableName]);

        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return is_array($columns) ? $columns : [];
    }
}

if (!function_exists('admin_pick_existing_column')) {
    function admin_pick_existing_column(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }
        return null;
    }
}

if (!function_exists('admin_permissions_from_database')) {
    function admin_permissions_from_database(int $roleId): array
    {
        if ($roleId <= 0) {
            return [];
        }

        try {
            $pdo = db();

            if (
                !admin_table_exists($pdo, 'permissions') ||
                !admin_table_exists($pdo, 'role_permissions')
            ) {
                return [];
            }

            $permissionsColumns = admin_get_table_columns($pdo, 'permissions');
            $rolePermissionsColumns = admin_get_table_columns($pdo, 'role_permissions');

            $permissionIdColumn = admin_pick_existing_column($permissionsColumns, ['id', 'permission_id']);
            $permissionKeyColumn = admin_pick_existing_column($permissionsColumns, ['permission_key', 'slug', 'code', 'name']);

            $rpRoleIdColumn = admin_pick_existing_column($rolePermissionsColumns, ['role_id', 'roles_id']);
            $rpPermissionIdColumn = admin_pick_existing_column($rolePermissionsColumns, ['permission_id', 'permissions_id']);

            if (!$permissionIdColumn || !$permissionKeyColumn || !$rpRoleIdColumn || !$rpPermissionIdColumn) {
                return [];
            }

            $sql = "
                SELECT p.`{$permissionKeyColumn}` AS permission_key
                FROM `role_permissions` rp
                INNER JOIN `permissions` p
                    ON p.`{$permissionIdColumn}` = rp.`{$rpPermissionIdColumn}`
                WHERE rp.`{$rpRoleIdColumn}` = :role_id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['role_id' => $roleId]);

            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return array_values(array_unique(array_filter(array_map(
                static fn($item) => admin_normalize_permission_key((string)$item),
                is_array($rows) ? $rows : []
            ))));
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('admin_resolve_permissions')) {
    function admin_resolve_permissions(): array
    {
        $roleName = (string)($_SESSION['admin_role_name'] ?? '');
        $roleId = (int)($_SESSION['admin_role_id'] ?? 0);

        $permissions = admin_permissions_from_session();

        if (!empty($permissions)) {
            return $permissions;
        }

        if (admin_is_full_access_role($roleName)) {
            return ['admin.full_access'];
        }

        if ($roleId > 0) {
            $permissions = admin_permissions_from_database($roleId);
            if (!empty($permissions)) {
                return $permissions;
            }
        }

        return [];
    }
}

if (!function_exists('admin_has_any_permission')) {
    function admin_has_any_permission(array $requiredPermissions): bool
    {
        $granted = admin_resolve_permissions();

        if (in_array('admin.full_access', $granted, true)) {
            return true;
        }

        $grantedMap = array_fill_keys($granted, true);

        foreach ($requiredPermissions as $permission) {
            $permission = admin_normalize_permission_key((string)$permission);
            if ($permission !== '' && isset($grantedMap[$permission])) {
                return true;
            }
        }

        return false;
    }
}

require_method('GET');
require_admin_auth_json();

$requiredPermissions = [
    'orders.view',
    'view_orders',
    'manage_orders',
    'orders_manage',
    'admin.full_access'
];

if (!admin_has_any_permission($requiredPermissions)) {
    json_response(false, ['message' => 'ليس لديك صلاحية لعرض الطلبات.'], 403);
}

$pdo = db();

$search = trim((string)($_GET['search'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$date = trim((string)($_GET['date'] ?? ''));

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(
        o.order_number LIKE :search
        OR o.customer_name_snapshot LIKE :search
        OR o.customer_email_snapshot LIKE :search
        OR o.customer_whatsapp_snapshot LIKE :search
    )";
    $params['search'] = '%' . $search . '%';
}

if ($status !== '') {
    $allowedStatuses = ['pending', 'approved', 'on_the_way', 'completed', 'cancelled', 'rejected'];
    if (!in_array($status, $allowedStatuses, true)) {
        json_response(false, ['message' => 'Invalid status filter'], 422);
    }

    $where[] = "o.status = :status";
    $params['status'] = $status;
}

if ($date !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date) {
        json_response(false, ['message' => 'Invalid date filter'], 422);
    }

    $where[] = "DATE(o.created_at) = :order_date";
    $params['order_date'] = $date;
}

$sql = "
    SELECT
        o.id,
        o.order_number,
        o.status,
        o.rejection_reason,
        o.customer_name_snapshot,
        o.customer_email_snapshot,
        o.customer_whatsapp_snapshot,
        o.subtotal_amount,
        o.discount_amount,
        o.delivery_amount,
        o.total_amount,
        o.currency_code,
        o.is_first_order,
        o.has_promotional_gift,
        o.gift_label,
        o.created_at,
        o.updated_at
    FROM orders o
";

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY o.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

if (!$orders) {
    json_response(true, [
        'orders' => [],
        'summary' => [
            'all' => 0,
            'pending' => 0,
            'delivered' => 0,
            'rejected_cancelled' => 0
        ]
    ]);
}

$orderIds = array_map(static fn($row) => (int)$row['id'], $orders);
$placeholders = implode(',', array_fill(0, count($orderIds), '?'));

$itemStmt = $pdo->prepare("
    SELECT
        order_id,
        product_title,
        product_image,
        qty,
        down_payment,
        monthly_amount,
        duration_months,
        devices_count,
        line_total
    FROM order_items
    WHERE order_id IN ($placeholders)
    ORDER BY id ASC
");
$itemStmt->execute($orderIds);
$itemRows = $itemStmt->fetchAll();

$itemsByOrder = [];
foreach ($itemRows as $row) {
    $itemsByOrder[(int)$row['order_id']][] = [
        'title' => (string)$row['product_title'],
        'image' => (string)($row['product_image'] ?? ''),
        'quantity' => (int)$row['qty'],
        'down_payment' => ((float)$row['down_payment']) . ' KD Down Payment',
        'monthly' => ((float)$row['monthly_amount']) . ' KD',
        'duration' => (int)$row['duration_months'] . ' Months',
        'devices_count' => (int)$row['devices_count'],
        'total_price' => ((float)$row['line_total']) . ' KD'
    ];
}

$mapped = [];
$summary = [
    'all' => 0,
    'pending' => 0,
    'delivered' => 0,
    'rejected_cancelled' => 0
];

foreach ($orders as $order) {
    $summary['all']++;

    $rawStatus = (string)$order['status'];
    $frontendStatus = 'Pending';

    if ($rawStatus === 'completed') {
        $frontendStatus = 'Delivered';
        $summary['delivered']++;
    } elseif ($rawStatus === 'cancelled') {
        $frontendStatus = 'Cancelled';
        $summary['rejected_cancelled']++;
    } elseif ($rawStatus === 'rejected') {
        $frontendStatus = 'Rejected';
        $summary['rejected_cancelled']++;
    } elseif ($rawStatus === 'on_the_way') {
        $frontendStatus = 'On The Way';
        $summary['pending']++;
    } elseif ($rawStatus === 'approved') {
        $frontendStatus = 'Approved';
        $summary['pending']++;
    } else {
        $frontendStatus = 'Pending';
        $summary['pending']++;
    }

    $mapped[] = [
        'id' => (int)$order['id'],
        'order_number' => (string)$order['order_number'],
        'status' => $frontendStatus,
        'raw_status' => $rawStatus,
        'rejection_reason' => (string)($order['rejection_reason'] ?? ''),
        'customer_name' => (string)($order['customer_name_snapshot'] ?? ''),
        'customer_email' => (string)($order['customer_email_snapshot'] ?? ''),
        'customer_whatsapp' => (string)($order['customer_whatsapp_snapshot'] ?? ''),
        'subtotal_amount' => (float)$order['subtotal_amount'],
        'discount_amount' => (float)$order['discount_amount'],
        'delivery_amount' => (float)$order['delivery_amount'],
        'total_amount' => (float)$order['total_amount'],
        'currency_code' => (string)($order['currency_code'] ?? 'KWD'),
        'is_first_order' => (bool)$order['is_first_order'],
        'has_promotional_gift' => (bool)$order['has_promotional_gift'],
        'gift_label' => (string)($order['gift_label'] ?? ''),
        'created_at' => (string)$order['created_at'],
        'updated_at' => (string)$order['updated_at'],
        'items' => $itemsByOrder[(int)$order['id']] ?? []
    ];
}

json_response(true, [
    'orders' => $mapped,
    'summary' => $summary
]);
