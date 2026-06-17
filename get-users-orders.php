<?php

require_once '../includes/config.php';

if (!in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    exit('Access denied');
}

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);

    $sql = "SELECT o.*, 
                   COUNT(oi.id) as item_count,
                   GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = $user_id
            GROUP BY o.id
            ORDER BY o.order_date DESC
            LIMIT 5";
    
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        echo '<table style="width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden;">';
        echo '<thead style="background: #f8f9fa;">';
        echo '<tr>';
        echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #eee;">Order ID</th>';
        echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #eee;">Date</th>';
        echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #eee;">Total</th>';
        echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #eee;">Status</th>';
        echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #eee;">Items</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while($order = mysqli_fetch_assoc($result)) {
            $status_colors = [
                'pending' => '#ffc107',
                
                'processing' => '#17a2b8',
                'completed' => '#28a745',
                'cancelled' => '#dc3545'
            ];
            $status_color = $status_colors[$order['status']] ?? '#6c757d';
            
            echo '<tr style="border-bottom: 1px solid #eee;">';
            echo '<td style="padding: 12px;">';
            echo '<strong>#' . $order['id'] . '</strong><br>';
            echo '<small style="color: #666;">' . ucfirst($order['payment_method']) . '</small>';
            echo '</td>';
            echo '<td style="padding: 12px;">' . date('M d, Y H:i', strtotime($order['order_date'])) . '</td>';
            echo '<td style="padding: 12px; font-weight: bold;">$' . number_format($order['total_price'], 2) . '</td>';
            echo '<td style="padding: 12px;">';
            echo '<span style="display: inline-block; padding: 4px 8px; border-radius: 20px; background: ' . $status_color . '20; color: ' . $status_color . '; font-weight: bold; font-size: 0.85rem;">';
            echo ucfirst($order['status'] === 'paid' ? 'pending' : $order['status']);
            echo '</span>';
            echo '</td>';
            echo '<td style="padding: 12px;">';
            echo '<span style="font-weight: bold;">' . $order['item_count'] . ' items</span><br>';
            echo '<small style="color: #666;">' . substr($order['product_names'], 0, 50) . (strlen($order['product_names']) > 50 ? '...' : '') . '</small>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';

        $count_sql = "SELECT COUNT(*) as total_orders FROM orders WHERE user_id = $user_id";
        $count_result = mysqli_query($conn, $count_sql);
        $count_data = mysqli_fetch_assoc($count_result);
        
        if ($count_data['total_orders'] > 5) {
            echo '<div style="text-align: center; margin-top: 15px;">';
            echo '<a href="manage-orders.php?search=' . $user_id . '" style="color: #007bff; text-decoration: none;">';
            echo '<i class="fas fa-external-link-alt"></i> View all ' . $count_data['total_orders'] . ' orders';
            echo '</a>';
            echo '</div>';
        }
    } else {
        echo '<div style="text-align: center; padding: 40px 20px; color: #666;">';
        echo '<i class="fas fa-shopping-bag" style="font-size: 2rem; margin-bottom: 15px; opacity: 0.3;"></i>';
        echo '<h4 style="margin: 0 0 10px 0;">No Orders Found</h4>';
        echo '<p style="margin: 0;">This user has not placed any orders yet.</p>';
        echo '</div>';
    }
} else {
    echo '<div style="text-align: center; padding: 20px; color: #dc3545;">';
    echo '<i class="fas fa-exclamation-circle"></i> Invalid user ID';
    echo '</div>';
}
?>