<?php

require_once 'includes/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: track.php');
    exit();
}

$order_id = intval($_GET['id']);

$sql = "SELECT o.*, 
               u.username, u.email as user_email,
               COUNT(oi.id) as item_count,
               SUM(oi.quantity * oi.price) as calculated_total
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = $order_id
        GROUP BY o.id";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {

    $page_title = "Order Not Found - QuickBite";
    include 'includes/header.php';
    ?>
    <div class="container">
        <div class="content">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h4>Order Not Found!</h4>
                    <p>The order you're looking for doesn't exist or you don't have permission to view it.</p>
                    <a href="track.php" class="btn" style="margin-top: 10px;">
                        <i class="fas fa-arrow-left"></i> Back to Order Tracking
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit();
}

$order = mysqli_fetch_assoc($result);

$can_view = false;

if ($is_logged_in) {

    if ($order['user_id'] == $_SESSION['user_id'] || in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
        $can_view = true;
    }
} else {



    $can_view = true; // Simplified for now
}

if (!$can_view) {
    $page_title = "Access Denied - QuickBite";
    include 'includes/header.php';
    ?>
    <div class="container">
        <div class="content">
            <div class="alert alert-warning">
                <i class="fas fa-lock"></i>
                <div>
                    <h4>Access Denied</h4>
                    <p>You don't have permission to view this order. Please log in to view your orders.</p>
                    <a href="login.php" class="btn" style="margin-top: 10px; background: #ff6b6b;">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="track.php" class="btn" style="margin-top: 10px; background: #6c757d;">
                        <i class="fas fa-search"></i> Track Another Order
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit();
}

$items_sql = "SELECT oi.*, p.name as product_name, p.image as product_image 
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_sql);
$order_items = [];
$items_total = 0;

if ($items_result) {
    while($item = mysqli_fetch_assoc($items_result)) {
        $order_items[] = $item;
        $items_total += $item['quantity'] * $item['price'];
    }
}

$order_date = date('F j, Y \a\t g:i A', strtotime($order['order_date']));

$display_order_status = ($order['status'] === 'paid') ? 'pending' : $order['status'];
$payment_method_text = strtolower($order['payment_method'] ?? '');
if ($display_order_status === 'cancelled') {
    $payment_status = 'Refunded';
    $payment_color = '#dc3545';
    $payment_bg = '#f8d7da';
} elseif (strpos($payment_method_text, 'cash') !== false) {
    $payment_status = 'Pending';
    $payment_color = '#ffc107';
    $payment_bg = '#fff3cd';
} else {
    $payment_status = 'Paid';
    $payment_color = '#28a745';
    $payment_bg = '#d4edda';
}

$status_info = [
    'pending' => [
        'color' => '#ffc107',
        'bg_color' => '#fff3cd',
        'icon' => 'fas fa-clock',
        'description' => 'Your order has been received and is awaiting processing.'
    ],
    'processing' => [
        'color' => '#17a2b8',
        'bg_color' => '#d1ecf1',
        'icon' => 'fas fa-utensils',
        'description' => 'Your stationery items are currently being prepared and packed.'
    ],
    'completed' => [
        'color' => '#28a745',
        'bg_color' => '#d4edda',
        'icon' => 'fas fa-check-circle',
        'description' => 'Your order has been completed.'
    ],
    'cancelled' => [
        'color' => '#dc3545',
        'bg_color' => '#f8d7da',
        'icon' => 'fas fa-times-circle',
        'description' => 'Your order has been cancelled.'
    ]
];

$current_status = $status_info[$display_order_status] ?? $status_info['pending'];

$page_title = "Order #" . $order['id'] . " - SmartWrite";
$show_sidebar = false;

include 'includes/header.php';
?>

