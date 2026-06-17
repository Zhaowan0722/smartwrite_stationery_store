<?php



require_once 'includes/config.php';



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    
    $action = $_POST['action'] ?? '';
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    $index = $_POST['index'] ?? '';

    
    switch($action) {

        
        case 'add':

            $found = false; 

            
            if (isset($_SESSION['cart'])) {

                
                foreach($_SESSION['cart'] as &$item) {

                    
                    if ($item['product_id'] == $product_id) {

                        
                        $item['quantity'] += $quantity;

                        $found = true;
                        break;
                    }
                }

            } else {

                
                $_SESSION['cart'] = [];
            }

            
            if (!$found) {

                
                $sql = "SELECT * FROM products WHERE id = $product_id";

                $result = mysqli_query($conn, $sql);

                
                if ($result && mysqli_num_rows($result) > 0) {

                    $product = mysqli_fetch_assoc($result);

                    
                    $_SESSION['cart'][] = [

                        'product_id' => $product_id,
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'quantity' => $quantity,
                        'image' => $product['image']
                    ];
                }
            }

            break;


        
        case 'update':

            
            if (isset($_SESSION['cart'][$index])) {

                
                $_SESSION['cart'][$index]['quantity'] = $quantity;
            }

            break;


        
        case 'remove':

            
            if (isset($_SESSION['cart'][$index])) {

                
                unset($_SESSION['cart'][$index]);

                
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            }

            break;


        
        case 'clear':

            
            $_SESSION['cart'] = [];

            break;
    }


    
    header('Location: cart.php');
    exit();
}





$page_title = "Shopping Cart";


$show_sidebar = true;



include 'includes/header.php';
?>

<style>
    
.cart-container {
    margin-top: 20px;
}


.cart-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08);
}


.cart-table thead {
    background: linear-gradient(135deg, #3498db 0%, #5dade2 100%);
}


.cart-table th {
    padding: 20px;
    text-align: left;
    color: white;
    font-weight: 600;
    font-size: 1rem;
}


.cart-table tbody tr {
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.3s;
}


.cart-table tbody tr:hover {
    background: #f4f9ff;
}


.cart-table td {
    padding: 20px;
    vertical-align: middle;
}


.cart-item-img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #eaf4fb;
}


.cart-item-info {
    flex: 1;
}


.cart-item-name {
    margin: 0 0 8px 0;
    font-size: 1.1rem;
    color: #333;
}


.cart-item-id {
    color: #666;
    font-size: 0.9rem;
}


.cart-price {
    font-weight: 600;
    color: #333;
    font-size: 1.1rem;
}


.cart-quantity-input {
    width: 70px;
    padding: 10px;
    border: 2px solid #d6eaf8;
    border-radius: 8px;
    text-align: center;
    font-weight: bold;
    font-size: 1rem;
}


.cart-quantity-input:focus {
    outline: none;
    border-color: #3498db;
}


.cart-remove-btn {
    background: none;
    border: none;
    color: #2980b9;
    cursor: pointer;
    font-size: 1.2rem;
    transition: transform 0.2s;
}


.cart-remove-btn:hover {
    transform: scale(1.1);
}


.cart-total {
    font-weight: bold;
    color: #3498db;
    font-size: 1.2rem;
}


.cart-actions {
    display: flex;
    justify-content: flex-start;
    margin-top: 30px;
    padding: 20px 0;
}


.cart-summary {
    background: linear-gradient(135deg, #f4f9ff 0%, #ffffff 100%);
    border-radius: 15px;
    padding: 30px;
    border: 2px solid #d6eaf8;
    box-shadow: 0 5px 20px rgba(52, 152, 219, 0.1);
}


.cart-summary h3 {
    margin-top: 0;
    margin-bottom: 25px;
    color: #3498db;
    font-size: 1.4rem;
    position: relative;
    padding-bottom: 10px;
}


.cart-summary h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background: #3498db;
    border-radius: 2px;
}


