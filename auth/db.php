<?php

$conn = new mysqli(
    'localhost',
    'click_user',
    'Admin@Hem@3282',
    'click_db'
);

if ($conn->connect_error) {
    die('DB ERROR: ' . $conn->connect_error);
}

echo "DB OK";
exit;
