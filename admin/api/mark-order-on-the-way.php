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

/*
  القواعد:
  - لا يمكن التحويل إلى on_the_way إذا:
    - الطلب cancelled
    - الطلب rejected
    - الطلب completed
    - الطلب بالفعل on_the_way
  - الطبيعي أن يكون الطلب approved أولًا،
    لكن سنسمح أيضًا من pending لو الأدمن أراد التحويل مباشرة.
*/

if ($currentStatus === 'on_the_way') {
    json_response(false, ['message' => 'Order already on the way'], 422);
}

if ($currentStatus === 'cancelled') {
    json_response(false, ['message' => 'Cancelled orders cannot be sent to delivery'], 422);
}

if ($currentStatus === 'rejected') {
    json_response(false, ['message' => 'Rejected orders cannot be sent to delivery'], 422);
}

if ($currentStatus === 'completed') {
    json_response(false, ['message' => 'Delivered orders cannot be sent to delivery again'], 422);
}

try {
    $pdo->beginTransaction();

    $update = $pdo->prepare("
        UPDATE orders
        SET
            status = 'on_the_way',
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
            'on_the_way',
            :changed_by,
            :notes,
            NOW()
        )
    ");

    $log->execute([
        'order_id' => (int)$order['id'],
        'old_status' => $currentStatus,
        'changed_by' => $_SESSION['admin_user_id'] ?? null,
        'notes' => 'Marked as on the way from admin dashboard'
    ]);

    $pdo->commit();

    json_response(true, [
        'message' => 'Order marked as on the way successfully',
        'order_number' => $orderNumber
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to mark order as on the way',
        'error' => $e->getMessage()
    ], 500);
}
