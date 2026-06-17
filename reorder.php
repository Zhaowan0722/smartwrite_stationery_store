<?php



require_once 'includes/config.php';



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    
    $order_id = $_POST['order_id'] ?? 0;

    $user_id = $_SESSION['user_id'] ?? 0;


    
    if ($user_id == 0) {

        echo json_encode([
            'success' => false,
            'message' => 'Please login first'
        ]);

        exit();
    }


    
    $sql =
        "SELECT
            oi.*,
            p.name,
            p.price,
            p.image
         FROM order_items oi
         JOIN orders o
         ON oi.order_id = o.id
         LEFT JOIN products p
         ON oi.product_id = p.id
         WHERE o.id = $order_id
         AND o.user_id = $user_id";


    $result = mysqli_query($conn, $sql);


    
    if (mysqli_num_rows($result) > 0) {

        
        if (!isset($_SESSION['cart'])) {

            $_SESSION['cart'] = [];
        }


        $added_count = 0;


        
        while ($item = mysqli_fetch_assoc($result)) {

            
            $found = false;


            foreach ($_SESSION['cart'] as &$cart_item) {

                if (
                    $cart_item['product_id'] ==
                    $item['product_id']
                ) {

                    
                    $cart_item['quantity'] +=
                        $item['quantity'];

                    $found = true;

                    $added_count++;

                    break;
                }
            }


            
            if (!$found) {

                $_SESSION['cart'][] = [

                    'product_id' =>
                        $item['product_id'],

                    'name' =>
                        $item['name'],

                    'price' =>
                        $item['price'],

                    'quantity' =>
                        $item['quantity'],

                    'image' =>
                        $item['image']
                ];

                $added_count++;
            }
        }


        
        echo json_encode([

            'success' => true,

            'message' =>
                "Added $added_count items to cart",

            'cart_count' =>
                count($_SESSION['cart'])
        ]);

    } else {

        
        echo json_encode([

            'success' => false,

            'message' => 'Order not found'
        ]);
    }

} else {

    
    echo json_encode([

        'success' => false,

        'message' => 'Invalid request'
    ]);
}
?>