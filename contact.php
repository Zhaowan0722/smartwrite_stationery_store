<?php

require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || intval($_SESSION['user_id']) <= 0) {
    $_SESSION['redirect_after_login'] = 'contact.php';
    $_SESSION['login_message'] = 'Please login to contact support.';
    header('Location: login.php');
    exit();
}

$page_title   = "Contact Us - SmartWrite";
$current_page = 'contact.php';

$success = false;
$errors  = [];

$name = '';
$email = '';
$phone = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name)) {
        $errors[] = "Name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($subject)) {
        $errors[] = "Subject is required.";
    }

    if (empty($message)) {
        $errors[] = "Message is required.";
    }

    if (empty($errors)) {

        $sql = "
            INSERT INTO contacts
            (name, email, phone, subject, message)
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sssss",
                $name,
                $email,
                $phone,
                $subject,
                $message
            );

            if (mysqli_stmt_execute($stmt)) {

                $success = true;

                $name = '';
                $email = '';
                $phone = '';
                $subject = '';
                $message = '';

            } else {

                $errors[] =
                    "Failed to send message. Please try again later.";
            }

            mysqli_stmt_close($stmt);

        } else {

            $errors[] =
                "Unable to process your request right now.";
        }
    }
}

require_once 'includes/header.php';
?>

<style>
.contact-page-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}

.contact-page-header {
    text-align: center;
    margin-bottom: 40px;
}

.contact-page-header h1 {
    color: #3498db;
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.contact-page-header p {
    color: #666;
    font-size: 1.1rem;
}

.contact-page-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.contact-details {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 10px;
    border: 1px solid #eee;
}

.contact-detail-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 25px;
}

.contact-detail-item:last-child {
    margin-bottom: 0;
}

.contact-detail-icon {
    width: 50px;
    height: 50px;
    background: #3498db;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.contact-detail-text h3 {
    color: #333;
    margin: 0 0 5px 0;
    font-size: 1.1rem;
}

.contact-detail-text p {
    color: #666;
    margin: 0;
    line-height: 1.5;
}

.contact-page-form {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 1px solid #eee;
}

.contact-form-group {
    margin-bottom: 20px;
}

.contact-form-group label {
    display: block;
    color: #333;
    margin-bottom: 8px;
    font-weight: 600;
}

.contact-form-group input,
.contact-form-group textarea,
.contact-form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.contact-form-group input:focus,
.contact-form-group textarea:focus,
.contact-form-group select:focus {
    outline: none;
    border-color: #3498db;
}

.contact-form-group textarea {
    min-height: 150px;
    resize: vertical;
}

.required {
    color: #3498db;
}

.btn-submit {
    background: #3498db;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
    width: 100%;
}

.btn-submit:hover {
    background: #2980b9;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {

    .contact-page-content {
        grid-template-columns: 1fr;
    }

    .contact-page-container {
        margin: 20px auto;
    }

    .contact-page-header h1 {
        font-size: 2rem;
    }

}
</style>

<div class="contact-page-container">

    <div class="contact-page-header">
        <h1>Contact Us</h1>
        <p>
            Have questions, feedback, or need assistance?
            We'd be glad to hear from you.
        </p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Thank you!</strong>
            Your message has been sent successfully.
            Our team will respond soon.
        </div>

    <?php elseif (!empty($errors)): ?>

        <div class="alert alert-error">

            <strong>Error:</strong>

            <ul style="margin:10px 0 0 20px;">

                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>

    <div class="contact-page-content">

        <!-- Contact Details -->
        <div class="contact-details">

            <div class="contact-detail-item">

                <div class="contact-detail-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>

                <div class="contact-detail-text">
                    <h3>Our Location</h3>
                    <p>
                        SmartWrite Stationery Store<br>
                        123,Bukit Beruang, Melaka
                    </p>
                </div>

            </div>

            <div class="contact-detail-item">

                <div class="contact-detail-icon">
                    <i class="fas fa-phone"></i>
                </div>

                <div class="contact-detail-text">
                    <h3>Phone Number</h3>
                    <p>
                        +60 11 3347 3876<br>
                        Monday - Sunday
                    </p>
                </div>

            </div>

            <div class="contact-detail-item">

                <div class="contact-detail-icon">
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="contact-detail-text">
                    <h3>Email Address</h3>
                    <p>
                       smartwrite@stationerystore.com
                    </p>
                </div>

            </div>

            <div class="contact-detail-item">

                <div class="contact-detail-icon">
                    <i class="fas fa-clock"></i>
                </div>

                <div class="contact-detail-text">
                    <h3>Business Hours</h3>
                    <p>
                        Monday - Sunday<br>
                        9:00 AM - 9:00 PM
                    </p>
                </div>

            </div>

        </div>

                <div class="contact-page-form">

            <form method="POST" action="">

                <div class="contact-form-group">
                    <label for="name">
                        Your Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($name ?? ''); ?>"
                        placeholder="Enter your full name"
                        required>
                </div>

                <div class="contact-form-group">
                    <label for="email">
                        Email Address <span class="required">*</span>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        placeholder="Enter your email address"
                        required>
                </div>

                <div class="contact-form-group">
                    <label for="phone">Phone Number</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                        placeholder="Enter your phone number">
                </div>

                <div class="contact-form-group">
                    <label for="subject">
                        Subject <span class="required">*</span>
                    </label>

                    <select id="subject" name="subject" required>
                        <option value="">Select a subject</option>

                        <option value="General Inquiry"
                            <?php echo ($subject ?? '') == 'General Inquiry' ? 'selected' : ''; ?>>
                            General Inquiry
                        </option>

                        <option value="Order Support"
                            <?php echo ($subject ?? '') == 'Order Support' ? 'selected' : ''; ?>>
                            Order Support
                        </option>

                        <option value="Product Question"
                            <?php echo ($subject ?? '') == 'Product Question' ? 'selected' : ''; ?>>
                            Product Question
                        </option>

                        <option value="Feedback"
                            <?php echo ($subject ?? '') == 'Feedback' ? 'selected' : ''; ?>>
                            Feedback
                        </option>

                        <option value="Partnership"
                            <?php echo ($subject ?? '') == 'Partnership' ? 'selected' : ''; ?>>
                            Partnership
                        </option>

                        <option value="Other"
                            <?php echo ($subject ?? '') == 'Other' ? 'selected' : ''; ?>>
                            Other
                        </option>
                    </select>
                </div>

                <div class="contact-form-group">
                    <label for="message">
                        Your Message <span class="required">*</span>
                    </label>

                    <textarea
                        id="message"
                        name="message"
                        placeholder="How can we help you today?"
                        required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Send Message
                </button>

            </form>

        </div>

    </div>

</div>

<?php require_once 'includes/footer.php'; ?>