<?php
require_once '../../config.php';
require_admin_auth_json();

$category_id = (int)($_GET['category_id'] ?? 0);

$pdo = db();

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.title,
        p.slug,
        p.image_path,
        p.monthly_amount,
        p.duration_months,
        p.is_available,
        p.is_hot_offer,
        p.product_order,
        b.name AS brand_name
    FROM products p
    INNER JOIN brands b ON b.id = p.brand_id
    WHERE p.category_id = ?
    ORDER BY p.product_order ASC, p.id ASC
");

$stmt->execute([$category_id]);

json_response(true, ['products' => $stmt->fetchAll()]);
