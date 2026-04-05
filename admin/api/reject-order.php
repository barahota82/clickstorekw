<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

require_post();
require_admin_auth_json();

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

$currentStatus = (string)$order['status'];

if ($currentStatus === 'rejected') {
    json_response(false, ['message' => 'Order already rejected'], 422);
}

if ($currentStatus === 'completed') {
    json_response(false, ['message' => 'Completed orders cannot be rejected'], 422);
}

if ($currentStatus === 'cancelled') {
    json_response(false, ['message' => 'Cancelled orders cannot be rejected'], 422);
}

if ($currentStatus === 'on_the_way') {
    json_response(false, ['message' => 'Orders on the way cannot be rejected'], 422);
}

$reason = 'Not matching conditions';
$notes = $reason;

if ($currentStatus === 'approved') {
    $notes = 'Admin action: changed order from approved to rejected - Not matching conditions';
} elseif ($currentStatus === 'pending') {
    $notes = 'Not matching conditions';
}

try {
    $pdo->beginTransaction();

    $update = $pdo->prepare("
        UPDATE orders
        SET
            status = 'rejected',
            rejection_reason = :reason,
            updated_at = NOW()
        WHERE id = :id
        LIMIT 1
    ");
    $update->execute([
        'reason' => $reason,
        'id' => (int)$order['id']
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
            'rejected',
            :changed_by,
            :notes,
            NOW()
        )
    ");
    $log->execute([
        'order_id' => (int)$order['id'],
        'old_status' => $currentStatus,
        'changed_by' => $_SESSION['admin_user_id'] ?? null,
        'notes' => $notes
    ]);

    $pdo->commit();

    json_response(true, [
        'message' => 'Order rejected successfully',
        'order_number' => $orderNumber,
        'reason' => $reason
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to reject order',
        'error' => $e->getMessage()
    ], 500);
}
