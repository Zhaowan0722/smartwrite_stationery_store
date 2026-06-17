<?php

require_once 'includes/config.php';

$page_title = "Register - Online Stationery Store System";
$show_sidebar = false;

$errors = [];
$success = false;
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $form_data = [
        'username' => $username,
        'email' => $email,
        'phone' => $phone
    ];

    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    } elseif (strlen($username) > 20) {
        $errors[] = "Username must be less than 20 characters";
    } else {

        $check_user = "SELECT id FROM users WHERE username='$username'";
        $result = mysqli_query($conn, $check_user);

        if (mysqli_num_rows($result) > 0) {
            $errors[] = "Username already exists";
        }
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {

        $check_email = "SELECT id FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $check_email);

        if (mysqli_num_rows($result) > 0) {
            $errors[] = "Email already registered";
        }
    }

    if (!empty($phone)) {

        $phone_clean = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone_clean) < 10) {
            $errors[] = "Phone number must be at least 10 digits";
        } elseif (strlen($phone_clean) > 15) {
            $errors[] = "Phone number is too long";
        } else {

            $check_phone = "SELECT id FROM users WHERE phone='$phone_clean'";
            $result = mysqli_query($conn, $check_phone);

            if (mysqli_num_rows($result) > 0) {
                $errors[] = "Phone number already registered";
            }
        }

        $phone = $phone_clean;
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one symbol";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (!isset($_POST['terms'])) {
        $errors[] = "You must agree to the terms and conditions";
    }

    if (empty($errors)) {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users
        (username, email, phone, password, user_type, created_at)
        VALUES
        ('$username', '$email', '$phone', '$hashed_password', 'user', NOW())";

        if (mysqli_query($conn, $query)) {

            $success = true;

            $user_id = mysqli_insert_id($conn);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = 'user';

            header("Location: login.php?registered=true");
            exit();

        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">

        <div class="auth-header">
            <div class="auth-icon">
                <i class="fas fa-user-plus"></i>
            </div>

            <h1>Create Account</h1>
            <h2>Register to purchase stationery products online</h2>
        </div>

        <br>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h4>Please fix the following errors:</h4>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="auth-form" id="registerForm">

            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>

                <input type="text"
                       id="username"
                       name="username"
                       class="form-control"
                       value="<?php echo isset($form_data['username']) ? htmlspecialchars($form_data['username']) : ''; ?>"
                       placeholder="Choose a username"
                       required
                       minlength="3"
                       maxlength="20">

                <div class="form-feedback">
                    <span id="usernameFeedback"></span>
                    <span class="char-count">0/20</span>
                </div>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>

                <input type="email"
                       id="email"
                       name="email"
                       class="form-control"
                       value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>"
                       placeholder="Enter your email"
                       required>

                <div class="form-feedback">
                    <span id="emailFeedback"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="phone"><i class="fas fa-phone"></i> Phone Number (Optional)</label>

                <input type="tel"
                       id="phone"
                       name="phone"
                       class="form-control"
                       value="<?php echo isset($form_data['phone']) ? htmlspecialchars($form_data['phone']) : ''; ?>"
                       placeholder="Enter your phone number">

                <div class="form-feedback">
                    <span id="phoneFeedback"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>

                <div class="input-with-icon">
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           placeholder="Create a password"
                           required
                           minlength="8">

                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <div class="password-strength">
                    <div class="strength-meter">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>

                    <span id="strengthText">
                        Password strength:
                        <span id="strengthValue">Weak</span>
                    </span>
                </div>

                <div class="password-requirements">
                    <p><i class="fas fa-check-circle" id="reqLength"></i> At least 8 characters</p>
                    <p><i class="fas fa-check-circle" id="reqUppercase"></i> One uppercase letter</p>
                    <p><i class="fas fa-check-circle" id="reqNumber"></i> One number</p>
                    <p><i class="fas fa-check-circle" id="reqSymbol"></i> One symbol</p>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>

                <div class="input-with-icon">
                    <input type="password"
                           id="confirm_password"
                           name="confirm_password"
                           class="form-control"
                           placeholder="Confirm your password"
                           required>

                    <button type="button" class="password-toggle" id="toggleConfirmPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <div class="form-feedback">
                    <span id="confirmFeedback"></span>
                </div>
            </div>

            <div class="form-group terms">
                <div class="terms-box">
                    <input type="checkbox"
                           id="terms"
                           name="terms"
                           <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>

                    <label for="terms">
                        I agree to the
                        <a href="terms.php" target="_blank">Terms of Service</a>
                        and
                        <a href="privacy.php" target="_blank">Privacy Policy</a>
                    </label>
                </div>

                <div class="form-feedback">
                    <span id="termsFeedback"></span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>

        </form>

        <div style="text-align:center; margin-top:20px; padding:15px; background:#f8f9fa; border-radius:5px;">
            <p style="margin:0; color:#666;">
                Already have an account?

                <a href="login.php"
                   style="color:#3498db; font-weight:600; text-decoration:none;">
                    Login here
                </a>
            </p>
        </div>

        <div class="auth-features">
        </div>

    </div>
</div>

<style>
    
.form-group input[type="tel"] {
    font-family: monospace;
    letter-spacing: 1px;
}


.form-feedback {
    display: flex;
    justify-content: space-between;
    margin-top: 5px;
    font-size: 0.85rem;
}

.form-feedback span {
    color: #666;
}

.char-count {
    color: #888;
}

.password-strength {
    margin-top: 10px;
}

.strength-meter {
    height: 5px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-bar {
    height: 100%;
    width: 0%;
    background: #3498db；
    border-radius: 3px;
    transition: width 0.3s, background 0.3s;
}

.password-requirements {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
    font-size: 0.85rem;
}

.password-requirements p {
    margin: 5px 0;
    color: #666;
    display: flex;
    align-items: center;
    gap: 8px;
}

.password-requirements i {
    color: #ddd;
    transition: color 0.3s;
}

.password-requirements i.valid {
    color: #28a745;
}

.terms-box {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #eee;
}

.terms-box input {
    margin-top: 5px;
}

.terms-box label {
    font-size: 0.9rem;
    line-height: 1.4;
    color: #555;
}

.terms-box a {
    color: #3498db;
    text-decoration: none;
}

.terms-box a:hover {
    text-decoration: underline;
}

.btn-outline {
    background: white;
    color: #3498db;
    border: 2px solid #3498db;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: block;
    text-align: center;
}

.btn-outline:hover {
    background: #ebf5fb;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    setupPasswordToggle('togglePassword', 'password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');

    const usernameInput = document.getElementById('username');
    const charCount = document.querySelector('.char-count');
    const usernameFeedback = document.getElementById('usernameFeedback');

    if (usernameInput && charCount) {

        usernameInput.addEventListener('input', function () {

            const length = this.value.length;
            charCount.textContent = `${length}/20`;

            if (length < 3) {
                usernameFeedback.textContent = 'Too short';
                usernameFeedback.style.color = '#3498db';

            } else if (length > 20) {
                usernameFeedback.textContent = 'Too long';
                usernameFeedback.style.color = '#3498db';

            } else {
                usernameFeedback.textContent = 'Good';
                usernameFeedback.style.color = '#28a745';
            }

        });
    }

    const phoneInput = document.getElementById('phone');
    const phoneFeedback = document.getElementById('phoneFeedback');

    if (phoneInput && phoneFeedback) {

        phoneInput.addEventListener('input', function () {

            let phone = this.value.replace(/[^0-9]/g, '');

            if (phone.length > 3 && phone.length <= 6) {
                phone = phone.replace(/(\d{3})(\d{1,3})/, '$1-$2');

            } else if (phone.length > 6 && phone.length <= 10) {
                phone = phone.replace(/(\d{3})(\d{3})(\d{1,4})/, '$1-$2-$3');

            } else if (phone.length > 10) {
                phone = phone.replace(/(\d{3})(\d{3})(\d{4})(\d{1,})/, '$1-$2-$3-$4');
            }

            this.value = phone;

            const cleanPhone = phone.replace(/[^0-9]/g, '');

            if (cleanPhone === '') {

                phoneFeedback.textContent = '';

            } else if (cleanPhone.length < 10) {

                phoneFeedback.textContent = 'Must be at least 10 digits';
                phoneFeedback.style.color = '#3498db';

            } else if (cleanPhone.length > 15) {

                phoneFeedback.textContent = 'Too long';
                phoneFeedback.style.color = '#3498db';

            } else {

                phoneFeedback.textContent = '✓ Valid phone number';
                phoneFeedback.style.color = '#28a745';
            }

        });
    }

    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthValue = document.getElementById('strengthValue');
    const reqLength = document.getElementById('reqLength');
    const reqUppercase = document.getElementById('reqUppercase');
    const reqNumber = document.getElementById('reqNumber');
    const reqSymbol = document.getElementById('reqSymbol');

    if (passwordInput && strengthBar) {

        passwordInput.addEventListener('input', function () {

            const password = this.value;
            let strength = 0;

            if (password.length >= 8) {
                strength += 25;
                reqLength.classList.add('valid');
            } else {
                reqLength.classList.remove('valid');
            }

            if (/[A-Z]/.test(password)) {
                strength += 25;
                reqUppercase.classList.add('valid');
            } else {
                reqUppercase.classList.remove('valid');
            }

            if (/[0-9]/.test(password)) {
                strength += 25;
                reqNumber.classList.add('valid');
            } else {
                reqNumber.classList.remove('valid');
            }

            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 25;
                reqSymbol.classList.add('valid');
            } else {
                reqSymbol.classList.remove('valid');
            }

            strengthBar.style.width = `${strength}%`;

            if (strength < 50) {

                strengthBar.style.background = '#3498db';
                strengthValue.textContent = 'Weak';
                strengthValue.style.color = '#3498db';

            } else if (strength < 75) {

                strengthBar.style.background = '#f39c12';
                strengthValue.textContent = 'Fair';
                strengthValue.style.color = '#f39c12';

            } else {

                strengthBar.style.background = '#3498db';
                strengthValue.textContent = 'Strong';
                strengthValue.style.color = '#3498db';
            }

        });
    }

    const confirmInput = document.getElementById('confirm_password');
    const confirmFeedback = document.getElementById('confirmFeedback');

    if (confirmInput && confirmFeedback) {

        confirmInput.addEventListener('input', function () {

            const password = passwordInput.value;
            const confirm = this.value;

            if (confirm === '') {

                confirmFeedback.textContent = '';

            } else if (password === confirm) {

                confirmFeedback.textContent = '✓ Passwords match';
                confirmFeedback.style.color = '#28a745';

            } else {

                confirmFeedback.textContent = '✗ Passwords do not match';
                confirmFeedback.style.color = '#3498db';
            }

        });
    }

    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');

    if (form) {

        form.addEventListener('submit', function (e) {

            const terms = document.getElementById('terms');

            if (!terms.checked) {

                e.preventDefault();

                document.getElementById('termsFeedback').textContent =
                    'You must agree to the terms';

                document.getElementById('termsFeedback').style.color =
                    '#dc3545';

                terms.parentElement.style.borderColor = '#3498db';
            }

        });
    }

    const termsCheckbox = document.getElementById('terms');

    if (termsCheckbox) {

        termsCheckbox.addEventListener('change', function () {

            const feedback = document.getElementById('termsFeedback');

            if (this.checked) {

                feedback.textContent = '';
                this.parentElement.style.borderColor = '#3498db';
            }

        });
    }

    function setupPasswordToggle(toggleId, inputId) {

        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);

        if (toggle && input) {

            toggle.addEventListener('click', function () {

                const type =
                    input.getAttribute('type') === 'password'
                    ? 'text'
                    : 'password';

                input.setAttribute('type', type);

                this.innerHTML =
                    type === 'password'
                    ? '<i class="fas fa-eye"></i>'
                    : '<i class="fas fa-eye-slash"></i>';

            });
        }
    }

    const emailInput = document.getElementById('email');
    const emailFeedback = document.getElementById('emailFeedback');

    if (emailInput && emailFeedback) {

        emailInput.addEventListener('input', function () {

            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (email === '') {

                emailFeedback.textContent = '';

            } else if (emailRegex.test(email)) {

                emailFeedback.textContent = '✓ Valid email format';
                emailFeedback.style.color = '#28a745';

            } else {

                emailFeedback.textContent = '✗ Invalid email format';
                emailFeedback.style.color = '#3498db';
            }

        });
    }

});
</script>

<?php require_once 'includes/footer.php'; ?>