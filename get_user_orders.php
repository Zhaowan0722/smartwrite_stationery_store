<?php

session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo '<div style="text-align: center; padding: 20px; color: #dc3545;">Invalid user ID</div>';
    exit();
}

$sql = "SELECT o.*, COUNT(oi.id) as items_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.order_date DESC
        LIMIT 5";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo '<div class="table-responsive">';
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<thead>';
    echo '<tr style="background: #f8f9fa;">';
    echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #eee;">Order ID</th>';
    echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #eee;">Date</th>';
    echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #eee;">Items</th>';
    echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #eee;">Total</th>';
    echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #eee;">Status</th>';
    echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #eee;">Service</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($order = mysqli_fetch_assoc($result)) {

        $status_class = '';
        switch ($order['status']) {
            case 'pending':
                $status_class = 'status-pending';
                break;
            
            case 'processing':
                $status_class = 'status-processing';
                break;
            case 'completed':
                $status_class = 'status-completed';
                break;
            case 'cancelled':
                $status_class = 'status-cancelled';
                break;
            default:
                $status_class = 'status-pending';
        }

        $service_type = ucfirst(str_replace('_', ' ', $order['service_type']));
        
        echo '<tr style="border-bottom: 1px solid #eee;">';
        echo '<td style="padding: 10px;">#' . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . '</td>';
        echo '<td style="padding: 10px;">' . date('M d, Y', strtotime($order['order_date'])) . '</td>';
        echo '<td style="padding: 10px;">' . $order['items_count'] . ' items</td>';
        echo '<td style="padding: 10px;">$' . number_format($order['total_price'], 2) . '</td>';
        echo '<td style="padding: 10px;">';
        echo '<span class="status-badge ' . $status_class . '" style="display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">';
        echo ucfirst($order['status'] === 'paid' ? 'pending' : $order['status']);
        echo '</span>';
        echo '</td>';
        echo '<td style="padding: 10px;">' . $service_type . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    echo '<div style="text-align: center; margin-top: 15px;">';
    echo '<a href="manage-orders.php?search=' . urlencode($user_id) . '&search_type=user_id" style="color: #007bff; text-decoration: none; font-weight: 600;">';
    echo '<i class="fas fa-external-link-alt"></i> View All Orders';
    echo '</a>';
    echo '</div>';
} else {
    echo '<div style="text-align: center; padding: 30px; color: #666; background: #f9f9f9; border-radius: 10px; border: 2px dashed #eee;">';
    echo '<i class="fas fa-shopping-cart fa-2x" style="margin-bottom: 15px; opacity: 0.5;"></i>';
    echo '<p style="margin: 0;">No orders found for this user.</p>';
    echo '</div>';
}

mysqli_stmt_close($stmt);
?>