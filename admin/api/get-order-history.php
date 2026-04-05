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

/*
========================================
  REQUIREMENTS
========================================
*/
require_method('GET');
require_admin_auth_json();

$requiredPermissions = [
    'orders.history.view',
    'view_order_history',
    'orders.view',
    'view_orders',
    'manage_orders',
    'admin.full_access'
];

if (!admin_has_any_permission($requiredPermissions)) {
    json_response(false, [
        'message' => 'ليس لديك صلاحية لعرض سجل الطلب'
    ], 403);
}

/*
========================================
  INPUT
========================================
*/
$orderNumber = trim((string)($_GET['order_number'] ?? ''));

if ($orderNumber === '') {
    json_response(false, [
        'message' => 'Order number is required'
    ], 422);
}

/*
========================================
  DB CONNECTION
========================================
*/
$pdo = db();

/*
========================================
  CHECK ORDER EXISTS
========================================
*/
$orderStmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        status
    FROM orders
    WHERE order_number = ?
    LIMIT 1
");
$orderStmt->execute([$orderNumber]);

$order = $orderStmt->fetch();

if (!$order) {
    json_response(false, [
        'message' => 'Order not found'
    ], 404);
}

/*
========================================
  LOAD ORDER HISTORY
========================================
*/
$logStmt = $pdo->prepare("
    SELECT
        l.id,
        l.order_id,
        l.old_status,
        l.new_status,
        l.changed_by,
        l.notes,
        l.created_at,
        u.username AS changed_by_username,
        u.full_name AS changed_by_full_name
    FROM order_status_logs l
    LEFT JOIN users u
        ON u.id = l.changed_by
    WHERE l.order_id = ?
    ORDER BY l.id ASC
");
$logStmt->execute([(int)$order['id']]);

$logs = $logStmt->fetchAll();

/*
========================================
  MAP DATA
========================================
*/
$history = [];

foreach ($logs as $log) {

    $changedById = $log['changed_by'] !== null
        ? (int)$log['changed_by']
        : null;

    $changedByName = trim((string)($log['changed_by_full_name'] ?? ''));
    $changedByUsername = trim((string)($log['changed_by_username'] ?? ''));

    $actorLabel = 'System';

    if ($changedById !== null) {
        if ($changedByName !== '') {
            $actorLabel = 'User: ' . $changedByName;
        } elseif ($changedByUsername !== '') {
            $actorLabel = 'User: ' . $changedByUsername;
        } else {
            $actorLabel = 'User #' . $changedById;
        }
    }

    $notes = (string)($log['notes'] ?? '');
    $oldStatus = (string)($log['old_status'] ?? '');
    $newStatus = (string)($log['new_status'] ?? '');

    $normalizedNotes = strtolower(trim($notes));
    $normalizedOldStatus = strtolower(trim($oldStatus));
    $normalizedNewStatus = strtolower(trim($newStatus));

    $isAdminOverride = false;

    if (
        str_contains($normalizedNotes, 'admin override') ||
        str_contains($normalizedNotes, 'override') ||
        str_contains($normalizedNotes, 'admin exception')
    ) {
        $isAdminOverride = true;
    }

    if ($normalizedOldStatus === 'rejected' && $normalizedNewStatus === 'approved') {
        $isAdminOverride = true;
    }

    if ($normalizedOldStatus === 'cancelled' && in_array($normalizedNewStatus, ['pending', 'approved'], true)) {
        $isAdminOverride = true;
    }

    if ($normalizedOldStatus === 'completed' && $normalizedNewStatus !== 'completed') {
        $isAdminOverride = true;
    }

    $history[] = [
        'id' => (int)$log['id'],
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'changed_by' => $changedById,
        'changed_by_label' => $actorLabel,
        'notes' => $notes,
        'created_at' => (string)$log['created_at'],
        'is_admin_override' => $isAdminOverride
    ];
}

/*
========================================
  RESPONSE
========================================
*/
json_response(true, [
    'order' => [
        'id' => (int)$order['id'],
        'order_number' => (string)$order['order_number'],
        'current_status' => (string)$order['status']
    ],
    'history' => $history
]);
