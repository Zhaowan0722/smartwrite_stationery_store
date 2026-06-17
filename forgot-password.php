<?php
require_once 'includes/config.php';
require_once 'includes/mailer.php';
require_once 'includes/mail-config.php';

$message = "";
$error = "";

function smartwrite_column_exists($conn, $table, $column) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $table, $column);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return ((int)$row['total']) > 0;
}

if (!smartwrite_column_exists($conn, 'users', 'reset_token')) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
}
if (!smartwrite_column_exists($conn, 'users', 'reset_expiry')) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN reset_expiry DATETIME NULL");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {

    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, email FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $check = mysqli_stmt_get_result($stmt);

        if ($check && mysqli_num_rows($check) > 0) {

            $user = mysqli_fetch_assoc($check);
            $otp = (string)random_int(100000, 999999);
            $expiry = date('Y-m-d H:i:s', time() + 900); // 15 minutes

            $update = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
            mysqli_stmt_bind_param($update, 'ssi', $otp, $expiry, $user['id']);
            mysqli_stmt_execute($update);

            $subject = "SmartWrite Password Reset Code";
            $safeName = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
            $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

            $email_body = "
            <div style='font-family:Arial,sans-serif;background:#f4f8fc;padding:30px;'>
                <div style='max-width:600px;margin:auto;background:white;border-radius:15px;padding:30px;box-shadow:0 10px 25px rgba(0,0,0,.08);'>
                    <h2 style='color:#2d9cdb;margin-top:0;'>SmartWrite Stationery</h2>
                    <h3>Password Reset Verification Code</h3>
                    <p>Hello <b>{$safeName}</b>,</p>
                    <p>Use the verification code below to reset your SmartWrite account password.</p>
                    <div style='font-size:34px;letter-spacing:8px;font-weight:bold;text-align:center;background:#eef8ff;color:#2d9cdb;border-radius:12px;padding:18px;margin:25px 0;'>{$safeOtp}</div>
                    <p>This code will expire in <b>15 minutes</b>.</p>
                    <p style='color:#999;font-size:12px;'>If you did not request this, you can ignore this email.</p>
                </div>
            </div>";

            try {
                send_smartwrite_email($user['email'], $user['username'], $subject, $email_body);
                header("Location: reset-password.php?email=" . urlencode($user['email']) . "&sent=1");
                exit();
            } catch (Exception $e) {
                $error = "Email failed: " . $e->getMessage();
            }

        } else {
            $error = "Email not found. Please enter a registered email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password | SmartWrite Stationery</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body{background:#f4f8fc;}
.forgot-wrapper{min-height:70vh;display:flex;justify-content:center;align-items:center;padding:45px 20px;}
.forgot-card{width:900px;max-width:100%;background:#fff;border-radius:24px;display:flex;overflow:hidden;box-shadow:0 18px 45px rgba(0,0,0,.12);}
.forgot-left{width:42%;background:linear-gradient(135deg,#2d9cdb,#56c1ff);color:white;padding:55px 35px;text-align:center;display:flex;flex-direction:column;justify-content:center;align-items:center;}
.icon-circle{width:110px;height:110px;border-radius:50%;background:rgba(255,255,255,.22);display:flex;justify-content:center;align-items:center;margin-bottom:25px;}
.icon-circle i{font-size:50px;}
.forgot-left h1{font-size:34px;margin-bottom:15px;}
.forgot-left p{font-size:15px;line-height:1.8;}
.forgot-right{width:58%;padding:55px;}
.forgot-right h2{font-size:32px;color:#222;margin-bottom:10px;}
.subtitle{color:#666;line-height:1.6;margin-bottom:25px;}
.alert-success{background:#e8f8ef;color:#18864b;padding:13px;border-radius:10px;margin-bottom:18px;font-weight:600;}
.alert-danger{background:#fff0f0;color:#c0392b;padding:13px;border-radius:10px;margin-bottom:18px;font-weight:600;}
.input-group{margin-bottom:25px;}
.input-group label{display:block;font-weight:700;margin-bottom:10px;}
.input-box{position:relative;}
.input-box i{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:#2d9cdb;}
.input-box input{width:100%;height:55px;border:1px solid #ddd;border-radius:13px;padding:0 18px 0 50px;font-size:15px;outline:none;background:#fafafa;}
.input-box input:focus{border-color:#2d9cdb;background:white;box-shadow:0 0 0 4px rgba(45,156,219,.13);}
.send-btn{width:100%;height:55px;border:none;border-radius:13px;background:linear-gradient(135deg,#2d9cdb,#3498db);color:white;font-size:16px;font-weight:800;cursor:pointer;}
.back-login{position:relative;z-index:9999;display:inline-flex;align-items:center;gap:8px;margin-top:22px;color:#2d9cdb;text-decoration:none;font-weight:700;cursor:pointer;}
.security-note{margin-top:28px;background:#eef8ff;border-left:5px solid #2d9cdb;padding:14px 16px;border-radius:10px;color:#4b5563;font-size:13px;line-height:1.6;}
@media(max-width:768px){.forgot-card{flex-direction:column;}.forgot-left,.forgot-right{width:100%;}.forgot-right{padding:40px 30px;}}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<section class="forgot-wrapper">
    <div class="forgot-card">
        <div class="forgot-left">
            <div class="icon-circle"><i class="fas fa-envelope-open-text"></i></div>
            <h1>Email OTP</h1>
            <p>We will send a 6-digit verification code to your registered email address.</p>
        </div>
        <div class="forgot-right">
            <h2>Forgot Password?</h2>
            <p class="subtitle">Enter your registered email address. You will receive a 6-digit code to reset your password.</p>
            <?php if($message != ""){ ?><div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div><?php } ?>
            <?php if($error != ""){ ?><div class="alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php } ?>
            <form action="" method="POST">
                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-box"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="example@gmail.com" required></div>
                </div>
                <button type="submit" name="submit" class="send-btn"><i class="fas fa-paper-plane"></i> Send Verification Code</button>
            </form>
            <a href="login.php" class="back-login"><i class="fas fa-arrow-left"></i> Back To Login</a>
            <div class="security-note"><i class="fas fa-shield-alt"></i> The verification code will expire in 15 minutes. Please check Inbox, Spam or Promotions.</div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
</body>
</html>
