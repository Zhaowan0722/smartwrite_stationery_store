<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

$order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
$redirect_url = 'order-confirmation.php';
if ($order_id !== '') {
    $redirect_url .= '?order_id=' . urlencode($order_id);
}

$payment_method_text = strtolower($_SESSION['last_order']['payment_method'] ?? '');

if ($payment_method_text === '' && $order_id !== '' && isset($conn)) {
    $safe_order_id = (int)$order_id;
    $method_result = mysqli_query($conn, "SELECT payment_method FROM orders WHERE id = $safe_order_id LIMIT 1");
    if ($method_result && mysqli_num_rows($method_result) > 0) {
        $method_row = mysqli_fetch_assoc($method_result);
        $payment_method_text = strtolower($method_row['payment_method'] ?? '');
    }
}

$is_cash_payment = (strpos($payment_method_text, 'cash') !== false);
$processing_title = $is_cash_payment ? 'Placing Order...' : 'Processing Payment...';
$processing_description = $is_cash_payment
    ? 'Please wait while SmartWrite confirms your order details.'
    : 'Please wait while SmartWrite securely verifies your transaction.';
$processing_status = $is_cash_payment ? 'Preparing your order record...' : 'Connecting to payment gateway...';
$final_title = $is_cash_payment ? 'Order Placed Successfully!' : 'Payment Successful!';
$final_description = $is_cash_payment
    ? 'Your cash payment is pending. Please make payment upon delivery or collection.'
    : 'Your transaction has been verified successfully.';
$popup_description = $is_cash_payment
    ? 'Your order has been placed successfully. Cash payment is pending until payment is received.'
    : 'Your payment has been completed successfully. Your order is now being processed.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $is_cash_payment ? 'Placing Order' : 'Processing Payment'; ?> | SmartWrite Stationery</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI', Arial, sans-serif;
}

