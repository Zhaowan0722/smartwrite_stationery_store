<?php
require_once 'includes/config.php';

$error = '';
$success = '';
$email = isset($_GET['email']) ? trim($_GET['email']) : (isset($_POST['email']) ? trim($_POST['email']) : '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {
        $error = 'Please enter the 6-digit verification code from your email.';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/',$password) || !preg_match('/[0-9]/',$password) || !preg_match('/[^A-Za-z0-9]/',$password)) {
        $error = 'Password must contain at least 8 characters, one uppercase letter, one number and one symbol.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username FROM users WHERE email = ? AND reset_token = ? AND reset_expiry > NOW() LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $email, $otp);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = mysqli_prepare($conn, "UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
            mysqli_stmt_bind_param($update, 'si', $hashed_password, $user['id']);
            mysqli_stmt_execute($update);
            $success = "Password reset successful. You can now login with your new password.";
        } else {
            $error = 'Invalid or expired verification code. Please request a new code.';
        }
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.reset-wrapper{min-height:70vh;background:#f4f8fc;display:flex;align-items:center;justify-content:center;padding:45px 20px;}
.reset-card{width:620px;max-width:100%;background:#fff;border-radius:22px;box-shadow:0 18px 45px rgba(0,0,0,.12);padding:40px;}
.reset-icon{width:86px;height:86px;border-radius:50%;background:#eef8ff;color:#2d9cdb;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 18px;}
.reset-card h2{text-align:center;margin-bottom:10px;color:#222;}
.reset-card p{text-align:center;color:#666;line-height:1.6;margin-bottom:25px;}
.form-group{margin-bottom:18px;}
.form-group label{display:block;font-weight:700;margin-bottom:8px;}
.form-control{width:100%;height:52px;border:1px solid #ddd;border-radius:12px;padding:0 15px;font-size:15px;background:#fafafa;outline:none;}
.form-control:focus{border-color:#2d9cdb;background:#fff;box-shadow:0 0 0 4px rgba(45,156,219,.13);}
.otp-input{text-align:center;font-size:24px;letter-spacing:8px;font-weight:800;}
.btn-reset{width:100%;height:54px;border:0;border-radius:13px;background:linear-gradient(135deg,#2d9cdb,#3498db);color:white;font-weight:800;font-size:16px;cursor:pointer;margin-top:8px;}
.alert{padding:13px 15px;border-radius:10px;margin-bottom:18px;font-weight:600;}
.alert-danger{background:#fff0f0;color:#c0392b;}
.alert-success{background:#e8f8ef;color:#18864b;}
.reset-links{text-align:center;margin-top:20px;}
.reset-links a{color:#2d9cdb;text-decoration:none;font-weight:700;}
</style>

<section class="reset-wrapper">
    <div class="reset-card">
        <div class="reset-icon"><i class="fas fa-key"></i></div>
        <h2>Reset Password</h2>
        <p>Enter the 6-digit code sent to your email, then create a new password.</p>

        <?php if(isset($_GET['sent'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Verification code sent. Please check your email.</div>
        <?php endif; ?>

        <?php if($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <div class="reset-links"><a href="login.php">Login Now</a></div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label>6-Digit Verification Code</label>
                    <input type="text" name="otp" class="form-control otp-input" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="000000" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8" id="password">
                </div>
                <div style="margin-top:10px">
                    <div id="strengthText">Password strength: Weak</div>
                    <div style="background:#f5f5f5;padding:12px;border-radius:8px;margin-top:8px">
                    <div id="r1">○ At least 8 characters</div>
                    <div id="r2">○ One uppercase letter</div>
                    <div id="r3">○ One number</div>
                    <div id="r4">○ One symbol</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8" id="confirm_password">
                </div>
                <button type="submit" class="btn-reset"><i class="fas fa-save"></i> Reset Password</button>
            </form>
            <div class="reset-links"><a href="forgot-password.php">Resend Code</a> &nbsp; | &nbsp; <a href="login.php">Back To Login</a></div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
const p=document.getElementById('password');
if(p){
p.addEventListener('input',function(){
const v=this.value;
const c1=v.length>=8,c2=/[A-Z]/.test(v),c3=/[0-9]/.test(v),c4=/[^A-Za-z0-9]/.test(v);
document.getElementById('r1').innerHTML=(c1?'✓ ':'○ ')+'At least 8 characters';
document.getElementById('r2').innerHTML=(c2?'✓ ':'○ ')+'One uppercase letter';
document.getElementById('r3').innerHTML=(c3?'✓ ':'○ ')+'One number';
document.getElementById('r4').innerHTML=(c4?'✓ ':'○ ')+'One symbol';
let score=[c1,c2,c3,c4].filter(Boolean).length;
document.getElementById('strengthText').innerHTML='Password strength: '+(score<2?'Weak':score<4?'Medium':'Strong');
});
}
</script>