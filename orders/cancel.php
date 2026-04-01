<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

require_post();
require_customer_auth_json();

$data = get_request_json();

$orderNumber = trim((string)($data['order_number'] ?? ''));
if ($orderNumber === '') {
    json_response(false, ['message' => 'Order number is required'], 422);
}

$customerId = current_customer_id();
$pdo = db();

$stmt = $pdo->prepare("
    SELECT id, status
    FROM orders
    WHERE customer_id = ?
      AND order_number = ?
    LIMIT 1
");
$stmt->execute([$customerId, $orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    json_response(false, ['message' => 'Order not found'], 404);
}

if ((string)$order['status'] === 'cancelled') {
    json_response(false, ['message' => 'Order already cancelled'], 422);
}

try {
    $pdo->beginTransaction();

    $update = $pdo->prepare("
        UPDATE orders
        SET status = 'cancelled', updated_at = NOW()
        WHERE id = ?
    ");
    $update->execute([(int)$order['id']]);

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
            'cancelled',
            NULL,
            'Cancelled by customer from website',
            NOW()
        )
    ");
    $log->execute([
        'order_id' => (int)$order['id'],
        'old_status' => (string)$order['status']
    ]);

    $pdo->commit();

    json_response(true, [
        'message' => 'Order cancelled successfully',
        'order_number' => $orderNumber
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to cancel order',
        'error' => $e->getMessage()
    ], 500);
}
