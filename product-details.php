<?php


session_start();


require_once 'includes/config.php';



if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    header('Location: menu.php');

    exit();
}



$product_id =
    intval($_GET['id']);



$product_sql =
    "SELECT
        p.*,
        c.name AS category_name
     FROM products p
     LEFT JOIN categories c
     ON p.category_id = c.id
     WHERE p.id = $product_id";


$product_result =
    mysqli_query($conn, $product_sql);



if (
    !$product_result ||
    mysqli_num_rows($product_result) == 0
) {

    $page_title =
        "Product Not Found - Stationery Store";

    include 'includes/header.php';
?>

<div class="container">

    <div class="content">

        <!-- Error alert  -->
        <div class="alert alert-danger">

            <i class="fas fa-exclamation-triangle"></i>

            <div>

                <h4>
                    Product Not Found!
                </h4>

                <p>
                    The product you are looking for
                    does not exist or has been removed.
                </p>

                <!-- Back button  -->
                <a href="menu.php"
                   class="btn"
                   style="margin-top: 10px;">

                    <i class="fas fa-arrow-left"></i>

                    Back to Products

                </a>

            </div>

        </div>

    </div>

</div>

<?php
    include 'includes/footer.php';

    exit();
}



$prod_details =
    mysqli_fetch_assoc($product_result);



$related_products = [];


$related_sql =
    "SELECT *
     FROM products
     WHERE category_id = {$prod_details['category_id']}
     AND id != {$prod_details['id']}
     AND available = 1
     LIMIT 4";


$related_result =
    mysqli_query($conn, $related_sql);



if ($related_result) {

    while (
        $row =
        mysqli_fetch_assoc($related_result)
    ) {

        $related_products[] = $row;
    }
}



$page_title =
    $prod_details['name'] .
    " - Stationery Store";



include 'includes/header.php';
?>

<style>
    
.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #3498db;
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 10px;
    transform: translateX(150%);
    transition: transform 0.3s ease;
}



.toast-notification.show {
    transform: translateX(0);
}



.toast-notification.error {
    background: #dc3545;
}



.toast-icon {
    font-size: 1.2rem;
}



.toast-message {
    font-weight: 500;
}



.qty-input {
    width: 70px;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    text-align: center;
    font-weight: bold;
    font-size: 1rem;
    transition: border-color 0.3s;
}



.qty-input:focus {
    outline: none;
    border-color: #3498db;
}



.btn-add-cart {
    flex: 1;

    background: linear-gradient(
        135deg,


    );

    color: white;
    border: none;
    padding: 12px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    position: relative;
    overflow: hidden;
}



.btn-add-cart:hover {
    transform: translateY(-2px);
    box-shadow:
        0 5px 15px rgba(52, 152, 219, 0.3);
}



.btn-add-cart:active {
    transform: translateY(0);
}



.btn-add-cart::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 5px;
    height: 5px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 0;
    border-radius: 100%;
    transform: scale(1, 1) translate(-50%);
    transform-origin: 50% 50%;
}



.btn-add-cart:focus:not(:active)::after {
    animation: ripple 1s ease-out;
}


.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 10px 20px;

    background: #6c757d;
    color: white;

    text-decoration: none;

    border-radius: 8px;
    font-weight: 500;

    transition: all 0.3s;

    border: none;
    cursor: pointer;

    margin-bottom: 20px;
}



.back-button:hover {
    background: #5a6268;

    transform: translateX(-3px);

    color: white;
    text-decoration: none;
}



.back-button i {
    font-size: 0.9rem;
}



.page-header {
    display: flex;
    align-items: center;
    gap: 15px;

    margin-bottom: 30px;
}



.page-header h1 {
    margin: 0;
    flex: 1;
}



@keyframes ripple {

    0% {
        transform: scale(0, 0);
        opacity: 0.5;
    }

    100% {
        transform: scale(20, 20);
        opacity: 0;
    }
}





