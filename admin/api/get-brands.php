<?php
require_once '../../config.php';
require_admin_auth_json();

$category_id = (int)($_GET['category_id'] ?? 0);

$pdo = db();

$stmt = $pdo->prepare("
    SELECT id, name, display_name, slug, sort_order, is_active
    FROM brands
    WHERE category_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$category_id]);

json_response(true, ['brands' => $stmt->fetchAll()]);
