<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

require_method('GET');
require_admin_auth_json();

$orderNumber = trim((string)($_GET['order_number'] ?? ''));

if ($orderNumber === '') {
    json_response(false, ['message' => 'Order number is required'], 422);
}

$pdo = db();

$orderStmt = $pdo->prepare("
    SELECT id, order_number, status
    FROM orders
    WHERE order_number = ?
    LIMIT 1
");
$orderStmt->execute([$orderNumber]);
$order = $orderStmt->fetch();

if (!$order) {
    json_response(false, ['message' => 'Order not found'], 404);
}

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

$mapped = [];

foreach ($logs as $log) {
    $changedById = $log['changed_by'] !== null ? (int)$log['changed_by'] : null;
    $changedByName = trim((string)($log['changed_by_full_name'] ?? ''));
    $changedByUsername = trim((string)($log['changed_by_username'] ?? ''));

    $actorLabel = 'System';

    if ($changedById !== null) {
        if ($changedByName !== '') {
            $actorLabel = 'Admin: ' . $changedByName;
        } elseif ($changedByUsername !== '') {
            $actorLabel = 'Admin: ' . $changedByUsername;
        } else {
            $actorLabel = 'Admin #' . $changedById;
        }
    }

    $mapped[] = [
        'id' => (int)$log['id'],
        'old_status' => (string)($log['old_status'] ?? ''),
        'new_status' => (string)($log['new_status'] ?? ''),
        'changed_by' => $changedById,
        'changed_by_label' => $actorLabel,
        'notes' => (string)($log['notes'] ?? ''),
        'created_at' => (string)$log['created_at']
    ];
}

json_response(true, [
    'order' => [
        'id' => (int)$order['id'],
        'order_number' => (string)$order['order_number'],
        'current_status' => (string)$order['status']
    ],
    'history' => $mapped
]);