@media (max-width: 1200px) {

    .products-grid[
        style*="grid-template-columns: repeat(4, 1fr)"
    ] {

        grid-template-columns:
        repeat(3, 1fr) !important;
    }
}



@media (max-width: 768px) {

    .products-grid[
        style*="grid-template-columns: repeat(4, 1fr)"
    ] {

        grid-template-columns:
        repeat(2, 1fr) !important;
    }


    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }


    
    .back-button {
        align-self: flex-start;
    }
}



@media (max-width: 480px) {

    .products-grid[
        style*="grid-template-columns: repeat(4, 1fr)"
    ] {

        grid-template-columns: 1fr !important;
    }


    
    div[
        style*="display: grid; grid-template-columns: 1fr 1fr"
    ] {

        grid-template-columns: 1fr !important;

        gap: 20px !important;
    }


    
    div[
        style*="min-height: 400px"
    ] {

        min-height: 300px !important;
    }
}

</style>

<!-- Toast Notification  -->
<div id="toast" class="toast-notification">

    <i class="fas fa-check-circle toast-icon"></i>

    <span class="toast-message"></span>

</div>


<div class="container">

    <div class="content">

        <!-- Page Header  -->
        <div class="page-header">

            <!-- Back button  -->
            <a href="menu.php" class="back-button">

                <i class="fas fa-arrow-left"></i>

                Back to Products

            </a>


            <!-- Product title  -->
            <h1 style="color: #333; text-align: center; flex: 1;">

                <?php echo htmlspecialchars($prod_details['name']); ?>

            </h1>

        </div>


        <!-- Hidden breadcrumb  -->
        <nav style="margin-bottom: 20px; font-size: 0.9rem; display: none;">

            <a href="index.php">Home</a> &gt;

            <a href="menu.php">Products</a> &gt;

            <?php if (isset($prod_details['category_name'])): ?>

                <a href="menu.php?category=<?php echo urlencode(strtolower(str_replace(' ', '', $prod_details['category_name']))); ?>">

                    <?php echo htmlspecialchars($prod_details['category_name']); ?>

                </a>

                &gt;

            <?php endif; ?>

            <span>

                <?php echo htmlspecialchars($prod_details['name']); ?>

            </span>

        </nav>


        <!-- Product layout  -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">

            
            <!-- Product Image  -->
            <div>

                <div style="background: #f8fbff; border-radius: 10px; overflow: hidden; position: relative; min-height: 400px; display: flex; align-items: center; justify-content: center; border: 1px solid #e3f2fd;">

                    <?php
                    
                    $image_path =
                        'images/products/' .
                        $prod_details['image'];
                    ?>


                    <?php if (
                        file_exists($image_path) &&
                        $prod_details['image'] != 'default.jpg'
                    ): ?>

                        <!-- Product image  -->
                        <img
                            src="<?php echo $image_path; ?>"
                            alt="<?php echo htmlspecialchars($prod_details['name']); ?>"
                            style="width: 100%; height: 100%; object-fit: contain; padding: 20px; display: block;"
                        >

                    <?php else: ?>

                        <!-- Placeholder image  -->
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #eef6fb;">

                            <i class="fas fa-pencil-ruler"
                               style="font-size: 4rem; color: #b0c4d4;"></i>

                        </div>

                    <?php endif; ?>

                </div>

                                <!-- Admin actions  -->
                <?php if (
                    isset($_SESSION['user_type']) &&
                    in_array($_SESSION['user_type'], ['admin', 'superadmin'])
                ): ?>

                    <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">

                        <!-- Edit product  -->
                        <a href="admin/edit-product.php?id=<?php echo $prod_details['id']; ?>"
                           class="btn"
                           style="background: #f1c40f; color: #333;">

                            <i class="fas fa-edit"></i>

                            Edit Product

                        </a>


                        <!-- Delete product  -->
                        <a href="admin/delete-product.php?id=<?php echo $prod_details['id']; ?>"
                           class="btn"
                           style="background: #e74c3c;"
                           onclick="return confirm('Are you sure you want to delete this product?');">

                            <i class="fas fa-trash"></i>

                            Delete

                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <!-- Product details  -->
            <div>

                <!-- Product top info  -->
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">

                    
                    <!-- Product price  -->
                    <span class="product-price"
                          style="font-size: 1.8rem; font-weight: bold; color: #3498db;">

                        RM <?php echo number_format($prod_details['price'], 2); ?>

                    </span>


                    <!-- Product category  -->
                    <?php if (isset($prod_details['category_name'])): ?>

                        <span style="background: #eef6fb; color: #3498db; padding: 6px 14px; border-radius: 20px; font-size: 0.9rem;">

                            <i class="fas fa-tag"></i>

                            <?php echo htmlspecialchars($prod_details['category_name']); ?>

                        </span>

                    <?php endif; ?>


                    <!-- Stock status  -->
                    <span style="
                        background: <?php echo $prod_details['available'] ? '#d4edda' : '#f8d7da'; ?>;
                        color: <?php echo $prod_details['available'] ? '#155724' : '#721c24'; ?>;
                        padding: 6px 14px;
                        border-radius: 20px;
                        font-size: 0.9rem;
                    ">

                        <i class="fas fa-<?php echo $prod_details['available'] ? 'check-circle' : 'times-circle'; ?>"></i>

                        <?php echo $prod_details['available'] ? 'In Stock' : 'Out of Stock'; ?>

                    </span>

                </div>


                <!-- Product description  -->
                <div style="margin-bottom: 30px;">

                    <h3 style="color: #444; margin-bottom: 12px;">

                        Product Description

                    </h3>


                    <p style="line-height: 1.8; color: #666;">

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $prod_details['description']
                                ?: 'No description available.'
                            )
                        );
                        ?>

                    </p>

                </div>

                                <!-- Add to cart section  -->
                <?php if ($prod_details['available']): ?>

                    <div style="background: #f8fbff; padding: 25px; border-radius: 12px; border: 1px solid #dbe9f6;">

                        <!-- Section title  -->
                        <h3 style="color: #444; margin-bottom: 20px; text-align: center;">

                            Add to Cart

                        </h3>


                        <!-- Quantity section  -->
                        <div class="form-group" style="margin-bottom: 25px;">

                            <label for="quantity"
                                   style="font-weight: 600; margin-bottom: 10px; display: block; text-align: center;">

                                Quantity

                            </label>


                            <!-- Quantity controls  -->
                            <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">

                                
                                <!-- Minus button  -->
                                <button type="button"
                                        onclick="updateQuantity(-1)"
                                        style="width: 45px; height: 45px; border: none; background: #3498db; color: white; border-radius: 50%; cursor: pointer; font-size: 1.1rem;">

                                    <i class="fas fa-minus"></i>

                                </button>


                                <!-- Quantity input  -->
                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="10"

                                    style="width: 100px; text-align: center; padding: 12px; border: 2px solid #dbe9f6; border-radius: 8px; font-size: 1.1rem;"
                                >


                                <!-- Plus button  -->
                                <button type="button"
                                        onclick="updateQuantity(1)"
                                        style="width: 45px; height: 45px; border: none; background: #3498db; color: white; border-radius: 50%; cursor: pointer; font-size: 1.1rem;">

                                    <i class="fas fa-plus"></i>

                                </button>

                            </div>

                        </div>


                        <!-- Add to cart button  -->
                        <div style="text-align: center;">

                            <button
                                type="button"
                                id="add-to-cart-btn"
                                class="btn-add-cart"
                                style="padding: 15px 50px; font-size: 1.1rem; border-radius: 8px;"

                                data-product-id="<?php echo $prod_details['id']; ?>"
                                data-product-name="<?php echo htmlspecialchars($prod_details['name']); ?>"
                            >

                                <i class="fas fa-cart-plus"></i>

                                Add to Cart

                            </button>

                        </div>

                    </div>

                <?php else: ?>

                    <!-- Out of stock  -->
                    <div style="background: #fff4e5; color: #8a5a00; padding: 25px; border-radius: 12px; text-align: center; border: 1px solid #ffd591;">

                        <i class="fas fa-box-open"
                           style="font-size: 2rem; margin-bottom: 12px;"></i>

                        <h3 style="margin: 0 0 10px 0;">

                            Currently Unavailable

                        </h3>

                        <p style="margin: 0; line-height: 1.6;">

                            This product is temporarily out of stock.

                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

                <!-- Related products  -->
        <?php if (!empty($related_products)): ?>

            <div class="popular-items">

                <!-- Section title  -->
                <h2 style="color: #333; margin-bottom: 25px;">

                    You Might Also Like

                </h2>


                <!-- Related products grid  -->
                <div class="products-grid"
                     style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">

                    <?php foreach ($related_products as $related): ?>

                        <div class="product-card"
                             style="border: 1px solid #e3f2fd; border-radius: 12px; padding: 15px; background: white; transition: all 0.3s;">

                            
                            <?php
                            
                            $related_image_path =
                                'images/products/' .
                                $related['image'];
                            ?>


                            <!-- Product image  -->
                            <?php if (
                                file_exists($related_image_path) &&
                                $related['image'] != 'default.jpg'
                            ): ?>

                                <div style="height: 160px; overflow: hidden; border-radius: 8px; margin-bottom: 15px; background: #f8fbff; display: flex; align-items: center; justify-content: center;">

                                    <img
                                        src="<?php echo $related_image_path; ?>"
                                        alt="<?php echo htmlspecialchars($related['name']); ?>"
                                        style="width: 100%; height: 100%; object-fit: contain; padding: 12px;"
                                    >

                                </div>

                            <?php else: ?>

                                <!-- Placeholder  -->
                                <div style="height: 160px; background: #eef6fb; display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 15px;">

                                    <i class="fas fa-pencil-ruler"
                                       style="font-size: 3rem; color: #b0c4d4;"></i>

                                </div>

                            <?php endif; ?>


                            <!-- Product name  -->
                            <h3 style="margin: 10px 0; font-size: 1.05rem; color: #333;">

                                <?php echo htmlspecialchars($related['name']); ?>

                            </h3>


                            <!-- Product description  -->
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 12px; line-height: 1.5;">

                                <?php
                                echo substr(
                                    htmlspecialchars(
                                        $related['description']
                                        ?: 'No description available'
                                    ),
                                    0,
                                    50
                                );
                                ?>...

                            </p>


                            <!-- Product price  -->
                            <div class="product-price"
                                 style="color: #3498db; font-weight: bold; font-size: 1.15rem; margin-bottom: 15px;">

                                RM <?php echo number_format($related['price'], 2); ?>

                            </div>


                            <!-- Action buttons  -->
                            <div style="display: flex; gap: 10px;">

                                
                                <!-- View details  -->
                                <a href="product-details.php?id=<?php echo $related['id']; ?>"
                                   class="btn"
                                   style="flex: 1; padding: 10px; font-size: 0.9rem; background: #6c757d; text-align: center;">

                                    <i class="fas fa-eye"></i>

                                    Details

                                </a>


                                <!-- Add to cart  -->
                                <button
                                    type="button"
                                    class="btn-add-cart add-to-cart-btn"
                                    style="flex: 1; padding: 10px; font-size: 0.9rem;"

                                    data-product-id="<?php echo $related['id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($related['name']); ?>"
                                >

                                    <i class="fas fa-cart-plus"></i>

                                    Add

                                </button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>

