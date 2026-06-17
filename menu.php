<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once 'includes/config.php';

$is_logged_in = (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0);

$initial_cart_count = 0;
$cart_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($is_logged_in && $cart_user_id > 0) {
    $cart_count_sql = "SELECT COALESCE(SUM(quantity), 0) AS total_items FROM cart WHERE user_id = $cart_user_id";
    $cart_count_result = mysqli_query($conn, $cart_count_sql);

    if ($cart_count_result && ($cart_count_row = mysqli_fetch_assoc($cart_count_result))) {
        $initial_cart_count = (int)$cart_count_row['total_items'];
    }
}



$page_title = "Our Products";

$hide_sidebar = false;

$current_page = 'menu.php';



$category_slug =
    isset($_GET['category'])
    ? trim($_GET['category'])
    : '';


$group_slug =
    isset($_GET['group'])
    ? trim($_GET['group'])
    : '';

$search_query =
    isset($_GET['q'])
    ? trim($_GET['q'])
    : '';



$where_clauses = [
    "p.available = 1"
];



$category_title =
    "All Products";

$category_desc =
    "Explore our collection of stationery and office supplies.";

$category_is_active = true;


$order_sql = "c.name ASC, p.name ASC";


$group_filters = [
    'writingtools' => [
        'title' => 'Writing Tools',
        'desc'  => 'Pens, pencils, markers and writing tools.',
        'where' => "(c.name = 'Pens' OR p.name LIKE '%Pen%' OR p.name LIKE '%Pencil%' OR p.name LIKE '%Marker%')"
    ],
    'notebookpaper' => [
        'title' => 'Notebook & Paper',
        'desc'  => 'Notebooks, memo pads, paper and study notes.',
        'where' => "(c.name = 'Notebooks' OR p.name LIKE '%Notebook%' OR p.name LIKE '%Memo%' OR p.name LIKE '%Paper%' OR p.name LIKE '%Note%')"
    ],
    'artcolouring' => [
        'title' => 'Art & Colouring',
        'desc'  => 'Colouring pencils, paint sets, sketch books and creative supplies.',
        'where' => "(c.name = 'Art Supplies')"
    ],
    'officetools' => [
        'title' => 'Office Tools',
        'desc'  => 'Staplers, clips, clipboard, paper and office items.',
        'where' => "(c.name = 'Office Supplies' AND p.name NOT LIKE '%Correction%' AND p.name NOT LIKE '%Tape%')"
    ],
    'correctionitems' => [
        'title' => 'Correction Items',
        'desc'  => 'Correction pen and correction tape for neat writing.',
        'where' => "(p.name LIKE '%Correction%' OR p.description LIKE '%correction%')"
    ],
    'schoolessentials' => [
        'title' => 'School Essentials',
        'desc'  => 'Daily student supplies for school and study.',
        'where' => "(c.name = 'School Supplies' AND p.name NOT LIKE '%Backpack%' AND p.name NOT LIKE '%Case%')"
    ],
    'examsupplies' => [
        'title' => 'Exam Supplies',
        'desc'  => 'Useful supplies for exam day and revision.',
        'where' => "(p.name LIKE '%Calculator%' OR p.name LIKE '%Ruler%' OR p.name LIKE '%Compass%' OR p.name LIKE '%Geometry%' OR p.name LIKE '%Eraser%')"
    ],
    'bagscases' => [
        'title' => 'Bags & Cases',
        'desc'  => 'Pencil cases, school bags and storage items.',
        'where' => "(p.name LIKE '%Bag%' OR p.name LIKE '%Backpack%' OR p.name LIKE '%Case%' OR p.description LIKE '%bag%' OR p.description LIKE '%case%')"
    ],
    'bestdeals' => [
        'title' => 'Best Deals',
        'desc'  => 'Affordable stationery picks with lower prices.',
        'where' => "p.price <= 10"
    ]
];


if (!empty($group_slug)) {

    $group_slug =
        preg_replace(
            '/[^a-z0-9\-]/',
            '',
            strtolower($group_slug)
        );

    
    if (isset($group_filters[$group_slug])) {

        $where_clauses[] =
            $group_filters[$group_slug]['where'];

        $category_title =
            $group_filters[$group_slug]['title'];

        $category_desc =
            $group_filters[$group_slug]['desc'];

        $category_is_active = true;

        if ($group_slug === 'bestdeals') {
            $order_sql = "p.price ASC, p.name ASC";
        }

        
        $category_slug = '';
    }

    
    else {

        $category_title =
            "Group Not Found";

        $category_desc =
            "This group does not exist. Showing all products.";

        $category_is_active = true;

        $group_slug = '';
    }
}



