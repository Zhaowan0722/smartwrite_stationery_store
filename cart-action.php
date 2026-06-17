<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

$action = $_POST['action'] ?? '';
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($quantity < 1) {
    $quantity = 1;
}

if ($action === 'add') {
    if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
        echo json_encode([
            'success' => false,
            'login_required' => true,
            'redirect' => 'login.php',
            'message' => 'Please login first before adding products to cart.'
        ]);
        exit();
    }
}

switch ($action) {
    case 'add':
        if ($product_id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid product.'
            ]);
            exit();
        }

        $product_id_safe = mysqli_real_escape_string($conn, $product_id);
        $sql = "SELECT * FROM products WHERE id = $product_id_safe AND available = TRUE";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
            $found = false;

            if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            foreach ($_SESSION['cart'] as &$item) {
                if ((int)$item['product_id'] === $product_id) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            unset($item);

            if (!$found) {
                $_SESSION['cart'][] = [
                    'product_id' => $product_id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $quantity,
                    'image' => $product['image']
                ];
            }

            $cart_count = 0;
            foreach ($_SESSION['cart'] as $cart_item) {
                $cart_count += isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 1;
            }

            echo json_encode([
                'success' => true,
                'cart_count' => $cart_count,
                'message' => $product['name'] . ' added to cart!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found or unavailable.'
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action.'
        ]);
        break;
}
?>
