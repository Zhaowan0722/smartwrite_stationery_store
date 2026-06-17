<?php


session_start();

require_once 'includes/config.php';



if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['user_id'] == 0
) {

    die("User not logged in");
}



$user_id = $_SESSION['user_id'];



echo "<h2>Testing Orders for User ID: $user_id</h2>";



$test1 =
    mysqli_query(
        $conn,
        "SHOW TABLES LIKE 'orders'"
    );

echo
    "<p>
        1. Orders table exists:
        " .
        (mysqli_num_rows($test1) > 0
            ? "YES"
            : "NO") .
    "</p>";




$test2 =
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS count
         FROM orders
         WHERE user_id = $user_id"
    );

$row2 =
    mysqli_fetch_assoc($test2);

echo
    "<p>
        2. User has orders:
        " .
        ($row2['count'] > 0
            ? "YES ({$row2['count']} orders)"
            : "NO") .
    "</p>";




echo "<p>3. Recent orders:</p>";


$test3 =
    mysqli_query(
        $conn,
        "SELECT *
         FROM orders
         WHERE user_id = $user_id
         ORDER BY order_date DESC
         LIMIT 5"
    );


if (mysqli_num_rows($test3) > 0) {

    echo "<ul>";

    while (
        $order =
        mysqli_fetch_assoc($test3)
    ) {

        echo
            "<li>

                Order #{$order['id']}

                -
                {$order['status']}

                -
                RM{$order['total_price']}

                -
                {$order['order_date']}

            </li>";
    }

    echo "</ul>";

} else {

    echo "<p>No orders found.</p>";
}




echo "<p>4. Testing first order items:</p>";


$test4 =
    mysqli_query(
        $conn,
        "SELECT *
         FROM orders
         WHERE user_id = $user_id
         LIMIT 1"
    );


if ($row4 = mysqli_fetch_assoc($test4)) {

    $order_id = $row4['id'];

    echo
        "<p>
            Checking order #$order_id items:
        </p>";


    $items_query =
        mysqli_query(
            $conn,
            "SELECT
                oi.*,
                p.name
             FROM order_items oi
             LEFT JOIN products p
             ON oi.product_id = p.id
             WHERE oi.order_id = $order_id"
        );


    if (mysqli_num_rows($items_query) > 0) {

        echo "<ul>";

        while (
            $item =
            mysqli_fetch_assoc($items_query)
        ) {

            echo
                "<li>

                    {$item['name']}

                    -

                    {$item['quantity']} x RM{$item['price']}

                </li>";
        }

        echo "</ul>";

    } else {

        echo
            "<p>
                No items found for this order.
            </p>";
    }
}

?>