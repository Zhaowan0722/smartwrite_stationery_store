<?php

require_once '../includes/config.php';

if (!in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $allowed_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'pending';
    }
    
    $sql = "UPDATE orders SET status = '$status' WHERE id = $order_id";
    if (mysqli_query($conn, $sql)) {
        $message = "Order #$order_id status updated to $status";
    } else {
        $error = "Error updating order: " . mysqli_error($conn);
    }
}

if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $where = [];
    
    if (!empty($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $where[] = "(o.id LIKE '%$search%' OR u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
    }
    
    if (!empty($_GET['status'])) {
        $status = mysqli_real_escape_string($conn, $_GET['status']);
        $where[] = "o.status = '$status'";
    }
    
    if (!empty($_GET['date_from'])) {
        $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
        $where[] = "DATE(o.order_date) >= '$date_from'";
    }
    
    if (!empty($_GET['date_to'])) {
        $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
        $where[] = "DATE(o.order_date) <= '$date_to'";
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $export_sql = "SELECT o.id as order_id, u.username, u.email, o.order_date, 
                          o.total_price, o.status, o.payment_method, o.delivery_address
                   FROM orders o 
                   LEFT JOIN users u ON o.user_id = u.id 
                   $where_clause
                   ORDER BY o.order_date DESC";
    $export_result = mysqli_query($conn, $export_sql);
    
    echo "<html>";
    echo "<head>";
    echo "<style>";
    echo "table { border-collapse: collapse; width: 100%; }";
    echo "th { background-color: #f2f2f2; font-weight: bold; }";
    echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    
    echo "<h2>Orders Report - " . date('Y-m-d') . "</h2>";
    echo "<table>";
    echo "<tr>";
    echo "<th>Order ID</th>";
    echo "<th>Customer Name</th>";
    echo "<th>Email</th>";
    echo "<th>Order Date</th>";
    echo "<th>Total Price</th>";
    echo "<th>Status</th>";
    echo "<th>Payment Method</th>";
    echo "<th>Delivery Address</th>";
    echo "</tr>";
    
    $total_revenue = 0;
    $total_orders = 0;
    while($row = mysqli_fetch_assoc($export_result)) {
        $total_revenue += $row['total_price'];
        $total_orders++;
        echo "<tr>";
        echo "<td>#" . $row['order_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . $row['order_date'] . "</td>";
        echo "<td>$" . number_format($row['total_price'], 2) . "</td>";
        echo "<td>" . ucfirst($row['status']) . "</td>";
        echo "<td>" . ucfirst($row['payment_method']) . "</td>";
        echo "<td>" . htmlspecialchars($row['delivery_address']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<br>";
    echo "<p><strong>Total Orders: " . $total_orders . "</strong></p>";
    echo "<p><strong>Total Revenue: $" . number_format($total_revenue, 2) . "</strong></p>";
    echo "</body>";
    echo "</html>";
    exit();
}

if (isset($_GET['export']) && $_GET['export'] == 'pdf') {

    $where = [];
    
    if (!empty($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $where[] = "(o.id LIKE '%$search%' OR u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
    }
    
    if (!empty($_GET['status'])) {
        $status = mysqli_real_escape_string($conn, $_GET['status']);
        $where[] = "o.status = '$status'";
    }
    
    if (!empty($_GET['date_from'])) {
        $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
        $where[] = "DATE(o.order_date) >= '$date_from'";
    }
    
    if (!empty($_GET['date_to'])) {
        $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
        $where[] = "DATE(o.order_date) <= '$date_to'";
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $export_sql = "SELECT o.id as order_id, u.username, u.email, o.order_date, 
                          o.total_price, o.status, o.payment_method, o.delivery_address
                   FROM orders o 
                   LEFT JOIN users u ON o.user_id = u.id 
                   $where_clause
                   ORDER BY o.order_date DESC";
    $export_result = mysqli_query($conn, $export_sql);

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>Orders Report - ' . date('Y-m-d') . '</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px;
                font-size: 12px;
            }
            .header { 
                text-align: center; 
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .header h1 { 
                margin: 0; 
                color: #333;
                font-size: 24px;
            }
            .header p { 
                margin: 5px 0; 
                color: #666;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 20px;
            }
            th { 
                background-color: #f2f2f2; 
                font-weight: bold;
                color: #333;
            }
            th, td { 
                border: 1px solid #ddd; 
                padding: 8px; 
                text-align: left;
                font-size: 11px;
            }
            .summary { 
                margin-top: 30px;
                padding: 15px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
            }
            .summary p { 
                margin: 5px 0; 
                font-weight: bold;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                color: #666;
                font-size: 10px;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            .page-break {
                page-break-after: always;
            }
            @media print {
                .no-print { display: none; }
                body { margin: 0; }
                .header { margin-top: 0; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Orders Report</h1>
            <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';

    if (!empty($_GET['status']) || !empty($_GET['search']) || !empty($_GET['date_from']) || !empty($_GET['date_to'])) {
        $html .= '<p>Filters: ';
        $filters = [];
        if (!empty($_GET['status'])) $filters[] = 'Status: ' . htmlspecialchars($_GET['status']);
        if (!empty($_GET['search'])) $filters[] = 'Search: ' . htmlspecialchars($_GET['search']);
        if (!empty($_GET['date_from'])) $filters[] = 'From: ' . htmlspecialchars($_GET['date_from']);
        if (!empty($_GET['date_to'])) $filters[] = 'To: ' . htmlspecialchars($_GET['date_to']);
        $html .= implode(' | ', $filters) . '</p>';
    }
    
    $html .= '</div>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Order Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>';
    
    $total_revenue = 0;
    $total_orders = 0;
    $row_count = 0;
    
    while($row = mysqli_fetch_assoc($export_result)) {
        $total_revenue += $row['total_price'];
        $total_orders++;
        $row_count++;

        if ($row_count > 25) {
            $html .= '</tbody></table><div class="page-break"></div><table><thead><tr><th>Order ID</th><th>Customer</th><th>Email</th><th>Order Date</th><th>Total</th><th>Status</th><th>Payment</th><th>Address</th></tr></thead><tbody>';
            $row_count = 0;
        }
        
        $html .= '<tr>
            <td>#' . $row['order_id'] . '</td>
            <td>' . htmlspecialchars($row['username']) . '</td>
            <td>' . htmlspecialchars($row['email']) . '</td>
            <td>' . date('M d, Y H:i', strtotime($row['order_date'])) . '</td>
            <td>$' . number_format($row['total_price'], 2) . '</td>
            <td>' . ucfirst($row['status']) . '</td>
            <td>' . ucfirst($row['payment_method']) . '</td>
            <td>' . htmlspecialchars(substr($row['delivery_address'], 0, 50)) . (strlen($row['delivery_address']) > 50 ? '...' : '') . '</td>
        </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <div class="summary">
            <h3>Summary</h3>
            <p>Total Orders: ' . $total_orders . '</p>
            <p>Total Revenue: $' . number_format($total_revenue, 2) . '</p>
        </div>
        
        <div class="footer">
            <p>Report generated by Restaurant Management System</p>
            <p>Page 1 of 1</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Print / Save as PDF
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Close Window
            </button>
        </div>
        
        <script>
            window.onload = function() {


            }
        </script>
    </body>
    </html>';
    
    echo $html;
    exit();
}

$where = [];

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where[] = "(o.id LIKE '%$search%' OR u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
}

if (!empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $where[] = "o.status = '$status'";
}

if (!empty($_GET['date_from'])) {
    $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
    $where[] = "DATE(o.order_date) >= '$date_from'";
}

if (!empty($_GET['date_to'])) {
    $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
    $where[] = "DATE(o.order_date) <= '$date_to'";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT o.*, u.username, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        $where_clause
        ORDER BY o.order_date DESC";
$orders = mysqli_query($conn, $sql);

$stats_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(total_price) as total_revenue
              FROM orders";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

$page_title = "Manage Orders";
$show_sidebar = true;

include '../includes/header.php';
?>

<div class="admin-header" style="margin-bottom: 30px;">
    <h1><i class="fas fa-shopping-bag"></i> Manage Orders</h1>
    <p>View and manage customer orders</p>
</div>

<?php if (isset($message)): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Order Statistics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: white; border-radius: 10px; padding: 20px; border-left: 4px solid #007bff;">
        <h3 style="margin: 0 0 10px 0; color: #007bff;">Total Orders</h3>
        <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $stats['total_orders']; ?></p>
    </div>
    
    <div style="background: white; border-radius: 10px; padding: 20px; border-left: 4px solid #ffc107;">
        <h3 style="margin: 0 0 10px 0; color: #ffc107;">Pending</h3>
        <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $stats['pending_orders']; ?></p>
    </div>
    
    <div style="background: white; border-radius: 10px; padding: 20px; border-left: 4px solid #17a2b8;">
        <h3 style="margin: 0 0 10px 0; color: #17a2b8;">Processing</h3>
        <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $stats['processing_orders']; ?></p>
    </div>
    
    <div style="background: white; border-radius: 10px; padding: 20px; border-left: 4px solid #28a745;">
        <h3 style="margin: 0 0 10px 0; color: #28a745;">Completed</h3>
        <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $stats['completed_orders']; ?></p>
    </div>
    
    <div style="background: white; border-radius: 10px; padding: 20px; border-left: 4px solid #ff6b6b;">
        <h3 style="margin: 0 0 10px 0; color: #ff6b6b;">Total Revenue</h3>
        <p style="font-size: 2rem; font-weight: bold; margin: 0;">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></p>
    </div>
</div>

<!-- Search and Filter -->
<div style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
    <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
        <div>
            <label>Search Orders</label>
            <input type="text" name="search" placeholder="Order ID, Customer Name, Email..." class="form-control" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </div>
        
        <div>
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
<option value="processing" <?php echo ($_GET['status'] ?? '') == 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="completed" <?php echo ($_GET['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo ($_GET['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div>
            <label>Date From</label>
            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
        </div>
        
        <div>
            <label>Date To</label>
            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
        </div>
        
        <div>
            <button type="submit" class="btn" style="background: #007bff;">
                <i class="fas fa-search"></i> Filter
            </button>
            <a href="manage-orders.php" class="btn" style="background: #6c757d;">
                <i class="fas fa-redo"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div style="background: white; border-radius: 10px; padding: 20px; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f9fa;">
                <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Order ID</th>
                <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Customer</th>
                <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Date</th>
                <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Total</th>
                <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Status</th>
                <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Payment</th>
                <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($order = mysqli_fetch_assoc($orders)): 

                $status_colors = [
                    'pending' => '#ffc107',
                    'processing' => '#17a2b8',
                    'completed' => '#28a745',
                    'cancelled' => '#dc3545'
                ];
                $status_color = $status_colors[$order['status']] ?? '#6c757d';
            ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 15px;">
                    <strong>#<?php echo $order['id']; ?></strong><br>
                    <small style="color: #666;"><?php echo substr($order['order_date'], 0, 10); ?></small>
                </td>
                <td style="padding: 15px;">
                    <strong><?php echo htmlspecialchars($order['username']); ?></strong><br>
                    <small style="color: #666;"><?php echo htmlspecialchars($order['email']); ?></small>
                </td>
                <td style="padding: 15px;">
                    <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?>
                </td>
                <td style="padding: 15px; font-weight: bold;">
                    $<?php echo number_format($order['total_price'], 2); ?>
                </td>
                <td style="padding: 15px;">
                    <span style="display: inline-block; padding: 5px 10px; border-radius: 20px; background: <?php echo $status_color; ?>20; color: <?php echo $status_color; ?>; font-weight: bold;">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </td>
                <td style="padding: 15px;">
                    <?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?>
                </td>
                <td style="padding: 15px;">
                    <div style="display: flex; gap: 10px;">
                        <!-- View Details Button -->
                        <button type="button" class="btn view-order-btn" 
                                data-order-id="<?php echo $order['id']; ?>"
                                style="background: #007bff; padding: 8px 12px; font-size: 0.9rem;">
                            <i class="fas fa-eye"></i> View
                        </button>
                        
                        <!-- Update Status Form -->
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" onchange="this.form.submit()" 
                                    style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9rem;">
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>
                </td>
            </tr>
            
            <!-- Order Details Row (Hidden by default) -->
            <tr id="order-details-<?php echo $order['id']; ?>" style="display: none; background: #f8f9fa;">
                <td colspan="7" style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                        <!-- Order Items -->
                        <div>
                            <h4 style="margin-top: 0; margin-bottom: 15px;">Order Items</h4>
                            <?php
                            $items_sql = "SELECT oi.*, p.name, p.image 
                                         FROM order_items oi 
                                         JOIN products p ON oi.product_id = p.id 
                                         WHERE oi.order_id = {$order['id']}";
                            $items_result = mysqli_query($conn, $items_sql);
                            
                            while($item = mysqli_fetch_assoc($items_result)):
                            ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?ixlib=rb-4.0.3&auto=format&fit=crop&w=50&q=80" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                        <small>Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></small>
                                    </div>
                                </div>
                                <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Order Summary -->
                        <div>
                            <h4 style="margin-top: 0; margin-bottom: 15px;">Order Summary</h4>
                            <div style="background: white; border-radius: 10px; padding: 20px;">
                                <div style="margin-bottom: 10px;">
                                    <strong>Delivery Address:</strong><br>
                                    <p style="margin: 5px 0;"><?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <strong>Payment Method:</strong><br>
                                    <span><?php echo ucfirst($order['payment_method']); ?></span>
                                </div>
                                
                                <div style="border-top: 1px solid #eee; padding-top: 15px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span>Subtotal:</span>
                                        <span>$<?php echo number_format($order['total_price'] - 3.99, 2); ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span>Delivery Fee:</span>
                                        <span>$3.99</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #eee; padding-top: 10px;">
                                        <span>Total:</span>
                                        <span>$<?php echo number_format($order['total_price'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            
                        </div>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <?php if (mysqli_num_rows($orders) == 0): ?>
        <div style="text-align: center; padding: 40px 20px; color: #666;">
            <i class="fas fa-shopping-bag" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
            <h3>No Orders Found</h3>
            <p>There are no orders matching your criteria.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Export Options -->
<div style="margin-top: 20px; text-align: right;">
    <?php

    $export_params = [];
    if (!empty($_GET['search'])) $export_params['search'] = $_GET['search'];
    if (!empty($_GET['status'])) $export_params['status'] = $_GET['status'];
    if (!empty($_GET['date_from'])) $export_params['date_from'] = $_GET['date_from'];
    if (!empty($_GET['date_to'])) $export_params['date_to'] = $_GET['date_to'];
    
    $excel_url = "?" . http_build_query(array_merge($export_params, ['export' => 'excel']));
    $pdf_url = "?" . http_build_query(array_merge($export_params, ['export' => 'pdf']));
    ?>
    
    <a href="<?php echo $excel_url; ?>" class="btn" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; border-radius: 5px;">
        <i class="fas fa-file-excel"></i> Export to Excel
    </a>
    
    <a href="<?php echo $pdf_url; ?>" target="_blank" class="btn" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; border-radius: 5px;">
        <i class="fas fa-file-pdf"></i> Export to PDF
    </a>
</div>

<script>

document.querySelectorAll('.view-order-btn').forEach(button => {
    button.addEventListener('click', function() {
        const orderId = this.getAttribute('data-order-id');
        const detailsRow = document.getElementById('order-details-' + orderId);
        
        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = 'table-row';
            this.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
        } else {
            detailsRow.style.display = 'none';
            this.innerHTML = '<i class="fas fa-eye"></i> View';
        }
    });
});

const dateFrom = document.querySelector('input[name="date_from"]');
const dateTo = document.querySelector('input[name="date_to"]');

if (dateFrom && dateTo) {

    const today = new Date().toISOString().split('T')[0];
    dateFrom.max = today;
    dateTo.max = today;

    dateFrom.addEventListener('change', function() {
        dateTo.min = this.value;
        if (dateTo.value && dateTo.value < this.value) {
            dateTo.value = this.value;
        }
    });

    dateTo.addEventListener('change', function() {
        dateFrom.max = this.value;
        if (dateFrom.value && dateFrom.value > this.value) {
            dateFrom.value = this.value;
        }
    });

    if (dateFrom.value) {
        dateTo.min = dateFrom.value;
    }
    if (dateTo.value) {
        dateFrom.max = dateTo.value;
    }
}
</script>

<?php include '../includes/footer.php'; ?>