<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_post();
require_admin_auth_json();
admin_require_permission_json('order.return_to_pending', 'ليس لديك صلاحية لإرجاع الطلب إلى Pending');

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

if ($currentStatus === 'pending') {
    json_response(false, ['message' => 'Order already pending'], 422);
}

if ($currentStatus === 'cancelled') {
    json_response(false, ['message' => 'Cancelled orders cannot be returned to pending'], 422);
}

if ($currentStatus === 'completed') {
    json_response(false, ['message' => 'Delivered orders cannot be returned to pending'], 422);
}

$notes = 'Returned to pending from admin dashboard';

if ($currentStatus === 'rejected') {
    $notes = 'Admin override: changed order from rejected to pending';
} elseif ($currentStatus === 'on_the_way') {
    $notes = 'Admin direct action: changed order from on_the_way to pending';
} elseif ($currentStatus === 'approved') {
    $notes = 'Returned to pending from admin dashboard';
}

try {
    $pdo->beginTransaction();

    $update = $pdo->prepare("
        UPDATE orders
        SET
            status = 'pending',
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
            'pending',
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
        'return_order_to_pending',
        'orders',
        'order',
        $orderId,
        'Returned order to pending | order number: ' . $orderNumber . ' | from status: ' . $currentStatus
    );

    json_response(true, [
        'message' => 'Order returned to pending successfully',
        'order_number' => $orderNumber
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to return order to pending',
        'error' => $e->getMessage()
    ], 500);
}
