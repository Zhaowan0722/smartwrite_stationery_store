<?php


$host = "localhost";
$username = "root";
$password = "";
$database = "smartwrite_stationery_store_system";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

date_default_timezone_set('Asia/Kuala_Lumpur');


@mysqli_query($conn, "UPDATE orders SET status = 'pending' WHERE status = 'paid'");
@mysqli_query($conn, "ALTER TABLE orders MODIFY status ENUM('pending','processing','completed','cancelled') DEFAULT 'pending'");
@mysqli_query($conn, "ALTER TABLE users MODIFY user_type ENUM('superadmin','admin','user') DEFAULT 'user'");

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 0;
    $_SESSION['username'] = 'Guest';
    $_SESSION['user_type'] = 'guest';
    $_SESSION['cart'] = [];
}

$is_logged_in = ($_SESSION['user_id'] > 0);
?>