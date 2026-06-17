<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

$is_logged_in = (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0);



$sw_daily_voucher_limit = 10;
$sw_rm2_daily_voucher_limit = 20;
$sw_voucher_today = date('Y-m-d');

function sw_load_daily_voucher_file($file_path, $today) {
    $data = [
        'date' => $today,
        'claimants' => []
    ];

    if (file_exists($file_path)) {
        $saved_data = json_decode(file_get_contents($file_path), true);

        if (is_array($saved_data)) {
            $data = array_merge($data, $saved_data);
        }
    }

    if (($data['date'] ?? '') !== $today) {
        $data = [
            'date' => $today,
            'claimants' => []
        ];
    }

    if (!isset($data['claimants']) || !is_array($data['claimants'])) {
        $data['claimants'] = [];
    }

    return $data;
}

function sw_save_daily_voucher_file($file_path, $data) {
    @file_put_contents(
        $file_path,
        json_encode($data, JSON_PRETTY_PRINT)
    );
}

$sw_voucher_user_key = '';

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    $sw_voucher_user_key = 'user_' . (int)$_SESSION['user_id'];
} else if (isset($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0) {
    $sw_voucher_user_key = 'customer_' . (int)$_SESSION['customer_id'];
} else {
    $sw_voucher_user_key = 'session_' . session_id();
}


$sw_voucher_file = __DIR__ . '/sw_voucher_claims.json';
$sw_voucher_data = sw_load_daily_voucher_file($sw_voucher_file, $sw_voucher_today);
$sw_user_has_free_shipping_voucher = isset($sw_voucher_data['claimants'][$sw_voucher_user_key]);

if (!empty($_SESSION['free_shipping_voucher_claimed'])) {
    $sw_user_has_free_shipping_voucher = true;
}

$sw_voucher_claimed_count = count($sw_voucher_data['claimants']);
$sw_voucher_remaining = max(0, $sw_daily_voucher_limit - $sw_voucher_claimed_count);


$sw_rm2_voucher_file = __DIR__ . '/sw_rm2_voucher_claims.json';
$sw_rm2_voucher_data = sw_load_daily_voucher_file($sw_rm2_voucher_file, $sw_voucher_today);
$sw_user_has_rm2_voucher = isset($sw_rm2_voucher_data['claimants'][$sw_voucher_user_key]);

if (!empty($_SESSION['rm2_discount_voucher_claimed'])) {
    $sw_user_has_rm2_voucher = true;
}

$sw_rm2_voucher_claimed_count = count($sw_rm2_voucher_data['claimants']);
$sw_rm2_voucher_remaining = max(0, $sw_rm2_daily_voucher_limit - $sw_rm2_voucher_claimed_count);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sw_voucher_action'])) {

    header('Content-Type: application/json');

    if (!$is_logged_in) {
        echo json_encode([
            'success' => false,
            'login_required' => true,
            'message' => 'Please login first to claim this voucher.'
        ]);
        exit();
    }

    $voucher_action = $_POST['sw_voucher_action'];

    if ($voucher_action === 'claim_free_shipping') {

        if ($sw_user_has_free_shipping_voucher) {
            echo json_encode([
                'success' => true,
                'already_claimed' => true,
                'remaining' => $sw_voucher_remaining,
                'message' => 'You already claimed this free shipping voucher today.'
            ]);
            exit();
        }

        if ($sw_voucher_remaining <= 0) {
            echo json_encode([
                'success' => false,
                'sold_out' => true,
                'remaining' => 0,
                'message' => 'Today\'s free shipping vouchers are fully claimed.'
            ]);
            exit();
        }

        $sw_voucher_data['claimants'][$sw_voucher_user_key] = [
            'claimed_at' => date('Y-m-d H:i:s'),
            'voucher_type' => 'free_shipping'
        ];

        sw_save_daily_voucher_file($sw_voucher_file, $sw_voucher_data);

        $_SESSION['free_shipping_voucher_claimed'] = true;
        $_SESSION['delivery_fee_voucher'] = 'free_shipping';

        $sw_voucher_remaining = max(0, $sw_daily_voucher_limit - count($sw_voucher_data['claimants']));

        echo json_encode([
            'success' => true,
            'remaining' => $sw_voucher_remaining,
            'message' => 'Free shipping voucher claimed successfully!'
        ]);
        exit();
    }

    if ($voucher_action === 'claim_rm2_discount') {

        if ($sw_user_has_rm2_voucher) {
            echo json_encode([
                'success' => true,
                'already_claimed' => true,
                'remaining' => $sw_rm2_voucher_remaining,
                'message' => 'You already claimed this RM2 discount voucher today.'
            ]);
            exit();
        }

        if ($sw_rm2_voucher_remaining <= 0) {
            echo json_encode([
                'success' => false,
                'sold_out' => true,
                'remaining' => 0,
                'message' => 'Today\'s RM2 discount vouchers are fully claimed.'
            ]);
            exit();
        }

        $sw_rm2_voucher_data['claimants'][$sw_voucher_user_key] = [
            'claimed_at' => date('Y-m-d H:i:s'),
            'voucher_type' => 'rm2_discount'
        ];

        sw_save_daily_voucher_file($sw_rm2_voucher_file, $sw_rm2_voucher_data);

        $_SESSION['rm2_discount_voucher_claimed'] = true;
        $_SESSION['discount_voucher_amount'] = 2.00;

        $sw_rm2_voucher_remaining = max(0, $sw_rm2_daily_voucher_limit - count($sw_rm2_voucher_data['claimants']));

        echo json_encode([
            'success' => true,
            'remaining' => $sw_rm2_voucher_remaining,
            'message' => 'RM2 discount voucher claimed successfully!'
        ]);
        exit();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Invalid voucher request.'
    ]);
    exit();
}

$initial_cart_count = 0;
$cart_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($is_logged_in && $cart_user_id > 0) {
    $cart_count_sql = "SELECT COALESCE(SUM(quantity), 0) AS total_items FROM cart WHERE user_id = $cart_user_id";
    $cart_count_result = mysqli_query($conn, $cart_count_sql);

    if ($cart_count_result && ($cart_count_row = mysqli_fetch_assoc($cart_count_result))) {
        $initial_cart_count = (int)$cart_count_row['total_items'];
    }
}

$page_title = "Home - SmartWrite";

$show_sidebar = false;

$sql = "SELECT p.*, c.name AS category_name,
               COUNT(oi.product_id) AS total_orders
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        WHERE p.available = TRUE
        GROUP BY p.id
        ORDER BY total_orders DESC, p.id DESC
        LIMIT 8";
$popular_products = mysqli_query($conn, $sql);

$categories_sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC LIMIT 10";
$categories_result = mysqli_query($conn, $categories_sql);

$categories_array = [];
if ($categories_result && mysqli_num_rows($categories_result) > 0) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories_array[] = $row;
    }
}


$product_detail_page = 'product-details.php';

