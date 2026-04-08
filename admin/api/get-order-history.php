<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

/*
========================================
  REQUIREMENTS
========================================
*/
require_method('GET');
require_admin_auth_json();
admin_require_permission_json('orders_view', 'ليس لديك صلاحية لعرض سجل الطلب');

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

    if ($normalizedOldStatus === 'on_the_way' && $normalizedNewStatus === 'pending') {
        $isAdminOverride = true;
    }

    if ($normalizedOldStatus === 'approved' && $normalizedNewStatus === 'completed') {
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
  ACTIVITY LOG
========================================
*/
admin_activity_log(
    'view_order_history',
    'orders',
    'order',
    (int)$order['id'],
    'Viewed order history | order number: ' . (string)$order['order_number']
);

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