if (!empty($category_slug)) {

    
    $category_slug =
        preg_replace(
            '/[^a-z0-9\-]/',
            '',
            strtolower($category_slug)
        );

    $cat_safe =
        mysqli_real_escape_string(
            $conn,
            $category_slug
        );


    
    $cat_sql =
        "SELECT *
         FROM categories
         WHERE (
            LOWER(REPLACE(name, ' ', '')) = '$cat_safe'
            OR
            LOWER(name) LIKE '%$cat_safe%'
         )
         LIMIT 1";


    $cat_result =
        mysqli_query($conn, $cat_sql);


    
    if (!$cat_result) {

        $category_title =
            "Products";

        $category_desc =
            "Showing all available products.";

    }

    
    else if (
        $cat_row =
        mysqli_fetch_assoc($cat_result)
    ) {

        
        if ($cat_row['is_active'] == 0) {

            $category_title =
                "Category Unavailable";

            $category_desc =
                "This category is currently unavailable.";

            $category_is_active = false;

        } else {

            
            $where_clauses[] =
                "p.category_id = " .
                (int)$cat_row['id'];

            $category_title =
                $cat_row['name'];

            $category_desc =
                $cat_row['description'];

            $category_is_active = true;
        }

    }

    
    else {

        $category_title =
            "Category Not Found";

        $category_desc =
            "Category not found. Showing all products.";

        $category_is_active = true;
    }
}



if (!empty($search_query)) {

    $search_safe =
        mysqli_real_escape_string(
            $conn,
            $search_query
        );


    $where_clauses[] =
        "(p.name LIKE '%$search_safe%'
        OR
        p.description LIKE '%$search_safe%')";


    $category_title =
        "Search: \"" .
        htmlspecialchars($search_query) .
        "\"";

    $category_desc =
        "Showing search results.";

    $category_is_active = true;
}



$where_sql =
    implode(
        ' AND ',
        $where_clauses
    );



if ($category_is_active) {

    $sql =
        "SELECT
            p.*,
            c.name AS category_name,
            c.is_active AS category_active
         FROM products p
         LEFT JOIN categories c
         ON p.category_id = c.id
         WHERE $where_sql
         AND (
            c.is_active = 1
            OR
            c.id IS NULL
         )
         ORDER BY
            $order_sql";


    $products =
        mysqli_query($conn, $sql);


    $has_products =
        $products &&
        mysqli_num_rows($products) > 0;

} else {

    
    $has_products = false;

    $products = null;
}



$category_sql =
    "SELECT *
     FROM categories
     WHERE is_active = 1
     AND id IN (
        SELECT DISTINCT category_id
        FROM products
        WHERE available = 1
     )
     ORDER BY name ASC";


$category_result =
    mysqli_query(
        $conn,
        $category_sql
    );


$categories = [];



if ($category_result) {

    while (
        $cat =
        mysqli_fetch_assoc($category_result)
    ) {

        $categories[] = $cat;
    }
}



if (empty($categories)) {

    $categories = [];
}



require_once 'includes/header.php';
?>

<style>
    
.menu-header {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
    padding: 40px 30px;
    border-radius: 15px;
    margin-bottom: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border-left: 5px solid #3498db;
    position: relative;
    overflow: hidden;
}


.menu-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200px;
    height: 200px;
    background: rgba(52, 152, 219, 0.05);
    border-radius: 50%;
}


.menu-header.unavailable {
    border-left-color: #6c757d;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}


.menu-header.unavailable::before {
    background: rgba(108, 117, 125, 0.05);
}


.menu-header h1 {
    color: #333;
    margin-bottom: 15px;
    font-size: 2.5rem;
    position: relative;
}


.menu-header p {
    color: #666;
    margin: 0;
    font-size: 1.1rem;
    max-width: 600px;
    line-height: 1.6;
}



.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}



.product-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    border: 1px solid #f0f0f0;
    position: relative;
}


.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    border-color: #3498db;
}



.product-card.inactive-category {
    opacity: 0.7;
    border-color: #ccc;
}


