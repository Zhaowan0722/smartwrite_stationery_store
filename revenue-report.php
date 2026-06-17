<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/config.php';

$page_title = "Revenue Report";
$show_sidebar = true;
$current_page = 'admin/revenue-report.php';

$revenue_filter = $_GET['revenue_filter'] ?? 'today';
$custom_start = $_GET['start_date'] ?? '';
$custom_end = $_GET['end_date'] ?? '';

switch ($revenue_filter) {
    case 'week':
        $date_condition = "YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)";
        $filter_label = "This Week";
        break;
    case 'month':
        $date_condition = "YEAR(order_date) = YEAR(CURDATE()) AND MONTH(order_date) = MONTH(CURDATE())";
        $filter_label = "This Month";
        break;
    case 'custom':
        if (!empty($custom_start) && !empty($custom_end)) {
            $safe_start = mysqli_real_escape_string($conn, $custom_start);
            $safe_end = mysqli_real_escape_string($conn, $custom_end);
            $date_condition = "DATE(order_date) BETWEEN '$safe_start' AND '$safe_end'";
            $filter_label = date('M d, Y', strtotime($custom_start)) . " - " . date('M d, Y', strtotime($custom_end));
        } else {
            $date_condition = "DATE(order_date) = CURDATE()";
            $filter_label = "Today";
            $revenue_filter = 'today';
        }
        break;
    case 'today':
    default:
        $date_condition = "DATE(order_date) = CURDATE()";
        $filter_label = "Today";
        $revenue_filter = 'today';
        break;
}

$analytics_query = "SELECT 
                        COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_price ELSE 0 END), 0) AS total_revenue,
                        COUNT(*) AS total_orders,
                        SUM(CASE WHEN status != 'cancelled' AND payment_method IS NOT NULL AND payment_method != '' AND payment_method NOT LIKE '%Cash%' THEN 1 ELSE 0 END) AS paid_orders,
                        COALESCE(SUM(CASE WHEN status = 'cancelled' THEN total_price ELSE 0 END), 0) AS refund_amount
                    FROM orders
                    WHERE $date_condition";
$analytics_result = mysqli_query($conn, $analytics_query);
$analytics_stats = mysqli_fetch_assoc($analytics_result);

$payment_summary_query = "SELECT 
                            CASE 
                                WHEN payment_method LIKE '%Online Banking%' OR payment_method LIKE '%FPX%' OR payment_method = 'online' THEN 'Online Banking'
                                WHEN payment_method LIKE '%Card%' OR payment_method LIKE '%Credit%' OR payment_method LIKE '%Debit%' THEN 'Credit / Debit Card'
                                WHEN payment_method LIKE '%Cash%' THEN 'Cash on Delivery'
                                WHEN payment_method LIKE '%eWallet%' OR payment_method LIKE '%Wallet Payment%' OR payment_method LIKE '%Touch n Go%' OR payment_method LIKE '%GrabPay%' OR payment_method LIKE '%DuitNow%' OR payment_method LIKE '%Tng%' THEN 'E-Wallet'
                                ELSE 'Other'
                            END AS method_group,
                            COUNT(*) AS orders_count,
                            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_price ELSE 0 END), 0) AS revenue
                         FROM orders
                         WHERE $date_condition
                         GROUP BY method_group
                         ORDER BY revenue DESC";
$payment_summary_result = mysqli_query($conn, $payment_summary_query);
$payment_summary = [];
$payment_total_revenue = 0;
while ($row = mysqli_fetch_assoc($payment_summary_result)) {
    $row['orders_count'] = (int)$row['orders_count'];
    $row['revenue'] = (float)$row['revenue'];
    $payment_total_revenue += $row['revenue'];
    $payment_summary[] = $row;
}

$payment_labels = [];
$payment_revenue = [];
$payment_percentages = [];
foreach ($payment_summary as $row) {
    $payment_labels[] = $row['method_group'];
    $payment_revenue[] = round($row['revenue'], 2);
    $payment_percentages[] = $payment_total_revenue > 0 ? round(($row['revenue'] / $payment_total_revenue) * 100, 1) : 0;
}

require_once '../includes/header.php';
?>