function sw_category_icon($name) {
    $name = strtolower($name ?? '');

    if (strpos($name, 'pen') !== false) return 'fas fa-pen-nib';
    if (strpos($name, 'note') !== false || strpos($name, 'book') !== false) return 'fas fa-book-open';
    if (strpos($name, 'art') !== false) return 'fas fa-palette';
    if (strpos($name, 'office') !== false) return 'fas fa-briefcase';
    if (strpos($name, 'school') !== false) return 'fas fa-school';
    if (strpos($name, 'file') !== false) return 'fas fa-folder-open';
    if (strpos($name, 'marker') !== false) return 'fas fa-highlighter';
    if (strpos($name, 'paper') !== false) return 'fas fa-file-alt';

    return 'fas fa-pencil-alt';
}

function sw_default_product_image($category_name) {
    $category_name = strtolower($category_name ?? 'default');

    if (strpos($category_name, 'pen') !== false) {
        return 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=600&q=80';
    }

    if (strpos($category_name, 'note') !== false || strpos($category_name, 'book') !== false) {
        return 'https://images.unsplash.com/photo-1531346878377-a5be20888e57?auto=format&fit=crop&w=600&q=80';
    }

    if (strpos($category_name, 'art') !== false) {
        return 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=600&q=80';
    }

    if (strpos($category_name, 'office') !== false) {
        return 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=600&q=80';
    }

    return 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=600&q=80';
}

function sw_category_image($category_name) {
    $category_name = strtolower($category_name ?? 'default');

    if (strpos($category_name, 'pen') !== false) {
        return 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=500&q=80';
    }

    if (strpos($category_name, 'note') !== false || strpos($category_name, 'book') !== false) {
        return 'https://images.unsplash.com/photo-1531346878377-a5be20888e57?auto=format&fit=crop&w=500&q=80';
    }

    if (strpos($category_name, 'art') !== false) {
        return 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=500&q=80';
    }

    if (strpos($category_name, 'office') !== false) {
        return 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?auto=format&fit=crop&w=500&q=80';
    }

    if (strpos($category_name, 'school') !== false) {
        return 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=500&q=80';
    }

    return 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=500&q=80';
}


function sw_modal_category_image($group_name) {
    $group_name = strtolower($group_name ?? 'default');

    $images = [
        'writing tools' => 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=500&q=80',
        'notebook & paper' => 'https://images.unsplash.com/photo-1531346878377-a5be20888e57?auto=format&fit=crop&w=500&q=80',
        'art & colouring' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=500&q=80',
        'office tools' => 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?auto=format&fit=crop&w=500&q=80',
        'correction items' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=500&q=80',
        'tape & adhesive' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=500&q=80',
        'school essentials' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=500&q=80',
        'exam supplies' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=500&q=80',
        'geometry tools' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=500&q=80',
        'files & folders' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=500&q=80',
        'bags & cases' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=500&q=80',
        'best deals' => 'https://images.unsplash.com/photo-1607083206968-13611e3d76db?auto=format&fit=crop&w=500&q=80'
    ];

    return $images[$group_name]
        ?? 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=500&q=80';
}

include 'includes/header.php';
?>

<script>
const swIsLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

