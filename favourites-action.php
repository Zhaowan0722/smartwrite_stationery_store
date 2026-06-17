<?php


session_start();

require_once 'includes/config.php'; // Include config 



if (!isset($_SESSION['user_id'])) {

    echo json_encode([

        'success' => false,

        'message' => 'Please login to add favorites',

        'redirect' => 'login.php'
    ]);

    exit();
}



$user_id = $_SESSION['user_id'];

$action = isset($_POST['action'])
    ? $_POST['action']
    : '';

$product_id = isset($_POST['product_id'])
    ? intval($_POST['product_id'])
    : 0;



$product_check = mysqli_query(

    $conn,

    "SELECT id 
     FROM products 
     WHERE id = $product_id 
     AND available = 1"
);



if (!$product_check || mysqli_num_rows($product_check) == 0) {

    echo json_encode([

        'success' => false,

        'message' => 'Product not found or unavailable'
    ]);

    exit();
}



if ($action === 'add') {

    
    $check_sql = "SELECT id 
                  FROM favorites 
                  WHERE user_id = $user_id 
                  AND product_id = $product_id";

    $check_result = mysqli_query($conn, $check_sql);


    
    if (mysqli_num_rows($check_result) == 0) {

        $sql = "INSERT INTO favorites 
                (user_id, product_id) 
                VALUES ($user_id, $product_id)";


        if (mysqli_query($conn, $sql)) {

            echo json_encode([

                'success' => true,

                'message' => 'Added to favorites'
            ]);

        } else {

            echo json_encode([

                'success' => false,

                'message' => 'Failed to add to favorites'
            ]);
        }

    } else {

        
        echo json_encode([

            'success' => false,

            'message' => 'Already in favorites'
        ]);
    }



} elseif ($action === 'remove') {

    $sql = "DELETE FROM favorites 
            WHERE user_id = $user_id 
            AND product_id = $product_id";


    if (mysqli_query($conn, $sql)) {

        echo json_encode([

            'success' => true,

            'message' => 'Removed from favorites'
        ]);

    } else {

        echo json_encode([

            'success' => false,

            'message' => 'Failed to remove from favorites'
        ]);
    }



} else {

    echo json_encode([

        'success' => false,

        'message' => 'Invalid action'
    ]);
}
?>