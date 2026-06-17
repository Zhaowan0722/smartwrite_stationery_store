<?php
require_once 'includes/config.php';
require_once 'includes/mailer.php';

$status = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $body = "<h2>SmartWrite Email Test</h2><p>If you received this email, Gmail SMTP is working.</p><p>Time: " . date('Y-m-d H:i:s') . "</p>";
            send_smartwrite_email($email, 'SmartWrite User', 'SmartWrite Email Test', $body);
            $status = 'Test email sent. Check Inbox, Spam or Promotions.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>SmartWrite Mail Test</title>
<style>
body{font-family:Arial;background:#f4f8fc;padding:40px}.box{max-width:560px;margin:auto;background:white;padding:28px;border-radius:16px;box-shadow:0 8px 25px rgba(0,0,0,.1)}input,button{width:100%;padding:13px;margin-top:10px;border-radius:8px;border:1px solid #ccc}button{background:#2d9cdb;color:white;font-weight:bold;border:0}.ok{background:#e8f8ef;color:#18864b;padding:12px;border-radius:8px}.err{background:#fff0f0;color:#c0392b;padding:12px;border-radius:8px;white-space:pre-wrap}
</style>
</head>
<body>
<div class="box">
<h2>SmartWrite Mail Test</h2>
<p>Use this page to test Gmail SMTP before testing Forgot Password.</p>
<?php if($status): ?><div class="ok"><?php echo htmlspecialchars($status); ?></div><?php endif; ?>
<?php if($error): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post">
<label>Send test email to:</label>
<input type="email" name="email" required placeholder="your email">
<button type="submit">Send Test Email</button>
</form>
<p style="font-size:13px;color:#777;margin-top:18px;">If this page shows an SMTP error, copy the error text and fix Gmail App Password / OpenSSL / Internet connection.</p>
</div>
</body>
</html>
