<?php


session_start();

require_once 'includes/config.php'; // Include config 



if (!isset($_SESSION['user_id'])) {

    header('Location: login.php?redirect=favorites.php');
    exit();
}



$page_title = "My Favorites";
$current_page = 'favorites.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {

    $product_id = intval($_POST['product_id']);
    $user_id = $_SESSION['user_id'];

    
    $remove_sql = "DELETE FROM favorites 
                   WHERE user_id = $user_id 
                   AND product_id = $product_id";

    if (mysqli_query($conn, $remove_sql)) {

        $success_message = "Item removed from favorites!";

    } else {

        $error_message = "Failed to remove item from favorites.";
    }
}



$user_id = $_SESSION['user_id'];

$favorites_sql = "SELECT p.*, 
                         c.name as category_name, 
                         f.created_at as favorited_date
                  FROM favorites f
                  JOIN products p ON f.product_id = p.id
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE f.user_id = $user_id 
                  AND p.available = 1
                  ORDER BY f.created_at DESC";

$favorites_result = mysqli_query($conn, $favorites_sql);



require_once 'includes/header.php';
?>

<style>


.favorites-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}


.page-header {
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    padding: 40px 30px;
    border-radius: 15px;
    margin-bottom: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border-left: 5px solid #3498db;
    position: relative;
    overflow: hidden;
}


.page-header h1 {
    color: #333;
    margin-bottom: 15px;
    font-size: 2.5rem;
    position: relative;
}


.page-header p {
    color: #666;
    margin: 0;
    font-size: 1.1rem;
    max-width: 600px;
    line-height: 1.6;
}


.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}


.favorite-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
    position: relative;
}


.favorite-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}


.favorite-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #3498db;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    z-index: 2;
}


.favorite-img-wrapper {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: linear-gradient(45deg, #f8f9fa, #e9ecef);
}


.favorite-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: transform 0.5s ease;
    background: #fff;
    padding: 10px;
}


.favorite-card:hover .favorite-img-wrapper img {
    transform: scale(1.05);
}


.favorite-info {
    padding: 25px;
}


.category-badge {
    font-size: 0.8rem;
    color: #3498db;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
    font-weight: 600;
    background: rgba(52, 152, 219, 0.1);
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-block;
}


.favorite-info h3 {
    margin: 0 0 12px 0;
    font-size: 1.3rem;
    line-height: 1.4;
}


.favorite-info h3 a {
    text-decoration: none;
    color: #333;
    transition: color 0.2s;
    display: block;
}


.favorite-info h3 a:hover {
    color: #3498db;
}


.product-desc {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 15px;

    
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;

    overflow: hidden;
}


.product-price {
    color: #3498db;
    font-weight: bold;
    font-size: 1.2rem;
    margin-bottom: 20px;
}


.favorite-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    justify-content: space-between;
}


.btn-add-cart {
    background: linear-gradient(135deg, #3498db 0%, #5dade2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    justify-content: center;
}


.btn-add-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}


.btn-remove-fav {
    background: #fff;
    color: #3498db;
    border: 2px solid #3498db;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 45px;
}


.btn-remove-fav:hover {
    background: #3498db;
    color: white;
}


.empty-state {
    text-align: center;
    padding: 80px 30px;
    background: linear-gradient(135deg, #f8fbff 0%, #eef5fb 100%);
    border-radius: 15px;
    grid-column: 1 / -1;
    border: 2px dashed #d6eaf8;
}


.empty-state i {
    font-size: 5rem;
    color: #aed6f1;
    margin-bottom: 25px;
    display: block;
}


.empty-state h3 {
    color: #495057;
    margin-bottom: 15px;
    font-size: 1.8rem;
}


.empty-state p {
    color: #6c757d;
    font-size: 1.1rem;
    max-width: 500px;
    margin: 0 auto 25px;
    line-height: 1.6;
}


.favorited-date {
    color: #888;
    font-size: 0.85rem;
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
}


@media (max-width: 768px) {

    .favorites-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }

    .page-header {
        padding: 30px 20px;
    }

    .page-header h1 {
        font-size: 2rem;
    }

    .favorite-actions {
        flex-direction: column;
    }

    .btn-add-cart,
    .btn-remove-fav {
        width: 100%;
    }
}