<style>
    .order-details-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        margin-bottom: 20px;
    }
    
    .back-button:hover {
        background: #5a6268;
        transform: translateX(-3px);
        color: white;
        text-decoration: none;
    }
    
    .order-header {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-left: 5px solid #ff6b6b;
    }
    
    .order-number {
        font-size: 2rem;
        color: #333;
        margin: 0 0 10px 0;
    }
    
    .order-date {
        color: #666;
        font-size: 1.1rem;
        margin-bottom: 20px;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .order-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .summary-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        border-top: 3px solid #ff6b6b;
    }
    
    .summary-card h3 {
        color: #333;
        margin: 0 0 10px 0;
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.8;
    }
    
    .summary-card p {
        color: #555;
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    .order-items {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .order-items h2 {
        color: #333;
        margin: 0 0 20px 0;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .item-card {
        display: flex;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s;
    }
    
    .item-card:hover {
        background: #f8f9fa;
    }
    
    .item-image {
        width: 80px;
        height: 80px;
        border-radius: 10px;
        overflow: hidden;
        margin-right: 20px;
        flex-shrink: 0;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .item-image .placeholder {
        font-size: 2rem;
        color: #ccc;
    }
    
    .item-details {
        flex: 1;
    }
    
    .item-name {
        font-size: 1.2rem;
        color: #333;
        margin: 0 0 5px 0;
    }
    
    .item-meta {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 10px;
    }
    
    .item-quantity {
        background: #e9ecef;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .item-price {
        font-size: 1.2rem;
        font-weight: 600;
        color: #ff6b6b;
        min-width: 100px;
        text-align: right;
    }
    
    .order-total {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }
    
    .total-row:last-child {
        border-bottom: none;
    }
    
    .total-label {
        color: #666;
        font-size: 1.1rem;
    }
    
    .total-value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
    }
    
    .grand-total {
        font-size: 1.5rem;
        color: #ff6b6b;
        border-top: 2px solid #ff6b6b;
        margin-top: 20px;
        padding-top: 20px;
    }
    
    .delivery-info {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-top: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .info-section {
        margin-bottom: 25px;
    }
    
    .info-section:last-child {
        margin-bottom: 0;
    }
    
    .info-section h3 {
        color: #333;
        margin: 0 0 15px 0;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .info-section p {
        color: #555;
        margin: 0;
        line-height: 1.6;
    }
    
    .info-section .address {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #ff6b6b;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
        display: block;
    }
    
    @media (max-width: 768px) {
        .order-header,
        .order-items,
        .order-total,
        .delivery-info {
            padding: 20px;
        }
        
        .order-number {
            font-size: 1.5rem;
        }
        
        .order-summary {
            grid-template-columns: 1fr;
        }
        
        .item-card {
            flex-direction: column;
            text-align: center;
        }
        
        .item-image {
            margin-right: 0;
            margin-bottom: 15px;
        }
        
        .item-price {
            text-align: center;
            min-width: auto;
            margin-top: 10px;
        }
        
        .total-row {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .total-value {
            margin-top: 5px;
        }
    }
</style>

<div class="order-details-container">
    <!-- Back Button -->
    <a href="track.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Order Tracking
    </a>
    
    <!-- Order Header -->
    <div class="order-header">
        <h1 class="order-number">Order #<?php echo $order['id']; ?></h1>
        <p class="order-date">Placed on <?php echo $order_date; ?></p>
        
        <div class="status-badge" style="color: <?php echo $current_status['color']; ?>; background: <?php echo $current_status['bg_color']; ?>;">
            <i class="<?php echo $current_status['icon']; ?>"></i>
            <span><?php echo ucfirst($display_order_status); ?></span>
        </div>
        
        <p style="margin-top: 15px; color: #666; max-width: 600px;">
            <?php echo $current_status['description']; ?>
        </p>
    </div>
    
    <!-- Order Summary -->
    <div class="order-summary">
        <div class="summary-card">
            <h3>Customer</h3>
            <p><?php echo $order['customer_name'] ? htmlspecialchars($order['customer_name']) : ($order['username'] ? htmlspecialchars($order['username']) : 'Guest'); ?></p>
        </div>
        
        <div class="summary-card">
            <h3>Service Type</h3>
            <p>
                <?php 
                $service_types = [
                    'delivery' => 'Delivery',
                    'takeout' => 'Takeout',
                    'dine_in' => 'Dine-in'
                ];
                echo $service_types[$order['delivery_method']] ?? 'Delivery';
                ?>
            </p>
        </div>
        
        <div class="summary-card">
            <h3>Payment Status</h3>
            <p>
                <span style="display:inline-block; padding:6px 14px; border-radius:20px; background: <?php echo $payment_bg; ?>; color: <?php echo $payment_color; ?>; font-weight:700;">
                    <?php echo $payment_status; ?>
                </span>
            </p>
        </div>

        <div class="summary-card">
            <h3>Payment Method</h3>
            <p><?php echo htmlspecialchars($order['payment_method'] ?: 'Not specified'); ?></p>
        </div>
        
        <div class="summary-card">
            <h3>Items</h3>
            <p><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></p>
        </div>
    </div>
    
    <!-- Order Items -->
    <div class="order-items">
        <h2>Order Items</h2>
        
        <?php if (!empty($order_items)): ?>
            <?php foreach ($order_items as $item): ?>
                <div class="item-card">
                    <div class="item-image">
                        <?php 
                        $image_path = 'images/products/' . $item['product_image'];
                        if ($item['product_image'] && $item['product_image'] != 'default.jpg' && file_exists($image_path)): 
                        ?>
                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        <?php else: ?>
                            <div class="placeholder">
                                <i class="fas fa-hamburger"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-details">
                        <h3 class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                        <div class="item-meta">
                            <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                        </div>
                    </div>
                    
                    <div class="item-price">
                        RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <p>No items found in this order.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Order Total -->
    <div class="order-total">
        <h2 style="color: #333; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">Order Summary</h2>
        
        <div class="total-row">
            <span class="total-label">Subtotal</span>
            <span class="total-value">RM <?php echo number_format($items_total, 2); ?></span>
        </div>
        
        <div class="total-row">
            <span class="total-label">Delivery Fee</span>
            <span class="total-value">
                <?php 
                $delivery_fee = 0;
                if ($order['delivery_method'] == 'delivery' && $items_total < 20) {
                    $delivery_fee = 3.99;
                }
                echo 'RM ' . number_format($delivery_fee, 2);
                ?>
            </span>
        </div>
        
        <?php if ($order['delivery_method'] == 'delivery' && $items_total < 20): ?>
            <div class="total-row">
                <span class="total-label" style="font-size: 0.9rem; color: #28a745;">
                    <i class="fas fa-info-circle"></i> Free delivery on orders above RM 20
                </span>
            </div>
        <?php endif; ?>
        
        <div class="total-row grand-total">
            <span class="total-label">Total Amount</span>
            <span class="total-value">
                RM <?php 
                $grand_total = $items_total + ($order['delivery_method'] == 'delivery' && $items_total < 20 ? 3.99 : 0);
                echo number_format($grand_total, 2);
                ?>
            </span>
        </div>
    </div>
    
    <!-- Delivery/Contact Information -->
    <div class="delivery-info">
        <h2 style="color: #333; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">Order Information</h2>
        
        <?php if ($order['delivery_method'] == 'delivery' && $order['delivery_address']): ?>
            <div class="info-section">
                <h3><i class="fas fa-map-marker-alt"></i> Delivery Address</h3>
                <div class="address">
                    <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($order['delivery_method'] == 'dine_in' && $order['table_number']): ?>
            <div class="info-section">
                <h3><i class="fas fa-chair"></i> Dine-in Information</h3>
                <p><strong>Table Number:</strong> <?php echo htmlspecialchars($order['table_number']); ?></p>
                <p><strong>Service Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['dine_in_type'])); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($order['delivery_method'] == 'takeout'): ?>
            <div class="info-section">
                <h3><i class="fas fa-store"></i> Pickup Information</h3>
                <p>Order ready for pickup at our store counter.</p>
                <?php if ($order['delivery_address']): ?>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-section">
            <h3><i class="fas fa-user"></i> Contact Information</h3>
            <?php if ($order['customer_name']): ?>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <?php endif; ?>
            
            <?php if ($order['customer_email']): ?>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
            <?php elseif ($order['user_email']): ?>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?></p>
            <?php endif; ?>
            
            <?php if ($order['customer_phone']): ?>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Help Section -->
    <div style="margin-top: 30px; padding: 25px; background: #f8f9fa; border-radius: 10px; text-align: center;">
        <h3 style="color: #333; margin-bottom: 15px;">Need Help With This Order?</h3>
        <p style="color: #666; margin-bottom: 20px;">
            If you have any questions or concerns about your order, our customer support team is here to help.
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="contact.php" class="btn" style="background: #ff6b6b; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-headset"></i> Contact Support
            </a>
            <a href="track.php" class="btn" style="background: #6c757d; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-search"></i> Track Another Order
            </a>
            <?php if (in_array($_SESSION['user_type'], ['admin', 'superadmin'])): ?>
                <a href="admin/edit-order.php?id=<?php echo $order['id']; ?>" class="btn" style="background: #ffc107; color: #000; padding: 12px 25px; border-radius: 8px; text-decoration: none;">
                    <i class="fas fa-edit"></i> Edit Order (Admin)
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php

include 'includes/footer.php';
?>