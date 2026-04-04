<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

require_method('GET');
require_customer_auth_json();

$customerId = current_customer_id();
$pdo = db();

$orderStmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        status,
        rejection_reason,
        created_at,
        is_first_order,
        has_promotional_gift,
        gift_label
    FROM orders
    WHERE customer_id = ?
    ORDER BY id DESC
");
$orderStmt->execute([$customerId]);
$orders = $orderStmt->fetchAll();

if (!$orders) {
    json_response(true, ['orders' => []]);
}

$orderIds = array_map(fn($row) => (int)$row['id'], $orders);
$placeholders = implode(',', array_fill(0, count($orderIds), '?'));

$itemStmt = $pdo->prepare("
    SELECT
        order_id,
        product_title,
        product_image,
        qty,
        down_payment,
        monthly_amount,
        duration_months,
        devices_count,
        line_total
    FROM order_items
    WHERE order_id IN ($placeholders)
    ORDER BY id ASC
");
$itemStmt->execute($orderIds);
$itemRows = $itemStmt->fetchAll();

$itemsByOrder = [];
foreach ($itemRows as $row) {
    $itemsByOrder[(int)$row['order_id']][] = [
        'title' => (string)$row['product_title'],
        'image' => (string)($row['product_image'] ?? ''),
        'quantity' => (int)$row['qty'],
        'down_payment' => ((float)$row['down_payment']) . ' KD Down Payment',
        'monthly' => ((float)$row['monthly_amount']) . ' KD',
        'duration' => (int)$row['duration_months'] . ' Months',
        'devices_count' => (int)$row['devices_count'],
        'total_price' => ((float)$row['line_total']) . ' KD',
        'checked' => false
    ];
}

$mapped = [];
foreach ($orders as $order) {
    $status = (string)$order['status'];

    $frontendStatus = 'Pending Delivery';

if ($status === 'pending') {
    $frontendStatus = 'Pending Delivery';
} elseif ($status === 'approved') {
    $frontendStatus = 'Approved';
} elseif ($status === 'on_the_way') {
    $frontendStatus = 'On The Way';
} elseif ($status === 'completed') {
    $frontendStatus = 'Delivered';
} elseif ($status === 'rejected') {
    $frontendStatus = 'Rejected';
} elseif ($status === 'cancelled') {
    $frontendStatus = 'Cancelled';
}

    $mapped[] = [
        'id' => (string)$order['order_number'],
        'db_id' => (int)$order['id'],
        'date' => date('Y-m-d h:i A', strtotime((string)$order['created_at'])),
        'status' => $frontendStatus,
        'rejection_reason' => (string)($order['rejection_reason'] ?? ''),
        'server_order' => true,
        'is_first_order' => (bool)$order['is_first_order'],
        'has_promotional_gift' => (bool)$order['has_promotional_gift'],
        'gift_label' => (string)($order['gift_label'] ?? ''),
        'items' => $itemsByOrder[(int)$order['id']] ?? []
    ];
}

json_response(true, ['orders' => $mapped]);