function swOpenLoginPopup() {
    const popup = document.getElementById('swLoginRequiredPopup');
    if (popup) {
        popup.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function swCloseLoginPopup() {
    const popup = document.getElementById('swLoginRequiredPopup');
    if (popup) {
        popup.classList.remove('active');
        document.body.style.overflow = '';
    }
}

document.addEventListener('DOMContentLoaded', function () {

    const cartForms = document.querySelectorAll('form[data-cart-form]');

    cartForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!swIsLoggedIn) {
                swOpenLoginPopup();
                return;
            }

            const formData = new FormData(this);
            const button = this.querySelector('.sw-add-cart');
            const originalText = button.innerHTML;

            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.disabled = true;

            const postData = new URLSearchParams();
            for (const pair of formData.entries()) {
                postData.append(pair[0], pair[1]);
            }

            fetch('cart-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: postData.toString()
            })
            .then(response => response.json())
            .then(data => {
                button.innerHTML = originalText;
                button.disabled = false;

                if (data.success) {
                    showNotice(data.message);
                    updateCart(data.cart_count);
                } else {
                    showNotice(data.message || 'Unable to add item.', 'error');
                }
            })
            .catch(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                showNotice('Unable to add item.', 'error');
            });
        });
    });

    function showNotice(message, type = 'success') {
        const old = document.querySelector('.cart-notification');
        if (old) old.remove();

        const box = document.createElement('div');
        box.className = 'cart-notification';
        box.innerHTML = message;

        box.style.cssText = `
            position:fixed;
            top:22px;
            right:22px;
            background:${type === 'success' ? '#2196df' : '#e74c3c'};
            color:white;
            padding:14px 22px;
            border-radius:12px;
            z-index:9999;
            font-weight:700;
            box-shadow:0 14px 35px rgba(0,0,0,.18);
        `;

        document.body.appendChild(box);
        setTimeout(() => box.remove(), 3000);
    }

    function findCartBadgeTarget() {
        const selectors = [
            'a[href*="cart.php"]',
            'a[href*="cart"]',
            '.cart-link',
            '.cart-icon',
            '.cart-btn',
            '.cart-container'
        ];

        for (const selector of selectors) {
            const found = document.querySelector(selector);
            if (found) return found;
        }

        const icon = document.querySelector('.fa-shopping-cart, .fa-cart-shopping');
        return icon ? icon.parentElement : null;
    }

    function updateCart(count = null) {
        let badges = document.querySelectorAll('.cart-count, .cart-badge, .sw-cart-badge');

        if (!badges.length) {
            const target = findCartBadgeTarget();

            if (!target) return;

            target.style.position = 'relative';

            const badge = document.createElement('span');
            badge.className = 'sw-cart-badge cart-count';
            target.appendChild(badge);

            badges = document.querySelectorAll('.cart-count, .cart-badge, .sw-cart-badge');
        }

        const firstBadge = badges[0];
        let finalCount = parseInt(count, 10);

        if (Number.isNaN(finalCount)) {
            const currentCount = parseInt(firstBadge.textContent, 10) || 0;
            finalCount = currentCount + 1;
        }

        badges.forEach(badge => {
            badge.textContent = finalCount;
            badge.style.display = finalCount > 0 ? 'inline-flex' : 'none';
        });
    }

    updateCart(<?php echo (int)$initial_cart_count; ?>);

    document.querySelectorAll('.sw-requires-login').forEach(item => {
        item.addEventListener('click', function (e) {
            if (!swIsLoggedIn) {
                e.preventDefault();
                swOpenLoginPopup();
            }
        });
    });

    const loginPopup = document.getElementById('swLoginRequiredPopup');
    const loginPopupClose = document.getElementById('swLoginPopupClose');
    const loginPopupCancel = document.getElementById('swLoginPopupCancel');

    if (loginPopupClose) loginPopupClose.addEventListener('click', swCloseLoginPopup);
    if (loginPopupCancel) loginPopupCancel.addEventListener('click', swCloseLoginPopup);

    if (loginPopup) {
        loginPopup.addEventListener('click', function (e) {
            if (e.target === loginPopup) {
                swCloseLoginPopup();
            }
        });
    }

    const openCategoryModal = document.getElementById('swOpenCategoryModal');
    const closeCategoryModal = document.getElementById('swCloseCategoryModal');
    const categoryModal = document.getElementById('swCategoryModal');


    const openVoucherModal = document.getElementById('swOpenVoucherModal');
    const closeVoucherModal = document.getElementById('swCloseVoucherModal');
    const voucherModal = document.getElementById('swVoucherModal');
    const claimFreeShippingBtn = document.getElementById('swClaimFreeShipping');
    const claimRm2DiscountBtn = document.getElementById('swClaimRm2Discount');
    const voucherMessage = document.getElementById('swVoucherMessage');
    const voucherRemainingText = document.getElementById('swVoucherRemainingText');
    const voucherRemainingMini = document.getElementById('swVoucherRemainingMini');
    const rm2VoucherMessage = document.getElementById('swRm2VoucherMessage');
    const rm2VoucherRemainingText = document.getElementById('swRm2VoucherRemainingText');

    function openVoucherPopup() {
        if (!swIsLoggedIn) {
            swOpenLoginPopup();
            return;
        }

        if (!voucherModal) return;
        voucherModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeVoucherPopup() {
        if (!voucherModal) return;
        voucherModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    function setVoucherMessage(message, type = 'info') {
        if (!voucherMessage) return;
        voucherMessage.textContent = message;
        voucherMessage.className = 'sw-voucher-message ' + type;
        voucherMessage.style.display = 'block';
    }

    function updateVoucherRemaining(remaining) {
        if (voucherRemainingText) {
            voucherRemainingText.textContent = remaining;
        }

        if (voucherRemainingMini) {
            voucherRemainingMini.textContent = 'Free ' + remaining + '/10 · RM2 <?php echo (int)$sw_rm2_voucher_remaining; ?>/20';
        }
    }


    function setRm2VoucherMessage(message, type = 'info') {
        if (!rm2VoucherMessage) return;
        rm2VoucherMessage.textContent = message;
        rm2VoucherMessage.className = 'sw-voucher-message ' + type;
        rm2VoucherMessage.style.display = 'block';
    }

    function updateRm2VoucherRemaining(remaining) {
        if (rm2VoucherRemainingText) {
            rm2VoucherRemainingText.textContent = remaining;
        }

        if (voucherRemainingMini) {
            voucherRemainingMini.textContent = 'Free <?php echo (int)$sw_voucher_remaining; ?>/10 · RM2 ' + remaining + '/20';
        }
    }

    if (openVoucherModal) {
        openVoucherModal.addEventListener('click', function (e) {
            e.preventDefault();
            openVoucherPopup();
        });
    }

    if (closeVoucherModal) {
        closeVoucherModal.addEventListener('click', closeVoucherPopup);
    }

    if (voucherModal) {
        voucherModal.addEventListener('click', function (e) {
            if (e.target === voucherModal) closeVoucherPopup();
        });
    }

    if (claimFreeShippingBtn) {
        claimFreeShippingBtn.addEventListener('click', function () {
            if (!swIsLoggedIn) {
                closeVoucherPopup();
                swOpenLoginPopup();
                return;
            }

            if (this.disabled) return;

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Claiming...';
            this.disabled = true;

            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'sw_voucher_action=claim_free_shipping'
            })
            .then(response => response.json())
            .then(data => {
                if (data.login_required) {
                    closeVoucherPopup();
                    swOpenLoginPopup();
                    return;
                }

                if (data.success) {
                    this.innerHTML = '<i class="fas fa-check-circle"></i> Claimed';
                    this.classList.add('claimed');
                    this.disabled = true;
                    updateVoucherRemaining(data.remaining ?? 0);
                    setVoucherMessage(data.message || 'Voucher claimed successfully!', 'success');
                    showNotice(data.message || 'Voucher claimed successfully!');
                    return;
                }

                this.innerHTML = originalText;
                this.disabled = false;

                if (data.sold_out) {
                    this.innerHTML = '<i class="fas fa-times-circle"></i> Fully Claimed';
                    this.disabled = true;
                    updateVoucherRemaining(0);
                }

                setVoucherMessage(data.message || 'Unable to claim voucher.', 'error');
                showNotice(data.message || 'Unable to claim voucher.', 'error');
            })
            .catch(() => {
                this.innerHTML = originalText;
                this.disabled = false;
                setVoucherMessage('Unable to claim voucher. Please try again.', 'error');
            });
        });
    }


    if (claimRm2DiscountBtn) {
        claimRm2DiscountBtn.addEventListener('click', function () {
            if (!swIsLoggedIn) {
                closeVoucherPopup();
                swOpenLoginPopup();
                return;
            }

            if (this.disabled) return;

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Claiming...';
            this.disabled = true;

            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'sw_voucher_action=claim_rm2_discount'
            })
            .then(response => response.json())
            .then(data => {
                if (data.login_required) {
                    closeVoucherPopup();
                    swOpenLoginPopup();
                    return;
                }

                if (data.success) {
                    this.innerHTML = '<i class="fas fa-check-circle"></i> Claimed';
                    this.classList.add('claimed');
                    this.disabled = true;
                    updateRm2VoucherRemaining(data.remaining ?? 0);
                    setRm2VoucherMessage(data.message || 'RM2 discount voucher claimed successfully!', 'success');
                    showNotice(data.message || 'RM2 discount voucher claimed successfully!');
                    return;
                }

                this.innerHTML = originalText;
                this.disabled = false;

                if (data.sold_out) {
                    this.innerHTML = '<i class="fas fa-times-circle"></i> Fully Claimed';
                    this.disabled = true;
                    updateRm2VoucherRemaining(0);
                }

                setRm2VoucherMessage(data.message || 'Unable to claim voucher.', 'error');
                showNotice(data.message || 'Unable to claim voucher.', 'error');
            })
            .catch(() => {
                this.innerHTML = originalText;
                this.disabled = false;
                setRm2VoucherMessage('Unable to claim voucher. Please try again.', 'error');
            });
        });
    }

    function openModal() {
        if (!categoryModal) return;
        categoryModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!categoryModal) return;
        categoryModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (openCategoryModal) {
        openCategoryModal.addEventListener('click', function (e) {
            e.preventDefault();
            openModal();
        });
    }

    if (closeCategoryModal) {
        closeCategoryModal.addEventListener('click', closeModal);
    }

    if (categoryModal) {
        categoryModal.addEventListener('click', function (e) {
            if (e.target === categoryModal) closeModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
            closeVoucherPopup();
            swCloseLoginPopup();
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const banner = document.getElementById('swMainBannerSlider');
    const dots = document.querySelectorAll('.sw-banner-dot');

    const bannerImages = [
        'index.png',
        'index2.png',
        'index3.png'
    ];

    if (!banner || bannerImages.length <= 1) return;

    let currentBanner = 0;

    function setActiveDot(index) {
        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle('active', dotIndex === index);
        });
    }

    function changeBanner(index) {
        currentBanner = (index + bannerImages.length) % bannerImages.length;

        banner.classList.add('sw-banner-fade-out');

        setTimeout(function () {
            banner.src = bannerImages[currentBanner] + '?v=' + new Date().getTime();
            setActiveDot(currentBanner);
            banner.classList.remove('sw-banner-fade-out');
        }, 250);
    }

    dots.forEach(dot => {
        dot.addEventListener('click', function () {
            const slideIndex = parseInt(this.getAttribute('data-slide'), 10) || 0;
            changeBanner(slideIndex);
        });
    });

    setInterval(function () {
        changeBanner(currentBanner + 1);
    }, 3000);
});
</script>