@media (max-width: 576px) {

    .favorites-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="favorites-container">

    <!-- Page header  -->
    <div class="page-header">

        <h1>
            <i class="fas fa-heart"
               style="color: #3498db; margin-right: 10px;"></i>

            My Favorites
        </h1>

        <p>
            View and manage your favorite stationery items.
            Add items to cart directly from here!
        </p>

    </div>


    <!-- Success message  -->
    <?php if (isset($success_message)): ?>

        <div class="toast-notification show" id="success-toast">

            <i class="fas fa-check-circle toast-icon"></i>

            <span class="toast-message">
                <?php echo $success_message; ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- Error message  -->
    <?php if (isset($error_message)): ?>

        <div class="toast-notification show error" id="error-toast">

            <i class="fas fa-exclamation-circle toast-icon"></i>

            <span class="toast-message">
                <?php echo $error_message; ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- Favorites grid  -->
    <div class="favorites-grid">

        <?php if ($favorites_result && mysqli_num_rows($favorites_result) > 0): ?>

            <?php while($product = mysqli_fetch_assoc($favorites_result)): ?>

                <div class="favorite-card">

                    <!-- Favorite badge  -->
                    <div class="favorite-badge">

                        <i class="fas fa-heart"></i> Favorite

                    </div>


                    <!-- Product image  -->
                    <div class="favorite-img-wrapper">

                        <a href="product-details.php?id=<?php echo $product['id']; ?>">

                            <?php
                            $image_path = "images/products/" . $product['image'];

                            
                            if (file_exists($image_path) && $product['image'] != 'default.jpg'):
                            ?>

                                <img src="<?php echo $image_path; ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">

                            <?php else: ?>

                                <?php
                                
                                $placeholders = [

                                    1 => 'https://images.unsplash.com/photo-1506784365847-bbad939e9335?auto=format&fit=crop&w=500&q=80',

                                    2 => 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=500&q=80',

                                    3 => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=500&q=80',

                                    4 => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=500&q=80',
                                ];

                                $img_src = $placeholders[$product['category_id']]
                                    ?? 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=500&q=80';
                                ?>

                                <img src="<?php echo $img_src; ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">

                            <?php endif; ?>

                        </a>
                    </div>


                    <!-- Product info  -->
                    <div class="favorite-info">

                        <!-- Category badge  -->
                        <div class="category-badge">

                            <?php
                            echo htmlspecialchars(
                                $product['category_name'] ?? 'General'
                            );
                            ?>

                        </div>


                        <!-- Product title  -->
                        <h3>

                            <a href="product-details.php?id=<?php echo $product['id']; ?>">

                                <?php echo htmlspecialchars($product['name']); ?>

                            </a>

                        </h3>


                        <!-- Product description  -->
                        <p class="product-desc">

                            <?php
                            echo htmlspecialchars(
                                $product['description']
                                ?: 'No description available.'
                            );
                            ?>

                        </p>


                        <!-- Product price  -->
                        <div class="product-price">

                            RM<?php echo number_format($product['price'], 2); ?>

                        </div>


                        <!-- Favorited date  -->
                        <div class="favorited-date">

                            <i class="far fa-clock"></i>

                            Added
                            <?php
                            echo date(
                                'M d, Y',
                                strtotime($product['favorited_date'])
                            );
                            ?>

                        </div>


                        <!-- Action buttons  -->
                        <div class="favorite-actions">

                            <!-- Add to cart form  -->
                            <form method="POST"
                                  action="cart-action.php"
                                  class="add-to-cart-form"
                                  style="flex: 1;">

                                <input type="hidden"
                                       name="action"
                                       value="add">

                                <input type="hidden"
                                       name="product_id"
                                       value="<?php echo $product['id']; ?>">

                                <input type="hidden"
                                       name="quantity"
                                       value="1">

                                <button type="submit" class="btn-add-cart">

                                    <i class="fas fa-cart-plus"></i>
                                    Add to Cart

                                </button>

                            </form>


                            <!-- Remove favorite form  -->
                            <form method="POST"
                                  action=""
                                  class="remove-favorite-form">

                                <input type="hidden"
                                       name="remove_favorite"
                                       value="1">

                                <input type="hidden"
                                       name="product_id"
                                       value="<?php echo $product['id']; ?>">

                                <button type="submit"
                                        class="btn-remove-fav"
                                        title="Remove from favorites">

                                    <i class="fas fa-trash"></i>

                                </button>

                            </form>

                        </div>
                    </div>
                </div>

            <?php endwhile; ?>


        <?php else: ?>

            <!-- Empty favorites  -->
            <div class="empty-state">

                <i class="far fa-heart"></i>

                <h3>No Favorites Yet</h3>

                <p>
                    You haven't added any stationery items to favorites yet.
                    Browse our menu and click the heart icon to save products!
                </p>

                <!-- Browse menu  -->
                <a href="menu.php"
                   class="btn-add-cart"
                   style="width: auto; padding: 12px 40px; margin-top: 10px;">

                    <i class="fas fa-shopping-bag"></i>
                    Browse Menu

                </a>

            </div>

        <?php endif; ?>

    </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {

    
    const cartForms = document.querySelectorAll('.add-to-cart-form');

    cartForms.forEach(form => {

        form.addEventListener('submit', function(e) {

            e.preventDefault(); // Prevent page refresh 

            const formData = new FormData(this);

            const productId = formData.get('product_id');

            const button = this.querySelector('.btn-add-cart');

            const originalText = button.innerHTML;


            
            button.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Adding...';

            button.disabled = true;


            
            fetch('cart-action.php', {

                method: 'POST',

                body: formData
            })

            .then(response => response.json())

            .then(data => {

                
                button.innerHTML = originalText;

                button.disabled = false;


                
                if (data.success) {

                    showToast(
                        data.message || 'Added to cart successfully!'
                    );


                    
                    const cartCountElement =
                        document.querySelector('.cart-count');

                    if (cartCountElement) {

                        let currentCount =
                            parseInt(cartCountElement.textContent) || 0;

                        cartCountElement.textContent = currentCount + 1;
                    }

                } else {

                    
                    showToast(
                        data.message || 'Failed to add to cart',
                        true
                    );
                }
            })

            .catch(error => {

                
                button.innerHTML = originalText;

                button.disabled = false;

                showToast(
                    'An error occurred. Please try again.',
                    true
                );

                console.error('Error:', error);
            });
        });
    });



    
    const removeForms =
        document.querySelectorAll('.remove-favorite-form');


    removeForms.forEach(form => {

        form.addEventListener('submit', function(e) {

            e.preventDefault();

            const formData = new FormData(this);

            const button =
                this.querySelector('.btn-remove-fav');

            const originalText = button.innerHTML;


            
            button.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i>';


            
            fetch('favorites-action.php', {

                method: 'POST',

                body: formData
            })

            .then(response => response.json())

            .then(data => {

                
                if (data.success) {

                    
                    const card =
                        this.closest('.favorite-card');

                    card.style.opacity = '0.5';

                    card.style.transform = 'scale(0.95)';


                    setTimeout(() => {

                        card.remove();


                        
                        const remainingCards =
                            document.querySelectorAll(
                                '.favorite-card'
                            ).length;


                        
                        if (remainingCards === 0) {

                            const grid =
                                document.querySelector(
                                    '.favorites-grid'
                                );

                            grid.innerHTML = `
                                <div class="empty-state">

                                    <i class="far fa-heart"></i>

                                    <h3>No Favorites Yet</h3>

                                    <p>
                                        You haven't added any stationery items
                                        to your favorites yet.
                                    </p>

                                    <a href="menu.php"
                                       class="btn-add-cart"
                                       style="
                                            width: auto;
                                            padding: 12px 40px;
                                            margin-top: 10px;
                                       ">

                                        <i class="fas fa-shopping-bag"></i>
                                        Browse Menu

                                    </a>

                                </div>
                            `;
                        }


                        
                        showToast(
                            data.message || 'Removed from favorites'
                        );

                    }, 300);

                } else {

                    
                    button.innerHTML = originalText;

                    showToast(
                        data.message ||
                        'Failed to remove from favorites',
                        true
                    );
                }
            })

            .catch(error => {

                
                button.innerHTML = originalText;

                showToast(
                    'An error occurred. Please try again.',
                    true
                );

                console.error('Error:', error);
            });
        });
    });

    
function showToast(message, isError = false) {

    
    const existingToast =
        document.querySelector(
            '.toast-notification:not(#success-toast):not(#error-toast)'
        );

    if (existingToast) {
        existingToast.remove();
    }


    
    const toast = document.createElement('div');

    toast.className =
        `toast-notification ${isError ? 'error' : ''}`;


    
    toast.innerHTML = `
        <i class="fas ${
            isError
            ? 'fa-exclamation-circle'
            : 'fa-check-circle'
        } toast-icon"></i>

        <span class="toast-message">${message}</span>
    `;


    
    document.body.appendChild(toast);


    
    setTimeout(() => {

        toast.classList.add('show');

    }, 10);


    
    setTimeout(() => {

        toast.classList.remove('show');

        setTimeout(() => toast.remove(), 300);

    }, 3000);
}



const autoToast =
    document.querySelector('.toast-notification.show');


if (autoToast) {

    setTimeout(() => {

        autoToast.classList.remove('show');

        setTimeout(() => autoToast.remove(), 300);

    }, 5000);
}

});
</script>

<?php require_once 'includes/footer.php'; ?>