<script>

function showToast(message, isError = false) {

    const toast =
        document.getElementById('toast');

    const toastMessage =
        toast.querySelector('.toast-message');

    const toastIcon =
        toast.querySelector('.toast-icon');


    
    toastMessage.textContent = message;


    
    if (isError) {

        toast.classList.add('error');

        toastIcon.className =
            'fas fa-exclamation-circle toast-icon';

    } else {

        toast.classList.remove('error');

        toastIcon.className =
            'fas fa-check-circle toast-icon';
    }


    
    toast.classList.add('show');


    
    setTimeout(() => {

        toast.classList.remove('show');

    }, 3000);
}



function updateCartCount(newCount = null) {

    const cartCountElement =
        document.querySelector('.cart-count');


    if (!cartCountElement) {
        return;
    }


    
    if (newCount !== null) {

        cartCountElement.textContent =
            newCount;

    } else {

        let currentCount =
            parseInt(cartCountElement.textContent) || 0;

        cartCountElement.textContent =
            currentCount + 1;
    }
}



function updateQuantity(change) {

    const quantityInput =
        document.getElementById('quantity');


    let quantity =
        parseInt(quantityInput.value);


    quantity += change;


    
    if (quantity < 1) {
        quantity = 1;
    }


    
    if (quantity > 10) {
        quantity = 10;
    }


    quantityInput.value = quantity;
}



