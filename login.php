<?php

require_once 'includes/config.php';

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header("Location: dashboard.php");
    exit();
}

$page_title = "Login to Online Stationery Store System";
$show_sidebar = false;

$error = '';
$success_message = '';

if (isset($_GET['registered']) && $_GET['registered'] == 'true') {
    $success_message = "Registration successful! Please login with your account.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username_email = mysqli_real_escape_string($conn, trim($_POST['username_email']));
    $password = $_POST['password'];

    $is_email = filter_var($username_email, FILTER_VALIDATE_EMAIL);

    if ($is_email) {
        $query = "SELECT id, username, password, user_type 
                  FROM users 
                  WHERE email='$username_email'";
    } else {
        $query = "SELECT id, username, password, user_type 
                  FROM users 
                  WHERE username='$username_email'";
    }

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];

            mysqli_query(
                $conn,
                "UPDATE users SET last_login = NOW() WHERE id={$user['id']}"
            );

            if (in_array($user['user_type'], ['admin', 'superadmin'])) {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();

        } elseif ($password === $user['password']) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            mysqli_query(
                $conn,
                "UPDATE users 
                 SET password='$hashed_password',
                     last_login = NOW()
                 WHERE id={$user['id']}"
            );

            if (in_array($user['user_type'], ['admin', 'superadmin'])) {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();

        } else {
            $error = "Incorrect password. Please try again.";
        }

    } else {

        if ($is_email) {
            $error = "No account found with this email.";
        } else {
            $error = "No account found with this username.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-wrapper">
<div class="auth-container">

<div class="auth-card">

<div class="auth-header">
    <div class="auth-icon">
        <i class="fas fa-sign-in-alt"></i>
    </div>

    <h1>Welcome Back!</h1>
    <p>Sign in to access your account</p>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <div>
            <h4>Success!</h4>
            <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <h4>Login Failed</h4>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    </div>
<?php endif; ?>

<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="auth-form" id="loginForm">

    <div class="form-group">
        <label for="username_email">
            <i class="fas fa-user"></i> Username or Email
        </label>

        <div class="input-with-icon">
            <input type="text"
                   id="username_email"
                   name="username_email"
                   class="form-control"
                   value="<?php echo isset($_POST['username_email']) ? htmlspecialchars($_POST['username_email']) : ''; ?>"
                   placeholder="Enter your username or email"
                   required autofocus>

            <i class="fas fa-check-circle validation-icon"
               id="inputValid"
               style="display: none;"></i>
        </div>

        <small class="form-text">
            Use your username or email to login
        </small>
    </div>

    <div class="form-group">
        <label for="password">
            <i class="fas fa-lock"></i> Password
        </label>

        <div class="input-with-icon">
            <input type="password"
                   id="password"
                   name="password"
                   class="form-control"
                   placeholder="Enter your password"
                   required>

            <button type="button" class="password-toggle" id="togglePassword">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <small class="form-text">Minimum 6 characters</small>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">
        <i class="fas fa-sign-in-alt"></i> Login
    </button>

</form>
<div style="text-align:center; margin-top:8px;">
    <a href="forgot-password.php" style="color:#3498db; text-decoration:none; font-size:0.9rem;">
        Forgot Password?
    </a>
</div>
<div class="auth-footer">
    <p>
        Don't have an account?
        <a href="register.php" class="auth-link" style="color:#3498db;">
            Register here
        </a>
    </p>

    <p class="back-home">
        <a href="index.php">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </p>
</div>

</div>

<div class="auth-features">

    <div class="feature">
        <i class="fas fa-pencil-alt"></i>
        <h3>Wide Selection</h3>
        <p>Choose from a variety of stationery products</p>
    </div>

    <div class="feature">
        <i class="fas fa-tags"></i>
        <h3>Best Prices</h3>
        <p>Affordable deals for students and offices</p>
    </div>

    <div class="feature">
        <i class="fas fa-history"></i>
        <h3>Order History</h3>
        <p>Track your purchases easily</p>
    </div>

    <div class="feature">
        <i class="fas fa-shield-alt"></i>
        <h3>Secure Account</h3>
        <p>Your information is protected</p>
    </div>

</div>

</div>
</div>

<style>

<style>
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 85vh;
        padding: 20px;
        background: #f8f9fa;
    }
    
    .auth-container {
        display: flex;
        gap: 40px;
        max-width: 1200px;
        width: 100%;
        align-items: stretch;
    }
    
    .auth-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        padding: 50px;
        flex: 1;
        min-width: 450px;
        animation: slideIn 0.5s ease;
    }
    
    .auth-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .auth-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #3498db 0%, #5359ff 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 2rem;
    }
    
    .auth-header h1 {
        color: #333;
        margin-bottom: 10px;
        font-size: 2.2rem;
    }
    
    .auth-header p {
        color: #666;
        font-size: 1.1rem;
    }
    
    .input-with-icon {
        position: relative;
    }
    
    .input-with-icon .validation-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #28a745;
    }
    
    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 1rem;
        padding: 5px;
        transition: color 0.3s;
    }
    
    .password-toggle:hover {
        color: #3498db;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
        font-size: 1rem;
    }
    
    .form-control {
        width: 100%;
        padding: 14px 20px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s;
        background: #f8f9fa;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #3498db;
        background: white;
        box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
    }
    
    .form-text {
        display: block;
        margin-top: 5px;
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 25px 0 30px;
    }
    
    .remember-me {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        color: #555;
        cursor: pointer;
    }
    
    .remember-me input {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .forgot-password {
        color: #3498db;
        text-decoration: none;
        font-size: 0.95rem;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .forgot-password:hover {
        color: #3498db;
        text-decoration: underline;
        transform: translateX(2px);
    }
    
    .btn {
        background: linear-gradient(135deg, #3498db 0%, #5359ff 100%);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-block;
        text-align: center;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(255, 107, 107, 0.3);
    }
    
    .btn-block {
        display: block;
        width: 100%;
    }
    
    .btn-lg {
        padding: 16px;
        font-size: 1.1rem;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 35px;
        padding-top: 25px;
        border-top: 1px solid #e9ecef;
    }
    
    .auth-footer p {
        margin: 12px 0;
        color: #666;
    }
    
    .auth-link {
        color: #3498db;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .auth-link:hover {
        color: #3498db;
        text-decoration: underline;
    }
    
    .back-home a {
        color: #666;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: color 0.3s;
        font-size: 0.95rem;
    }
    
    .back-home a:hover {
        color: #3498db;
    }
    
    .auth-features {
        flex: 0 0 300px;
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    
    .feature {
        background: white;
        padding: 25px 20px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        text-align: center;
        transition: all 0.3s;
        border: 1px solid #f0f0f0;
    }
    
    .feature:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    
    .feature i {
        font-size: 2.5rem;
        color: #3498db;
        margin-bottom: 15px;
        display: block;
    }
    
    .feature h3 {
        color: #333;
        margin-bottom: 10px;
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    .feature p {
        color: #666;
        font-size: 0.95rem;
        margin: 0;
        line-height: 1.5;
    }
    
    .alert {
        display: flex;
        align-items: flex-start;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        animation: slideIn 0.3s ease;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .alert i {
        font-size: 1.5rem;
        margin-right: 15px;
        margin-top: 2px;
    }
    
    .alert h4 {
        margin: 0 0 5px 0;
        font-size: 1.1rem;
    }
    
    .alert p {
        margin: 0;
        font-size: 0.95rem;
        line-height: 1.5;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @media (min-width: 1200px) {
        .auth-container {
            gap: 60px;
        }
        
        .auth-card {
            min-width: 500px;
        }
        
        .auth-features {
            flex: 0 0 350px;
        }
    }
    
    @media (max-width: 1100px) {
        .auth-container {
            gap: 30px;
        }
        
        .auth-card {
            min-width: 400px;
        }
        
        .auth-features {
            flex: 0 0 280px;
        }
    }
    
    @media (max-width: 992px) {
        .auth-features {
            display: none;
        }
        
        .auth-container {
            justify-content: center;
        }
        
        .auth-card {
            max-width: 500px;
            width: 100%;
            min-width: auto;
        }
    }
    
    @media (max-width: 576px) {
        .auth-wrapper {
            padding: 10px;
        }
        
        .auth-card {
            padding: 30px 20px;
        }
        
        .form-options {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .auth-header h1 {
            font-size: 1.8rem;
        }
        
        .auth-header p {
            font-size: 1rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {

        togglePassword.addEventListener('click', function () {

            const type =
                passwordInput.getAttribute('type') === 'password'
                ? 'text'
                : 'password';

            passwordInput.setAttribute('type', type);

            this.innerHTML =
                type === 'password'
                ? '<i class="fas fa-eye"></i>'
                : '<i class="fas fa-eye-slash"></i>';

        });
    }

    const usernameEmailInput = document.getElementById('username_email');
    const inputValidIcon = document.getElementById('inputValid');

    if (usernameEmailInput && inputValidIcon) {

        usernameEmailInput.addEventListener('input', function () {

            const value = this.value.trim();

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const isEmail = emailRegex.test(value);

            const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
            const isUsername = usernameRegex.test(value);

            if (value.length > 2 && (isEmail || isUsername)) {

                inputValidIcon.style.display = 'block';
                usernameEmailInput.style.borderColor = '#3498db';

            } else {

                inputValidIcon.style.display = 'none';
                usernameEmailInput.style.borderColor = '#e9ecef';
            }

        });
    }

    const rememberCheckbox = document.getElementById('remember');

    if (rememberCheckbox) {

        rememberCheckbox.addEventListener('change', function () {

            const label = this.nextElementSibling;

            if (this.checked) {
                label.style.transform = 'scale(1.05)';

                setTimeout(() => {
                    label.style.transform = 'scale(1)';
                }, 200);
            }

        });
    }

    const form = document.getElementById('loginForm');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

    if (form && submitBtn) {

        form.addEventListener('submit', function () {

            if (!form.checkValidity()) {
                return;
            }

            const originalHTML = submitBtn.innerHTML;

            submitBtn.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Logging In...';

            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.innerHTML = originalHTML;
                submitBtn.disabled = false;
            }, 3000);

        });
    }

    if (usernameEmailInput) {

        usernameEmailInput.addEventListener('blur', function () {

            const value = this.value.trim();

            if (value) {

                const isEmail = value.includes('@');
                const label = document.querySelector('label[for="username_email"]');

                if (label) {

                    if (isEmail) {
                        label.innerHTML =
                            '<i class="fas fa-envelope"></i> Email Address';
                    } else {
                        label.innerHTML =
                            '<i class="fas fa-user"></i> Username';
                    }
                }
            }

        });
    }

});
</script>

<?php require_once 'includes/footer.php'; ?>