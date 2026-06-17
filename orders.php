<?php



require_once 'includes/config.php';



if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_id'] == 0
) {

    $_SESSION['error_message'] =
        'Please login to view your orders';

    header('Location: login.php?redirect=orders');

    exit();
}



$page_title = "My Orders";

$show_sidebar = true;

$current_page = 'orders.php';



$user_id = $_SESSION['user_id'];

$order_sql =
    "SELECT
        o.*,
        COUNT(oi.id) AS item_count,
        SUM(oi.quantity * oi.price) AS order_total
     FROM orders o
     LEFT JOIN order_items oi
     ON o.id = oi.order_id
     WHERE o.user_id = $user_id
     GROUP BY o.id
     ORDER BY o.order_date DESC";


$orders_result =
    mysqli_query($conn, $order_sql);



require_once 'includes/header.php';
?>

<style>


.orders-header {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
    padding: 40px 30px;
    border-radius: 15px;
    margin-bottom: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border-left: 5px solid #3498db;
}


.orders-header h1 {
    color: #333;
    margin-bottom: 15px;
    font-size: 2.5rem;
    position: relative;
}


.orders-header p {
    color: #666;
    margin: 0;
    font-size: 1.1rem;
    max-width: 600px;
    line-height: 1.6;
}


.orders-container {
    margin-top: 20px;
}


.order-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}


.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    border-color: #3498db;
}


.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f8f9fa;
}


.order-id {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
}


.order-id span {
    color: #3498db;
}


.order-date {
    color: #666;
    font-size: 0.95rem;
}


.order-date i {
    margin-right: 8px;
    color: #3498db;
}


.order-status {
    display: inline-block;
    padding: 8px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}


.status-pending {
    background: rgba(255, 193, 7, 0.1);
    color: #f39c12;
    border: 1px solid rgba(243, 156, 18, 0.3);
}




.status-processing {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border: 1px solid rgba(52, 152, 219, 0.3);
}


.status-completed {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}


.status-cancelled {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}


.order-details-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 30px;
    margin-bottom: 20px;
}


.order-items {
    grid-column: 1 / 3;
}


.order-summary {
    background: #f8fbff;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #d6eaf8;
}


.order-summary h4 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    font-size: 1.1rem;
    border-bottom: 1px solid #d6eaf8;
    padding-bottom: 10px;
}


.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    color: #555;
}


.summary-total {
    font-weight: bold;
    font-size: 1.2rem;
    color: #3498db;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #d6eaf8;
}


.order-items h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 1.1rem;
}


.order-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8fbff;
    border-radius: 8px;
    margin-bottom: 10px;
    border: 1px solid #d6eaf8;
}


.order-item:last-child {
    margin-bottom: 0;
}


.item-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 15px;
    border: 2px solid #fff;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}


.item-info {
    flex: 1;
}


.item-name {
    margin: 0 0 5px 0;
    font-weight: 600;
    color: #333;
}


.item-qty-price {
    color: #666;
    font-size: 0.9rem;
}


.item-total {
    font-weight: bold;
    color: #3498db;
    min-width: 80px;
    text-align: right;
}


.order-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 2px solid #f8f9fa;
}


.order-delivery {
    color: #666;
    font-size: 0.95rem;
}


.order-delivery i {
    color: #3498db;
    margin-right: 8px;
}


.order-buttons {
    display: flex;
    gap: 10px;
}


.btn-order-action {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}


.btn-reorder {
    background: #3498db;
    color: white;
    border: none;
    cursor: pointer;
}


.btn-reorder:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}


.btn-view-details {
    background: #6c757d;
    color: white;
}


.btn-view-details:hover {
    background: #5a6268;
    transform: translateY(-2px);
}


.btn-track {
    background: #5dade2;
    color: white;
}


.btn-track:hover {
    background: #3498db;
    transform: translateY(-2px);
}


.empty-orders {
    text-align: center;
    padding: 80px 30px;
    background: linear-gradient(135deg, #f8fbff 0%, #ebf5fb 100%);
    border-radius: 15px;
    border: 2px dashed #d6eaf8;
    margin: 30px 0;
}


.empty-orders-icon {
    font-size: 5rem;
    color: #95a5a6;
    margin-bottom: 25px;
}


.filters-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}


.filter-label {
    font-weight: 600;
    color: #495057;
}


.filter-select {
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: white;
    font-size: 1rem;
    color: #495057;
    min-width: 150px;
    cursor: pointer;
}