.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px dashed #d6eaf8;
}


.summary-total {
    font-weight: bold;
    font-size: 1.3rem;
    color: #3498db;
    margin: 20px 0;
    padding-top: 20px;
    border-top: 2px solid #3498db;
}


.checkout-btn {
    display: block;
    text-align: center;
    background: linear-gradient(135deg, #3498db 0%, #5dade2 100%); 
    color: white;
    padding: 18px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1.1rem;
    text-decoration: none;
    transition: all 0.3s;
    margin-bottom: 15px;
}


.checkout-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
}


.empty-cart {
    text-align: center;
    padding: 80px 30px;
    background: linear-gradient(135deg, #f8fbff 0%, #eef5fb 100%);
    border-radius: 15px;
    border: 2px dashed #d6eaf8;
    margin: 30px 0;
}


.empty-cart-icon {
    font-size: 5rem;
    color: #aed6f1;
    margin-bottom: 25px;
}


.guest-notice {
    background: #ebf5fb;
    color: #21618c;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
    border-left: 5px solid #3498db;
    display: flex;
    align-items: center;
    gap: 15px;
}



.qty-confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
}

.qty-confirm-box {
    width: 90%;
    max-width: 420px;
    background: #ffffff;
    border-radius: 16px;
    padding: 28px 24px;
    text-align: center;
    box-shadow: 0 20px 50px rgba(0,0,0,0.18);
    animation: qtyPopupFade 0.25s ease;
}

@keyframes qtyPopupFade {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.qty-confirm-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 15px;
    border-radius: 50%;
    background: #eaf4fd;
    color: #3498db;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.qty-confirm-box h3 {
    margin: 0 0 10px;
    color: #1e3554;
    font-size: 1.45rem;
}

.qty-confirm-box p {
    margin: 0 0 22px;
    color: #666;
    line-height: 1.6;
}

