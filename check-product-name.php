<?php

session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    
    if (!empty($name)) {

        $current_id = isset($_POST['current_id']) ? (int)$_POST['current_id'] : 0;
        
        if ($current_id > 0) {
            $sql = "SELECT id FROM products WHERE name = '$name' AND id != $current_id";
        } else {
            $sql = "SELECT id FROM products WHERE name = '$name'";
        }
        
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            echo json_encode(['exists' => true]);
        } else {
            echo json_encode(['exists' => false]);
        }
    } else {
        echo json_encode(['exists' => false]);
    }
}
?>