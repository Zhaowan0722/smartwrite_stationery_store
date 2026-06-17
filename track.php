<?php



require_once 'includes/config.php';



$page_title = "Track Your Order";

$show_sidebar = false;


if (!$is_logged_in) {
    header('Location: login.php?redirect=track.php');
    exit();
}



$has_orders = false;

$orders = [];



if ($is_logged_in) {

    $user_id = $_SESSION['user_id'];


    
    $sql =
        "SELECT
            o.*,
            COUNT(oi.id) AS item_count,
            SUM(oi.quantity * oi.price) AS calculated_total
         FROM orders o
         LEFT JOIN order_items oi
         ON o.id = oi.order_id
         WHERE o.user_id = $user_id
         GROUP BY o.id
         ORDER BY o.order_date DESC
         LIMIT 10";


    $result = mysqli_query($conn, $sql);


    
    if (
        $result &&
        mysqli_num_rows($result) > 0
    ) {

        $has_orders = true;

        $orders =
            mysqli_fetch_all(
                $result,
                MYSQLI_ASSOC
            );
    }
}



include 'includes/header.php';
?>


<div class="content">

    <!-- Page title  -->
    <h1>Track Your Order</h1>


    <?php if (!$is_logged_in): ?>

    <!-- Guest tracking section  -->
    <div class="guest-tracking"
         style="background: #f8fbff; padding: 30px; border-radius: 10px; margin-bottom: 30px;">

        <h2 style="color: #3498db; margin-bottom: 20px;">

            <i class="fas fa-search"></i>

            Track by Order Number

        </h2>


        <p style="margin-bottom: 20px;">

            Enter your order number and email address
            to check your stationery order status.

        </p>


        <!-- Tracking form  -->
        <form method="GET"
              action="track-order.php"
              style="max-width: 600px;">


            <!-- Order number input  -->
            <div class="form-group"
                 style="margin-bottom: 20px;">

                <label for="order_number"
                       style="display: block; margin-bottom: 8px; font-weight: 600; color: #555;">

                    Order Number

                </label>


                <input type="text"
                       id="order_number"
                       name="order_id"
                       class="form-control"
                       style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px;"
                       placeholder="Enter your order number (e.g. #1001)"
                       required>

            </div>


            <!-- Email input  -->
            <div class="form-group"
                 style="margin-bottom: 20px;">

                <label for="email"
                       style="display: block; margin-bottom: 8px; font-weight: 600; color: #555;">

                    Email Address

                </label>


                <input type="email"
                       id="email"
                       name="email"
                       class="form-control"
                       style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px;"
                       placeholder="Enter your email address"
                       required>

            </div>


            <!-- Submit button  -->
            <button type="submit"
                    class="btn"
                    style="background: #3498db; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-weight: 600; cursor: pointer;">

                <i class="fas fa-search"></i>

                Track Order

            </button>

        </form>


        <!-- Register section  -->
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">

            <h3 style="color: #333; margin-bottom: 10px;">

                Don't have an account?

            </h3>


            <p style="margin-bottom: 15px;">

                Create an account to easily manage and
                track all your stationery orders.

            </p>


            <!-- Benefits list  -->
            <ul style="color: #666; margin-left: 20px; margin-bottom: 20px;">

                <li>View your order history</li>

                <li>Track multiple orders easily</li>

                <li>Save your delivery addresses</li>

                <li>Receive special promotions and offers</li>

            </ul>


            <!-- Register button  -->
            <a href="register.php"
               class="btn"
               style="background: #6c757d; color: white; border: none; padding: 10px 25px; border-radius: 5px; text-decoration: none; display: inline-block;">

                <i class="fas fa-user-plus"></i>

                Create Account

            </a>

        </div>

    </div>

    <?php elseif ($has_orders): ?>