.qty-confirm-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.qty-confirm-yes,
.qty-confirm-no {
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.qty-confirm-yes {
    background: #3498db;
    color: white;
}

.qty-confirm-yes:hover {
    background: #2980b9;
}

.qty-confirm-no {
    background: #eef3f8;
    color: #333;
}

.qty-confirm-no:hover {
    background: #dfe8f1;
}


@media (max-width: 992px) {
    .cart-container > div {
        grid-template-columns: 1fr;
    }

    .cart-table {
        display: block;
        overflow-x: auto; 
    }
}


@media (max-width: 768px) {

    .cart-table th,
    .cart-table td {
        padding: 15px 10px;
    }

    .cart-item-img {
        width: 60px;
        height: 60px;
    }

    .cart-actions {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<h1>Shopping Cart</h1>

<!-- Quantity Confirm Popup  -->
<div id="qtyConfirmPopup" class="qty-confirm-overlay">
    <div class="qty-confirm-box">
        <div class="qty-confirm-icon">
            <i class="fas fa-question-circle"></i>
        </div>

        <h3>Confirm Quantity Change</h3>

        <p id="qtyConfirmText">
            Are you sure you want to update the quantity?
        </p>

        <div class="qty-confirm-actions">
            <button type="button" id="qtyConfirmYes" class="qty-confirm-yes">
                Yes, Update
            </button>

            <button type="button" id="qtyConfirmNo" class="qty-confirm-no">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Guest Notice  -->
<?php if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0): ?>
    <div class="guest-notice">

        <!-- Info icon  -->
        <i class="fas fa-info-circle" style="font-size: 1.8rem;"></i>

        <div>

            <!-- Guest message  -->
            <strong>You're shopping as a guest</strong>

            <p style="margin: 8px 0 0 0;">
                Login or register to save your cart and view order history.
            </p>

            <!-- Login & Register buttons  -->
            <div style="margin-top: 15px; display: flex; gap: 10px;">

                <!-- Login button  -->
                <a href="login.php?redirect=cart"
                   class="btn"
                   style="background: #3498db; padding: 10px 20px;">

                    <i class="fas fa-sign-in-alt"></i> Login
                </a>

                <!-- Register button  -->
                <a href="register.php?redirect=cart"
                   class="btn"
                   style="background: #5dade2; padding: 10px 20px;">

                    <i class="fas fa-user-plus"></i> Register
                </a>

            </div>
        </div>
    </div>
<?php endif; ?>


<?php

if (empty($_SESSION['cart'])):
?>

    <!-- Empty cart section  -->
    <div class="empty-cart">

        <i class="fas fa-shopping-cart empty-cart-icon"></i>

        <h2 style="color: #666; margin-bottom: 20px;">
            Your cart is empty
        </h2>

        <p style="color: #888; margin-bottom: 30px; font-size: 1.1rem;">
            Add some delicious items to get started!
        </p>

        <!-- Browse menu button  -->
        <a href="menu.php"
           class="btn"
           style="padding: 15px 40px; font-size: 1.1rem; background: #3498db;">

            <i class="fas fa-utensils"></i> Browse Menu
        </a>

    </div>

<?php else: ?>

    <!-- Cart container  -->
    <div class="cart-container">

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">

            <!-- Cart Items  -->
            <div>

                <table class="cart-table">

                    <!-- Table header  -->
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: center;">Price</th>
                            <th style="text-align: center;">Quantity</th>
                            <th style="text-align: center;">Total</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        
                        $subtotal = 0;

                        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {

                            
                            foreach($_SESSION['cart'] as $index => $item):

                                
                                $item_total = $item['price'] * $item['quantity'];

                                
                                $subtotal += $item_total;

                                
                                $image_filename = $item['image'] ?? 'default.jpg';

                                $image_path = "images/products/" . $image_filename;

                                
                                $full_image_path = __DIR__ . '/images/products/' . $image_filename;

                                if ($image_filename == 'default.jpg' || !file_exists($full_image_path)) {

                                    
                                    $img_src = 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=100&q=80';

                                } else {

                                    $img_src = $image_path;
                                }
                        ?>

                        <tr>

                            <!-- Product information  -->
                            <td>

                                <div style="display: flex; align-items: center; gap: 15px;">

                                    <!-- Product image  -->
                                    <img src="<?php echo htmlspecialchars($img_src); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="cart-item-img">

                                    <div class="cart-item-info">

                                        <!-- Product name  -->
                                        <h4 class="cart-item-name">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </h4>

                                        <!-- Product ID  -->
                                        <div class="cart-item-id">
                                            Item ID: <?php echo $item['product_id']; ?>
                                        </div>

                                    </div>
                                </div>
                            </td>

                            <!-- Product price  -->
                            <td style="text-align: center;" class="cart-price">
                                RM<?php echo number_format($item['price'], 2); ?>
                            </td>

                            <!-- Quantity update form  -->
                            <td style="text-align: center;">

                                <form method="POST"
                                      action="cart.php"
                                      style="display: inline-block;">

                                    <input type="hidden"
                                           name="action"
                                           value="update">

                                    <input type="hidden"
                                           name="index"
                                           value="<?php echo $index; ?>">

                                    <input type="number"
                                           name="quantity"
                                           value="<?php echo $item['quantity']; ?>"
                                           min="1"
                                           max="10"
                                           class="cart-quantity-input"
                                           data-original="<?php echo $item['quantity']; ?>"
                                           onchange="confirmQuantityChange(this)">

                                </form>
                            </td>

                            <!-- Item total  -->
                            <td style="text-align: center;" class="cart-total">
                                RM<?php echo number_format($item_total, 2); ?>
                            </td>

                            <!-- Remove item  -->
                            <td style="text-align: center;">

                                <form method="POST"
                                      action="cart.php"
                                      style="display: inline;">

                                    <input type="hidden"
                                           name="action"
                                           value="remove">

                                    <input type="hidden"
                                           name="index"
                                           value="<?php echo $index; ?>">

                                    <!-- Remove button  -->
                                    <button type="submit"
                                            class="cart-remove-btn"
                                            title="Remove item">

                                        <i class="fas fa-trash-alt"></i>
                                    </button>

                                </form>
                            </td>

                        </tr>

                        <?php
                            endforeach;
                        }
                        ?>

                    </tbody>
                </table>

                
                <!-- Cart action buttons  -->
                <div class="cart-actions">

                    <!-- Continue shopping button  -->
                    <a href="menu.php"
                       class="btn"
                       style="background: #5dade2; padding: 12px 30px;">

                        <i class="fas fa-arrow-left"></i>
                        Continue Shopping
                    </a>
                </div>
            </div>


            <!-- Order Summary  -->
            <div>

                <div class="cart-summary">

                    <!-- Summary title  -->
                    <h3>Order Summary</h3>


                    <!-- Subtotal  -->
                    <div class="summary-row">

                        <span>Subtotal</span>

                        <span>
                            RM<?php echo number_format($subtotal, 2); ?>
                        </span>

                    </div>


                    <!-- Delivery fee  -->
                    <div class="summary-row">

                        <span>Delivery Fee</span>

                        <span>
                            RM<?php echo $subtotal > 20 ? '0.00' : '3.99'; ?>
                        </span>

                    </div>


                    <!-- Final total  -->
                    <div class="summary-row summary-total">

                        <span>Total</span>

                        <span>
                            RM<?php
                            echo number_format(
                                $subtotal + ($subtotal > 20 ? 0 : 3.99),
                                2
                            );
                            ?>
                        </span>

                    </div>


                    <!-- Checkout button  -->
                    <a href="checkout.php"
                       class="checkout-btn">

                        <i class="fas fa-credit-card"></i>
                        Proceed to Checkout

                    </a>

                </div>

            </div>

        </div>
    </div>

<?php endif; ?>


<script>
let selectedQuantityInput = null;
let selectedOldQuantity = null;
let selectedNewQuantity = null;

function confirmQuantityChange(input) {
    const oldQuantity = parseInt(input.dataset.original || input.defaultValue || '1', 10);
    let newQuantity = parseInt(input.value || '1', 10);

    if (newQuantity < 1) {
        newQuantity = 1;
        input.value = 1;
    }

    if (newQuantity > 10) {
        newQuantity = 10;
        input.value = 10;
    }

    if (newQuantity === oldQuantity) {
        return;
    }

    selectedQuantityInput = input;
    selectedOldQuantity = oldQuantity;
    selectedNewQuantity = newQuantity;

    const text = document.getElementById('qtyConfirmText');
    const popup = document.getElementById('qtyConfirmPopup');

    text.textContent =
        'Are you sure you want to change quantity from ' +
        oldQuantity +
        ' to ' +
        newQuantity +
        '?';

    popup.style.display = 'flex';
}

function closeQuantityPopup() {
    const popup = document.getElementById('qtyConfirmPopup');
    popup.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    const yesBtn = document.getElementById('qtyConfirmYes');
    const noBtn = document.getElementById('qtyConfirmNo');
    const popup = document.getElementById('qtyConfirmPopup');

    if (yesBtn) {
        yesBtn.addEventListener('click', function () {
            if (selectedQuantityInput) {
                selectedQuantityInput.dataset.original = selectedNewQuantity;
                selectedQuantityInput.form.submit();
            }
        });
    }

    if (noBtn) {
        noBtn.addEventListener('click', function () {
            if (selectedQuantityInput) {
                selectedQuantityInput.value = selectedOldQuantity;
            }

            closeQuantityPopup();
        });
    }

    if (popup) {
        popup.addEventListener('click', function (e) {
            if (e.target === popup) {
                if (selectedQuantityInput) {
                    selectedQuantityInput.value = selectedOldQuantity;
                }

                closeQuantityPopup();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>