<style>
    .revenue-page { padding: 25px; }
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 28px;
        border-radius: 15px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .page-header h1 { margin: 0 0 8px 0; font-size: 2rem; }
    .page-header p { margin: 0; opacity: .9; }
    .back-btn {
        color: white;
        background: rgba(255,255,255,.18);
        padding: 11px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
    }
    .filter-card, .chart-card, .table-card {
        background: white;
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 24px;
        box-shadow: 0 5px 20px rgba(0,0,0,.08);
    }
    .filter-card { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px; }
    .filter-card h3 { margin: 0 0 5px 0; color: #333; }
    .filter-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .filter-form select, .filter-form input { padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; }
    .filter-form button {
        border: 0;
        color: white;
        background: #667eea;
        padding: 11px 18px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }
    .analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    .analytics-card {
        background: white;
        border-radius: 14px;
        padding: 22px;
        box-shadow: 0 5px 20px rgba(0,0,0,.08);
        border-left: 5px solid #667eea;
    }
    .analytics-card .label { color: #666; font-size: .95rem; margin-bottom: 12px; font-weight: 600; }
    .analytics-card .value { color: #222; font-size: 1.8rem; font-weight: 800; }
    .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
    .chart-card h3, .table-card h3 { margin: 0 0 18px 0; color: #333; }
    .chart-box { height: 330px; position: relative; }
    .empty-analytics { height: 100%; display: flex; justify-content: center; align-items: center; text-align: center; color: #888; }
    .table-responsive { overflow-x: auto; }
    .admin-table { width: 100%; border-collapse: collapse; min-width: 650px; }
    .admin-table th, .admin-table td { padding: 14px 12px; border-bottom: 1px solid #eee; text-align: left; }
    .admin-table th { background: #f8f9fa; color: #333; font-weight: 700; }
    .percentage-pill { background: #eef2ff; color: #4c63d2; padding: 5px 10px; border-radius: 20px; font-weight: 700; }
    @media (max-width: 768px) { .revenue-page { padding: 15px; } .page-header { padding: 22px; } }
</style>

<div class="revenue-page">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-chart-line"></i> Revenue Report</h1>
            <p>View sales performance, payment method breakdown and refund summary.</p>
        </div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="filter-card">
        <div>
            <h3>Sales Analytics</h3>
            <small>Showing data for: <strong><?php echo htmlspecialchars($filter_label); ?></strong></small>
        </div>
        <form method="GET" class="filter-form">
            <select name="revenue_filter" id="revenue_filter" onchange="toggleCustomDates()">
                <option value="today" <?php echo $revenue_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="week" <?php echo $revenue_filter == 'week' ? 'selected' : ''; ?>>This Week</option>
                <option value="month" <?php echo $revenue_filter == 'month' ? 'selected' : ''; ?>>This Month</option>
                <option value="custom" <?php echo $revenue_filter == 'custom' ? 'selected' : ''; ?>>Custom Date</option>
            </select>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($custom_start); ?>">
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($custom_end); ?>">
            <button type="submit"><i class="fas fa-filter"></i> Apply</button>
        </form>
    </div>

    <div class="analytics-grid">
        <div class="analytics-card"><div class="label"><i class="fas fa-money-bill-wave"></i> Total Revenue</div><div class="value">RM <?php echo number_format($analytics_stats['total_revenue'] ?? 0, 2); ?></div></div>
        <div class="analytics-card"><div class="label"><i class="fas fa-shopping-bag"></i> Orders</div><div class="value"><?php echo $analytics_stats['total_orders'] ?? 0; ?></div></div>
        <div class="analytics-card"><div class="label"><i class="fas fa-check-circle"></i> Paid Orders</div><div class="value"><?php echo $analytics_stats['paid_orders'] ?? 0; ?></div></div>
        <div class="analytics-card"><div class="label"><i class="fas fa-undo"></i> Refund Amount</div><div class="value">RM <?php echo number_format($analytics_stats['refund_amount'] ?? 0, 2); ?></div></div>
    </div>

    <div class="charts-grid">
        <div class="chart-card">
            <h3><i class="fas fa-chart-pie"></i> Payment Method Breakdown</h3>
            <div class="chart-box">
                <?php if ($payment_total_revenue > 0): ?><canvas id="paymentPieChart"></canvas><?php else: ?><div class="empty-analytics">No payment data for this period.</div><?php endif; ?>
            </div>
        </div>
        <div class="chart-card">
            <h3><i class="fas fa-chart-bar"></i> Revenue by Payment Method</h3>
            <div class="chart-box">
                <?php if ($payment_total_revenue > 0): ?><canvas id="paymentBarChart"></canvas><?php else: ?><div class="empty-analytics">No revenue data for this period.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="table-card">
        <h3><i class="fas fa-table"></i> Payment Method Summary</h3>
        <div class="table-responsive">
            <table class="admin-table">
                <thead><tr><th>Payment Method</th><th>Orders</th><th>Revenue</th><th>Percentage</th></tr></thead>
                <tbody>
                    <?php if (!empty($payment_summary)): ?>
                        <?php foreach ($payment_summary as $index => $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['method_group']); ?></td>
                                <td><?php echo $payment['orders_count']; ?></td>
                                <td>RM <?php echo number_format($payment['revenue'], 2); ?></td>
                                <td><span class="percentage-pill"><?php echo $payment_percentages[$index]; ?>%</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;color:#666;padding:20px;">No payment records found for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleCustomDates() {
    const filter = document.getElementById('revenue_filter').value;
    const showCustom = filter === 'custom';
    document.getElementById('start_date').style.display = showCustom ? 'inline-block' : 'none';
    document.getElementById('end_date').style.display = showCustom ? 'inline-block' : 'none';
}
toggleCustomDates();

const paymentLabels = <?php echo json_encode($payment_labels); ?>;
const paymentRevenue = <?php echo json_encode($payment_revenue); ?>;
const paymentPercentages = <?php echo json_encode($payment_percentages); ?>;

if (paymentLabels.length > 0 && document.getElementById('paymentPieChart')) {
    new Chart(document.getElementById('paymentPieChart'), {
        type: 'pie',
        data: { labels: paymentLabels, datasets: [{ data: paymentRevenue, backgroundColor: ['#ff6b6b', '#4dabf7', '#51cf66', '#ffd43b', '#9775fa'], borderWidth: 2, borderColor: '#ffffff' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { tooltip: { callbacks: { label: function(context) { return context.label + ': RM ' + Number(context.raw).toFixed(2) + ' (' + paymentPercentages[context.dataIndex] + '%)'; } } } } }
    });
}

if (paymentLabels.length > 0 && document.getElementById('paymentBarChart')) {
    new Chart(document.getElementById('paymentBarChart'), {
        type: 'bar',
        data: { labels: paymentLabels, datasets: [{ label: 'Revenue (RM)', data: paymentRevenue, backgroundColor: '#667eea', borderRadius: 8 }] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return 'RM ' + value; } } } } }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
