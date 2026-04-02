<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

unset($_SESSION['customer_auth']);
unset($_SESSION['pending_customer_auth']);
unset($_SESSION['pending_customer_email']);
unset($_SESSION['pending_customer_id']);

json_response(true, [
    'message' => 'Logged out successfully.'
]);
