<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('orders_view', 'ليس لديك صلاحية لعرض الطلبات.');

$pdo = db();

$search = trim((string)($_GET['search'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$date   = trim((string)($_GET['date'] ?? ''));

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(
        o.order_number LIKE :search
        OR o.customer_name_snapshot LIKE :search
        OR o.customer_email_snapshot LIKE :search
        OR o.customer_whatsapp_snapshot LIKE :search
    )";
    $params['search'] = '%' . $search . '%';
}

if ($status !== '') {
    $allowedStatuses = ['pending', 'approved', 'on_the_way', 'completed', 'cancelled', 'rejected'];
    if (!in_array($status, $allowedStatuses, true)) {
        json_response(false, ['message' => 'Invalid status filter'], 422);
    }

    $where[] = "o.status = :status";
    $params['status'] = $status;
}

if ($date !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date) {
        json_response(false, ['message' => 'Invalid date filter'], 422);
    }

    $where[] = "DATE(o.created_at) = :order_date";
    $params['order_date'] = $date;
}

$sql = "
    SELECT
        o.id,
        o.order_number,
        o.status,
        o.rejection_reason,
        o.customer_name_snapshot,
        o.customer_email_snapshot,
        o.customer_whatsapp_snapshot,
        o.subtotal_amount,
        o.discount_amount,
        o.delivery_amount,
        o.total_amount,
        o.currency_code,
        o.is_first_order,
        o.has_promotional_gift,
        o.gift_label,
        o.created_at,
        o.updated_at
    FROM orders o
";

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY o.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

if (!$orders) {
    json_response(true, [
        'orders' => [],
        'summary' => [
            'all' => 0,
            'pending' => 0,
            'delivered' => 0,
            'rejected_cancelled' => 0
        ],
        'permissions' => [
            'approve' => admin_has_permission('order.approve'),
            'reject' => admin_has_permission('order.reject'),
            'on_the_way' => admin_has_permission('order.on_the_way'),
            'deliver' => admin_has_permission('order.deliver'),
            'return_to_pending' => admin_has_permission('order.return_to_pending'),
        ]
    ]);
}

$orderIds = array_map(static fn(array $row): int => (int)$row['id'], $orders);
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
    $orderId = (int)$row['order_id'];

    $itemsByOrder[$orderId][] = [
        'title' => (string)$row['product_title'],
        'image' => (string)($row['product_image'] ?? ''),
        'quantity' => (int)$row['qty'],
        'down_payment' => ((float)$row['down_payment']) . ' KD Down Payment',
        'monthly' => ((float)$row['monthly_amount']) . ' KD',
        'duration' => (int)$row['duration_months'] . ' Months',
        'devices_count' => (int)$row['devices_count'],
        'total_price' => ((float)$row['line_total']) . ' KD'
    ];
}

$mapped = [];
$summary = [
    'all' => 0,
    'pending' => 0,
    'delivered' => 0,
    'rejected_cancelled' => 0
];

foreach ($orders as $order) {
    $summary['all']++;

    $rawStatus = (string)$order['status'];
    $frontendStatus = 'Pending';

    if ($rawStatus === 'completed') {
        $frontendStatus = 'Delivered';
        $summary['delivered']++;
    } elseif ($rawStatus === 'cancelled') {
        $frontendStatus = 'Cancelled';
        $summary['rejected_cancelled']++;
    } elseif ($rawStatus === 'rejected') {
        $frontendStatus = 'Rejected';
        $summary['rejected_cancelled']++;
    } elseif ($rawStatus === 'on_the_way') {
        $frontendStatus = 'On The Way';
        $summary['pending']++;
    } elseif ($rawStatus === 'approved') {
        $frontendStatus = 'Approved';
        $summary['pending']++;
    } else {
        $frontendStatus = 'Pending';
        $summary['pending']++;
    }

    $mapped[] = [
        'id' => (int)$order['id'],
        'order_number' => (string)$order['order_number'],
        'status' => $frontendStatus,
        'raw_status' => $rawStatus,
        'rejection_reason' => (string)($order['rejection_reason'] ?? ''),
        'customer_name' => (string)($order['customer_name_snapshot'] ?? ''),
        'customer_email' => (string)($order['customer_email_snapshot'] ?? ''),
        'customer_whatsapp' => (string)($order['customer_whatsapp_snapshot'] ?? ''),
        'subtotal_amount' => (float)$order['subtotal_amount'],
        'discount_amount' => (float)$order['discount_amount'],
        'delivery_amount' => (float)$order['delivery_amount'],
        'total_amount' => (float)$order['total_amount'],
        'currency_code' => (string)($order['currency_code'] ?? 'KWD'),
        'is_first_order' => (bool)$order['is_first_order'],
        'has_promotional_gift' => (bool)$order['has_promotional_gift'],
        'gift_label' => (string)($order['gift_label'] ?? ''),
        'created_at' => (string)$order['created_at'],
        'updated_at' => (string)$order['updated_at'],
        'items' => $itemsByOrder[(int)$order['id']] ?? []
    ];
}

admin_activity_log(
    'view_orders',
    'orders',
    null,
    null,
    'Viewed orders list' .
    ($search !== '' ? ' | search: ' . $search : '') .
    ($status !== '' ? ' | status: ' . $status : '') .
    ($date !== '' ? ' | date: ' . $date : '')
);

json_response(true, [
    'orders' => $mapped,
    'summary' => $summary,
    'permissions' => [
        'approve' => admin_has_permission('order.approve'),
        'reject' => admin_has_permission('order.reject'),
        'on_the_way' => admin_has_permission('order.on_the_way'),
        'deliver' => admin_has_permission('order.deliver'),
        'return_to_pending' => admin_has_permission('order.return_to_pending'),
    ]
]);
