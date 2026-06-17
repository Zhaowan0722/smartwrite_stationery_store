
document.addEventListener('DOMContentLoaded', function () {

    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');

    if (menuToggle && navLinks) {

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('active');
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                navLinks.classList.remove('active');
            }
        });
    }

    const forms = document.querySelectorAll('form');

    forms.forEach(form => {

        form.addEventListener('submit', function (e) {

            const requiredInputs = this.querySelectorAll(
                'input[required], select[required], textarea[required]'
            );

            let isValid = true;

            requiredInputs.forEach(input => {

                if (!input.value.trim()) {

                    isValid = false;
                    input.style.borderColor = '#3498db';

                    if (
                        !input.nextElementSibling ||
                        !input.nextElementSibling.classList.contains('error-msg')
                    ) {
                        const errorMsg = document.createElement('small');

                        errorMsg.className = 'error-msg';
                        errorMsg.textContent = 'This field is required';
                        errorMsg.style.color = '#3498db';
                        errorMsg.style.display = 'block';
                        errorMsg.style.marginTop = '5px';
                        errorMsg.style.fontSize = '0.9rem';

                        input.parentNode.appendChild(errorMsg);
                    }

                } else {

                    input.style.borderColor = '';

                    const errorMsg = input.parentNode.querySelector('.error-msg');

                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });

    document.addEventListener('click', function (e) {

        if (
            e.target.classList.contains('add-to-cart') ||
            (e.target.parentElement &&
             e.target.parentElement.classList.contains('add-to-cart'))
        ) {

            const button = e.target.classList.contains('add-to-cart')
                ? e.target
                : e.target.parentElement;

            const productName = button.getAttribute('data-name');
            const cartCount = document.querySelector('.cart-count');

            if (cartCount) {

                let count = parseInt(cartCount.textContent) || 0;

                count++;
                cartCount.textContent = count;
                cartCount.style.display = 'flex';

                showNotification(`${productName} added to cart!`);
            }
        }
    });

    function showNotification(message) {

        const notification = document.createElement('div');

        notification.className = 'notification';
        notification.textContent = message;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #3498db;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';

            setTimeout(() => {
                notification.remove();
            }, 300);

        }, 3000);
    }

    const style = document.createElement('style');

    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;

    document.head.appendChild(style);

});