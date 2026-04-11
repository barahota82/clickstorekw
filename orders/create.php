<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

require_post();

$data = get_request_json();
$items = $data['items'] ?? [];
$orderNumber = trim((string)($data['order_number'] ?? ''));

if (!is_array($items) || count($items) === 0) {
    json_response(false, ['message' => 'No items selected'], 422);
}

$pdo = db();

$customerId = current_customer_id();
$isGuestOrder = $customerId <= 0;

$guestName = trim((string)($data['guest_name'] ?? ''));
$guestEmail = trim((string)($data['guest_email'] ?? ''));
$guestWhatsapp = trim((string)($data['guest_whatsapp'] ?? ''));

$customer = null;

if ($isGuestOrder) {
    if ($guestName === '' || $guestWhatsapp === '') {
        json_response(false, [
            'message' => 'Guest name and WhatsApp are required'
        ], 422);
    }
} else {
    $customerStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
    $customerStmt->execute([$customerId]);
    $customer = $customerStmt->fetch();

    if (!$customer) {
        json_response(false, ['message' => 'Customer not found'], 404);
    }

    $guestName = (string)$customer['full_name'];
    $guestEmail = (string)($customer['email'] ?? '');
    $guestWhatsapp = (string)($customer['whatsapp_full'] ?? '');
}

if ($orderNumber === '') {
    $orderNumber = generate_order_number();
}

$isFirstOrder = 0;
$hasGift = 0;
$giftLabel = '';

if (!$isGuestOrder && $customerId > 0) {
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) AS total_orders
        FROM orders
        WHERE customer_id = ?
          AND status IN ('pending', 'approved', 'on_the_way', 'completed')
    ");
    $countStmt->execute([$customerId]);
    $countRow = $countStmt->fetch();

    $isFirstOrder = ((int)($countRow['total_orders'] ?? 0) === 0) ? 1 : 0;

    $giftEnabled = get_setting_bool('first_order_gift_enabled', true);
    $giftLabel = trim((string)get_setting('first_order_gift_label', 'Free gift for first order'));
    $hasGift = ($giftEnabled && $isFirstOrder === 1) ? 1 : 0;
}

$subtotal = 0.000;
$normalizedItems = [];

foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }

    $title = trim((string)($item['title'] ?? 'Offer'));
    $qty = max(1, (int)($item['quantity'] ?? 1));
    $monthly = parse_money_to_decimal($item['monthly'] ?? '');
    $downPayment = parse_money_to_decimal($item['down_payment'] ?? '');
    $duration = max(1, (int)parse_money_to_decimal($item['duration'] ?? '1'));
    $lineTotal = parse_money_to_decimal($item['total_price'] ?? 0);

    if ($lineTotal <= 0) {
        $lineTotal = (($monthly * $duration) + $downPayment) * $qty;
    }

    $subtotal += $lineTotal;

    $normalizedItems[] = [
        'product_id' => null,
        'product_title' => $title,
        'product_sku' => null,
        'product_image' => trim((string)($item['image'] ?? '')),
        'qty' => $qty,
        'unit_price' => $qty > 0 ? round($lineTotal / $qty, 3) : 0.000,
        'line_total' => round($lineTotal, 3),
        'down_payment' => round($downPayment, 3),
        'monthly_amount' => round($monthly, 3),
        'duration_months' => $duration,
        'devices_count' => max(1, (int)($item['devices_count'] ?? 1)),
        'frontend_item' => [
            'title' => $title,
            'image' => trim((string)($item['image'] ?? '')),
            'quantity' => $qty,
            'monthly' => $item['monthly'] ?? '',
            'down_payment' => $item['down_payment'] ?? '',
            'duration' => $item['duration'] ?? '',
            'total_price' => $item['total_price'] ?? '',
            'devices_count' => $item['devices_count'] ?? 1,
            'checked' => false
        ]
    ];
}

if (count($normalizedItems) === 0) {
    json_response(false, ['message' => 'No valid items found'], 422);
}

$totalAmount = round($subtotal, 3);