.product-card.inactive-category:hover {
    transform: none;
    border-color: #ccc;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}


.product-card.inactive-category .category-badge {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}



.product-card.featured::before {
    content: 'BEST SELLER';
    position: absolute;
    top: 15px;
    left: -25px;
    background: #3498db;
    color: white;
    padding: 5px 30px;
    font-size: 0.7rem;
    font-weight: bold;
    transform: rotate(-45deg);
    z-index: 2;
}



.product-card.new::before {
    content: 'NEW';
    position: absolute;
    top: 15px;
    left: -25px;
    background: #28a745;
    color: white;
    padding: 5px 30px;
    font-size: 0.7rem;
    font-weight: bold;
    transform: rotate(-45deg);
    z-index: 2;
}


.product-img-wrapper {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: linear-gradient(45deg, #f8fbff, #ebf5fb);
}


.product-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}


.product-card:hover .product-img-wrapper img {
    transform: scale(1.1);
}



.price-tag {
    position: absolute;
    bottom: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.95);
    padding: 8px 20px;
    border-radius: 25px;
    font-weight: bold;
    color: #3498db;
    box-shadow: 0 3px 15px rgba(52, 152, 219, 0.2);
    font-size: 1.1rem;
    border: 2px solid #3498db;
}



.product-info {
    padding: 25px;
    flex: 1;
    display: flex;
    flex-direction: column;
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
    width: fit-content;
}



.product-info h3 {
    margin: 0 0 12px 0;
    font-size: 1.3rem;
    line-height: 1.4;
}



.product-info h3 a {
    text-decoration: none;
    color: #333;
    transition: color 0.2s;
    display: block;
}


.product-info h3 a:hover {
    color: #3498db;
}



.rating {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 12px;
}


.rating-stars {
    color: #ffc107;
    font-size: 0.9rem;
}


.rating-count {
    color: #666;
    font-size: 0.85rem;
}



.product-desc {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 20px;
    flex: 1;

    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;

    overflow: hidden;
}



.product-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: auto;
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
    background: linear-gradient(135deg, #3498db 0%, #5dade2 100%);
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
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}



.btn-add-cart:active {
    transform: translateY(0);
}



.btn-add-cart:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
}