.filter-select:focus {
    outline: none;
    border-color: #3498db;
}


.filter-btn {
    background: #3498db;
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}


.filter-btn:hover {
    background: #2980b9;
    transform: translateY(-2px);
}


.reset-btn {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}


.reset-btn:hover {
    background: #5a6268;
    transform: translateY(-2px);
}


@media (max-width: 992px) {

    
    .order-details-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    
    .order-items {
        grid-column: 1;
    }

    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}



@media (max-width: 768px) {

    
    .orders-header {
        padding: 30px 20px;
    }

    
    .orders-header h1 {
        font-size: 2rem;
    }

    
    .order-actions {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }

    
    .order-buttons {
        width: 100%;
        flex-wrap: wrap;
    }

    
    .btn-order-action {
        flex: 1;
        text-align: center;
        justify-content: center;
    }

    
    .filters-section {
        flex-direction: column;
        align-items: stretch;
    }

    
    .filter-select {
        width: 100%;
    }
}



@media (max-width: 576px) {

    
    .order-item {
        flex-direction: column;
        text-align: center;
    }

    
    .item-img {
        margin-right: 0;
        margin-bottom: 10px;
    }

    
    .item-total {
        text-align: center;
        margin-top: 10px;
    }
}

</style>

<!-- Orders container  -->
<div class="orders-container">

    <?php if (mysqli_num_rows($orders_result) > 0): ?>

        <?php while($order = mysqli_fetch_assoc($orders_result)):

            
            $order_id = $order['id'];

            $items_sql =
                "SELECT oi.*, p.name, p.image
                 FROM order_items oi
                 LEFT JOIN products p
                 ON oi.product_id = p.id
                 WHERE oi.order_id = $order_id";

            $items_result = mysqli_query($conn, $items_sql);

            
            $order_date =
                date(
                    'F j, Y \a\t g:i A',
                    strtotime($order['order_date'])
                );

            
            $display_status = ($order['status'] === 'paid') ? 'pending' : $order['status'];
            $status_class = 'status-' . $display_status;

        ?>

        <!-- Single order card  -->
        <div class="order-card">

            <div class="order-header">

                <div>

                    <!-- Order ID  -->
                    <div class="order-id">

                        Order #

                        <span>

                            <?php
                            echo str_pad(
                                $order['id'],
                                6,
                                '0',
                                STR_PAD_LEFT
                            );
                            ?>

                        </span>

                    </div>

                    <!-- Order date  -->
                    <div class="order-date">

                        <i class="far fa-calendar-alt"></i>

                        <?php echo $order_date; ?>

                    </div>

                </div>

                <!-- Order status  -->
                <div class="order-status <?php echo $status_class; ?>">

                    <?php echo ucfirst($display_status); ?>

                </div>

            </div>


            <!-- Order details  -->
            <div class="order-details-grid">

                <!-- Order items  -->
                <div class="order-items">

                    <h4>
                        Order Items
                        (<?php echo $order['item_count']; ?>)
                    </h4>


                    <?php while($item = mysqli_fetch_assoc($items_result)):

                        
                        $item_total =
                            $item['price'] * $item['quantity'];

                        
                        $image_filename =
                            $item['image'] ?? 'default.jpg';

                        $image_path =
                            "images/products/" . $image_filename;

                        $full_image_path =
                            __DIR__ .
                            '/images/products/' .
                            $image_filename;

                        
                        if (
                            $image_filename == 'default.jpg' ||
                            !file_exists($full_image_path)
                        ) {

                            $img_src =
                                'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=100&q=80';

                        } else {

                            $img_src = $image_path;
                        }

                    ?>

                    <!-- Single item  -->
                    <div class="order-item">

                        <img src="<?php echo htmlspecialchars($img_src); ?>"
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="item-img">

                        <div class="item-info">

                            <h5 class="item-name">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h5>

                            <div class="item-qty-price">
                                Quantity: <?php echo $item['quantity']; ?>
                                × RM<?php echo number_format($item['price'], 2); ?>
                            </div>

                        </div>

                        <!-- Item total  -->
                        <div class="item-total">
                            RM<?php echo number_format($item_total, 2); ?>
                        </div>

                    </div>

                    <?php endwhile; ?>

                </div>

                <div class="order-summary">

    <h4>Order Summary</h4>

    <!-- Subtotal  -->
    <div class="summary-row">
        <span>Subtotal</span>
        <span>
            RM<?php echo number_format($order['order_total'], 2); ?>
        </span>
    </div>

    <!-- Delivery fee  -->
    <div class="summary-row">
        <span>Delivery Fee</span>
        <span>
            RM<?php echo $order['order_total'] > 20 ? '0.00' : '3.99'; ?>
        </span>
    </div>

    <!-- Tax  -->
    <div class="summary-row">
        <span>Tax</span>
        <span>RM0.00</span>
    </div>

    <!-- Total  -->
    <div class="summary-row summary-total">
        <span>Total</span>

        <span>
            RM<?php
                echo number_format(
                    $order['order_total'] +
                    ($order['order_total'] > 20 ? 0 : 3.99),
                    2
                );
            ?>
        </span>
    </div>