body{
    min-height:100vh;
    background:linear-gradient(135deg,#eaf6ff,#f8fbff 45%,#ffffff);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:25px;
}

.processing-card{
    width:480px;
    max-width:100%;
    background:#fff;
    border-radius:28px;
    padding:45px 35px;
    text-align:center;
    box-shadow:0 22px 60px rgba(20,55,90,.18);
    position:relative;
    overflow:hidden;
}

.processing-card::before{
    content:'';
    position:absolute;
    width:220px;
    height:220px;
    border-radius:50%;
    background:rgba(45,156,219,.08);
    top:-90px;
    right:-90px;
}

.processing-card::after{
    content:'';
    position:absolute;
    width:160px;
    height:160px;
    border-radius:50%;
    background:rgba(39,174,96,.08);
    left:-70px;
    bottom:-70px;
}

.content{
    position:relative;
    z-index:2;
}

.loader{
    width:95px;
    height:95px;
    border:8px solid #e8f4ff;
    border-top:8px solid #2d9cdb;
    border-radius:50%;
    margin:0 auto 25px;
    animation:spin 1s linear infinite;
}

@keyframes spin{
    100%{transform:rotate(360deg);}
}

.success-icon{
    width:98px;
    height:98px;
    background:#eafaf1;
    color:#27ae60;
    border-radius:50%;
    display:none;
    align-items:center;
    justify-content:center;
    font-size:50px;
    margin:0 auto 25px;
    animation:pop .35s ease;
}

@keyframes pop{
    from{transform:scale(.75);opacity:0;}
    to{transform:scale(1);opacity:1;}
}

h2{
    color:#1f2937;
    font-size:30px;
    margin-bottom:12px;
}

p{
    color:#667085;
    line-height:1.7;
    font-size:15px;
}

.progress-wrap{
    width:100%;
    height:9px;
    background:#e8f4ff;
    border-radius:30px;
    overflow:hidden;
    margin:28px 0 8px;
}

.progress{
    height:100%;
    width:0%;
    border-radius:30px;
    background:linear-gradient(135deg,#2d9cdb,#56c1ff);
    animation:loading 3s ease forwards;
}

@keyframes loading{
    100%{width:100%;}
}

.status-text{
    color:#98a2b3;
    font-size:13px;
    margin-top:8px;
}

.success-actions{
    display:none;
    gap:12px;
    margin-top:28px;
}

.btn{
    flex:1;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    padding:14px 12px;
    border-radius:14px;
    text-decoration:none;
    font-weight:800;
    font-size:14px;
}

.primary{
    background:#2d9cdb;
    color:#fff;
}

.secondary{
    background:#eef6fc;
    color:#2d9cdb;
}

.modal-bg{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.45);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:99;
    padding:20px;
}

.success-popup{
    width:430px;
    max-width:100%;
    background:#fff;
    border-radius:26px;
    padding:35px 30px;
    text-align:center;
    box-shadow:0 25px 70px rgba(0,0,0,.28);
    animation:popupIn .3s ease;
}

@keyframes popupIn{
    from{transform:translateY(22px) scale(.92);opacity:0;}
    to{transform:translateY(0) scale(1);opacity:1;}
}

.popup-icon{
    width:95px;
    height:95px;
    border-radius:50%;
    background:#eafaf1;
    color:#27ae60;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:48px;
    margin:0 auto 20px;
}

.success-popup h3{
    font-size:28px;
    color:#1f2937;
    margin-bottom:10px;
}

.success-popup p{
    margin-bottom:24px;
}

.popup-buttons{
    display:flex;
    gap:12px;
}

@media(max-width:520px){
    .success-actions,
    .popup-buttons{
        flex-direction:column;
    }
}
</style>
</head>
<body>

<div class="processing-card">
    <div class="content">
        <div class="loader" id="loader"></div>

        <div class="success-icon" id="successIcon">
            <i class="fas fa-check"></i>
        </div>

        <h2 id="title"><?php echo htmlspecialchars($processing_title); ?></h2>

        <p id="description">
            <?php echo htmlspecialchars($processing_description); ?>
        </p>

        <div class="progress-wrap" id="progressWrap">
            <div class="progress"></div>
        </div>

        <div class="status-text" id="statusText">
            <?php echo htmlspecialchars($processing_status); ?>
        </div>

        <div class="success-actions" id="successActions">
            <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="btn primary">
                <i class="fas fa-receipt"></i> View Order
            </a>
            <a href="menu.php" class="btn secondary">
                <i class="fas fa-bag-shopping"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<div class="modal-bg" id="successModal">
    <div class="success-popup">
        <div class="popup-icon">
            <i class="fas fa-check"></i>
        </div>

        <h3><?php echo htmlspecialchars($final_title); ?></h3>

        <p>
            <?php echo htmlspecialchars($popup_description); ?>
        </p>

        <div class="popup-buttons">
            <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="btn primary">
                <i class="fas fa-receipt"></i> View Order
            </a>
            <a href="menu.php" class="btn secondary">
                <i class="fas fa-bag-shopping"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<script>
const isCashPayment = <?php echo $is_cash_payment ? 'true' : 'false'; ?>;
const finalTitle = <?php echo json_encode($final_title); ?>;
const finalDescription = <?php echo json_encode($final_description); ?>;

setTimeout(function(){
    document.getElementById('statusText').innerHTML = isCashPayment ? 'Confirming order details...' : 'Verifying payment details...';
}, 1200);

setTimeout(function(){
    document.getElementById('statusText').innerHTML = isCashPayment ? 'Finalising your order...' : 'Finalising your order...';
}, 2200);

setTimeout(function(){
    document.getElementById('loader').style.display = 'none';
    document.getElementById('successIcon').style.display = 'flex';
    document.getElementById('title').innerHTML = finalTitle;
    document.getElementById('description').innerHTML = finalDescription;
    document.getElementById('progressWrap').style.display = 'none';
    document.getElementById('statusText').style.display = 'none';
    document.getElementById('successActions').style.display = 'flex';
    document.getElementById('successModal').style.display = 'flex';
}, 3200);
</script>

</body>
</html>
