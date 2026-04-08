<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('reports_view', 'You do not have permission to view reports');

$pdo = db();

$from = trim((string)($_GET['from'] ?? ''));
$to   = trim((string)($_GET['to'] ?? ''));

$where = [];
$params = [];

if ($from !== '') {
    $where[] = "DATE(o.created_at) >= :date_from";
    $params['date_from'] = $from;
}

if ($to !== '') {
    $where[] = "DATE(o.created_at) <= :date_to";
    $params['date_to'] = $to;
}

$whereSql = '';
if ($where) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS all_orders,
        SUM(CASE WHEN o.status IN ('pending', 'approved', 'on_the_way') THEN 1 ELSE 0 END) AS active_orders,
        SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) AS delivered_orders,
        SUM(CASE WHEN o.status IN ('rejected', 'cancelled') THEN 1 ELSE 0 END) AS rejected_or_cancelled_orders,
        COALESCE(SUM(o.total_amount), 0) AS total_sales,
        COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) AS completed_sales
    FROM orders o
    {$whereSql}
");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch() ?: [];

$dailyStmt = $pdo->prepare("
    SELECT
        DATE(o.created_at) AS report_date,
        COUNT(*) AS orders_count,
        COALESCE(SUM(o.total_amount), 0) AS total_amount
    FROM orders o
    {$whereSql}
    GROUP BY DATE(o.created_at)
    ORDER BY DATE(o.created_at) DESC
    LIMIT 60
");
$dailyStmt->execute($params);

$daily = [];
foreach ($dailyStmt->fetchAll() as $row) {
    $daily[] = [
        'date' => (string)$row['report_date'],
        'orders_count' => (int)$row['orders_count'],
        'total_amount' => (float)$row['total_amount'],
    ];
}

$topStmt = $pdo->prepare("
    SELECT
        oi.product_title,
        SUM(oi.qty) AS total_qty,
        COALESCE(SUM(oi.line_total), 0) AS total_sales
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    {$whereSql}
    GROUP BY oi.product_title
    ORDER BY total_qty DESC, total_sales DESC
    LIMIT 10
");
$topStmt->execute($params);

$topProducts = [];
foreach ($topStmt->fetchAll() as $row) {
    $topProducts[] = [
        'product_title' => (string)$row['product_title'],
        'total_qty' => (int)$row['total_qty'],
        'total_sales' => (float)$row['total_sales'],
    ];
}

json_response(true, [
    'summary' => [
        'all_orders' => (int)($summary['all_orders'] ?? 0),
        'active_orders' => (int)($summary['active_orders'] ?? 0),
        'delivered_orders' => (int)($summary['delivered_orders'] ?? 0),
        'rejected_or_cancelled_orders' => (int)($summary['rejected_or_cancelled_orders'] ?? 0),
        'total_sales' => (float)($summary['total_sales'] ?? 0),
        'completed_sales' => (float)($summary['completed_sales'] ?? 0),
    ],
    'daily' => $daily,
    'top_products' => $topProducts,
]);
