<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

if (empty($_SESSION['pending_checkout_form'])) {
    header('Location: checkout.php');
    exit();
}

$method = $_GET['method'] ?? '';
$method_map = [
    'Touch n Go eWallet' => [
        'title' => "Touch 'n Go eWallet",
        'short' => 'TNG',
        'icon' => 'TNG',
        'color' => '#0078d7',
        'prefix' => 'TNG',
        'label' => 'Touch n Go eWallet',
        'sub' => 'Wallet payment'
    ],
    'GrabPay' => [
        'title' => 'GrabPay',
        'short' => 'Grab',
        'icon' => 'GP',
        'color' => '#00b14f',
        'prefix' => 'GRAB',
        'label' => 'GrabPay',
        'sub' => 'Wallet payment'
    ],
    'DuitNow' => [
        'title' => 'DuitNow',
        'short' => 'DuitNow',
        'icon' => 'DN',
        'color' => '#d71920',
        'prefix' => 'DN',
        'label' => 'DuitNow',
        'sub' => 'Linked mobile number payment'
    ]
];

if (!isset($method_map[$method])) {
    header('Location: checkout.php');
    exit();
}

$payment = $method_map[$method];
$reference_session_key = 'qr_receipt_reference_' . md5($method . '_' . session_id());
if (empty($_SESSION[$reference_session_key])) {
    $_SESSION[$reference_session_key] = $payment['prefix'] . date('YmdHis') . random_int(100, 999);
}
$generated_reference = $_SESSION[$reference_session_key];

$total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total += ((float)($item['price'] ?? 0)) * ((int)($item['quantity'] ?? 0));
    }
    if ($total < 100) {
        $total += 10;
    }
}

$pending_form = $_SESSION['pending_checkout_form'];
$phone = preg_replace('/\D+/', '', $pending_form['phone'] ?? '');

if ($phone === '' && !empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $phone_result = mysqli_query($conn, "SELECT phone FROM users WHERE id = $uid LIMIT 1");
    if ($phone_result && mysqli_num_rows($phone_result) > 0) {
        $phone_row = mysqli_fetch_assoc($phone_result);
        $phone = preg_replace('/\D+/', '', $phone_row['phone'] ?? '');
    }
}

if ($phone === '') {
    $phone = '601133473876';
}

function smartwrite_mask_phone($phone) {
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) < 7) {
        return $digits;
    }
    return substr($digits, 0, 3) . str_repeat('*', max(3, strlen($digits) - 7)) . substr($digits, -4);
}

$masked_phone = smartwrite_mask_phone($phone);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($payment['title']); ?> Payment | SmartWrite</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}
body{background:rgba(0,0,0,.58);color:#1f2937;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:22px;}
.pay-sheet{width:520px;max-width:100%;background:#fff;border-radius:18px;box-shadow:0 18px 50px rgba(0,0,0,.25);overflow:hidden;}
.pay-header{height:56px;display:flex;align-items:center;justify-content:center;position:relative;border-bottom:1px solid #e5e7eb;font-weight:900;font-size:18px;color:#111827;}
.pay-header a{position:absolute;right:18px;top:50%;transform:translateY(-50%);color:#777;text-decoration:none;font-size:22px;}
.pay-body{padding:18px 22px 24px;}
.section-title{font-size:14px;font-weight:800;margin-bottom:14px;color:#1f2937;}
.amount-box{border:1px solid #dbeafe;background:#eff6ff;border-radius:10px;padding:14px 15px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;font-weight:800;}
.amount-box span:last-child{font-size:22px;color:#3498db;}
.method-card{border:2px solid <?php echo $payment['color']; ?>;background:#fbfdff;border-radius:8px;display:flex;align-items:center;gap:12px;padding:13px 14px;margin-bottom:18px;position:relative;}
.wallet-logo{width:48px;height:34px;border-radius:7px;background:<?php echo $payment['color']; ?>;color:#fff;font-weight:900;font-size:13px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,.12);}
.method-info{flex:1;min-width:0;}
.method-info strong{display:block;font-size:15px;color:#111827;line-height:1.3;}
.method-info small{display:block;color:#6b7280;margin-top:2px;font-size:12px;}
.check-dot{width:20px;height:20px;border-radius:50%;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;}
.protection{display:flex;align-items:center;justify-content:flex-end;gap:6px;font-size:12px;color:#16a34a;margin-bottom:9px;font-weight:700;}
.steps{background:#f8fafc;border-radius:12px;padding:14px 15px;margin:14px 0;color:#4b5563;font-size:13px;line-height:1.7;}
.btn{width:100%;height:50px;border:none;border-radius:10px;font-size:16px;font-weight:900;cursor:pointer;margin-top:10px;display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;}
.pay-btn{background:#3498db;color:#fff;}.pay-btn:hover{background:#2383c4;}.cancel-btn{background:#eef2f7;color:#344054;}
@media(max-width:560px){body{padding:0;align-items:flex-end}.pay-sheet{width:100%;border-radius:18px 18px 0 0}.pay-body{padding:16px 18px 22px}}
</style>
</head>
<body>
<div class="pay-sheet">
    <div class="pay-header">
        Select Payment Method
        <a href="checkout.php" aria-label="Close">&times;</a>
    </div>
    <div class="pay-body">
        <div class="protection"><i class="fas fa-shield-alt"></i> SmartWrite Payment Protection</div>
        <div class="amount-box"><span>Amount Payable</span><span>RM<?php echo number_format($total, 2); ?></span></div>

        <div class="section-title">My saved payment method</div>
        <div class="method-card">
            <div class="wallet-logo"><?php echo htmlspecialchars($payment['icon']); ?></div>
            <div class="method-info">
                <strong><?php echo htmlspecialchars($masked_phone); ?></strong>
                <small><?php echo htmlspecialchars($payment['label']); ?> · Pay with linked phone number</small>
            </div>
            <div class="check-dot"><i class="fas fa-check"></i></div>
        </div>

        <div class="steps">
            <strong><i class="fas fa-list-check"></i> Payment steps:</strong><br>
            1. Confirm the selected <?php echo htmlspecialchars($payment['short']); ?> account.<br>
            2. Click confirm payment to complete the transaction.<br>
            3. Your order will be placed after payment confirmation.
        </div>

        <form method="post" action="checkout.php">
            <input type="hidden" name="gateway_confirmed" value="1">
            <input type="hidden" name="receipt_reference" value="<?php echo htmlspecialchars($generated_reference); ?>">
            <button type="submit" class="btn pay-btn"><i class="fas fa-check-circle"></i> Confirm Payment</button>
        </form>
        <a href="checkout.php" class="btn cancel-btn"><i class="fas fa-arrow-left"></i> Back To Checkout</a>
    </div>
</div>
</body>
</html>
