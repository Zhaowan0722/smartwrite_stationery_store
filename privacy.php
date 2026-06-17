<?php

require_once 'includes/config.php';

$page_title   = "Privacy Policy - SmartWrite";
$current_page = 'privacy.php';

require_once 'includes/header.php';
?>

<div class="content">

    <h1>
        <i class="fas fa-shield-alt" style="color:#3498db;"></i>
        Privacy Policy
    </h1>

    <div class="privacy-content"
         style="
            background:white;
            padding:40px;
            border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,0.05);
         ">

        <div class="last-updated"
             style="
                background:#f8f9fa;
                padding:15px;
                border-radius:5px;
                margin-bottom:30px;
                border-left:4px solid #3498db;
             ">

            <p style="margin:0; color:#666;">
                <i class="fas fa-clock"></i>
                Last updated:
                <?php echo date('F d, Y'); ?>
            </p>
        </div>

        <!-- Section 1 -->
        <div class="privacy-section" style="margin-bottom:30px;">

            <h2 style="
                color:#333;
                margin-bottom:15px;
                border-bottom:2px solid #eee;
                padding-bottom:10px;
            ">
                1. Information We Collect
            </h2>

            <p style="color:#666; line-height:1.6;">
                We collect information when you:
            </p>

            <ul style="color:#666; line-height:1.6; padding-left:20px;">
                <li>Create an account</li>
                <li>Place an order</li>
                <li>Contact customer support</li>
                <li>Subscribe to updates</li>
            </ul>

            <p style="color:#666; line-height:1.6;">
                This may include your username, email address,
                phone number, shipping address,
                and payment details.
            </p>

        </div>

        <!-- Section 2 -->
        <div class="privacy-section" style="margin-bottom:30px;">

            <h2 style="
                color:#333;
                margin-bottom:15px;
                border-bottom:2px solid #eee;
                padding-bottom:10px;
            ">
                2. How We Use Your Information
            </h2>

            <ul style="color:#666; line-height:1.6; padding-left:20px;">
                <li>Process your purchases</li>
                <li>Send order confirmations</li>
                <li>Provide customer support</li>
                <li>Improve our website and services</li>
                <li>Maintain account security</li>
            </ul>

        </div>

        <!-- Section 3 -->
        <div class="privacy-section" style="margin-bottom:30px;">

            <h2 style="
                color:#333;
                margin-bottom:15px;
                border-bottom:2px solid #eee;
                padding-bottom:10px;
            ">
                3. Information Sharing
            </h2>

            <p style="color:#666; line-height:1.6;">
                We do not sell your personal data.
                Information may only be shared when necessary
                for payment processing, delivery,
                or legal requirements.
            </p>

        </div>

        <!-- Section 4 -->
        <div class="privacy-section" style="margin-bottom:30px;">

            <h2 style="
                color:#333;
                margin-bottom:15px;
                border-bottom:2px solid #eee;
                padding-bottom:10px;
            ">
                4. Data Security
            </h2>

            <p style="color:#666; line-height:1.6;">
                We apply reasonable security measures to
                protect your information. Sadly, the internet
                remains the internet.
            </p>

        </div>

        <!-- Section 5 -->
        <div class="privacy-section" style="margin-bottom:30px;">

            <h2 style="
                color:#333;
                margin-bottom:15px;
                border-bottom:2px solid #eee;
                padding-bottom:10px;
            ">
                5. Cookies
            </h2>

            <p style="color:#666; line-height:1.6;">
                Cookies may be used to remember your cart,
                preferences, and improve browsing experience.
            </p>

        </div>

        <!-- Section 6 -->
        <div class="privacy-section" style="margin-bottom:30px;">

            <h2 style="
                color:#333;
                margin-bottom:15px;
                border-bottom:2px solid #eee;
                padding-bottom:10px;
            ">
                6. Your Rights
            </h2>

            <ul style="color:#666; line-height:1.6; padding-left:20px;">
                <li>Access your data</li>
                <li>Correct inaccurate details</li>
                <li>Request deletion</li>
                <li>Unsubscribe from marketing emails</li>
            </ul>

        </div>

        <!-- Contact -->
        <div class="contact-section"
             style="
                background:#ebf5fb;
                padding:20px;
                border-radius:10px;
                margin-top:40px;
                border:1px solid #3498db;
             ">

            <h3 style="color:#333; margin-bottom:15px;">
                <i class="fas fa-envelope" style="color:#3498db;"></i>
                Contact Us
            </h3>

            <p style="color:#666; margin:0; line-height:1.8;">
                Email: smartwrite@stationerystore.com<br>
                Phone: +60 11 3347 3876<br>
                Address: 123,Bukit Beruang, Melaka, Malaysia
            </p>

        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>