</div>

</div>


<!-- Order actions  -->
<div class="order-actions">

    <!-- Delivery information  -->
    <div class="order-delivery">

        <i class="fas fa-map-marker-alt"></i>

        <span>

            <?php if ($order['delivery_address']): ?>

                Delivered to:
                <?php echo htmlspecialchars($order['delivery_address']); ?>

            <?php else: ?>

                Delivery address not specified

            <?php endif; ?>

        </span>

    </div>


    <!-- Order buttons  -->
    <div class="order-buttons">

        <!-- Reorder button  -->
        <form method="POST" action="cart.php">

            <input type="hidden"
                   name="action"
                   value="reorder">

            <input type="hidden"
                   name="order_id"
                   value="<?php echo $order['id']; ?>">

            <button type="submit"
                    class="btn-order-action btn-reorder">

                <i class="fas fa-redo"></i>

                Reorder

            </button>

        </form>


        <!-- View details button  -->
        <a href="order-details.php?id=<?php echo $order['id']; ?>"
           class="btn-order-action btn-view-details">

            <i class="fas fa-eye"></i>

            View Details

        </a>


        <!-- Track order button  -->
        <?php if (
            $order['status'] == 'processing' ||
            $order['status'] == 'pending'
        ): ?>

            <a href="track.php?id=<?php echo $order['id']; ?>"
               class="btn-order-action btn-track">

                <i class="fas fa-shipping-fast"></i>

                Track Order

            </a>

        <?php endif; ?>

    </div>

</div>

</div>

<?php endwhile; ?>


<?php else: ?>

<!-- Empty orders  -->
<div class="empty-orders">

    <i class="fas fa-box-open empty-orders-icon"></i>

    <h2 style="color: #666; margin-bottom: 20px;">

        No Orders Yet

    </h2>

    <p style="color: #888; margin-bottom: 30px; font-size: 1.1rem; max-width: 500px; margin-left: auto; margin-right: auto;">

        You haven't placed any stationery orders yet.
        Start browsing and place your first order today!

    </p>


    <!-- Browse products button  -->
    <a href="products.php"
       class="btn"
       style="padding: 15px 40px; font-size: 1.1rem;">

        <i class="fas fa-pencil-ruler"></i>

        Browse Products

    </a>

</div>

<?php endif; ?>

</div>

<script>


document.addEventListener('DOMContentLoaded', function() {

    
    const reorderForms =
        document.querySelectorAll(
            'form[action="cart.php"]'
        );


    
    reorderForms.forEach(form => {

        form.addEventListener('submit', function(e) {

            e.preventDefault();


            
            const orderId =
                this.querySelector(
                    'input[name="order_id"]'
                ).value;


            
            const button =
                this.querySelector('button');

            const originalText =
                button.innerHTML;


            button.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Adding...';

            button.disabled = true;


            
            fetch('reorder.php', {

                method: 'POST',

                headers: {
                    'Content-Type':
                    'application/x-www-form-urlencoded',
                },

                body: 'order_id=' + orderId

            })


            
            .then(response => response.json())

            .then(data => {

                
                button.innerHTML = originalText;

                button.disabled = false;


                
                if (data.success) {

                    alert('Items added to cart!');

                    
                    window.location.href = 'cart.php';

                } else {

                    alert(
                        data.message ||
                        'Failed to reorder items'
                    );
                }

            })


            
            .catch(error => {

                button.innerHTML = originalText;

                button.disabled = false;

                alert(
                    'An error occurred. Please try again.'
                );

                console.error('Error:', error);

            });

        });

    });

});

</script>


<?php require_once 'includes/footer.php'; ?>