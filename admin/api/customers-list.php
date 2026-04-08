<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../helpers/permissions_helper.php';

require_method('GET');
require_admin_auth_json();
admin_require_permission_json('orders_view', 'You do not have permission to view customers');

$pdo = db();

$search = trim((string)($_GET['search'] ?? ''));
$limit = (int)($_GET['limit'] ?? 100);

if ($limit <= 0) {
    $limit = 100;
}
if ($limit > 300) {
    $limit = 300;
}

$sql = "
    SELECT
        c.id,
        c.full_name,
        c.email,
        c.whatsapp_country_code,
        c.whatsapp_number,
        c.whatsapp_full,
        c.is_verified,
        c.installment_approved,
        c.area,
        c.city,
        c.created_at,
        c.updated_at,
        COUNT(o.id) AS orders_count,
        COALESCE(SUM(o.total_amount), 0) AS orders_total
    FROM customers c
    LEFT JOIN orders o ON o.customer_id = c.id
";

$params = [];
if ($search !== '') {
    $sql .= "
        WHERE
            c.full_name LIKE :search
            OR c.email LIKE :search
            OR c.whatsapp_full LIKE :search
            OR c.whatsapp_number LIKE :search
    ";
    $params['search'] = '%' . $search . '%';
}

$sql .= "
    GROUP BY
        c.id,
        c.full_name,
        c.email,
        c.whatsapp_country_code,
        c.whatsapp_number,
        c.whatsapp_full,
        c.is_verified,
        c.installment_approved,
        c.area,
        c.city,
        c.created_at,
        c.updated_at
    ORDER BY c.id DESC
    LIMIT {$limit}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$customers = [];
foreach ($stmt->fetchAll() as $row) {
    $customers[] = [
        'id' => (int)$row['id'],
        'full_name' => (string)$row['full_name'],
        'email' => (string)($row['email'] ?? ''),
        'whatsapp_country_code' => (string)($row['whatsapp_country_code'] ?? ''),
        'whatsapp_number' => (string)($row['whatsapp_number'] ?? ''),
        'whatsapp_full' => (string)($row['whatsapp_full'] ?? ''),
        'is_verified' => (int)$row['is_verified'],
        'installment_approved' => (int)$row['installment_approved'],
        'area' => (string)($row['area'] ?? ''),
        'city' => (string)($row['city'] ?? ''),
        'orders_count' => (int)$row['orders_count'],
        'orders_total' => (float)$row['orders_total'],
        'created_at' => (string)$row['created_at'],
        'updated_at' => (string)$row['updated_at'],
    ];
}

json_response(true, ['customers' => $customers]);