<style>

.sidebar,
.left-sidebar,
.quick-sidebar {
    display:none !important;
}

.main-content,
.content,
.page-content {
    width:100% !important;
    max-width:100% !important;
    margin-left:0 !important;
}

body {
    background:#f4f8fb !important;
}


.sw-cart-badge,
.cart-count {
    position:absolute !important;
    top:-9px !important;
    right:-11px !important;
    min-width:20px;
    height:20px;
    padding:0 6px;
    border-radius:999px;
    background:#ffffff;
    color:#2196df;
    border:2px solid #2196df;
    font-size:12px;
    font-weight:900;
    line-height:1;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 3px 8px rgba(0,0,0,.18);
    z-index:20;
}

.smartwrite-home {
    width:min(1360px, calc(100vw - 36px));
    margin:0 auto;
    padding:26px 0 55px;
}

.sw-section-title {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    margin:0 0 18px;
}

.sw-section-title h2 {
    color:#243447;
    font-size:1.35rem;
    font-weight:800;
    margin:0;
}

.sw-section-title a {
    color:#2196df;
    font-weight:700;
    text-decoration:none;
}

.sw-promo-grid {
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:12px;
    margin-bottom:18px;
}

.sw-main-banner,
.sw-side-banner {
    position:relative;
    overflow:hidden;
    border-radius:8px;
    background:#2196df;
    color:white;
    box-shadow:0 7px 22px rgba(33,150,223,.15);
}

.sw-main-banner {
    min-height:315px;
    padding:0;
    display:block;
    background:#ffffff;
}

.sw-banner-carousel {
    position:relative;
    width:100%;
    height:315px;
    overflow:hidden;
    border-radius:8px;
    background:#ffffff;
}

.sw-banner-slide {
    position:absolute;
    inset:0;
    display:block;
    opacity:0;
    pointer-events:none;
    transition:opacity .65s ease;
}

.sw-banner-slide.active {
    opacity:1;
    pointer-events:auto;
}

.sw-banner-image-link {
    display:block;
    width:100%;
    height:100%;
}

.sw-banner-image-only {
    width:100%;
    height:315px;
    object-fit:cover;
    display:block;
}

.sw-banner-fade-out {
    opacity:0;
    transition:opacity .25s ease;
}

    transition:opacity .25s ease;
}

.sw-banner-dots {
    position:absolute;
    left:50%;
    bottom:14px;
    transform:translateX(-50%);
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    z-index:5;
}

.sw-banner-dot {
    width:9px;
    height:9px;
    border:0;
    border-radius:999px;
    background:rgba(255,255,255,.72);
    cursor:pointer;
    transition:.25s;
    box-shadow:0 2px 6px rgba(0,0,0,.15);
}

.sw-banner-dot.active {
    width:25px;
    background:#2196df;
}

.sw-primary-btn,
.sw-secondary-btn {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    border-radius:6px;
    padding:13px 25px;
    font-weight:800;
    text-decoration:none;
    transition:.25s;
}

.sw-primary-btn {
    background:white;
    color:#168ad5;
}

.sw-primary-btn:hover {
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,.16);
}

.sw-secondary-btn {
    color:white;
    border:1px solid rgba(255,255,255,.55);
    background:rgba(255,255,255,.12);
}

.sw-secondary-btn:hover {
    background:rgba(255,255,255,.22);
}

.sw-side-stack {
    display:grid;
    grid-template-rows:1fr 1fr;
    gap:12px;
}

.sw-side-banner {
    min-height:150px;
    padding:24px;
    display:flex;
    align-items:center;
    background:
        linear-gradient(135deg, rgba(232,247,255,.93), rgba(175,224,252,.84)),
        url('https://images.unsplash.com/photo-1456735190827-d1262f71b8a3?auto=format&fit=crop&w=700&q=80');
    background-size:cover;
    background-position:center;
    color:#17476a;
    border:1px solid #d5edfb;
    box-shadow:0 7px 22px rgba(33,150,223,.10);
}

.sw-side-banner.second {
    background:
        linear-gradient(135deg, rgba(241,250,255,.94), rgba(191,233,255,.82)),
        url('https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=700&q=80');
    background-size:cover;
    background-position:center;
}

.sw-side-banner h3 {
    font-size:1.55rem;
    line-height:1.12;
    margin:0 0 8px;
    font-weight:900;
    color:#0e4f80;
    text-shadow:none;
}

.sw-side-banner p {
    margin:0 0 12px;
    color:#315a75;
    font-weight:600;
}

.sw-mini-link {
    display:inline-flex;
    align-items:center;
    color:white;
    background:#2196df;
    padding:7px 13px;
    border-radius:999px;
    font-weight:800;
    text-decoration:none;
    box-shadow:0 5px 14px rgba(33,150,223,.20);
}

.sw-mini-link:hover {
    background:#168ad5;
}

.sw-shortcuts {
    background:white;
    border-radius:8px;
    padding:18px 18px 14px;
    margin-bottom:22px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
    display:grid;
    grid-template-columns:repeat(8, 1fr);
    gap:12px;
}

.sw-shortcut-item {
    color:#243447;
    text-decoration:none;
    text-align:center;
    font-weight:650;
    font-size:.9rem;
}

.sw-shortcut-icon {
    width:54px;
    height:54px;
    border-radius:15px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 9px;
    color:#2196df;
    background:#e9f5ff;
    border:1px solid #d3ebff;
    font-size:1.35rem;
    transition:.25s;
}

.sw-shortcut-item:hover .sw-shortcut-icon {
    transform:translateY(-5px);
    color:white;
    background:#2196df;
}

.sw-deal-strip {
    display:grid;
    grid-template-columns:1.2fr 1fr 1fr;
    gap:12px;
    margin-bottom:24px;
}