document.addEventListener('DOMContentLoaded', function() {

    const addToCartBtn =
        document.getElementById('add-to-cart-btn');


    
    if (addToCartBtn) {

        addToCartBtn.addEventListener('click', function(e) {

            e.preventDefault();


            const productId =
                this.getAttribute('data-product-id');

            const productName =
                this.getAttribute('data-product-name');

            const quantityInput =
                document.getElementById('quantity');

            const quantity =
                quantityInput
                ? parseInt(quantityInput.value)
                : 1;


            
            const originalText =
                this.innerHTML;

            this.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Adding...';

            this.disabled = true;


            
            fetch('cart-action.php', {

                method: 'POST',

                headers: {
                    'Content-Type':
                    'application/x-www-form-urlencoded',
                },

                body:
                    'action=add&product_id=' +
                    productId +
                    '&quantity=' +
                    quantity
            })

            .then(response => response.json())

            .then(data => {

                
                this.innerHTML =
                    originalText;

                this.disabled = false;


                
                if (data.success) {

                    showToast(
                        productName +
                        ' added to cart!'
                    );


                    
                    updateCartCount(
                        data.cart_count
                    );

                } else {

                    
                    showToast(
                        data.message ||
                        'Failed to add item',
                        true
                    );
                }
            })

            .catch(error => {

                
                this.innerHTML =
                    originalText;

                this.disabled = false;


                
                showToast(
                    'An error occurred. Please try again.',
                    true
                );

                console.error('Error:', error);
            });

        });
    }


    
    const relatedButtons =
        document.querySelectorAll('.add-to-cart-btn');


    relatedButtons.forEach(button => {

        
        if (button.id === 'add-to-cart-btn') {
            return;
        }


        button.addEventListener('click', function(e) {

            e.preventDefault();


            const productId =
                this.getAttribute('data-product-id');

            const productName =
                this.getAttribute('data-product-name');


            
            const originalText =
                this.innerHTML;

            this.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i>';

            this.disabled = true;


            
            fetch('cart-action.php', {

                method: 'POST',

                headers: {
                    'Content-Type':
                    'application/x-www-form-urlencoded',
                },

                body:
                    'action=add&product_id=' +
                    productId +
                    '&quantity=1'
            })

            .then(response => response.json())

            .then(data => {

                
                this.innerHTML =
                    originalText;

                this.disabled = false;


                if (data.success) {

                    showToast(
                        productName +
                        ' added to cart!'
                    );

                    updateCartCount(
                        data.cart_count
                    );

                } else {

                    showToast(
                        data.message ||
                        'Failed to add item',
                        true
                    );
                }
            })

            .catch(error => {

                this.innerHTML =
                    originalText;

                this.disabled = false;

                showToast(
                    'An error occurred. Please try again.',
                    true
                );

                console.error('Error:', error);
            });

        });

    });

});
</script>


<?php include 'includes/footer.php'; ?>