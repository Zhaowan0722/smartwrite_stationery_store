<?php


require_once 'includes/config.php';



if (!isset($_SESSION['last_order'])) {

    header('Location: index.php');
    exit();
}



$order = $_SESSION['last_order'];

$order_status = ($order['status'] ?? 'pending') === 'paid' ? 'pending' : ($order['status'] ?? 'pending');
$status_label = ucfirst($order_status);
$status_bg = ($order_status === 'pending') ? '#f39c12' : (($order_status === 'processing') ? '#17a2b8' : (($order_status === 'completed') ? '#27ae60' : '#dc3545'));

$payment_method_text = strtolower($order['payment_method'] ?? '');
if ($order_status === 'cancelled') {
    $payment_status = 'Refunded';
    $payment_bg = '#dc3545';
} elseif (strpos($payment_method_text, 'cash') !== false) {
    $payment_status = 'Pending';
    $payment_bg = '#f39c12';
} else {
    $payment_status = 'Paid';
    $payment_bg = '#27ae60';
}


unset($_SESSION['last_order']);



$page_title = "Order Confirmation";

$show_sidebar = true;



include 'includes/header.php';
?>

<style>


@media print {

    header,
    footer,
    .top-bar,
    .navbar,
    .sidebar,
    .no-print {

        display: none !important;
    }

    body {

        background: white !important;
    }

    .receipt-container {

        width: 100% !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        border: none !important;
    }

}

</style>


<div style="text-align: center; padding: 40px 20px;">

    <!-- Success icon  -->
    <div style="width: 80px; height: 80px; background: #3498db; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">

        <i class="fas fa-check" style="font-size: 2.5rem;"></i>

    </div>


    <!-- Confirmation title  -->
    <h1 style="color: #3498db; margin-bottom: 10px;">

        Order Confirmed!

    </h1>


    <!-- Confirmation message  -->
    <p style="font-size: 1.2rem; color: #666; margin-bottom: 30px;">

        Thank you for your order,

        <?php echo isset($order['customer_name']) ? htmlspecialchars($order['customer_name']) : 'Customer'; ?>!

        Your stationery order has been received successfully.

    </p>


    <!-- Receipt container  -->
    <div class="receipt-container">

        <!-- Receipt header  -->
        <div style="text-align: center; border-bottom: 2px solid #3498db; padding-bottom: 20px; margin-bottom: 25px;">

            <div style="font-size: 2rem; color: #3498db;">

                <i class="fas fa-pencil-ruler"></i>

                SmartWrite

            </div>

            <h2 style="font-size: 1.8rem; color: #3498db; margin: 10px 0;">

                ORDER CONFIRMED

            </h2>

            <div style="color: #666;">

                Receipt Date:
                <?php echo date('F d, Y'); ?>

            </div>

        </div>


        <!-- Order details  -->
        <div style="margin: 20px 0;">

            <!-- Order number  -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dotted #eee;">

                <span style="color: #666;">Order Number:</span>

                <span style="font-weight: bold; color: #3498db;">

                    <?php echo $order['order_number']; ?>

                </span>

            </div>


            <!-- Order date  -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dotted #eee;">

                <span style="color: #666;">Order Date:</span>

                <span>

                    <?php echo date('F d, Y h:i A'); ?>

                </span>

            </div>


            <!-- Customer name  -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dotted #eee;">

                <span style="color: #666;">Customer:</span>

                <span>

                    <?php echo isset($order['customer_name']) ? htmlspecialchars($order['customer_name']) : 'Guest'; ?>

                </span>

            </div>


            <!-- Total amount  -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px dotted #eee;">

                <span style="color: #666;">Total Amount:</span>

                <span style="font-weight: bold; font-size: 1.2rem; color: #3498db;">

                    RM<?php echo number_format($order['total'], 2); ?>

                </span>

            </div>


            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">

                <span style="color: #666;">Payment Status:</span>

                <span style="background: <?php echo $payment_bg; ?>; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold;">
                    <?php echo htmlspecialchars($payment_status); ?>
                </span>

            </div>

        </div>


        <!-- Receipt footer  -->
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 0.9rem;">

            <p>

                <strong>
                    Thank you for shopping with SmartWrite!
                </strong>

            </p>

            <p>

                Receipt ID:
                <?php echo $order['order_number']; ?>

            </p>

        </div>

    </div>


    <!-- What's next section  -->
    <div class="no-print"
     style="text-align: left; max-width: 500px; margin: 40px auto;">
     
        <h3 style="text-align: center; color: #3498db;">

            What's Next?

        </h3>


        <!-- Step 1  1 -->
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">

            <div style="width: 30px; height: 30px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">

                1

            </div>

            <div>

                <strong>Order Confirmation</strong>

                <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">

                    Your order has been confirmed successfully

                </p>

            </div>

        </div>


        <!-- Step 2  2 -->
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">

            <div style="width: 30px; height: 30px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">

                2

            </div>

            <div>

                <strong>Order Processing</strong>

                <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">

                    We are preparing your stationery items

                </p>

            </div>

        </div>


        <!-- Step 3  3 -->
        <div style="display: flex; align-items: center; gap: 15px;">

            <div style="width: 30px; height: 30px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">

                3

            </div>

            <div>

                <strong>Delivery / Pickup</strong>

                <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">

                    Estimated delivery within 2 - 5 working days

                </p>

            </div>

        </div>

    </div>


    <!-- Action buttons  -->
    <div class="no-print"
         style="display: flex; justify-content: center; gap: 20px; margin-top: 30px;">

        <!-- Home button  -->
        <a href="index.php"
           class="btn"
           style="background: #3498db;">

            <i class="fas fa-home"></i>

            Back to Home

        </a>


        <!-- Track order button  -->
        <a href="track.php"
           class="btn"
           style="background: #5dade2;">

            <i class="fas fa-box"></i>

            Track Order

        </a>


        <!-- Print button  -->
        <button class="btn"
                onclick="window.print()"
                style="background: #6c757d;">

            <i class="fas fa-print"></i>

            Print Receipt

        </button>

    </div>

</div>

<?php include 'includes/footer.php'; ?>