.sw-deal-card {
    border-radius:8px;
    padding:20px 22px;
    background:linear-gradient(135deg, #0d79c7, #37b2ef);
    color:white;
    min-height:105px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    box-shadow:0 8px 22px rgba(33,150,223,.16);
}

.sw-deal-card.light {
    background:white;
    color:#243447;
    border:1px solid #e7eef5;
}

.sw-deal-card h3 {
    margin:0 0 5px;
    font-size:1.15rem;
    font-weight:900;
}

.sw-deal-card p {
    margin:0;
    opacity:.9;
    font-size:.94rem;
}

.sw-deal-card i {
    font-size:2rem;
    opacity:.9;
}


button.sw-deal-card {
    width:100%;
    border:0;
    text-align:left;
    font-family:inherit;
    cursor:pointer;
}

button.sw-deal-card:hover {
    transform:translateY(-4px);
    box-shadow:0 14px 32px rgba(33,150,223,.22);
}

.sw-deal-topline {
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:6px;
}

.sw-deal-pill {
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:4px 10px;
    border-radius:999px;
    background:rgba(255,255,255,.18);
    color:#ffffff;
    font-size:.76rem;
    font-weight:900;
}

.sw-voucher-modal {
    width:min(880px, 96vw);
}

.sw-voucher-body {
    padding:24px;
    display:grid;
    grid-template-columns:1.2fr 1fr;
    gap:18px;
}

.sw-voucher-card {
    border:1px solid #dbeaf7;
    border-radius:16px;
    background:#ffffff;
    padding:22px;
    text-decoration:none;
    color:#243447;
    box-shadow:0 8px 24px rgba(33,150,223,.08);
    display:flex;
    flex-direction:column;
    gap:12px;
}

.sw-voucher-card.featured {
    background:linear-gradient(135deg, #eaf7ff 0%, #ffffff 100%);
    border:2px solid #2196df;
}

.sw-voucher-card.discount {
    background:linear-gradient(135deg, #f3fbff 0%, #ffffff 100%);
    border:2px solid #5dade2;
}

.sw-voucher-card h3 {
    margin:0;
    color:#123a5a;
    font-size:1.25rem;
    font-weight:950;
}

.sw-voucher-card p {
    margin:0;
    color:#607084;
    line-height:1.55;
    font-weight:600;
}

.sw-voucher-icon-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
}

.sw-voucher-big-icon {
    width:62px;
    height:62px;
    border-radius:18px;
    background:#dff2ff;
    color:#2196df;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.7rem;
}

.sw-voucher-limit {
    font-size:.95rem;
    color:#47657d;
    font-weight:800;
}

.sw-voucher-limit strong {
    color:#2196df;
    font-size:1.3rem;
}

.sw-claim-btn {
    border:0;
    border-radius:12px;
    padding:13px 18px;
    background:#2196df;
    color:#ffffff;
    font-weight:950;
    cursor:pointer;
    transition:.22s;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}

.sw-claim-btn:hover:not(:disabled) {
    background:#0d79c7;
    transform:translateY(-2px);
}

.sw-claim-btn:disabled {
    opacity:.75;
    cursor:not-allowed;
}

.sw-claim-btn.claimed {
    background:#2ecc71;
    opacity:1;
}

.sw-voucher-message {
    display:none;
    padding:12px 14px;
    border-radius:10px;
    font-weight:800;
    line-height:1.4;
}

.sw-voucher-message.success {
    display:block;
    background:#eafaf1;
    color:#1e8449;
    border:1px solid #bfe9d0;
}

.sw-voucher-message.error {
    display:block;
    background:#fff0f0;
    color:#c0392b;
    border:1px solid #f5c6cb;
}

.sw-voucher-side-list {
    display:grid;
    gap:12px;
}

.sw-voucher-mini-card {
    border:1px solid #e3edf6;
    border-radius:14px;
    padding:18px;
    color:#243447;
    text-decoration:none;
    background:#f9fcff;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    transition:.22s;
}

.sw-voucher-mini-card:hover {
    border-color:#2196df;
    transform:translateY(-2px);
    box-shadow:0 10px 24px rgba(33,150,223,.12);
}

.sw-voucher-mini-card strong {
    display:block;
    margin-bottom:5px;
    color:#123a5a;
    font-weight:950;
}

.sw-voucher-mini-card span {
    color:#6c7a89;
    font-size:.9rem;
    font-weight:600;
}

.sw-voucher-mini-card i {
    color:#2196df;
    font-size:1.35rem;
}

.sw-panel {
    background:white;
    border-radius:8px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
    margin-bottom:24px;
    overflow:hidden;
}

.sw-panel-header {
    padding:18px 22px;
    border-bottom:1px solid #edf1f5;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.sw-panel-header h2 {
    margin:0;
    font-size:1.25rem;
    color:#243447;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.3px;
}

.sw-panel-header span,
.sw-panel-header a {
    color:#2196df;
    font-weight:800;
    text-decoration:none;
}

.sw-category-grid {
    display:grid;
    grid-template-columns:repeat(5, 1fr);
}

.sw-category-box {
    min-height:150px;
    border-right:1px solid #edf1f5;
    border-bottom:1px solid #edf1f5;
    text-align:center;
    text-decoration:none;
    color:#243447;
    padding:18px 10px 15px;
    transition:.25s;
}

.sw-category-box:hover {
    transform:translateY(-2px);
    box-shadow:0 8px 18px rgba(0,0,0,.08);
    position:relative;
    z-index:1;
}

.sw-category-img {
    width:74px;
    height:74px;
    margin:0 auto 11px;
    border-radius:50%;
    object-fit:cover;
    background:#f1f7fc;
    display:block;
}

.sw-category-box h3 {
    margin:0;
    font-size:.95rem;
    line-height:1.28;
    font-weight:750;
}

.sw-modal-overlay {
    position:fixed;
    inset:0;
    background:rgba(15, 35, 52, .48);
    z-index:9998;
    display:none;
    align-items:center;
    justify-content:center;
    padding:22px;
}

.sw-modal-overlay.active {
    display:flex;
}

.sw-category-modal {
    width:min(980px, 96vw);
    max-height:88vh;
    overflow:auto;
    background:#ffffff;
    border-radius:16px;
    box-shadow:0 25px 70px rgba(0,0,0,.22);
    animation:swModalPop .22s ease;
}

@keyframes swModalPop {
    from {
        opacity:0;
        transform:translateY(14px) scale(.98);
    }
    to {
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

.sw-category-modal-header {
    position:sticky;
    top:0;
    z-index:2;
    background:#ffffff;
    border-bottom:1px solid #e8eef5;
    padding:20px 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
}

.sw-category-modal-header h2 {
    margin:0;
    color:#163b5c;
    font-size:1.35rem;
    font-weight:950;
}

.sw-category-modal-header p {
    margin:5px 0 0;
    color:#6c7a89;
    font-weight:600;
    font-size:.92rem;
}

.sw-modal-close {
    width:40px;
    height:40px;
    border:0;
    border-radius:50%;
    background:#eaf6ff;
    color:#168ad5;
    cursor:pointer;
    font-size:1.1rem;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:.2s;
}

.sw-modal-close:hover {
    background:#2196df;
    color:#ffffff;
}

.sw-modal-category-grid {
    padding:22px 24px 26px;
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:0;
    border-top:1px solid #edf1f5;
    border-left:1px solid #edf1f5;
}

.sw-modal-category-item {
    min-height:160px;
    padding:18px 10px 15px;
    border-right:1px solid #edf1f5;
    border-bottom:1px solid #edf1f5;
    background:#ffffff;
    text-decoration:none;
    color:#243447;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;
    transition:.22s;
}

.sw-modal-category-item:hover {
    transform:translateY(-2px);
    box-shadow:0 12px 28px rgba(33,150,223,.12);
    position:relative;
    z-index:1;
    background:#f8fcff;
}

.sw-modal-category-thumb {
    width:78px;
    height:78px;
    border-radius:50%;
    object-fit:cover;
    display:block;
    margin:0 auto 12px;
    background:#f1f7fc;
    box-shadow:0 4px 12px rgba(20, 69, 110, .12);
}

.sw-modal-category-item strong {
    font-size:.96rem;
    color:#102b44;
    line-height:1.25;
    font-weight:800;
}


.sw-modal-category-item span {
    margin-top:4px;
    font-size:.78rem;
    color:#7b8a9a;
    line-height:1.3;
}

.sw-products-grid {
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:14px;
    padding:16px;
}

.sw-product-card {
    border:1px solid #edf1f5;
    background:white;
    border-radius:8px;
    overflow:hidden;
    transition:.25s;
    position:relative;
}

.sw-product-card:hover {
    transform:translateY(-5px);
    box-shadow:0 12px 28px rgba(0,0,0,.10);
    border-color:#bfe5ff;
}

.sw-product-link {
    display:block;
    color:inherit;
    text-decoration:none;
    cursor:pointer;
}

.sw-product-link:hover h3 {
    color:#168ad5;
}

.sw-product-link:focus {
    outline:2px solid #168ad5;
    outline-offset:3px;
}

.sw-product-img-wrap {
    position:relative;
    background:#f5f9fd;
}

.sw-product-card img {
    width:100%;
    height:195px;
    object-fit:cover;
    display:block;
}

.sw-discount-label {
    position:absolute;
    top:10px;
    left:10px;
    background:#2196df;
    color:white;
    border-radius:6px;
    padding:6px 9px;
    font-size:.76rem;
    font-weight:900;
}

.sw-product-info {
    padding:13px 13px 15px;
}

.sw-product-info h3 {
    margin:0 0 8px;
    color:#243447;
    font-size:1rem;
    line-height:1.35;
    min-height:42px;
    font-weight:800;
}

.sw-product-desc {
    color:#6c7a89;
    font-size:.86rem;
    line-height:1.45;
    min-height:38px;
    margin:0 0 10px;
}

.sw-product-meta {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:12px;
}

.sw-price {
    color:#168ad5;
    font-size:1.25rem;
    font-weight:950;
}

.sw-sold {
    color:#8492a6;
    font-size:.82rem;
    white-space:nowrap;
}

.sw-add-cart {
    width:100%;
    border:0;
    border-radius:6px;
    background:#2196df;
    color:white;
    padding:11px 13px;
    font-weight:850;
    cursor:pointer;
    transition:.25s;
}

.sw-add-cart:hover {
    background:#0d79c7;
}

.sw-empty {
    padding:35px 20px;
    text-align:center;
    color:#6c7a89;
}

.sw-service-grid {
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:14px;
    margin-top:24px;
}

.sw-service-box {
    background:white;
    border:1px solid #e7eef5;
    border-radius:8px;
    padding:22px;
    text-align:center;
    box-shadow:0 2px 8px rgba(0,0,0,.04);
}

.sw-service-box i {
    font-size:2.1rem;
    color:#2196df;
    margin-bottom:12px;
}

.sw-service-box h3 {
    margin:0 0 8px;
    color:#243447;
    font-size:1rem;
    font-weight:850;
}

.sw-service-box p {
    margin:0;
    color:#6c7a89;
    line-height:1.55;
    font-size:.9rem;
}

.sw-login-popup-overlay {
    position:fixed;
    inset:0;
    background:rgba(15, 35, 52, .52);
    z-index:10000;
    display:none;
    align-items:center;
    justify-content:center;
    padding:22px;
}

.sw-login-popup-overlay.active {
    display:flex;
}

.sw-login-popup-box {
    width:min(430px, 94vw);
    background:#ffffff;
    border-radius:18px;
    padding:30px 26px;
    text-align:center;
    box-shadow:0 24px 70px rgba(0,0,0,.25);
    position:relative;
    animation:swModalPop .22s ease;
}

.sw-login-popup-close {
    position:absolute;
    top:14px;
    right:14px;
    width:38px;
    height:38px;
    border:0;
    border-radius:50%;
    background:#eaf6ff;
    color:#168ad5;
    cursor:pointer;
    font-size:1rem;
    display:flex;
    align-items:center;
    justify-content:center;
}

.sw-login-popup-close:hover {
    background:#2196df;
    color:#ffffff;
}

.sw-login-popup-icon {
    width:76px;
    height:76px;
    border-radius:50%;
    background:#eaf6ff;
    color:#2196df;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:2rem;
    margin:6px auto 16px;
}

.sw-login-popup-box h3 {
    margin:0 0 10px;
    color:#163b5c;
    font-size:1.55rem;
    font-weight:950;
}

.sw-login-popup-box p {
    margin:0 0 22px;
    color:#6c7a89;
    line-height:1.6;
    font-weight:600;
}

.sw-login-popup-actions {
    display:flex;
    justify-content:center;
    gap:12px;
    flex-wrap:wrap;
}

.sw-login-btn-primary,
.sw-login-btn-secondary {
    border:0;
    border-radius:10px;
    padding:12px 22px;
    font-weight:850;
    text-decoration:none;
    cursor:pointer;
    transition:.22s;
}

.sw-login-btn-primary {
    background:#2196df;
    color:#ffffff;
}

.sw-login-btn-primary:hover {
    background:#0d79c7;
    color:#ffffff;
}

.sw-login-btn-secondary {
    background:#edf4fa;
    color:#243447;
}

.sw-login-btn-secondary:hover {
    background:#dfeaf3;
}

@media (max-width:900px) {
    .sw-voucher-body {
        grid-template-columns:1fr;
    }
}

@media (max-width:1100px) {
    .sw-promo-grid,
    .sw-deal-strip {
        grid-template-columns:1fr;
    }

    .sw-shortcuts {
        grid-template-columns:repeat(4, 1fr);
    }

    .sw-products-grid {
        grid-template-columns:repeat(3, 1fr);
    }
}

@media (max-width:800px) {
    .smartwrite-home {
        width:min(100%, calc(100vw - 22px));
        padding-top:15px;
    }

    .sw-main-banner {
        min-height:auto;
    }

    .sw-banner-carousel,
    .sw-banner-image-only {
        height:260px;
    }

    .sw-shortcuts {
        grid-template-columns:repeat(2, 1fr);
    }

    .sw-category-grid,
    .sw-products-grid,
    .sw-service-grid {
        grid-template-columns:repeat(2, 1fr);
    }

    .sw-modal-category-grid {
        grid-template-columns:repeat(2, 1fr);
    }
}

@media (max-width:520px) {
    .sw-category-grid,
    .sw-products-grid,
    .sw-service-grid {
        grid-template-columns:1fr;
    }

    .sw-banner-carousel,
    .sw-banner-image-only {
        height:200px;
    }

    .sw-modal-category-grid {
        grid-template-columns:1fr;
        padding:16px;
    }

    .sw-category-modal-header {
        padding:16px;
    }
}
</style>

<div class="sw-login-popup-overlay" id="swLoginRequiredPopup" aria-hidden="true">
    <div class="sw-login-popup-box" role="dialog" aria-modal="true" aria-labelledby="swLoginPopupTitle">
        <button type="button" class="sw-login-popup-close" id="swLoginPopupClose" aria-label="Close login popup">
            <i class="fas fa-times"></i>
        </button>

        <div class="sw-login-popup-icon">
            <i class="fas fa-user-lock"></i>
        </div>

        <h3 id="swLoginPopupTitle">Login Required</h3>

        <p>
            Please login first to view product details or add items to your cart.
        </p>

        <div class="sw-login-popup-actions">
            <a href="login.php" class="sw-login-btn-primary">Go to Login</a>
            <button type="button" class="sw-login-btn-secondary" id="swLoginPopupCancel">Cancel</button>
        </div>
    </div>

</div>

<div class="sw-modal-overlay" id="swVoucherModal" aria-hidden="true">
    <div class="sw-category-modal sw-voucher-modal" role="dialog" aria-modal="true" aria-labelledby="swVoucherModalTitle">
        <div class="sw-category-modal-header">
            <div>
                <h2 id="swVoucherModalTitle">Voucher Centre</h2>
                <p>Choose and claim available SmartWrite vouchers.</p>
            </div>
            <button type="button" class="sw-modal-close" id="swCloseVoucherModal" aria-label="Close voucher popup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sw-voucher-body">
            <div class="sw-voucher-card featured">
                <div class="sw-voucher-icon-row">
                    <div>
                        <h3>Free Shipping Voucher</h3>
                        <p>Claim this voucher to enjoy free delivery for your stationery order.</p>
                    </div>
                    <div class="sw-voucher-big-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                </div>

                <div class="sw-voucher-limit">
                    Daily limit: <strong id="swVoucherRemainingText"><?php echo (int)$sw_voucher_remaining; ?></strong> / 10 left today
                </div>

                <button type="button"
                        id="swClaimFreeShipping"
                        class="sw-claim-btn <?php echo $sw_user_has_free_shipping_voucher ? 'claimed' : ''; ?>"
                        <?php echo ($sw_user_has_free_shipping_voucher || $sw_voucher_remaining <= 0) ? 'disabled' : ''; ?>>
                    <?php if ($sw_user_has_free_shipping_voucher): ?>
                        <i class="fas fa-check-circle"></i> Claimed
                    <?php elseif ($sw_voucher_remaining <= 0): ?>
                        <i class="fas fa-times-circle"></i> Fully Claimed
                    <?php else: ?>
                        <i class="fas fa-bolt"></i> Claim Now
                    <?php endif; ?>
                </button>

                <div id="swVoucherMessage" class="sw-voucher-message <?php echo $sw_user_has_free_shipping_voucher ? 'success' : ''; ?>" style="<?php echo $sw_user_has_free_shipping_voucher ? 'display:block;' : ''; ?>">
                    <?php echo $sw_user_has_free_shipping_voucher ? 'You already claimed this voucher today.' : ''; ?>
                </div>
            </div>

            <div class="sw-voucher-card discount">
                <div class="sw-voucher-icon-row">
                    <div>
                        <h3>RM2 Discount Voucher</h3>
                        <p>Claim this voucher to deduct RM2 from your checkout total.</p>
                    </div>
                    <div class="sw-voucher-big-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                </div>

                <div class="sw-voucher-limit">
                    Daily limit: <strong id="swRm2VoucherRemainingText"><?php echo (int)$sw_rm2_voucher_remaining; ?></strong> / 20 left today
                </div>

                <button type="button"
                        id="swClaimRm2Discount"
                        class="sw-claim-btn <?php echo $sw_user_has_rm2_voucher ? 'claimed' : ''; ?>"
                        <?php echo ($sw_user_has_rm2_voucher || $sw_rm2_voucher_remaining <= 0) ? 'disabled' : ''; ?>>
                    <?php if ($sw_user_has_rm2_voucher): ?>
                        <i class="fas fa-check-circle"></i> Claimed
                    <?php elseif ($sw_rm2_voucher_remaining <= 0): ?>
                        <i class="fas fa-times-circle"></i> Fully Claimed
                    <?php else: ?>
                        <i class="fas fa-bolt"></i> Claim Now
                    <?php endif; ?>
                </button>

                <div id="swRm2VoucherMessage" class="sw-voucher-message <?php echo $sw_user_has_rm2_voucher ? 'success' : ''; ?>" style="<?php echo $sw_user_has_rm2_voucher ? 'display:block;' : ''; ?>">
                    <?php echo $sw_user_has_rm2_voucher ? 'You already claimed this RM2 discount voucher today.' : ''; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="smartwrite-home">

    <section class="sw-promo-grid">
        <div class="sw-main-banner">
            <a href="menu.php" class="sw-banner-image-link">
                <img src="index.png?v=<?php echo time(); ?>"
                     alt="SmartWrite Home Banner"
                     class="sw-banner-image-only"
                     id="swMainBannerSlider">
            </a>

            <div class="sw-banner-dots" aria-label="Banner navigation">
                <button type="button" class="sw-banner-dot active" data-slide="0" aria-label="Show banner 1"></button>
                <button type="button" class="sw-banner-dot" data-slide="1" aria-label="Show banner 2"></button>
                <button type="button" class="sw-banner-dot" data-slide="2" aria-label="Show banner 3"></button>
            </div>
        </div>

        <div class="sw-side-stack">
            <div class="sw-side-banner">
                <div>
                    <h3>Up To 40% Off</h3>
                    <p>Selected stationery items</p>
                    <a href="menu.php" class="sw-mini-link">Shop deals →</a>
                </div>
            </div>

            <div class="sw-side-banner second">
                <div>
                    <h3>Study Essentials</h3>
                    <p>Pens, notebooks and study tools</p>
                    <a href="menu.php" class="sw-mini-link">Explore now →</a>
                </div>
            </div>
        </div>
    </section>

    <section class="sw-shortcuts">
        <a class="sw-shortcut-item" href="menu.php">
            <div class="sw-shortcut-icon"><i class="fas fa-percent"></i></div>
            Daily Deals
        </a>
        <a class="sw-shortcut-item" href="menu.php">
            <div class="sw-shortcut-icon"><i class="fas fa-pen"></i></div>
            Pens
        </a>
        <a class="sw-shortcut-item" href="menu.php">
            <div class="sw-shortcut-icon"><i class="fas fa-book"></i></div>
            Notebooks
        </a>
        <a class="sw-shortcut-item" href="menu.php">
            <div class="sw-shortcut-icon"><i class="fas fa-palette"></i></div>
            Art Tools
        </a>
        <a class="sw-shortcut-item" href="menu.php">
            <div class="sw-shortcut-icon"><i class="fas fa-briefcase"></i></div>
            Office
        </a>
        <a class="sw-shortcut-item" href="cart.php">
            <div class="sw-shortcut-icon"><i class="fas fa-shopping-cart"></i></div>
            View Cart
        </a>
        <?php if ($is_logged_in): ?>
        <a class="sw-shortcut-item" href="track.php">
            <div class="sw-shortcut-icon"><i class="fas fa-map-marker-alt"></i></div>
            Track Order
        </a>
        <?php endif; ?>
        <?php if ($is_logged_in): ?>
        <a class="sw-shortcut-item" href="contact.php">
            <div class="sw-shortcut-icon"><i class="fas fa-headset"></i></div>
            Support
        </a>
        <?php endif; ?>
    </section>

    <section class="sw-deal-strip">
        <button type="button" class="sw-deal-card sw-voucher-entry" id="swOpenVoucherModal">
            <div>
                <div class="sw-deal-topline">
                    <h3>Voucher Centre</h3>
                    <span class="sw-deal-pill" id="swVoucherRemainingMini">Free <?php echo (int)$sw_voucher_remaining; ?>/10 · RM2 <?php echo (int)$sw_rm2_voucher_remaining; ?>/20</span>
                </div>
                <p>Click to claim free shipping or RM2 discount voucher.</p>
            </div>
            <i class="fas fa-truck"></i>
        </button>

        <div class="sw-deal-card light">
            <div>
                <h3>Student Choice</h3>
                <p>Useful items for class, notes and revision.</p>
            </div>
            <i class="fas fa-graduation-cap"></i>
        </div>

        <div class="sw-deal-card light">
            <div>
                <h3>Office Essentials</h3>
                <p>Simple tools for better daily work.</p>
            </div>
            <i class="fas fa-clipboard-list"></i>
        </div>
    </section>

    <section class="sw-panel" id="categories">
        <div class="sw-panel-header">
            <h2>Categories</h2>
            <a href="#" id="swOpenCategoryModal">View All</a>
        </div>

        <div class="sw-category-grid">
            <?php if (!empty($categories_array)): ?>
                <?php foreach ($categories_array as $category): ?>
                    <?php
                        $cat_name = $category['name'] ?? 'Category';
                        $cat_url_name = strtolower(str_replace(' ', '', $cat_name));
                        $cat_image = sw_category_image($cat_name);
                    ?>

                    <a href="menu.php?category=<?php echo urlencode($cat_url_name); ?>" class="sw-category-box">
                        <img class="sw-category-img"
                             src="<?php echo $cat_image; ?>"
                             alt="<?php echo htmlspecialchars($cat_name); ?>">
                        <h3><?php echo htmlspecialchars($cat_name); ?></h3>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <?php
                    $fallback_categories = [
                        'Pens', 'Notebooks', 'Art Supplies', 'Office Supplies', 'School Supplies',
                        'Markers', 'Paper', 'Accessories', 'Best Deals'
                    ];
                ?>

                <?php foreach ($fallback_categories as $cat_name): ?>
                    <?php $cat_url_name = strtolower(str_replace(' ', '', $cat_name)); ?>
                    <a href="menu.php?category=<?php echo urlencode($cat_url_name); ?>" class="sw-category-box">
                        <img class="sw-category-img"
                             src="<?php echo sw_category_image($cat_name); ?>"
                             alt="<?php echo htmlspecialchars($cat_name); ?>">
                        <h3><?php echo htmlspecialchars($cat_name); ?></h3>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="sw-modal-overlay" id="swCategoryModal">
        <div class="sw-category-modal" role="dialog" aria-modal="true" aria-labelledby="swCategoryModalTitle">
            <div class="sw-category-modal-header">
                <div>
                    <h2 id="swCategoryModalTitle">More Stationery Groups</h2>
                    <p>Choose a group to explore more SmartWrite products.</p>
                </div>
                <button type="button" class="sw-modal-close" id="swCloseCategoryModal" aria-label="Close category popup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="sw-modal-category-grid">
                <?php
                    $modal_categories = [


                        ["Writing Tools", "Pens, pencils and refills", "menu.php?group=writingtools"],
                        ["Notebook & Paper", "Notes, memo pads and paper", "menu.php?group=notebookpaper"],
                        ["Art & Colouring", "Colour pencils and paint tools", "menu.php?group=artcolouring"],
                        ["Office Tools", "Staplers, clips and clipboard", "menu.php?group=officetools"],
                        ["Correction Items", "Correction pen and tape", "menu.php?group=correctionitems"],
                        ["School Essentials", "Daily student supplies", "menu.php?group=schoolessentials"],
                        ["Exam Supplies", "Useful items for exam day", "menu.php?group=examsupplies"],
                        ["Bags & Cases", "Pencil case and school bag", "menu.php?group=bagscases"],
                        ["Best Deals", "Affordable stationery picks", "menu.php?group=bestdeals"]
                    ];
                ?>

                <?php foreach ($modal_categories as $modal_cat): ?>
                    <?php $modal_image = sw_modal_category_image($modal_cat[0]); ?>
                    <a href="<?php echo htmlspecialchars($modal_cat[2]); ?>" class="sw-modal-category-item">
                        <img class="sw-modal-category-thumb"
                             src="<?php echo $modal_image; ?>"
                             alt="<?php echo htmlspecialchars($modal_cat[0]); ?>">
                        <strong><?php echo htmlspecialchars($modal_cat[0]); ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <section class="sw-panel">
        <div class="sw-panel-header">
            <h2>Popular Products</h2>
            <a href="menu.php">See More</a>
        </div>

        <?php if ($popular_products && mysqli_num_rows($popular_products) > 0): ?>
            <div class="sw-products-grid">
                <?php while ($product = mysqli_fetch_assoc($popular_products)): ?>
                    <?php
                        $image_filename = $product['image'] ?? 'default.jpg';
                        $category_name = $product['category_name'] ?? 'default';

                        if ($image_filename === 'default.jpg' || empty($image_filename)) {
                            $image_url = sw_default_product_image($category_name);
                        } else {
                            $image_url = 'images/products/' . $image_filename;
                        }

                        $description = $product['description'] ?? '';
                        if (strlen($description) > 85) {
                            $description = substr($description, 0, 85) . '...';
                        }
                    ?>

                    <div class="sw-product-card">
                        <?php
                            $product_detail_url = $product_detail_page . '?id=' . (int)$product['id'];
                        ?>

                        <a href="<?php echo $is_logged_in ? htmlspecialchars($product_detail_url) : '#'; ?>"
                           class="sw-product-link <?php echo $is_logged_in ? '' : 'sw-requires-login'; ?>">
                            <div class="sw-product-img-wrap">
                                <span class="sw-discount-label">HOT</span>
                                <img src="<?php echo htmlspecialchars($image_url); ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>

                            <div class="sw-product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>

                                <p class="sw-product-desc">
                                    <?php echo htmlspecialchars($description); ?>
                                </p>

                                <div class="sw-product-meta">
                                    <div class="sw-price">
                                        RM<?php echo number_format($product['price'], 2); ?>
                                    </div>
                                    <div class="sw-sold">
                                        <?php echo (int)($product['total_orders'] ?? 0); ?> sold
                                    </div>
                                </div>
                            </div>
                        </a>

                        <div class="sw-product-info sw-product-cart-area">
                            <form method="POST" action="cart-action.php" data-cart-form>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">

                                <button type="submit" class="sw-add-cart <?php echo $is_logged_in ? '' : 'sw-requires-login'; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                    Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="sw-empty">
                No products available yet.
            </div>
        <?php endif; ?>
    </section>

    <section class="sw-section-title">
        <h2>Why Choose SmartWrite?</h2>
    </section>

    <section class="sw-service-grid">
        <div class="sw-service-box">
            <i class="fas fa-pen"></i>
            <h3>Quality Products</h3>
            <p>Carefully selected stationery items for study and daily work.</p>
        </div>

        <div class="sw-service-box">
            <i class="fas fa-shipping-fast"></i>
            <h3>Fast Delivery</h3>
            <p>Quick shipping so your supplies arrive when you need them.</p>
        </div>

        <div class="sw-service-box">
            <i class="fas fa-tags"></i>
            <h3>Affordable Prices</h3>
            <p>Better value for students, office users and families.</p>
        </div>

        <div class="sw-service-box">
            <i class="fas fa-headset"></i>
            <h3>Helpful Support</h3>
            <p>Friendly assistance for orders, products and account questions.</p>
        </div>
    </section>

</div>

<?php include 'includes/footer.php'; ?>
