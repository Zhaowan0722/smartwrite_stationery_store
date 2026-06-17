<?php


session_start();



error_reporting(E_ALL);
ini_set('display_errors', 1);



header('Content-Type: application/json');



$response = [
    'success' => false,
    'message' => '',
    'order' => null,
    'items' => []
];


try {

    
    $config_path = __DIR__ . '/includes/config.php';

    if (!file_exists($config_path)) {

        throw new Exception(
            'Config file not found'
        );
    }

    require_once $config_path;


    
    if (
        !isset($_SESSION['user_id']) ||
        $_SESSION['user_id'] == 0
    ) {

        throw new Exception(
            'Please login first'
        );
    }


    
    if (
        !isset($_GET['order_id']) ||
        empty($_GET['order_id'])
    ) {

        throw new Exception(
            'Order ID not provided'
        );
    }


    $order_id = intval($_GET['order_id']);

    $user_id = intval($_SESSION['user_id']);


    
    if ($order_id <= 0) {

        throw new Exception(
            'Invalid order ID'
        );
    }


    
    if (!isset($conn) || !$conn) {

        throw new Exception(
            'Database connection failed'
        );
    }


    
    $order_query =
        "SELECT *
         FROM orders
         WHERE id = ?
         AND user_id = ?";


    if ($stmt = mysqli_prepare($conn, $order_query)) {

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $order_id,
            $user_id
        );

        mysqli_stmt_execute($stmt);

        $order_result =
            mysqli_stmt_get_result($stmt);


        
        if (!$order_result) {

            throw new Exception(
                'Database query failed'
            );
        }


        
        if (mysqli_num_rows($order_result) === 0) {

            throw new Exception(
                'Order not found'
            );
        }


        $order =
            mysqli_fetch_assoc($order_result);

        mysqli_stmt_close($stmt);

    } else {

        throw new Exception(
            'Failed to prepare query'
        );
    }


    
    $items = [];


    $items_query =
        "SELECT
            oi.*,
            p.name AS product_name,
            p.price,
            p.image AS product_image
         FROM order_items oi
         LEFT JOIN products p
         ON oi.product_id = p.id
         WHERE oi.order_id = ?";


    if ($stmt = mysqli_prepare($conn, $items_query)) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $order_id
        );

        mysqli_stmt_execute($stmt);

        $items_result =
            mysqli_stmt_get_result($stmt);


        
        if ($items_result) {

            while (
                $item = mysqli_fetch_assoc($items_result)
            ) {

                $items[] = $item;
            }
        }

        mysqli_stmt_close($stmt);
    }


    
    $response['success'] = true;

    $response['order'] = $order;

    $response['items'] = $items;

    $response['message'] =
        'Order details loaded successfully';


} catch (Exception $e) {

    
    $response['success'] = false;

    $response['message'] =
        $e->getMessage();


    
    error_log(
        'get-order-details.php error: ' .
        $e->getMessage()
    );
}



echo json_encode($response);

exit;
?>