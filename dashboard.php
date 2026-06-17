<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/config.php';

$page_title = "Admin Dashboard";
$show_sidebar = true;
$current_page = 'dashboard.php';

$admin_name = $_SESSION['username'] ?? 'Admin';
$role_label = ($_SESSION['user_type'] === 'superadmin') ? 'Super Admin' : 'Admin';

function fetch_single($conn, $query, $defaults = []) {
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return array_merge($defaults, mysqli_fetch_assoc($result));
    }
    return $defaults;
}

$total_users = fetch_single($conn, "SELECT COUNT(*) AS total_users FROM users", ['total_users' => 0]);
$new_users_today = fetch_single($conn, "SELECT COUNT(*) AS new_users FROM users WHERE DATE(created_at) = CURDATE()", ['new_users' => 0]);
$total_products = fetch_single($conn, "SELECT COUNT(*) AS total_products FROM products", ['total_products' => 0]);
$low_stock = fetch_single($conn, "SELECT COUNT(*) AS low_stock FROM products WHERE available = 0", ['low_stock' => 0]);
$order_overview = fetch_single($conn, "SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_price), 0) AS total_revenue FROM orders", ['total_orders' => 0, 'total_revenue' => 0]);
$today_overview = fetch_single($conn, "SELECT COUNT(*) AS today_orders, COALESCE(SUM(total_price), 0) AS today_revenue FROM orders WHERE DATE(order_date) = CURDATE()", ['today_orders' => 0, 'today_revenue' => 0]);
$yesterday_overview = fetch_single($conn, "SELECT COALESCE(SUM(total_price), 0) AS yesterday_revenue FROM orders WHERE DATE(order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", ['yesterday_revenue' => 0]);
$refund_overview = fetch_single($conn, "SELECT COUNT(*) AS refund_count, COALESCE(SUM(total_price), 0) AS refund_amount FROM orders WHERE status = 'cancelled'", ['refund_count' => 0, 'refund_amount' => 0]);
$today_refund = fetch_single($conn, "SELECT COUNT(*) AS refund_today_count, COALESCE(SUM(total_price), 0) AS refund_today FROM orders WHERE status = 'cancelled' AND DATE(order_date) = CURDATE()", ['refund_today_count' => 0, 'refund_today' => 0]);

$revenue_difference = floatval($today_overview['today_revenue']) - floatval($yesterday_overview['yesterday_revenue']);
$revenue_note = ($revenue_difference >= 0 ? '+' : '-') . 'RM ' . number_format(abs($revenue_difference), 2) . ' from yesterday';

$status_counts = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'cancelled' => 0];
$status_result = mysqli_query($conn, "SELECT status, COUNT(*) AS total FROM orders GROUP BY status");
while ($status_result && $row = mysqli_fetch_assoc($status_result)) {
    $status_counts[$row['status']] = intval($row['total']);
}

$trend_labels = [];
$trend_values = [];
for ($i = 6; $i >= 0; $i--) {
    $date_key = date('Y-m-d', strtotime("-$i days"));
    $trend_labels[$date_key] = date('D', strtotime($date_key));
    $trend_values[$date_key] = 0;
}
$trend_query = "SELECT DATE(order_date) AS order_day, COALESCE(SUM(total_price), 0) AS revenue
                FROM orders
                WHERE DATE(order_date) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(order_date)
                ORDER BY order_day";
$trend_result = mysqli_query($conn, $trend_query);
while ($trend_result && $row = mysqli_fetch_assoc($trend_result)) {
    if (isset($trend_values[$row['order_day']])) {
        $trend_values[$row['order_day']] = floatval($row['revenue']);
    }
}

$recent_orders_query = "SELECT o.*, COALESCE(u.username, o.customer_name, 'Guest Customer') AS customer_display
                        FROM orders o
                        LEFT JOIN users u ON o.user_id = u.id
                        ORDER BY o.order_date DESC
                        LIMIT 6";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);

require_once '../includes/header.php';
?>

