</main>
</div>

<!-- ====== FOOTER ====== -->
<footer>
    <div class="footer-top">
        <div class="container">
            <div class="footer-content">

                <!-- BRAND -->
                <div class="footer-column">
                    <h3>Online Stationery Store</h3>
                    <p style="color: #bdc3c7; line-height: 1.6; margin-bottom: 20px;">
                        Your one-stop shop for quality stationery products. 
                        Perfect for students, offices, and everyday use.
                    </p>
                </div>

                <!-- QUICK LINKS -->
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <?php
                        $current_dir = dirname($_SERVER['PHP_SELF']);

                        function createFooterLink($page, $title, $current_dir) {
                            if (strpos($current_dir, 'admin') !== false) {
                                echo '<li><a href="../' . $page . '"><i class="fas fa-chevron-right"></i> ' . $title . '</a></li>';
                            } else {
                                echo '<li><a href="' . $page . '"><i class="fas fa-chevron-right"></i> ' . $title . '</a></li>';
                            }
                        }

                        createFooterLink('index.php', 'Home', $current_dir);
                        createFooterLink('menu.php', 'Products', $current_dir);
                        createFooterLink('contact.php', 'Contact Us', $current_dir);
                        createFooterLink('about.php', 'About Us', $current_dir);
                        createFooterLink('feedback.php', 'Submit Feedback', $current_dir);
                        ?>
                    </ul>
                </div>

                <!-- CUSTOMER SERVICE -->
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <ul class="footer-links">
                        <?php
                        createFooterLink('faq.php', 'FAQ', $current_dir);
                        if (isset($is_logged_in) && $is_logged_in) { createFooterLink('track.php', 'Track Order', $current_dir); }
                        createFooterLink('privacy.php', 'Privacy Policy', $current_dir);
                        createFooterLink('terms.php', 'Terms & Conditions', $current_dir);
                        ?>
                    </ul>
                </div>

                <!-- CONTACT INFO -->
                <div class="footer-column">
                    <h3>Contact Info</h3>
                    <div class="contact-info-footer">
                        <p><i class="fas fa-map-marker-alt"></i> 123,Bukit Beruang, Melaka, Malaysia</p>
                        <p><i class="fas fa-phone"></i> +60 11 3347 3876</p>
                        <p><i class="fas fa-envelope"></i> smartwrite@stationerystore.com</p>
                        <p><i class="fas fa-clock"></i> Mon-Sun: 9:00 AM - 9:00 PM</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- BOTTOM -->
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Online Stationery Store System. All rights reserved.</p>
            <p>University Project - Final Year Project</p>

            <div class="payment-methods">
                <i class="fab fa-cc-visa"></i>
                <i class="fab fa-cc-mastercard"></i>
                <i class="fab fa-cc-paypal"></i>
                <i class="fab fa-cc-apple-pay"></i>
            </div>

            <div class="footer-links-bottom">
                <?php
                if (strpos($current_dir, 'admin') !== false) {
                    echo '<a href="../privacy.php">Privacy Policy</a>';
                    echo '<a href="../terms.php">Terms of Service</a>';
                } else {
                    echo '<a href="privacy.php">Privacy Policy</a>';
                    echo '<a href="terms.php">Terms of Service</a>';
                }
                ?>
            </div>
        </div>
    </div>
</footer>

<?php
if (strpos($current_dir, 'admin') !== false) {
    echo '<script src="../js/script.js"></script>';
} else {
    echo '<script src="js/script.js"></script>';
}
?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const guestBtn = document.getElementById("guestButton");
    const guestPopup = document.getElementById("guestOptions");

    if (guestBtn && guestPopup) {

        guestBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            guestPopup.classList.toggle("active");
        });

        document.addEventListener("click", function () {
            guestPopup.classList.remove("active");
        });

    }

});
</script>