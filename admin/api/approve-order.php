<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_post();
require_admin_auth_json();
admin_require_permission_json('order.approve', 'ليس لديك صلاحية لاعتماد الطلب');

$data = get_request_json();
$orderNumber = trim((string)($data['order_number'] ?? ''));

if ($orderNumber === '') {
    json_response(false, ['message' => 'Order number is required'], 422);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT id, status
    FROM orders
    WHERE order_number = ?
    LIMIT 1
");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    json_response(false, ['message' => 'Order not found'], 404);
}

$orderId = (int)$order['id'];
$currentStatus = (string)$order['status'];

if ($currentStatus === 'approved') {
    json_response(false, ['message' => 'Order already approved'], 422);
}

if ($currentStatus === 'cancelled') {
    json_response(false, ['message' => 'Cancelled orders cannot be approved'], 422);
}

if ($currentStatus === 'completed') {
    json_response(false, ['message' => 'Delivered orders cannot be approved'], 422);
}

if ($currentStatus === 'on_the_way') {
    json_response(false, ['message' => 'On the way orders cannot be approved again'], 422);
}

$notes = 'Approved from admin dashboard';
if ($currentStatus === 'rejected') {
    $notes = 'Admin override: changed order from rejected to approved';
}

try {
    $pdo->beginTransaction();

    $update = $pdo->prepare("
        UPDATE orders
        SET
            status = 'approved',
            rejection_reason = NULL,
            updated_at = NOW()
        WHERE id = :id
        LIMIT 1
    ");
    $update->execute([
        'id' => $orderId
    ]);

    $log = $pdo->prepare("
        INSERT INTO order_status_logs
        (
            order_id,
            old_status,
            new_status,
            changed_by,
            notes,
            created_at
        )
        VALUES
        (
            :order_id,
            :old_status,
            'approved',
            :changed_by,
            :notes,
            NOW()
        )
    ");

    $log->execute([
        'order_id' => $orderId,
        'old_status' => $currentStatus,
        'changed_by' => admin_current_user_id() > 0 ? admin_current_user_id() : null,
        'notes' => $notes
    ]);

    $pdo->commit();

    admin_activity_log(
        'approve_order',
        'orders',
        'order',
        $orderId,
        'Approved order number: ' . $orderNumber . ' | from status: ' . $currentStatus
    );

    json_response(true, [
        'message' => 'Order approved successfully',
        'order_number' => $orderNumber
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to approve order',
        'error' => $e->getMessage()
    ], 500);
}