<style>
    .admin-dashboard { padding: 24px; background: #f6f7fb; min-height: 100vh; }
    .dashboard-hero { display:flex; justify-content:space-between; align-items:stretch; gap:20px; background:linear-gradient(135deg,#fff,#fff7f7); border:1px solid #ffe2e2; border-radius:22px; padding:24px; box-shadow:0 12px 30px rgba(31,45,61,.08); margin-bottom:24px; }
    .hero-title h1 { margin:0 0 8px; color:#202124; font-size:2rem; }
    .hero-title p { margin:0; color:#667085; }
    .hero-summary { display:flex; gap:14px; flex-wrap:wrap; margin-top:18px; }
    .hero-pill { background:#fff; border:1px solid #f0eeee; border-radius:16px; padding:12px 16px; min-width:150px; box-shadow:0 6px 18px rgba(0,0,0,.04); }
    .hero-pill span { display:block; color:#667085; font-size:.82rem; margin-bottom:4px; }
    .hero-pill strong { color:#1f2937; font-size:1.05rem; }
    .hero-date { min-width:220px; border-radius:18px; background:#1f2937; color:#fff; padding:18px; display:flex; flex-direction:column; justify-content:center; }
    .hero-date span { opacity:.78; font-size:.9rem; }
    .hero-date strong { font-size:1.35rem; margin-top:6px; }
    .kpi-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; margin-bottom:24px; }
    .kpi-card { background:#fff; border-radius:18px; padding:20px; box-shadow:0 10px 25px rgba(31,45,61,.07); border:1px solid #edf0f5; transition:.25s ease; display:flex; gap:15px; align-items:flex-start; }
    .kpi-card:hover { transform:translateY(-5px); box-shadow:0 16px 34px rgba(31,45,61,.12); }
    .kpi-icon { width:52px; height:52px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:1.35rem; background:#fff1f1; color:#ff6b6b; flex-shrink:0; }
    .kpi-content .label { color:#667085; font-weight:700; font-size:.88rem; margin-bottom:7px; }
    .kpi-content .value { color:#111827; font-weight:800; font-size:1.5rem; line-height:1.1; }
    .kpi-content .note { color:#7a8699; font-size:.83rem; margin-top:7px; }
    .section-card { background:#fff; border:1px solid #edf0f5; border-radius:20px; padding:22px; box-shadow:0 10px 25px rgba(31,45,61,.07); }
    .section-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
    .section-header h3 { margin:0; color:#202124; font-size:1.15rem; display:flex; align-items:center; gap:10px; }
    .section-header a { color:#ff6b6b; text-decoration:none; font-weight:700; }
    .quick-actions-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:15px; margin-bottom:24px; }
    .quick-action { background:#fff; border:1px solid #edf0f5; border-radius:18px; padding:18px; text-decoration:none; color:#1f2937; font-weight:800; box-shadow:0 8px 22px rgba(31,45,61,.06); transition:.25s ease; }
    .quick-action:hover { transform:translateY(-4px); color:#ff6b6b; box-shadow:0 14px 30px rgba(31,45,61,.1); }
    .quick-action i { width:42px; height:42px; border-radius:14px; background:#fff1f1; color:#ff6b6b; display:flex; align-items:center; justify-content:center; margin-bottom:12px; font-size:1.1rem; }
    .dashboard-main-grid { display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:24px; }
    .status-list { display:flex; flex-direction:column; gap:12px; }
    .status-row { display:flex; justify-content:space-between; align-items:center; padding:13px 14px; border-radius:14px; font-weight:800; }
    .status-row span { display:flex; align-items:center; gap:9px; }
    .status-row small { font-size:1rem; }
    .status-pending-row { background:#fff7e6; color:#b76e00; }
    .status-processing-row { background:#eaf3ff; color:#1d64c8; }
    .status-completed-row { background:#eaf8ef; color:#16803a; }
    .status-cancelled-row { background:#fff0f0; color:#cc2b2b; }
    .status-refund-row { background:#f4efff; color:#7446c2; }
    .recent-orders-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:15px; }
    .order-card { border:1px solid #edf0f5; border-radius:18px; padding:16px; background:#fbfcff; transition:.25s ease; }
    .order-card:hover { transform:translateY(-4px); box-shadow:0 12px 25px rgba(31,45,61,.09); background:#fff; }
    .order-top { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; margin-bottom:12px; }
    .order-id { font-weight:900; color:#111827; }
    .order-date { color:#7a8699; font-size:.78rem; }
    .order-customer { color:#4b5563; font-weight:700; margin-bottom:7px; }
    .order-amount { color:#111827; font-size:1.2rem; font-weight:900; margin-bottom:12px; }
    .badge-status { display:inline-flex; align-items:center; gap:7px; padding:7px 11px; border-radius:999px; font-size:.82rem; font-weight:900; }
    .badge-pending { background:#fff7e6; color:#b76e00; }
    .badge-processing { background:#eaf3ff; color:#1d64c8; }
    .badge-completed { background:#eaf8ef; color:#16803a; }
    .badge-cancelled { background:#fff0f0; color:#cc2b2b; }
    .empty-state { padding:28px; text-align:center; color:#7a8699; border:1px dashed #d7dce5; border-radius:16px; }
    @media (max-width:1200px){ .kpi-grid,.quick-actions-grid{grid-template-columns:repeat(2,1fr);} .dashboard-main-grid{grid-template-columns:1fr;} .recent-orders-grid{grid-template-columns:repeat(2,1fr);} }
    @media (max-width:768px){ .admin-dashboard{padding:15px;} .dashboard-hero{flex-direction:column;} .kpi-grid,.quick-actions-grid,.recent-orders-grid{grid-template-columns:1fr;} }
</style>

<div class="admin-dashboard">
    <div class="dashboard-hero">
        <div class="hero-title">
            <h1>Good Afternoon, <?php echo htmlspecialchars($role_label); ?> 👋</h1>
            <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?>. Here is your store overview for today.</p>
            <div class="hero-summary">
                <div class="hero-pill"><span>Today Revenue</span><strong>RM <?php echo number_format($today_overview['today_revenue'], 2); ?></strong></div>
                <div class="hero-pill"><span>Orders Today</span><strong><?php echo intval($today_overview['today_orders']); ?></strong></div>
                <div class="hero-pill"><span>Last Login</span><strong><?php echo date('d M Y'); ?></strong></div>
            </div>
        </div>
        <div class="hero-date">
            <span>Today</span>
            <strong><?php echo date('d M Y'); ?></strong>
            <span><?php echo date('h:i A'); ?></span>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-sack-dollar"></i></div><div class="kpi-content"><div class="label">Revenue</div><div class="value">RM <?php echo number_format($order_overview['total_revenue'], 2); ?></div><div class="note"><?php echo $revenue_note; ?></div></div></div>
        <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-box"></i></div><div class="kpi-content"><div class="label">Orders</div><div class="value"><?php echo intval($order_overview['total_orders']); ?></div><div class="note">+<?php echo intval($today_overview['today_orders']); ?> new today</div></div></div>
        <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-users"></i></div><div class="kpi-content"><div class="label">Customers</div><div class="value"><?php echo intval($total_users['total_users']); ?></div><div class="note">+<?php echo intval($new_users_today['new_users']); ?> new users</div></div></div>
        <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-rotate-left"></i></div><div class="kpi-content"><div class="label">Refunds</div><div class="value">RM <?php echo number_format($refund_overview['refund_amount'], 2); ?></div><div class="note"><?php echo intval($refund_overview['refund_count']); ?> refund requests</div></div></div>
    </div>

    <div class="quick-actions-grid">
        <a class="quick-action" href="add-product.php"><i class="fas fa-plus"></i>Add Product</a>
        <a class="quick-action" href="manage-orders.php"><i class="fas fa-box-open"></i>Manage Orders</a>
        <a class="quick-action" href="manage-users.php"><i class="fas fa-user-group"></i>Manage Users</a>
        <a class="quick-action" href="revenue-report.php"><i class="fas fa-chart-line"></i>Revenue Analytics</a>
        <a class="quick-action" href="manage-orders.php?status=cancelled"><i class="fas fa-rotate-left"></i>Refund Requests</a>
    </div>

    <div class="dashboard-main-grid">
        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-chart-line"></i> Last 7 Days Revenue</h3><a href="revenue-report.php">View Analytics</a></div>
            <canvas id="revenueTrendChart" height="110"></canvas>
        </div>
        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-list-check"></i> Order Status</h3><a href="manage-orders.php">View Orders</a></div>
            <div class="status-list">
                <div class="status-row status-pending-row"><span>🟠 Pending</span><small><?php echo $status_counts['pending']; ?></small></div>
                <div class="status-row status-processing-row"><span>🔵 Processing</span><small><?php echo $status_counts['processing']; ?></small></div>
                <div class="status-row status-completed-row"><span>🟢 Completed</span><small><?php echo $status_counts['completed']; ?></small></div>
                <div class="status-row status-cancelled-row"><span>🔴 Cancelled</span><small><?php echo $status_counts['cancelled']; ?></small></div>
                <div class="status-row status-refund-row"><span>🟣 Refund Requested</span><small><?php echo intval($today_refund['refund_today_count']); ?></small></div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header"><h3><i class="fas fa-clock-rotate-left"></i> Recent Orders</h3><a href="manage-orders.php">View All Orders →</a></div>
        <?php if ($recent_orders_result && mysqli_num_rows($recent_orders_result) > 0): ?>
            <div class="recent-orders-grid">
                <?php while ($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                    <?php $status = strtolower($order['status']); ?>
                    <div class="order-card">
                        <div class="order-top">
                            <div><div class="order-id">#ORD-<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></div><div class="order-date"><?php echo date('d M Y', strtotime($order['order_date'])); ?></div></div>
                            <span class="badge-status badge-<?php echo htmlspecialchars($status); ?>"><?php echo ucfirst($status); ?></span>
                        </div>
                        <div class="order-customer"><?php echo htmlspecialchars($order['customer_display']); ?></div>
                        <div class="order-amount">RM <?php echo number_format($order['total_price'], 2); ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><i class="fas fa-shopping-cart"></i><br>No recent orders found.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const revenueLabels = <?php echo json_encode(array_values($trend_labels)); ?>;
const revenueValues = <?php echo json_encode(array_values($trend_values)); ?>;
new Chart(document.getElementById('revenueTrendChart'), {
    type: 'line',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Revenue (RM)',
            data: revenueValues,
            borderColor: '#ff6b6b',
            backgroundColor: 'rgba(255, 107, 107, 0.12)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
