<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['pending_checkout_form'])) {
    header('Location: checkout.php');
    exit();
}

$bank_raw = $_POST['bank'] ?? ($_GET['bank'] ?? 'FPX Online Banking');
$bank_name = str_replace('FPX ', '', $bank_raw);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bank Payment Verification</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}
body{min-height:100vh;background:#eef3f8;display:flex;align-items:center;justify-content:center;padding:35px 18px;}
.verify-card{width:430px;background:#fff;border-radius:22px;box-shadow:0 18px 45px rgba(0,0,0,.16);padding:35px;text-align:center;}
.icon{width:95px;height:95px;border-radius:50%;background:#eef8ff;color:#3498db;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:40px;}
.verify-card h2{font-size:27px;margin-bottom:8px;color:#111;}
.verify-card p{color:#667085;line-height:1.6;margin-bottom:22px;}
.pin-box{display:flex;justify-content:center;gap:10px;margin-bottom:18px;}
.pin-box input{width:48px;height:58px;text-align:center;font-size:24px;font-weight:700;border:1px solid #dce4ec;border-radius:12px;outline:none;background:#fff;}
.pin-box input:focus{border-color:#3498db;box-shadow:0 0 0 4px rgba(52,152,219,.12);}
.hint{font-size:13px;color:#667085;margin-bottom:18px;}
.btn{width:100%;height:54px;border:none;border-radius:12px;background:#3498db;color:white;font-size:16px;font-weight:800;cursor:pointer;}
.btn:hover{background:#2383c4;}
.cancel{display:block;margin-top:18px;color:#667085;text-decoration:none;font-weight:600;}
.error{display:none;background:#fff0f0;color:#c0392b;padding:12px;border-radius:10px;margin-bottom:15px;font-weight:700;}
</style>
</head>
<body>
<div class="verify-card">
    <div class="icon"><i class="fas fa-mobile-alt"></i></div>
    <h2><?php echo htmlspecialchars($bank_name); ?> Secure Verification</h2>
    <p>Please enter your 6-digit banking password to authorize this payment.</p>

    <div id="pinError" class="error">Please enter a valid 6-digit password.</div>

    <form id="pinForm" method="post" action="checkout.php">
        <input type="hidden" name="gateway_confirmed" value="1">
        <input type="hidden" id="bankPin" name="bank_pin">

        <div class="pin-box" aria-label="6-digit banking password">
            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
            <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
        </div>

        <div class="hint">For this demo system, any 6-digit number is accepted.</div>

        <button type="submit" class="btn"><i class="fas fa-check-circle"></i> Confirm Payment</button>
    </form>

    <a href="checkout.php" class="cancel"><i class="fas fa-arrow-left"></i> Back to checkout</a>
</div>
<script>
const inputs = document.querySelectorAll('.pin-box input');
const hiddenPin = document.getElementById('bankPin');
const pinError = document.getElementById('pinError');

inputs.forEach((input, index) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/\D/g, '').slice(0, 1);
        if (input.value && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Backspace' && !input.value && index > 0) {
            inputs[index - 1].focus();
        }
    });

    input.addEventListener('paste', (event) => {
        event.preventDefault();
        const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
        pasted.split('').forEach((digit, i) => {
            if (inputs[i]) inputs[i].value = digit;
        });
        if (pasted.length === 6) inputs[5].focus();
    });
});

document.getElementById('pinForm').addEventListener('submit', function(e) {
    const pin = Array.from(inputs).map(input => input.value).join('');
    if (!/^\d{6}$/.test(pin)) {
        e.preventDefault();
        pinError.style.display = 'block';
        inputs[0].focus();
        return;
    }
    hiddenPin.value = pin;
});
</script>
</body>
</html>
