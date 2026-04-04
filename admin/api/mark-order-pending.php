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

if ($currentStatus === 'pending') {
    json_response(false, ['message' => 'Order already pending'], 422);
}

if ($currentStatus === 'cancelled') {
    json_response(false, ['message' => 'Cancelled orders cannot be returned to pending'], 422);
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
            'pending',
            :changed_by,
            :notes,
            NOW()
        )
    ");
    $log->execute([
        'order_id' => (int)$order['id'],
        'old_status' => $currentStatus,
        'changed_by' => $_SESSION['admin_user_id'] ?? null,
        'notes' => 'Returned to pending from admin dashboard'
    ]);

    $pdo->commit();

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