<!-- Logged in user with orders  -->
<div class="user-orders"
     style="background: #f8fbff; padding: 30px; border-radius: 10px; margin-bottom: 30px;">

    <h2 style="color: #3498db; margin-bottom: 20px;">

        <i class="fas fa-history"></i>

        Your Recent Orders

    </h2>


    <p style="margin-bottom: 20px;">

        Here are your recent stationery orders.
        Click any order to view details and track status.

    </p>


    <!-- Orders table  -->
    <div style="overflow-x: auto;">

        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">

            <!-- Table header  -->
            <thead style="background: #3498db; color: white;">

                <tr>

                    <th style="padding: 15px; text-align: left;">
                        Order #
                    </th>

                    <th style="padding: 15px; text-align: left;">
                        Date
                    </th>

                    <th style="padding: 15px; text-align: left;">
                        Items
                    </th>

                    <th style="padding: 15px; text-align: left;">
                        Total
                    </th>

                    <th style="padding: 15px; text-align: left;">
                        Payment Status
                    </th>

                    <th style="padding: 15px; text-align: left;">
                        Order Status
                    </th>

                    <th style="padding: 15px; text-align: left;">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php foreach ($orders as $order):

                    
                    $order_date =
                        date(
                            'M d, Y h:i A',
                            strtotime($order['order_date'])
                        );


                    
                    $status_colors = [

                        'pending' => '#ffc107',

                        

                        'processing' => '#17a2b8',

                        'completed' => '#28a745',

                        'cancelled' => '#dc3545'
                    ];


                    $display_status = ($order['status'] === 'paid') ? 'pending' : $order['status'];
                    $status_color =
                        $status_colors[$display_status]
                        ?? '#6c757d';

                    $payment_method_text = strtolower($order['payment_method'] ?? '');
                    if ($display_status === 'cancelled') {
                        $payment_status = 'Refunded';
                        $payment_color = '#dc3545';
                    } elseif (strpos($payment_method_text, 'cash') !== false) {
                        $payment_status = 'Pending';
                        $payment_color = '#ffc107';
                    } else {
                        $payment_status = 'Paid';
                        $payment_color = '#28a745';
                    }


                    
                    $total =
                        isset($order['calculated_total']) &&
                        $order['calculated_total'] > 0

                        ? $order['calculated_total']

                        : $order['total_price'];

                ?>

                <!-- Single order row  -->
                <tr style="border-bottom: 1px solid #eee;">

                    <td style="padding: 15px; font-weight: 600;">


                    </td>


                    <td style="padding: 15px;">

                        <?php echo $order_date; ?>

                    </td>


                    <td style="padding: 15px;">

                        <?php echo $order['item_count']; ?>
                        items

                    </td>


                    <td style="padding: 15px; font-weight: 600;">

                        RM<?php echo number_format($total, 2); ?>

                    </td>


                    <td style="padding: 15px;">
                        <span style="display: inline-block; padding: 5px 12px; border-radius: 20px; background: <?php echo $payment_color; ?>20; color: <?php echo $payment_color; ?>; font-weight: 600; font-size: 0.9rem;">
                            <?php echo $payment_status; ?>
                        </span>
                    </td>

                    <td style="padding: 15px;">
                        <span style="display: inline-block; padding: 5px 12px; border-radius: 20px; background: <?php echo $status_color; ?>20; color: <?php echo $status_color; ?>; font-weight: 600; font-size: 0.9rem;">
                            <?php echo ucfirst($display_status); ?>
                        </span>
                    </td>


                     <!-- View button  -->
                    <td style="padding: 15px;">

                        <a href="order-details.php?id=<?php echo $order['id']; ?>"
                           style="color: #3498db; text-decoration: none; font-weight: 600;">

                            <i class="fas fa-eye"></i>

                            View Details

                        </a>

                    </td>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>


    <!-- View all orders button  -->
    <div style="margin-top: 30px; text-align: center;">

        <a href="orders.php"
           class="btn"
           style="background: #3498db; color: white; border: none; padding: 12px 30px; border-radius: 5px; text-decoration: none; display: inline-block;">

            <i class="fas fa-list"></i>

            View All Orders

        </a>

    </div>

</div>


<?php else: ?>

<!-- No orders section  -->
<div class="no-orders"
     style="text-align: center; padding: 60px 20px;">

    <div style="font-size: 4rem; color: #ddd; margin-bottom: 20px;">

        <i class="fas fa-box-open"></i>

    </div>


    <h2 style="color: #666; margin-bottom: 20px;">

        No Orders Yet

    </h2>


    <p style="color: #666; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto;">

        You haven't placed any stationery orders yet.
        Browse our products and start shopping today!

    </p>


    <!-- Browse products button  -->
    <a href="products.php"
       class="btn"
       style="background: #3498db; color: white; border: none; padding: 15px 40px; border-radius: 30px; text-decoration: none; display: inline-block; font-weight: 600;">

        <i class="fas fa-pencil-ruler"></i>

        Browse Products

    </a>

</div>

<?php endif; ?>


<!-- Order status guide  -->
<div class="status-info"
     style="background: white; padding: 30px; border-radius: 10px; border: 1px solid #eee; margin-top: 30px;">

    <h2 style="color: #3498db; margin-bottom: 20px;">

        <i class="fas fa-info-circle"></i>

        Order Status Guide

    </h2>


    <!-- Status cards  -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">

        <div style="padding: 20px; border-left: 4px solid #ffc107;">

            <h3 style="margin: 0 0 10px 0; color: #333;">

                Pending

            </h3>

            <p style="color: #666; margin: 0; font-size: 0.9rem;">

                Your order has been received and is waiting for confirmation.

            </p>

        </div>


        <div style="padding: 20px; border-left: 4px solid #17a2b8;">

            <h3 style="margin: 0 0 10px 0; color: #333;">

                Processing

            </h3>

            <p style="color: #666; margin: 0; font-size: 0.9rem;">

                Your stationery items are currently being prepared and packed.

            </p>

        </div>


        <div style="padding: 20px; border-left: 4px solid #28a745;">

            <h3 style="margin: 0 0 10px 0; color: #333;">

                Completed

            </h3>

            <p style="color: #666; margin: 0; font-size: 0.9rem;">

                Your order has been delivered or collected successfully.

            </p>

        </div>


        <div style="padding: 20px; border-left: 4px solid #dc3545;">

            <h3 style="margin: 0 0 10px 0; color: #333;">

                Cancelled

            </h3>

            <p style="color: #666; margin: 0; font-size: 0.9rem;">

                Your order has been cancelled.

            </p>

        </div>

    </div>


    <!-- Help section  -->
    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">

        <h3 style="margin: 0 0 15px 0; color: #333;">

            Need Help?

        </h3>


        <p style="color: #666; margin: 0 0 15px 0;">

            Contact our support team if you need assistance with your order.

        </p>


        <a href="contact.php"
           style="color: #3498db; text-decoration: none; font-weight: 600;">

            <i class="fas fa-headset"></i>

            Contact Support

        </a>

    </div>

</div>

</div>


<?php

include 'includes/footer.php';
?>