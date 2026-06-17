<?php

require_once 'includes/config.php';

$page_title = "Frequently Asked Questions - SmartWrite";
$show_sidebar = false;

require_once 'includes/header.php';
?>

<style>
.faq-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.faq-header {
    text-align: center;
    margin-bottom: 50px;
}

.faq-header h1 {
    color: #3498db;
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.faq-header p {
    color: #666;
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
}

.faq-categories {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.faq-category-btn {
    padding: 12px 25px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 30px;
    color: #555;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.faq-category-btn.active,
.faq-category-btn:hover {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.faq-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.faq-section h2 {
    color: #333;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.faq-section h2 i {
    color: #3498db;
}

.faq-item {
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s;
}

.faq-item.active {
    box-shadow: 0 5px 15px rgba(0,0,0,0.10);
    border-color: #3498db;
}

.faq-question {
    padding: 20px;
    background: #f8f9fa;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s;
}

.faq-question:hover {
    background: #eef6fc;
}

.faq-question h3 {
    margin: 0;
    color: #333;
    font-size: 1.1rem;
    flex: 1;
}

.faq-toggle {
    background: none;
    border: none;
    color: #3498db;
    font-size: 1.2rem;
    cursor: pointer;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.faq-toggle:hover {
    background: #3498db;
    color: white;
}

.faq-answer {
    padding: 0;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item.active .faq-answer {
    padding: 20px;
    max-height: 500px;
}

.faq-answer p {
    color: #555;
    line-height: 1.6;
    margin: 0;
}

.faq-contact {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    border-radius: 15px;
    padding: 40px;
    text-align: center;
    margin-top: 50px;
}

.faq-contact h2 {
    color: white;
    margin-bottom: 15px;
}

.faq-contact p {
    color: rgba(255,255,255,0.92);
    margin-bottom: 25px;
    font-size: 1.1rem;
}

.contact-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: white;
    color: #3498db;
    padding: 12px 30px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.contact-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.20);
}

@media (max-width: 768px) {

    .faq-container {
        padding: 20px 15px;
    }

    .faq-header h1 {
        font-size: 2rem;
    }

    .faq-section {
        padding: 20px;
    }

    .faq-question {
        padding: 15px;
    }

    .faq-question h3 {
        font-size: 1rem;
    }

    .faq-contact {
        padding: 30px 20px;
    }

}
</style>

<div class="faq-container">

    <div class="faq-header">
        <h1>Frequently Asked Questions</h1>
        <p>
            Find quick answers to common questions about
            SmartWrite products, orders, delivery, and more.
        </p>
    </div>

    <div class="faq-categories">
        <button class="faq-category-btn active" data-category="all">
            All Questions
        </button>

        <button class="faq-category-btn" data-category="ordering">
            Ordering
        </button>

        <button class="faq-category-btn" data-category="delivery">
            Delivery
        </button>

        <button class="faq-category-btn" data-category="account">
            Account
        </button>

        <button class="faq-category-btn" data-category="payment">
            Payment
        </button>
    </div>

    <!-- Ordering Questions -->
    <div class="faq-section" data-category="ordering">

        <h2>
            <i class="fas fa-shopping-cart"></i>
            Ordering Questions
        </h2>

        <div class="faq-item">
            <div class="faq-question">
                <h3>How do I place an order?</h3>
                <button class="faq-toggle">+</button>
            </div>

            <div class="faq-answer">
                <p>
                    Browse our products, choose the stationery
                    items you need, add them to your cart,
                    then proceed to checkout and confirm
                    your order.
                </p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <h3>Can I modify or cancel my order?</h3>
                <button class="faq-toggle">+</button>
            </div>

            <div class="faq-answer">
                <p>
                    Orders may be changed or cancelled before
                    they are processed. Once packed or shipped,
                    changes may no longer be possible.
                </p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <h3>Do you sell office and school supplies?</h3>
                <button class="faq-toggle">+</button>
            </div>

            <div class="faq-answer">
                <p>
                    Yes. We provide stationery for students,
                    teachers, offices, and personal use,
                    including pens, notebooks, files,
                    markers, and more.
                </p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <h3>Is there a minimum order amount?</h3>
                <button class="faq-toggle">+</button>
            </div>

            <div class="faq-answer">
                <p>
                    No minimum order is required. Humanity
                    occasionally gets something right.
                </p>
            </div>
        </div>

    </div>

    <!-- Delivery Questions -->
    <div class="faq-section" data-category="delivery">

        <h2>
            <i class="fas fa-shipping-fast"></i>
            Delivery Questions
        </h2>

        <div class="faq-item">
            <div class="faq-question">
                <h3>What are your delivery hours?</h3>
                <button class="faq-toggle">+</button>
            </div>

            <div class="faq-answer">
                <p>
                    Deliveries are available daily from
                    9:00 AM to 9:00 PM.
                </p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <h3>How long does delivery take?</h3>
                <button class="faq-toggle">+</button>
            </div>

            <div class="faq-answer">
                <p>
                    Standard delivery usually takes
                    1 to 3 business days depending on
                    location and stock availability.
                </p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <h3>What areas do you deliver to?</h3>
                <button class="faq-toggle">+</button>
            </div>

            <div class="faq-answer">
                <p>
                    We currently deliver across selected
                    areas in Malaysia. Enter your address
                    during checkout to confirm coverage.
                </p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <h3>What is your delivery fee?</h3>
                <button class="faq-toggle">+</button>
            </div>

            <div class="faq-answer">
                <p>
                    Delivery fees depend on location and
                    order size. The final fee is shown
                    clearly before payment.
                </p>
            </div>
        </div>

    </div>

    <!-- Account Questions -->
<div class="faq-section" data-category="account">

    <h2>
        <i class="fas fa-user-circle"></i>
        Account Questions
    </h2>

    <div class="faq-item">
        <div class="faq-question">
            <h3>How do I create an account?</h3>
            <button class="faq-toggle">+</button>
        </div>

        <div class="faq-answer">
            <p>
                Click the Register button at the top of
                the website. Fill in your details and
                create a password. Bureaucracy, but faster.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            <h3>I forgot my password. How can I reset it?</h3>
            <button class="faq-toggle">+</button>
        </div>

        <div class="faq-answer">
            <p>
                Use the Forgot Password option on the
                login page and follow the reset steps
                sent to your registered email address.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            <h3>Can I view my order history?</h3>
            <button class="faq-toggle">+</button>
        </div>

        <div class="faq-answer">
            <p>
                Yes. After logging in, visit your dashboard
                to view previous orders and account activity.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            <h3>How do I update my account information?</h3>
            <button class="faq-toggle">+</button>
        </div>

        <div class="faq-answer">
            <p>
                Log in to your account and edit your profile
                details such as email address or password.
            </p>
        </div>
    </div>

</div>

<!-- Payment Questions -->
<div class="faq-section" data-category="payment">

    <h2>
        <i class="fas fa-credit-card"></i>
        Payment Questions
    </h2>

    <div class="faq-item">
        <div class="faq-question">
            <h3>What payment methods do you accept?</h3>
            <button class="faq-toggle">+</button>
        </div>

        <div class="faq-answer">
            <p>
                We accept online banking, debit cards,
                credit cards, and selected e-wallets.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            <h3>Is it safe to pay online?</h3>
            <button class="faq-toggle">+</button>
        </div>

        <div class="faq-answer">
            <p>
                Yes. Payments are processed through secure
                gateways using modern encryption standards.
                Humanity occasionally encrypts things well.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            <h3>Do you offer cash on delivery?</h3>
            <button class="faq-toggle">+</button>
        </div>

        <div class="faq-answer">
            <p>
                Cash on delivery depends on delivery area
                and order eligibility at checkout.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            <h3>Can I request a refund?</h3>
            <button class="faq-toggle">+</button>
        </div>

        <div class="faq-answer">
            <p>
                If there is an issue with your order,
                contact support promptly and we will
                review the case fairly.
            </p>
        </div>
    </div>

</div>

<!-- Contact Section -->
<div class="faq-contact">

    <h2>Still Have Questions?</h2>

    <p>
        Can't find what you need? Our support team
        is ready to help.
    </p>

    <a href="contact.php" class="contact-btn">
        <i class="fas fa-headset"></i>
        Contact Support
    </a>

</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {

        const question = item.querySelector('.faq-question');
        const toggle = item.querySelector('.faq-toggle');

        question.addEventListener('click', () => {

            const isActive = item.classList.contains('active');

            faqItems.forEach(otherItem => {

                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.faq-toggle').textContent = '+';
                }

            });

            if (isActive) {
                item.classList.remove('active');
                toggle.textContent = '+';
            } else {
                item.classList.add('active');
                toggle.textContent = '−';
            }

        });

    });

    const categoryButtons =
        document.querySelectorAll('.faq-category-btn');

    const faqSections =
        document.querySelectorAll('.faq-section');

    categoryButtons.forEach(button => {

        button.addEventListener('click', () => {

            const category =
                button.getAttribute('data-category');

            categoryButtons.forEach(btn =>
                btn.classList.remove('active')
            );

            button.classList.add('active');

            faqSections.forEach(section => {

                if (
                    category === 'all' ||
                    section.getAttribute('data-category') === category
                ) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }

            });

            faqItems.forEach(item => {
                item.classList.remove('active');
                item.querySelector('.faq-toggle').textContent = '+';
            });

        });

    });

    function addSearchFunctionality() {

        const searchInput =
            document.createElement('input');

        searchInput.type = 'text';
        searchInput.placeholder =
            'Search FAQ...';
        searchInput.className = 'faq-search';

        searchInput.style.cssText = `
            width:100%;
            max-width:500px;
            padding:12px 20px;
            margin:20px auto 40px;
            display:block;
            border:2px solid #e9ecef;
            border-radius:30px;
            font-size:1rem;
            outline:none;
        `;

        const header =
            document.querySelector('.faq-header');

        header.parentNode.insertBefore(
            searchInput,
            header.nextSibling
        );

        searchInput.addEventListener('input', function () {

            const searchTerm =
                this.value.toLowerCase().trim();

            faqItems.forEach(item => {

                const question =
                    item.querySelector('.faq-question h3')
                    .textContent.toLowerCase();

                const answer =
                    item.querySelector('.faq-answer p')
                    .textContent.toLowerCase();

                if (
                    searchTerm === '' ||
                    question.includes(searchTerm) ||
                    answer.includes(searchTerm)
                ) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }

            });

        });

    }



});
</script>

<?php require_once 'includes/footer.php'; ?>