.btn-add-cart:disabled:hover {
    transform: none;
    box-shadow: none;
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



.empty-state {
    text-align: center;
    padding: 80px 30px;
    background: linear-gradient(135deg, #f8fbff 0%, #ebf5fb 100%);
    border-radius: 15px;
    grid-column: 1 / -1;
    border: 2px dashed #d6eaf8;
}



.empty-state i {
    font-size: 5rem;
    color: #95a5a6;
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



.menu-categories {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}



.menu-category {
    padding: 12px 25px;
    background: #f8f9fa;
    border-radius: 25px;
    text-decoration: none;
    color: #495057;
    font-weight: 600;
    transition: all 0.3s;
    border: 2px solid transparent;
}



.menu-category:hover {
    background: #3498db;
    color: white;
    transform: translateY(-2px);
}



.menu-category.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}


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


.sw-cart-badge,
.cart-count {
    position: absolute !important;
    top: -9px !important;
    right: -11px !important;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 999px;
    background: #ffffff;
    color: #2196df;
    border: 2px solid #2196df;
    font-size: 12px;
    font-weight: 900;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 8px rgba(0,0,0,.18);
    z-index: 20;
}



@media (max-width: 1200px) {

    .products-grid {
        grid-template-columns:
        repeat(auto-fill, minmax(250px, 1fr));
    }
}



@media (max-width: 768px) {

    
    .menu-header {
        padding: 30px 20px;
    }

    
    .menu-header h1 {
        font-size: 2rem;
    }

    
    .products-grid {
        grid-template-columns:
        repeat(auto-fill, minmax(220px, 1fr));

        gap: 20px;
    }

    
    .menu-categories {
        padding: 15px;
        justify-content: center;
    }

    
    .menu-category {
        padding: 10px 20px;
        font-size: 0.9rem;
    }

    
    .toast-notification {
        top: 10px;
        right: 10px;
        left: 10px;

        transform: translateY(-100%);
    }

    
    .toast-notification.show {
        transform: translateY(0);
    }
}



@media (max-width: 576px) {

    
    .products-grid {
        grid-template-columns: 1fr;
    }

    
    .product-actions {
        flex-direction: column;
    }

    
    .qty-input {
        width: 100%;
    }

    
    .btn-add-cart {
        width: 100%;
    }
}

</style>

<!-- Toast Notification  -->
<div id="toast" class="toast-notification">
    <i class="fas fa-check-circle toast-icon"></i>
    <span class="toast-message"></span>
</div>


<!-- Menu Header  -->
<div class="menu-header <?php echo !$category_is_active ? 'unavailable' : ''; ?>">

    <h1>
        <?php echo htmlspecialchars($category_title); ?>
    </h1>

    <p>
        <?php echo htmlspecialchars($category_desc); ?>
    </p>

</div>


<!-- Category Menu  -->
<?php if (!empty($categories)): ?>

<div class="menu-categories">

    <!-- All products  -->
    <a href="menu.php"
       class="menu-category <?php echo (empty($category_slug) && empty($group_slug)) ? 'active' : ''; ?>">

        All Products

    </a>


    <!-- Dynamic categories  -->
    <?php foreach ($categories as $cat): ?>

        <?php
        
        $cat_url_name =
            strtolower(
                str_replace(' ', '', $cat['name'])
            );
        ?>

        <a href="menu.php?category=<?php echo urlencode($cat_url_name); ?>"
           class="menu-category <?php echo ($category_slug === $cat_url_name) ? 'active' : ''; ?>">

            <?php echo htmlspecialchars($cat['name']); ?>

        </a>

    <?php endforeach; ?>

</div>

<?php endif; ?>

<!-- Popup Group Menu  -->
<div class="menu-categories" style="margin-top: -10px;">

    <?php foreach ($group_filters as $key => $group): ?>

        <a href="menu.php?group=<?php echo urlencode($key); ?>"
           class="menu-category <?php echo ($group_slug === $key) ? 'active' : ''; ?>">

            <?php echo htmlspecialchars($group['title']); ?>

        </a>

    <?php endforeach; ?>

</div>


<!-- Products Grid  -->
<div class="products-grid">

<?php if ($has_products): ?>

    <?php while($product = mysqli_fetch_assoc($products)): ?>

        <div class="product-card <?php echo (isset($product['category_active']) && $product['category_active'] == 0) ? 'inactive-category' : ''; ?>">

            
            <!-- Product Image  -->
            <div class="product-img-wrapper">

                <a href="product-details.php?id=<?php echo $product['id']; ?>">

                    <?php
                    
                    $image_filename = $product['image'];

                    
                    $image_path =
                        "images/products/" . $image_filename;

                    $full_image_path =
                        __DIR__ .
                        '/images/products/' .
                        $image_filename;


                    
                    if (
                        $image_filename == 'default.jpg'
                        || !file_exists($full_image_path)
                    ) {

                        
                        $category_id =
                            $product['category_id'] ?? 0;

                        $placeholders = [

                            
                            1 => 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=500&q=80',

                            
                            2 => 'https://images.unsplash.com/photo-1531346680769-a1d79b57de5c?auto=format&fit=crop&w=500&q=80',

                            
                            3 => 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=500&q=80',

                            
                            4 => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=500&q=80',
                        ];


                        
                        $img_src =
                            $placeholders[$category_id]
                            ??
                            'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=500&q=80';

                    } else {

                        
                        $img_src = $image_path;
                    }
                    ?>


                    <!-- Product image  -->
                    <img
                        src="<?php echo htmlspecialchars($img_src); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        loading="lazy"
                    >

                </a>


                <!-- Product price  -->
                <span class="price-tag">
                    RM<?php echo number_format($product['price'], 2); ?>
                </span>

            </div>

            <div class="product-info">

    <!-- Category badge  -->
    <div class="category-badge">

        <?php
        echo htmlspecialchars(
            $product['category_name'] ?? 'General'
        );

        
        if (
            isset($product['category_active'])
            && $product['category_active'] == 0
        ) {

            echo ' (Unavailable)';
        }
        ?>

    </div>


    <!-- Product name  -->
    <h3>

        <a href="product-details.php?id=<?php echo $product['id']; ?>">

            <?php echo htmlspecialchars($product['name']); ?>

        </a>

    </h3>


    <!-- Product description  -->
    <p class="product-desc">

        <?php echo htmlspecialchars($product['description']); ?>

    </p>


    <!-- Product actions  -->
    <div class="product-actions">

        <!-- Quantity input  -->
        <input
            type="number"
            id="quantity_<?php echo $product['id']; ?>"
            value="1"
            min="1"
            max="10"
            class="qty-input"
            aria-label="Quantity"
        >


        <!-- Disabled product  -->
        <?php if (
            isset($product['category_active'])
            && $product['category_active'] == 0
        ): ?>

            <button
                type="button"
                class="btn-add-cart"
                disabled
            >

                <i class="fas fa-ban"></i>

                Unavailable

            </button>

        <?php else: ?>

            <!-- Add to cart button  -->
            <button
                type="button"
                class="btn-add-cart add-to-cart-btn"
                data-product-id="<?php echo $product['id']; ?>"
                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
            >

                <i class="fas fa-cart-plus"></i>

                Add to Cart

            </button>

        <?php endif; ?>

    </div>

</div>

</div>

<?php endwhile; ?>


<?php else: ?>

<!-- Empty products state  -->
<div class="empty-state">

    <i class="fas fa-pencil-ruler"></i>

    <h3>

        <?php if (!$category_is_active): ?>

            Category Unavailable

        <?php else: ?>

            No Products Found

        <?php endif; ?>

    </h3>


    <p>

        <?php if (!$category_is_active): ?>

            Sorry, this category is currently unavailable.
            Please try another category.

        <?php else: ?>

            No stationery products found right now.
            Please check again later.

        <?php endif; ?>

    </p>


    <!-- Back to menu button  -->
    <a href="menu.php"
       class="btn-add-cart"
       style="width: auto; padding: 10px 30px;">

        View All Products

    </a>

</div>

<?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    
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


    function updateCartCount(count = null) {

        let badges =
            document.querySelectorAll('.cart-count, .cart-badge, .sw-cart-badge');


        if (!badges.length) {

            const target =
                findCartBadgeTarget();

            if (!target) {
                return;
            }

            target.style.position = 'relative';

            const badge =
                document.createElement('span');

            badge.className =
                'sw-cart-badge cart-count';

            target.appendChild(badge);

            badges =
                document.querySelectorAll('.cart-count, .cart-badge, .sw-cart-badge');
        }


        const firstBadge = badges[0];

        let finalCount =
            parseInt(count, 10);


        if (Number.isNaN(finalCount)) {

            const currentCount =
                parseInt(firstBadge.textContent, 10) || 0;

            finalCount =
                currentCount + 1;
        }


        badges.forEach(badge => {

            badge.textContent = finalCount;

            badge.style.display =
                finalCount > 0 ? 'inline-flex' : 'none';
        });
    }


    
    updateCartCount(<?php echo (int)$initial_cart_count; ?>);


    
    const addToCartButtons =
        document.querySelectorAll('.add-to-cart-btn');


    addToCartButtons.forEach(button => {

        button.addEventListener('click', function (e) {

            e.preventDefault();


            
            const productId =
                this.getAttribute('data-product-id');

            const productName =
                this.getAttribute('data-product-name');

            const quantityInput =
                document.getElementById(
                    'quantity_' + productId
                );

            const quantity =
                quantityInput
                ? parseInt(quantityInput.value)
                : 1;


            
            const productCard =
                this.closest('.product-card');

            const categoryBadge =
                productCard.querySelector('.category-badge');


            if (
                categoryBadge &&
                categoryBadge.textContent.includes('(Unavailable)')
            ) {

                showToast(
                    'This product is currently unavailable.',
                    true
                );

                return;
            }


            
            const originalText = this.innerHTML;

            this.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Adding...';

            this.disabled = true;


            
            fetch('cart-action.php', {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },

                body:
                    'action=add'
                    + '&product_id=' + productId
                    + '&quantity=' + quantity
            })


            .then(response => response.json())


            .then(data => {

                
                this.innerHTML = originalText;

                this.disabled = false;


                
                if (data.success) {

                    showToast(
                        productName + ' added to cart!'
                    );

                    updateCartCount(data.cart_count);

                } else {

                    if (data.login_required) {
                        showToast(data.message || 'Please login first before adding products to cart.', true);
                        setTimeout(function () {
                            window.location.href = data.redirect || 'login.php';
                        }, 900);
                        return;
                    }

                    showToast(
                        data.message || 'Failed to add product',
                        true
                    );
                }
            })


            .catch(error => {

                
                this.innerHTML = originalText;

                this.disabled = false;


                
                showToast(
                    'Something went wrong. Please try again.',
                    true
                );


                console.error('Error:', error);
            });

        });

    });

});
</script>

<?php require_once 'includes/footer.php'; ?>

