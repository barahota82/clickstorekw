<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

if (!function_exists('admin_normalize_permission_key')) {
    function admin_normalize_permission_key(string $value): string
    {
        return strtolower(trim($value));
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

if (!function_exists('admin_is_full_access_role')) {
    function admin_is_full_access_role(string $roleName): bool
    {
        $roleName = strtolower(trim($roleName));
        return in_array($roleName, ['admin', 'super_admin', 'super admin'], true);
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

if (!function_exists('admin_permissions_from_database')) {
    function admin_permissions_from_database(int $roleId, ?int $userId = null): array
    {
        if ($roleId <= 0) {
            return [];
        }

        try {
            $pdo = db();

            if (!admin_table_exists($pdo, 'permissions')) {
                return [];
            }

            $permissionColumns = admin_get_table_columns($pdo, 'permissions');
            $permissionIdColumn = admin_pick_existing_column($permissionColumns, ['id', 'permission_id']);
            $permissionKeyColumn = admin_pick_existing_column($permissionColumns, ['permission_key', 'slug', 'code', 'name']);

            if (!$permissionIdColumn || !$permissionKeyColumn) {
                return [];
            }

            $permissionKeys = [];

            if (admin_table_exists($pdo, 'role_permissions')) {
                $rolePermissionColumns = admin_get_table_columns($pdo, 'role_permissions');

                $rpRoleIdColumn = admin_pick_existing_column($rolePermissionColumns, ['role_id', 'roles_id']);
                $rpPermissionIdColumn = admin_pick_existing_column($rolePermissionColumns, ['permission_id', 'permissions_id']);

                if ($rpRoleIdColumn && $rpPermissionIdColumn) {
                    $sql = "
                        SELECT p.`{$permissionKeyColumn}` AS permission_key
                        FROM role_permissions rp
                        INNER JOIN permissions p
                            ON p.`{$permissionIdColumn}` = rp.`{$rpPermissionIdColumn}`
                        WHERE rp.`{$rpRoleIdColumn}` = :role_id
                    ";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['role_id' => $roleId]);

                    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    if (is_array($rows)) {
                        $permissionKeys = array_merge($permissionKeys, $rows);
                    }
                }
            }

            if ($userId !== null && $userId > 0 && admin_table_exists($pdo, 'user_permissions')) {
                $userPermissionColumns = admin_get_table_columns($pdo, 'user_permissions');

                $upUserIdColumn = admin_pick_existing_column($userPermissionColumns, ['user_id', 'users_id']);
                $upPermissionIdColumn = admin_pick_existing_column($userPermissionColumns, ['permission_id', 'permissions_id']);
                $upIsAllowedColumn = admin_pick_existing_column($userPermissionColumns, ['is_allowed']);

                if ($upUserIdColumn && $upPermissionIdColumn) {
                    $sql = "
                        SELECT p.`{$permissionKeyColumn}` AS permission_key, up.`{$upIsAllowedColumn}` AS is_allowed
                        FROM user_permissions up
                        INNER JOIN permissions p
                            ON p.`{$permissionIdColumn}` = up.`{$upPermissionIdColumn}`
                        WHERE up.`{$upUserIdColumn}` = :user_id
                    ";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['user_id' => $userId]);

                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (is_array($rows)) {
                        foreach ($rows as $row) {
                            $key = admin_normalize_permission_key((string)($row['permission_key'] ?? ''));
                            $isAllowed = (int)($row['is_allowed'] ?? 1) === 1;

                            if ($key === '') {
                                continue;
                            }

                            if ($isAllowed) {
                                $permissionKeys[] = $key;
                            } else {
                                $permissionKeys = array_values(array_filter(
                                    $permissionKeys,
                                    static fn($item) => admin_normalize_permission_key((string)$item) !== $key
                                ));
                            }
                        }
                    }
                }
            }

            return array_values(array_unique(array_filter(array_map(
                static fn($item) => admin_normalize_permission_key((string)$item),
                $permissionKeys
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
        $userId = (int)($_SESSION['admin_user_id'] ?? 0);

        $permissions = admin_permissions_from_session();
        if (!empty($permissions)) {
            return $permissions;
        }

        if (admin_is_full_access_role($roleName)) {
            return ['admin.full_access'];
        }

        if ($roleId > 0) {
            $permissions = admin_permissions_from_database($roleId, $userId);
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

if (!function_exists('admin_require_any_permission')) {
    function admin_require_any_permission(array $requiredPermissions, string $message = 'ليس لديك صلاحية لتنفيذ هذا الإجراء'): void
    {
        if (!admin_has_any_permission($requiredPermissions)) {
            json_response(false, ['message' => $message], 403);
        }
    }
}

if (!function_exists('admin_get_current_user')) {
    function admin_get_current_user(): ?array
    {
        if (!is_admin_logged_in()) {
            return null;
        }

        return [
            'id' => (int)($_SESSION['admin_user_id'] ?? 0),
            'full_name' => (string)($_SESSION['admin_full_name'] ?? ''),
            'username' => (string)($_SESSION['admin_username'] ?? ''),
            'email' => (string)($_SESSION['admin_email'] ?? ''),
            'role_id' => (int)($_SESSION['admin_role_id'] ?? 0),
            'role_name' => (string)($_SESSION['admin_role_name'] ?? ''),
        ];
    }
}