try {
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare("
        INSERT INTO orders
        (
            customer_id,
            customer_name_snapshot,
            customer_email_snapshot,
            customer_whatsapp_snapshot,
            order_number,
            status,
            subtotal_amount,
            discount_amount,
            delivery_amount,
            total_amount,
            currency_code,
            source_channel,
            is_first_order,
            has_promotional_gift,
            gift_label,
            notes,
            created_at,
            updated_at
        )
        VALUES
        (
            :customer_id,
            :customer_name_snapshot,
            :customer_email_snapshot,
            :customer_whatsapp_snapshot,
            :order_number,
            'pending',
            :subtotal_amount,
            0.000,
            0.000,
            :total_amount,
            'KWD',
            'website',
            :is_first_order,
            :has_promotional_gift,
            :gift_label,
            :notes,
            NOW(),
            NOW()
        )
    ");

    $orderStmt->execute([
        'customer_id' => $isGuestOrder ? null : $customerId,
        'customer_name_snapshot' => $guestName,
        'customer_email_snapshot' => $guestEmail !== '' ? $guestEmail : null,
        'customer_whatsapp_snapshot' => $guestWhatsapp !== '' ? $guestWhatsapp : null,
        'order_number' => $orderNumber,
        'subtotal_amount' => $subtotal,
        'total_amount' => $totalAmount,
        'is_first_order' => $isFirstOrder,
        'has_promotional_gift' => $hasGift,
        'gift_label' => $hasGift ? $giftLabel : null,
        'notes' => $isGuestOrder ? 'Guest order from website' : null,
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare("
        INSERT INTO order_items
        (
            order_id,
            product_id,
            product_title,
            product_sku,
            product_image,
            qty,
            unit_price,
            line_total,
            down_payment,
            monthly_amount,
            duration_months,
            devices_count,
            created_at
        )
        VALUES
        (
            :order_id,
            :product_id,
            :product_title,
            :product_sku,
            :product_image,
            :qty,
            :unit_price,
            :line_total,
            :down_payment,
            :monthly_amount,
            :duration_months,
            :devices_count,
            NOW()
        )
    ");

    foreach ($normalizedItems as $item) {
        $itemStmt->execute([
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'product_title' => $item['product_title'],
            'product_sku' => $item['product_sku'],
            'product_image' => $item['product_image'],
            'qty' => $item['qty'],
            'unit_price' => $item['unit_price'],
            'line_total' => $item['line_total'],
            'down_payment' => $item['down_payment'],
            'monthly_amount' => $item['monthly_amount'],
            'duration_months' => $item['duration_months'],
            'devices_count' => $item['devices_count'],
        ]);
    }

    $logStmt = $pdo->prepare("
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
            NULL,
            'pending',
            NULL,
            :notes,
            NOW()
        )
    ");
    $logStmt->execute([
        'order_id' => $orderId,
        'notes' => $isGuestOrder ? 'Guest order created from website' : 'Order created from website'
    ]);

    $pdo->commit();

    $frontendOrder = [
        'id' => $orderNumber,
        'db_id' => $orderId,
        'date' => date('Y-m-d h:i A'),
        'status' => 'Pending Delivery',
        'server_order' => true,
        'is_first_order' => (bool)$isFirstOrder,
        'has_promotional_gift' => (bool)$hasGift,
        'gift_label' => $hasGift ? $giftLabel : '',
        'items' => array_map(fn($row) => $row['frontend_item'], $normalizedItems),
    ];

    $giftText = $hasGift ? "🎁 Gift: {$giftLabel}" : '';

    $whatsLines = [];
    foreach ($frontendOrder['items'] as $idx => $item) {
        $imageUrl = trim((string)$item['image']);

        if ($imageUrl !== '' && !preg_match('#^https?://#i', $imageUrl)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $imageUrl = '/' . ltrim($imageUrl, '/');
            $imageUrl = $scheme . $host . $imageUrl;
        }

        $whatsLines[] = implode("\n", [
            "🔹 Offer " . ($idx + 1),
            "Offer Name: " . $item['title'],
            "Devices in Offer: " . ($item['devices_count'] ?: 1),
            "Quantity: " . $item['quantity'],
            "Down Payment: " . ($item['down_payment'] ?: '0 KD Down Payment'),
            "Monthly Installment: " . ($item['monthly'] ?: ''),
            "Months: " . ($item['duration'] ?: ''),
            "Total Price: " . ($item['total_price'] ?: ''),
            "Image: " . $imageUrl
        ]);
    }

    $messageParts = [
        "Welcome to Click Company 👋",
        "",
        "#ORDER",
        "Order Reference: {$orderNumber}",
        "Customer Name: " . $guestName,
        "Customer Email: " . ($guestEmail !== '' ? $guestEmail : '-'),
        "Customer WhatsApp: " . ($guestWhatsapp !== '' ? $guestWhatsapp : '-'),
        "Order Date: " . date('Y-m-d h:i A'),
    ];

    if ($giftText !== '') {
        $messageParts[] = $giftText;
    }

    $messageParts[] = implode("\n\n", $whatsLines);
    $messageParts[] = "";
    $messageParts[] = "Please confirm this order and proceed with processing.";

    $whatsappMessage = implode("\n", $messageParts);

    json_response(true, [
        'message' => 'Order created successfully',
        'order' => $frontendOrder,
        'whatsapp_message' => $whatsappMessage
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [
        'message' => 'Failed to create order',
        'error' => $e->getMessage()
    ], 500);
}
