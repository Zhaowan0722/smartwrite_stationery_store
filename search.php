<?php
require_once 'includes/config.php';

$page_title = "Search Results";
$show_sidebar = true;
$is_logged_in = (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0);

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

include 'includes/header.php';
?>

<div class="content">

    <!-- HEADER -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.08); margin-bottom: 25px;">
        <h1 style="margin:0; color:#2c3e50;">
            <i class="fas fa-search" style="color:#3498db;"></i> Search Results
        </h1>

        <?php if(!empty($search_query)): ?>
            <p style="margin-top:10px; color:#666;">
                Results for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
            </p>
        <?php endif; ?>
    </div>

    <?php if(empty($search_query)): ?>

        <div style="background:#fdecea; padding:20px; border-radius:8px; color:#c0392b;">
            <i class="fas fa-exclamation-circle"></i>
            Please enter a search term.
        </div>

    <?php else: ?>

    <?php
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.available = 1 
            AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)
            ORDER BY p.name";

    $search_param = "%{$search_query}%";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    ?>

    <?php if(mysqli_num_rows($result) > 0): ?>

        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:20px;">

            <?php while($product = mysqli_fetch_assoc($result)):

                $image_filename = $product['image'];
                $image_url = ($image_filename === 'default.jpg')
                    ? 'https://images.unsplash.com/photo-1551782450-17144efb9c50?auto=format&fit=crop&w=400&q=80'
                    : 'images/products/' . $image_filename;
            ?>

            <div style="background:white; border-radius:10px; padding:15px; box-shadow:0 3px 10px rgba(0,0,0,0.08);">

                <img src="<?php echo $image_url; ?>"
                     style="width:100%; height:150px; object-fit:cover; border-radius:8px;">

                <h3 style="margin:10px 0 5px;">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h3>

                <p style="font-size:0.9rem; color:#666;">
                    <?php echo htmlspecialchars($product['description']); ?>
                </p>

                <p style="color:#3498db; font-weight:bold; font-size:1.2rem;">
                    RM<?php echo number_format($product['price'], 2); ?>
                </p>

                <form method="POST" action="cart-action.php" class="search-cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                    <button type="submit"
                            style="width:100%; padding:10px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer;">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </form>

            </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div style="text-align:center; padding:40px;">
            <i class="fas fa-search" style="font-size:3rem; color:#ccc;"></i>
            <h3>No results found</h3>
            <p style="color:#666;">
                No products match "<?php echo htmlspecialchars($search_query); ?>"
            </p>

            <a href="menu.php"
               style="display:inline-block; margin-top:15px; background:#3498db; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">
                Browse Menu
            </a>
        </div>

    <?php endif; ?>

    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
<script>
document.querySelectorAll('.search-cart-form').forEach(function(form){
    form.addEventListener('submit', function(e){
        const loggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        if(!loggedIn){
            e.preventDefault();
            alert('Please login first before adding products to cart.');
            window.location.href = 'login.php';
        }
    });
});
</script>
