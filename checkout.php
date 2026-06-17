<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}


$subtotal = 0.00;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += ((float)($item['price'] ?? 0)) * ((int)($item['quantity'] ?? 0));
}

$has_free_shipping_voucher =
    !empty($_SESSION['free_shipping_voucher_claimed']) ||
    (($_SESSION['delivery_fee_voucher'] ?? '') === 'free_shipping');

$has_rm2_discount_voucher =
    !empty($_SESSION['rm2_discount_voucher_claimed']) ||
    ((float)($_SESSION['discount_voucher_amount'] ?? 0) > 0);

$default_delivery_fee = ($subtotal >= 100) ? 0.00 : 10.00;
$preview_total = $subtotal + $default_delivery_fee;


$user_info = [];
$logged_in_user_id = 0;

if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
    $logged_in_user_id = (int)$_SESSION['user_id'];

    $user_sql = "
        SELECT username, email
        FROM users
        WHERE id = $logged_in_user_id
        LIMIT 1
    ";

    $user_result = mysqli_query($conn, $user_sql);

    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user_info = mysqli_fetch_assoc($user_result);
    }
}


$address_table_exists = false;
$saved_addresses = [];

if ($logged_in_user_id > 0) {
    $address_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'user_addresses'");

    if ($address_table_check && mysqli_num_rows($address_table_check) > 0) {
        $address_table_exists = true;

        $address_sql = "
            SELECT *
            FROM user_addresses
            WHERE user_id = $logged_in_user_id
            ORDER BY is_default DESC, id DESC
        ";

        $address_result = mysqli_query($conn, $address_sql);

        if ($address_result) {
            while ($row = mysqli_fetch_assoc($address_result)) {
                $saved_addresses[] = $row;
            }
        }
    }
}


$card_table_exists = false;
$saved_cards = [];

if ($logged_in_user_id > 0) {
    $card_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'user_saved_cards'");

    if ($card_table_check && mysqli_num_rows($card_table_check) > 0) {
        $card_table_exists = true;

        $card_sql = "
            SELECT *
            FROM user_saved_cards
            WHERE user_id = $logged_in_user_id
            ORDER BY is_default DESC, id DESC
        ";

        $card_result = mysqli_query($conn, $card_sql);

        if ($card_result) {
            while ($row = mysqli_fetch_assoc($card_result)) {
                $saved_cards[] = $row;
            }
        }
    }
}

$error = '';


function smartwrite_is_future_expiry($expiry_date_raw) {
    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2}|\d{4})$/', $expiry_date_raw, $match)) {
        return false;
    }

    $month = (int)$match[1];
    $year = (int)$match[2];

    if ($year < 100) {
        $year += 2000;
    }

    $expiry_month_start = strtotime(sprintf('%04d-%02d-01 00:00:00', $year, $month));
    $current_month_start = strtotime(date('Y-m-01 00:00:00'));

    return $expiry_month_start >= $current_month_start;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['gateway_confirmed']) && !empty($_SESSION['pending_checkout_form']) && is_array($_SESSION['pending_checkout_form'])) {
        $gateway_receipt_reference = trim($_POST['receipt_reference'] ?? '');
        $_POST = $_SESSION['pending_checkout_form'];
        $_POST['gateway_confirmed'] = '1';
        if ($gateway_receipt_reference !== '') {
            $_POST['receipt_reference'] = $gateway_receipt_reference;
        }
        unset($_SESSION['pending_checkout_form']);
    }

    $service_type = mysqli_real_escape_string($conn, $_POST['service_type'] ?? 'delivery');
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cash');
    $special_instructions = mysqli_real_escape_string($conn, $_POST['special_instructions'] ?? '');

    if ($logged_in_user_id > 0 && !empty($user_info)) {
        $name = mysqli_real_escape_string($conn, $user_info['username']);
        $email = mysqli_real_escape_string($conn, $user_info['email']);
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    } else {
        $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
        $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    }

    $raw_address = '';
    $selected_address_id = (int)($_POST['selected_address_id'] ?? 0);
    $new_address_label = trim($_POST['new_address_label'] ?? '');
    $new_address_text = trim($_POST['new_address'] ?? '');
    $guest_address_text = trim($_POST['address'] ?? '');

    if ($service_type === 'pickup') {
        $raw_address = 'Self Pickup';
    } else {
        if ($logged_in_user_id > 0 && $selected_address_id > 0 && $address_table_exists) {
            $saved_address_sql = "
                SELECT receiver_name, phone, address_line, city, postcode, state
                FROM user_addresses
                WHERE id = $selected_address_id
                AND user_id = $logged_in_user_id
                LIMIT 1
            ";

            $saved_address_result = mysqli_query($conn, $saved_address_sql);

            if ($saved_address_result && mysqli_num_rows($saved_address_result) > 0) {
                $saved_address = mysqli_fetch_assoc($saved_address_result);

                $raw_address = trim(
                    ($saved_address['receiver_name'] ? $saved_address['receiver_name'] . ', ' : '') .
                    ($saved_address['phone'] ? $saved_address['phone'] . ', ' : '') .
                    $saved_address['address_line'] . ', ' .
                    trim($saved_address['postcode'] . ' ' . $saved_address['city']) . ', ' .
                    $saved_address['state']
                );
            }
        }

        if (empty($raw_address)) {
            $raw_address = !empty($new_address_text) ? $new_address_text : $guest_address_text;
        }

        if ($logged_in_user_id > 0 && !empty($new_address_text) && isset($_POST['save_new_address']) && $address_table_exists) {
            $save_label = mysqli_real_escape_string($conn, $new_address_label ?: 'Address');
            $save_receiver = mysqli_real_escape_string($conn, $user_info['username'] ?? $name);
            $save_phone = mysqli_real_escape_string($conn, $phone);
            $save_address = mysqli_real_escape_string($conn, $new_address_text);

            $insert_address_sql = "
                INSERT INTO user_addresses (
                    user_id,
                    label,
                    receiver_name,
                    phone,
                    address_line,
                    city,
                    postcode,
                    state,
                    is_default,
                    created_at
                ) VALUES (
                    $logged_in_user_id,
                    '$save_label',
                    '$save_receiver',
                    '$save_phone',
                    '$save_address',
                    '',
                    '',
                    '',
                    0,
                    NOW()
                )
            ";

            mysqli_query($conn, $insert_address_sql);
        }
    }

    $address = mysqli_real_escape_string($conn, $raw_address);

    
    $card_bank_raw = trim($_POST['card_bank'] ?? '');
    $card_name_raw = trim($_POST['card_name'] ?? '');
    $card_number_raw = trim($_POST['card_number'] ?? '');
    $expiry_date_raw = trim($_POST['expiry_date'] ?? '');
    $cvv_raw = trim($_POST['cvv'] ?? '');
    $online_bank_raw = trim($_POST['online_bank'] ?? '');
    $online_account_raw = trim($_POST['online_account'] ?? '');
    $online_password_raw = trim($_POST['online_password'] ?? '');
    $online_phone_raw = trim($_POST['online_phone'] ?? '');
    $online_pin_raw = trim($_POST['online_pin'] ?? '');
    $online_otp_raw = trim($_POST['online_otp'] ?? '');
    $online_expected_otp_raw = trim($_POST['online_expected_otp'] ?? '');
    $receipt_reference_raw = trim($_POST['receipt_reference'] ?? '');
    $saved_card_id = (int)($_POST['saved_card_id'] ?? 0);
    $save_card_to_profile = !empty($_POST['save_card_to_profile']);
    $selected_saved_card = null;

    $card_bank = mysqli_real_escape_string($conn, $card_bank_raw);
    $card_name = mysqli_real_escape_string($conn, $card_name_raw);
    $expiry_date = mysqli_real_escape_string($conn, $expiry_date_raw);
    $online_bank = mysqli_real_escape_string($conn, $online_bank_raw);
    $online_account = mysqli_real_escape_string($conn, $online_account_raw);
    $online_password = mysqli_real_escape_string($conn, $online_password_raw);
    $online_phone = mysqli_real_escape_string($conn, $online_phone_raw);
    $online_pin = mysqli_real_escape_string($conn, $online_pin_raw);
    $online_otp = mysqli_real_escape_string($conn, $online_otp_raw);
    $receipt_reference = mysqli_real_escape_string($conn, $receipt_reference_raw);
    $clean_card = preg_replace('/\D+/', '', $card_number_raw);
    $clean_online_account = preg_replace('/\D+/', '', $online_account_raw);
    $clean_online_phone = preg_replace('/\D+/', '', $online_phone_raw);

    $online_is_bank = ($payment_method === 'online' && stripos($online_bank_raw, 'FPX') === 0);
    $online_is_wallet = ($payment_method === 'online' && !$online_is_bank && !empty($online_bank_raw));

    if ($payment_method === 'online' && empty($_POST['gateway_confirmed'])) {
        if (empty($online_bank_raw)) {
            $error = 'Please select your online bank or eWallet.';
        } else {
            $_SESSION['pending_checkout_form'] = $_POST;
            $gateway_method = urlencode($online_bank_raw);
            if ($online_is_bank) {
                header('Location: bank-login.php?bank=' . $gateway_method);
            } else {
                header('Location: wallet-payment.php?method=' . $gateway_method);
            }
            exit();
        }
    }

    if ($payment_method === 'card' && $logged_in_user_id > 0 && $saved_card_id > 0 && $card_table_exists) {
        $saved_card_sql = "
            SELECT *
            FROM user_saved_cards
            WHERE id = $saved_card_id
            AND user_id = $logged_in_user_id
            LIMIT 1
        ";

        $saved_card_result = mysqli_query($conn, $saved_card_sql);

        if ($saved_card_result && mysqli_num_rows($saved_card_result) > 0) {
            $selected_saved_card = mysqli_fetch_assoc($saved_card_result);
            $card_bank_raw = $selected_saved_card['bank_name'];
            $card_name_raw = $selected_saved_card['cardholder_name'];
            $expiry_date_raw = $selected_saved_card['expiry_date'];
        }
    }

    $payment_errors = [];

    if ($service_type === 'delivery' && empty($raw_address)) {
        $payment_errors[] = 'Please enter or select a delivery address.';
    }

    if ($payment_method === 'card') {
        if ($saved_card_id > 0 && empty($selected_saved_card)) {
            $payment_errors[] = 'Selected saved card was not found. Please choose another card or use a new card.';
        }

        if (!empty($selected_saved_card)) {
            if (!preg_match('/^\d{3,4}$/', $cvv_raw)) {
                $payment_errors[] = 'CVV must be 3 or 4 digits.';
            }
        } else {
            if (empty($card_bank_raw)) {
                $payment_errors[] = 'Please select your card bank.';
            }

            if (empty($card_name_raw)) {
                $payment_errors[] = 'Please enter the cardholder name.';
            }

            if (strlen($clean_card) !== 16) {
                $payment_errors[] = 'Card number must be exactly 16 digits.';
            }

            if (!smartwrite_is_future_expiry($expiry_date_raw)) {
                $payment_errors[] = 'Expiry date must use MM/YY format and cannot be in the past.';
            }

            if (!preg_match('/^\d{3,4}$/', $cvv_raw)) {
                $payment_errors[] = 'CVV must be 3 or 4 digits.';
            }
        }
    }

    if ($payment_method === 'online') {
        if (empty($online_bank_raw)) {
            $payment_errors[] = 'Please select your online bank or eWallet.';
        }

        if (!empty($_POST['gateway_confirmed']) && $online_is_wallet && empty($receipt_reference_raw)) {
            $payment_errors[] = 'Please enter the payment reference / receipt number after scanning QR.';
        }
    }

    if (empty($name)) {
        $payment_errors[] = 'Please enter your name.';
    }

    if (empty($email)) {
        $payment_errors[] = 'Please enter your email.';
    }

    if (!empty($payment_errors)) {
        $error = implode(' ', $payment_errors);
    } else {
        $payment_display = ucfirst($payment_method);

        if ($payment_method === 'online') {
            if ($online_is_bank) {
                $payment_display = 'Online Banking - ' . $online_bank . ' - ****' . substr($clean_online_account, -4);
            } else {
                $payment_display = 'Wallet Payment - ' . $online_bank;
                if (!empty($receipt_reference)) {
                    $payment_display .= ' - Ref: ' . $receipt_reference;
                }
            }
        }

        if ($payment_method === 'card') {
            if (!empty($selected_saved_card)) {
                $payment_display =
                    'Card - ' .
                    mysqli_real_escape_string($conn, $selected_saved_card['bank_name']) .
                    ' - **** **** **** ' .
                    mysqli_real_escape_string($conn, $selected_saved_card['last4']);
            } else {
                $payment_display = 'Card - ' . $card_bank . ' - **** **** **** ' . substr($clean_card, -4);
            }
        }

        $use_free_shipping_voucher = $has_free_shipping_voucher && !empty($_POST['use_free_shipping_voucher']);
        $use_rm2_discount_voucher = $has_rm2_discount_voucher && !empty($_POST['use_rm2_discount_voucher']);

        $free_shipping_effective =
            $use_free_shipping_voucher &&
            $service_type !== 'pickup' &&
            $subtotal < 100;

        if ($service_type === 'pickup' || $free_shipping_effective || $subtotal >= 100) {
            $delivery_fee = 0.00;
        } else {
            $delivery_fee = 10.00;
        }

        $voucher_discount = $use_rm2_discount_voucher ? min(2.00, $subtotal) : 0.00;
        $total = max(0, $subtotal + $delivery_fee - $voucher_discount);

        $order_user_id = $logged_in_user_id > 0 ? $logged_in_user_id : 'NULL';
        $order_number = 'ORD' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $order_status = 'pending';

        $order_sql = "
            INSERT INTO orders (
                user_id,
                total_price,
                status,
                delivery_address,
                payment_method,
                customer_name,
                customer_email,
                customer_phone,
                delivery_method
            ) VALUES (
                $order_user_id,
                '$total',
                '$order_status',
                '$address',
                '$payment_display',
                '$name',
                '$email',
                '$phone',
                '$service_type'
            )
        ";

        if (mysqli_query($conn, $order_sql)) {
            $order_id = mysqli_insert_id($conn);

            if (
                $payment_method === 'card' &&
                empty($selected_saved_card) &&
                $save_card_to_profile &&
                $logged_in_user_id > 0 &&
                strlen($clean_card) === 16 &&
                $card_table_exists
            ) {
                $save_card_last4 = mysqli_real_escape_string($conn, substr($clean_card, -4));
                $save_card_bank = mysqli_real_escape_string($conn, $card_bank_raw);
                $save_card_name = mysqli_real_escape_string($conn, $card_name_raw);
                $save_card_expiry = mysqli_real_escape_string($conn, $expiry_date_raw);

                $check_card_sql = "
                    SELECT id
                    FROM user_saved_cards
                    WHERE user_id = $logged_in_user_id
                    AND bank_name = '$save_card_bank'
                    AND last4 = '$save_card_last4'
                    AND expiry_date = '$save_card_expiry'
                    LIMIT 1
                ";

                $check_card_result = mysqli_query($conn, $check_card_sql);

                if (!$check_card_result || mysqli_num_rows($check_card_result) === 0) {
                    $insert_card_sql = "
                        INSERT INTO user_saved_cards (
                            user_id,
                            cardholder_name,
                            bank_name,
                            last4,
                            expiry_date,
                            is_default,
                            created_at
                        ) VALUES (
                            $logged_in_user_id,
                            '$save_card_name',
                            '$save_card_bank',
                            '$save_card_last4',
                            '$save_card_expiry',
                            0,
                            NOW()
                        )
                    ";

                    mysqli_query($conn, $insert_card_sql);
                }
            }

            foreach ($_SESSION['cart'] as $cart_item) {
                $order_product_id = (int)$cart_item['product_id'];
                $order_quantity = (int)$cart_item['quantity'];
                $order_price = mysqli_real_escape_string($conn, $cart_item['price']);

                $item_sql = "
                    INSERT INTO order_items (
                        order_id,
                        product_id,
                        quantity,
                        price
                    ) VALUES (
                        '$order_id',
                        '$order_product_id',
                        '$order_quantity',
                        '$order_price'
                    )
                ";

                mysqli_query($conn, $item_sql);
            }

            $_SESSION['cart'] = [];

            if (!empty($free_shipping_effective)) {
                unset($_SESSION['free_shipping_voucher_claimed']);
                unset($_SESSION['delivery_fee_voucher']);
            }

            if (!empty($use_rm2_discount_voucher)) {
                unset($_SESSION['rm2_discount_voucher_claimed']);
                unset($_SESSION['discount_voucher_amount']);
            }

            $_SESSION['last_order'] = [
                'order_id' => $order_id,
                'order_number' => $order_number,
                'total' => $total,
                'customer_name' => $name,
                'delivery_method' => $service_type,
                'delivery_address' => $address,
                'payment_method' => $payment_display,
                'status' => $order_status
            ];

            header('Location: payment-loading.php?order_id=' . $order_id);
            exit();
        } else {
            $error = 'Error placing order: ' . mysqli_error($conn);
        }
    }
}

$page_title = 'Checkout';
$show_sidebar = true;

include 'includes/header.php';
?>

<style>
.checkout-page-wrap {
    max-width: 1280px;
    margin: 0 auto;
}

.checkout-layout {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(340px, 0.85fr);
    gap: 28px;
    align-items: start;
}

.checkout-section {
    background: #fff;
    border-radius: 15px;
    padding: 26px;
    margin-bottom: 24px;
    border: 1px solid #e9ecef;
    box-shadow: 0 5px 15px rgba(0,0,0,0.07);
}

.checkout-section h3 {
    margin: 0 0 22px;
    padding-bottom: 13px;
    color: #3498db;
    font-size: 1.35rem;
    border-bottom: 2px solid #f1f3f5;
}

.checkout-section h3 i {
    width: 28px;
    margin-right: 8px;
    text-align: center;
}

.form-control,
.checkout-form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #dbe9f6;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
    background: #fff;
}

.form-control:focus,
.checkout-form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 600;
}

.user-info-display {
    background: #f8fbff;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid #3498db;
    border-radius: 10px;
}

.user-info-row {
    display: flex;
    margin-bottom: 10px;
}

.user-info-label {
    min-width: 120px;
    color: #555;
    font-weight: 600;
}

.service-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.service-option {
    position: relative;
}

.service-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.service-option label {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    height: 100%;
    padding: 25px 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.service-option label:hover {
    border-color: #3498db;
    transform: translateY(-3px);
}

.service-option input[type="radio"]:checked + label {
    border-color: #3498db;
    background: rgba(52,152,219,0.05);
    box-shadow: 0 5px 15px rgba(52,152,219,0.1);
}

.service-icon {
    font-size: 2.4rem;
    color: #3498db;
    margin-bottom: 14px;
}

.service-name {
    margin-bottom: 8px;
    color: #333;
    font-size: 1.05rem;
    font-weight: 700;
}

.service-desc {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
}

.service-fields {
    display: none;
    margin-top: 20px;
    padding: 20px;
    background: #f8fbff;
    border: 1px solid #dbe9f6;
    border-radius: 10px;
}

.service-fields.active {
    display: block;
}

.address-choice-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
    margin-bottom: 16px;
}

.address-choice-card {
    display: block;
    border: 2px solid #dbe9f6;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    background: #fff;
    transition: all 0.25s ease;
}

.address-choice-card:hover {
    border-color: #3498db;
    transform: translateY(-2px);
}

.address-choice-card input {
    display: none;
}

.address-choice-card:has(input:checked) {
    border-color: #3498db;
    background: #eef8ff;
    box-shadow: 0 5px 15px rgba(52,152,219,0.12);
}

.address-choice-title {
    font-weight: 700;
    color: #1e3554;
    margin-bottom: 8px;
}

.address-choice-text {
    color: #666;
    font-size: 0.92rem;
    line-height: 1.5;
}

.address-default-badge {
    display: inline-block;
    margin-left: 6px;
    padding: 2px 8px;
    border-radius: 999px;
    background: #3498db;
    color: #fff;
    font-size: 0.72rem;
}

.add-address-toggle {
    border: none;
    background: #eaf4fd;
    color: #3498db;
    padding: 10px 16px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    margin-bottom: 14px;
}

.inline-address-box {
    display: none;
    padding: 16px;
    background: #fff;
    border: 1px solid #dbe9f6;
    border-radius: 12px;
    margin-top: 12px;
}

.inline-address-box.active {
    display: block;
}

.voucher-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.voucher-choice {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    border: 2px solid #dbe9f6;
    border-radius: 12px;
    background: #f8fbff;
    cursor: pointer;
    transition: all 0.25s ease;
}

.voucher-choice:hover {
    border-color: #3498db;
    transform: translateY(-2px);
}

.voucher-choice input {
    width: 20px;
    height: 20px;
    accent-color: #3498db;
}

.voucher-choice-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: #eaf4fd;
    color: #3498db;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.voucher-choice-title {
    font-weight: 700;
    color: #1e3554;
    margin-bottom: 4px;
}

.voucher-choice-desc {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

.payment-option-box {
    display: block;
    margin-bottom: 12px;
}

.payment-option-box input[type="radio"] {
    margin-right: 8px;
}

.payment-option-label {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 20px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.25s;
}

.payment-option-label:hover {
    border-color: #3498db;
    background: #f8fbff;
}

.saved-card-panel {
    margin-bottom: 18px;
    padding: 18px;
    background: #f8fbff;
    border: 1px solid #d6eaf8;
    border-radius: 12px;
}

.saved-card-panel h4 {
    margin: 0 0 12px;
    color: #3498db;
}

.saved-card-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    margin-bottom: 10px;
    border: 1px solid #dbe9f6;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
}

.saved-card-option:hover {
    border-color: #3498db;
    background: #f4f9ff;
}

.saved-card-option input {
    width: 18px;
    height: 18px;
}

.saved-card-main {
    font-weight: 700;
    color: #1e3554;
}

.saved-card-sub {
    font-size: 0.9rem;
    color: #6c7a89;
    margin-top: 3px;
}

.save-card-box {
    margin-top: 15px;
    padding: 14px 16px;
    background: #fff;
    border: 1px dashed #9dccf0;
    border-radius: 10px;
}

.save-card-box label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #1e3554;
    cursor: pointer;
}

.order-summary-card {
    background: #f8fbff;
    border: 1px solid #d6eaf8;
    border-radius: 15px;
    padding: 28px;
    position: sticky;
    top: 20px;
    box-shadow: 0 5px 20px rgba(52,152,219,0.1);
}

.order-items-scroll {
    max-height: 260px;
    overflow-y: auto;
    margin-bottom: 24px;
    padding-right: 10px;
}

.order-item-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px dashed #d6eaf8;
}

.price-breakdown {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #3498db;
}

.price-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    color: #555;
}

.price-total {
    margin-top: 15px;
    padding-top: 15px;
    color: #3498db;
    font-size: 1.3rem;
    font-weight: 700;
    border-top: 2px solid #3498db;
}

.delivery-info {
    margin-top: 20px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

.terms-box {
    background: #f8fbff;
    padding: 18px;
    margin-bottom: 20px;
    border: 2px solid #3498db;
    border-radius: 10px;
}

.place-order-btn {
    display: block;
    width: 100%;
    padding: 18px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #3498db 0%, #5dade2 100%);
    color: #fff;
    font-size: 1.15rem;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.place-order-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(52,152,219,0.3);
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

@media (max-width: 992px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }

    .order-summary-card {
        position: static;
    }
}

@media (max-width: 768px) {
    .service-options,
    .voucher-options {
        grid-template-columns: 1fr;
    }

    .checkout-section {
        padding: 20px;
    }
}


.online-auth-box,
.online-otp-box {
    margin-top: 18px;
    padding: 18px;
    background: #f8fbff;
    border: 1px solid #dbe9f6;
    border-radius: 12px;
}

.online-auth-box h4 {
    margin: 0 0 15px;
    color: #1e3554;
    font-size: 1.05rem;
}

.form-hint {
    margin-top: 6px;
    color: #7b8a9a;
    font-size: 0.86rem;
}

.otp-generate-btn {
    border: none;
    background: linear-gradient(135deg, #3498db 0%, #5dade2 100%);
    color: white;
    padding: 13px 18px;
    border-radius: 9px;
    font-weight: 700;
    cursor: pointer;
    min-height: 48px;
}

.otp-generate-btn:hover {
    background: #2980b9;
}

.otp-notice {
    margin-top: 12px;
    padding: 12px 14px;
    background: #eaf7ee;
    color: #1e8449;
    border-left: 4px solid #28a745;
    border-radius: 8px;
    font-weight: 600;
}

</style>

<div class="checkout-page-wrap">

    <h1 style="margin-bottom: 25px;">
        Checkout
    </h1>

    <?php if (!empty($error)): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="checkout.php" id="checkoutForm">
        <div class="checkout-layout">

            <div>
                <?php if ($logged_in_user_id > 0 && !empty($user_info)): ?>
                    <div class="checkout-section">
                        <h3>
                            <i class="fas fa-user-check"></i>
                            Your Information
                        </h3>

                        <div class="user-info-display">
                            <div class="user-info-row">
                                <div class="user-info-label">Name:</div>
                                <div><?php echo htmlspecialchars($user_info['username']); ?></div>
                            </div>

                            <div class="user-info-row">
                                <div class="user-info-label">Email:</div>
                                <div><?php echo htmlspecialchars($user_info['email']); ?></div>
                            </div>

                            <div class="user-info-row">
                                <div class="user-info-label">Phone:</div>
                                <div>
                                    <input type="tel"
                                           name="phone"
                                           class="form-control"
                                           placeholder="Your phone number (optional)"
                                           style="max-width: 260px;">
                                </div>
                            </div>
                        </div>

                        <div style="padding:12px 15px;border-radius:8px;background:rgba(52,152,219,0.1);color:#3498db;">
                            <i class="fas fa-info-circle"></i>
                            Your account information will be used for this order.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="checkout-section">
                        <h3>
                            <i class="fas fa-user"></i>
                            Customer Information
                        </h3>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="Your full name">
                            </div>

                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" required pattern="[0-9]{10,11}" placeholder="e.g. 0123456789">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" class="form-control" required placeholder="your.email@example.com">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="checkout-section">
                    <h3>
                        <i class="fas fa-truck"></i>
                        Delivery Method
                    </h3>

                    <div class="service-options">
                        <div class="service-option">
                            <input type="radio" name="service_type" id="service_delivery" value="delivery" checked>
                            <label for="service_delivery">
                                <i class="fas fa-shipping-fast service-icon"></i>
                                <span class="service-name">Home Delivery</span>
                                <span class="service-desc">Deliver stationery items to your address</span>
                            </label>
                        </div>

                        <div class="service-option">
                            <input type="radio" name="service_type" id="service_pickup" value="pickup">
                            <label for="service_pickup">
                                <i class="fas fa-store service-icon"></i>
                                <span class="service-name">Self Pickup</span>
                                <span class="service-desc">Collect your order at our store</span>
                            </label>
                        </div>
                    </div>

                    <div id="deliveryFields" class="service-fields active">
                        <?php if ($logged_in_user_id > 0): ?>

                            <?php if (!$address_table_exists): ?>
                                <div style="padding:14px 16px;border-radius:10px;background:#fff4e5;color:#8a5a00;margin-bottom:15px;">
                                    <i class="fas fa-info-circle"></i>
                                    Please import <strong>create_user_addresses_table.sql</strong> first to save and manage addresses.
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($saved_addresses)): ?>
                                <div class="form-group">
                                    <label>Select Delivery Address *</label>

                                    <div class="address-choice-grid">
                                        <?php foreach ($saved_addresses as $address_item): ?>
                                            <label class="address-choice-card">
                                                <input type="radio"
                                                       name="selected_address_id"
                                                       value="<?php echo (int)$address_item['id']; ?>"
                                                       <?php echo $address_item['is_default'] ? 'checked' : ''; ?>>

                                                <div class="address-choice-content">
                                                    <div class="address-choice-title">
                                                        <?php echo htmlspecialchars($address_item['label'] ?: 'Address'); ?>
                                                        <?php if ($address_item['is_default']): ?>
                                                            <span class="address-default-badge">Default</span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="address-choice-text">
                                                        <?php echo htmlspecialchars($address_item['receiver_name']); ?>
                                                        <?php if (!empty($address_item['phone'])): ?>
                                                            · <?php echo htmlspecialchars($address_item['phone']); ?>
                                                        <?php endif; ?>
                                                        <br>
                                                        <?php echo htmlspecialchars($address_item['address_line']); ?>
                                                        <?php if (!empty($address_item['postcode']) || !empty($address_item['city'])): ?>
                                                            <br>
                                                            <?php echo htmlspecialchars(trim($address_item['postcode'] . ' ' . $address_item['city'])); ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($address_item['state'])): ?>
                                                            , <?php echo htmlspecialchars($address_item['state']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                    <button type="button" class="add-address-toggle" id="showAddAddressBtn">
                                        <i class="fas fa-plus"></i>
                                        Add New Address
                                    </button>

                                    <a href="manage-addresses.php" style="margin-left:10px;color:#3498db;font-weight:700;text-decoration:none;">
                                        Manage Address
                                    </a>
                                </div>

                                <div class="inline-address-box" id="inlineNewAddressBox">
                                    <label class="address-choice-card" style="margin-bottom:15px;">
                                        <input type="radio" name="selected_address_id" value="0" id="newAddressRadio">
                                        <div class="address-choice-content">
                                            <div class="address-choice-title">Use New Address</div>
                                            <div class="address-choice-text">Fill in a new delivery address for this order.</div>
                                        </div>
                                    </label>

                                    <div class="form-group" style="margin-bottom:12px;">
                                        <label>Address Label</label>
                                        <input type="text" name="new_address_label" class="form-control" placeholder="Home, Hostel, Office">
                                    </div>

                                    <div class="form-group">
                                        <label>New Delivery Address *</label>
                                        <textarea name="new_address" class="form-control new-address-field" rows="3" placeholder="House number, street, city, postcode"></textarea>
                                    </div>

                                    <label style="display:flex;gap:10px;align-items:center;margin-top:12px;cursor:pointer;">
                                        <input type="checkbox" name="save_new_address" value="1" checked>
                                        <span>Save this address to my profile</span>
                                    </label>
                                </div>
                            <?php else: ?>
                                <div style="padding:14px 16px;border-radius:10px;background:#eaf4fd;color:#21618c;margin-bottom:15px;">
                                    <i class="fas fa-map-marker-alt"></i>
                                    You do not have a saved address yet. Add one below for this order.
                                </div>

                                <input type="hidden" name="selected_address_id" value="0">

                                <div class="form-group" style="margin-bottom:12px;">
                                    <label>Address Label</label>
                                    <input type="text" name="new_address_label" class="form-control" placeholder="Home, Hostel, Office">
                                </div>

                                <div class="form-group">
                                    <label>Delivery Address *</label>
                                    <textarea name="new_address" class="form-control new-address-field" rows="3" required placeholder="House number, street, city, postcode"></textarea>
                                </div>

                                <label style="display:flex;gap:10px;align-items:center;margin-top:12px;cursor:pointer;">
                                    <input type="checkbox" name="save_new_address" value="1" checked>
                                    <span>Save this address to my profile</span>
                                </label>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="form-group">
                                <label>Delivery Address *</label>
                                <textarea name="address" class="form-control" rows="3" required placeholder="House number, street, city, postcode"></textarea>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="pickupFields" class="service-fields">
                        <div class="form-group">
                            <label>Pickup Notes (Optional)</label>
                            <textarea name="pickup_notes" class="form-control" rows="2" placeholder="Any special pickup instructions?"></textarea>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:20px;">
                        <label>Special Instructions (Optional)</label>
                        <textarea name="special_instructions" class="form-control" rows="2" placeholder="Special requests or delivery notes"></textarea>
                    </div>
                </div>

                <div class="checkout-section">
                    <h3>
                        <i class="fas fa-ticket-alt"></i>
                        Voucher
                    </h3>

                    <?php if ($has_free_shipping_voucher || $has_rm2_discount_voucher): ?>
                        <div class="voucher-options">
                            <?php if ($has_free_shipping_voucher): ?>
                                <label class="voucher-choice">
                                    <input type="checkbox" name="use_free_shipping_voucher" id="useFreeShippingVoucher" value="1">
                                    <span class="voucher-choice-icon"><i class="fas fa-truck"></i></span>
                                    <span>
                                        <div class="voucher-choice-title">Free Shipping Voucher</div>
                                        <div class="voucher-choice-desc">Use now to waive delivery fee.</div>
                                    </span>
                                </label>
                            <?php endif; ?>

                            <?php if ($has_rm2_discount_voucher): ?>
                                <label class="voucher-choice">
                                    <input type="checkbox" name="use_rm2_discount_voucher" id="useRm2Voucher" value="1">
                                    <span class="voucher-choice-icon"><i class="fas fa-tags"></i></span>
                                    <span>
                                        <div class="voucher-choice-title">RM2 Discount Voucher</div>
                                        <div class="voucher-choice-desc">Use now to deduct RM2 from subtotal.</div>
                                    </span>
                                </label>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="background:#f8fbff;border:1px dashed #b9dcf5;border-radius:12px;padding:18px;color:#666;">
                            No voucher available right now.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="checkout-section">
                    <h3>
                        <i class="fas fa-credit-card"></i>
                        Payment Method
                    </h3>

                    <div>
                        <label class="payment-option-box">
                            <input type="radio" name="payment_method" id="payment_cash" value="cash" checked>
                            <span class="payment-option-label">
                                <i class="fas fa-money-bill-wave" style="color:#3498db;font-size:1.5rem;"></i>
                                <span>
                                    <strong>Cash Payment</strong><br>
                                    <small style="color:#666;">Pay when you receive your stationery order</small>
                                </span>
                            </span>
                        </label>

                        <label class="payment-option-box">
                            <input type="radio" name="payment_method" id="payment_card" value="card">
                            <span class="payment-option-label">
                                <i class="fas fa-credit-card" style="color:#3498db;font-size:1.5rem;"></i>
                                <span>
                                    <strong>Credit / Debit Card</strong><br>
                                    <small style="color:#666;">Pay securely using your bank card</small>
                                </span>
                            </span>
                        </label>

                        <label class="payment-option-box">
                            <input type="radio" name="payment_method" id="payment_online" value="online">
                            <span class="payment-option-label">
                                <i class="fas fa-wallet" style="color:#3498db;font-size:1.5rem;"></i>
                                <span>
                                    <strong>Online Payment</strong><br>
                                    <small style="color:#666;">Touch 'n Go, FPX, GrabPay, DuitNow</small>
                                </span>
                            </span>
                        </label>
                    </div>

                    <div id="cardFields" class="service-fields" style="margin-top:20px;">

                        <?php if ($logged_in_user_id > 0 && !empty($saved_cards)): ?>
                            <div class="saved-card-panel" id="savedCardPanel">
                                <h4>
                                    <i class="fas fa-credit-card"></i>
                                    Saved Cards
                                </h4>

                                <label class="saved-card-option">
                                    <input type="radio" name="saved_card_id" value="0" checked>
                                    <span>
                                        <div class="saved-card-main">Use a new card</div>
                                        <div class="saved-card-sub">Enter new card information below</div>
                                    </span>
                                </label>

                                <?php foreach ($saved_cards as $saved_card): ?>
                                    <label class="saved-card-option">
                                        <input type="radio" name="saved_card_id" value="<?php echo (int)$saved_card['id']; ?>">
                                        <span>
                                            <div class="saved-card-main">
                                                <?php echo htmlspecialchars($saved_card['bank_name']); ?> · **** <?php echo htmlspecialchars($saved_card['last4']); ?>
                                            </div>
                                            <div class="saved-card-sub">
                                                <?php echo htmlspecialchars($saved_card['cardholder_name']); ?> · Exp <?php echo htmlspecialchars($saved_card['expiry_date']); ?>
                                            </div>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($logged_in_user_id > 0 && !$card_table_exists): ?>
                            <div style="padding:14px 16px;border-radius:10px;background:#fff4e5;color:#8a5a00;margin-bottom:15px;">
                                <i class="fas fa-info-circle"></i>
                                Please import <strong>create_user_saved_cards_table.sql</strong> first to save cards.
                            </div>
                        <?php endif; ?>

                        <div id="newCardFields">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px;">
                                <div class="form-group">
                                    <label>Card Bank *</label>
                                    <select name="card_bank" class="form-control card-required">
                                        <option value="">Select bank</option>
                                        <option value="Maybank">Maybank</option>
                                        <option value="CIMB Bank">CIMB Bank</option>
                                        <option value="Public Bank">Public Bank</option>
                                        <option value="Hong Leong Bank">Hong Leong Bank</option>
                                        <option value="RHB Bank">RHB Bank</option>
                                        <option value="AmBank">AmBank</option>
                                        <option value="Bank Islam">Bank Islam</option>
                                        <option value="UOB Bank">UOB Bank</option>
                                        <option value="OCBC Bank">OCBC Bank</option>
                                        <option value="HSBC Bank">HSBC Bank</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Cardholder Name *</label>
                                    <input type="text" name="card_name" class="form-control card-required" placeholder="Name on card">
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px;">
                                <div class="form-group">
                                    <label>Card Number *</label>
                                    <input type="text" name="card_number" id="cardNumber" class="form-control card-required" maxlength="19" placeholder="1234 5678 9012 3456">
                                    <small style="color:#6c7a89;">Must be exactly 16 digits.</small>
                                </div>

                                <div class="form-group">
                                    <label>Expiry Date *</label>
                                    <input type="text" name="expiry_date" id="expiryDate" class="form-control card-required" maxlength="5" placeholder="MM/YY">
                                    <small style="color:#6c7a89;">Use MM/YY. Date must not be expired.</small>
                                    <div id="expiryError" class="field-error"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="max-width: 360px;">
                            <label>CVV *</label>
                            <input type="password" name="cvv" id="cvv" class="form-control" maxlength="4" placeholder="123">
                            <small style="color:#6c7a89;">3 or 4 digits. CVV is never saved.</small>
                        </div>

                        <?php if ($logged_in_user_id > 0 && $card_table_exists): ?>
                            <div class="save-card-box" id="saveCardBox">
                                <label>
                                    <input type="checkbox" name="save_card_to_profile" value="1">
                                    <span>Save this card for next time</span>
                                </label>
                                <div style="margin-top:8px;color:#666;font-size:0.9rem;">
                                    We only save bank, cardholder name, expiry date and last 4 digits. CVV is not saved.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="onlineFields" class="service-fields" style="margin-top:20px;">
                        <div class="form-group">
                            <label>Choose eWallet / Online Bank *</label>
                            <select name="online_bank" id="onlineBankSelect" class="form-control">
                                <option value="">Select payment option</option>
                                <option value="Touch n Go eWallet">Touch 'n Go eWallet</option>
                                <option value="GrabPay">GrabPay</option>
                                <option value="DuitNow">DuitNow</option>
                                <option value="FPX Maybank2u">FPX - Maybank2u</option>
                                <option value="FPX CIMB Clicks">FPX - CIMB Clicks</option>
                                <option value="FPX Public Bank">FPX - Public Bank</option>
                                <option value="FPX RHB Now">FPX - RHB Now</option>
                                <option value="FPX Hong Leong Connect">FPX - Hong Leong Connect</option>
                            </select>
                        </div>

                        <div class="gateway-info-box">
                            <i class="fas fa-shield-alt"></i>
                            <span>After clicking <strong>Place Order & Pay</strong>, eWallet options will open a phone number payment confirmation page. FPX banks will open a secure online banking login page.</span>
                        </div>
                    </div>
                </div>

                <div class="terms-box">
                    <label style="display:flex;align-items:flex-start;gap:15px;cursor:pointer;">
                        <input type="checkbox" required style="margin-top:3px;width:20px;height:20px;">
                        <span>
                            I agree to the
                            <a href="terms.php" style="color:#3498db;font-weight:600;">Terms and Conditions</a>
                            and the
                            <a href="privacy.php" style="color:#3498db;font-weight:600;">Privacy Policy</a>.
                        </span>
                    </label>
                </div>

                <button type="submit" class="place-order-btn">
                    <i class="fas fa-lock"></i>
                    <span id="placeOrderText">
                        Place Order & Pay RM<span id="totalAmount"><?php echo number_format($preview_total, 2); ?></span>
                    </span>
                </button>

                <p style="margin-top:15px;text-align:center;color:#666;font-size:0.9rem;">
                    <i class="fas fa-shield-alt"></i>
                    Your payment is secure and encrypted
                </p>
            </div>

            <div>
                <div class="order-summary-card">
                    <h3 style="margin:0 0 25px;color:#3498db;">
                        Order Summary
                    </h3>

                    <div class="order-items-scroll">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="order-item-row">
                                <div>
                                    <div style="font-weight:700;margin-bottom:5px;">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </div>
                                    <div style="font-size:0.9rem;color:#666;">
                                        Qty: <?php echo (int)$item['quantity']; ?> × RM<?php echo number_format($item['price'], 2); ?>
                                    </div>
                                </div>

                                <div style="font-weight:700;color:#333;">
                                    RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="price-breakdown">
                        <div class="price-row">
                            <span>Subtotal</span>
                            <span>RM<?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <div class="price-row" id="deliveryFeeRow">
                            <span>Delivery Fee</span>
                            <span id="deliveryFee">RM<?php echo number_format($default_delivery_fee, 2); ?></span>
                        </div>

                        <div class="price-row" id="freeShippingVoucherRow" style="display:none;color:#3498db;">
                            <span><i class="fas fa-check-circle"></i> Free Shipping Voucher</span>
                            <span id="freeShippingVoucherAmount">-RM10.00</span>
                        </div>

                        <div class="price-row" id="rm2VoucherRow" style="display:none;color:#1e8449;">
                            <span><i class="fas fa-check-circle"></i> RM2 Discount Voucher</span>
                            <span>-RM2.00</span>
                        </div>

                        <div class="price-row price-total">
                            <span>Total</span>
                            <span id="orderTotal">RM<?php echo number_format($preview_total, 2); ?></span>
                        </div>
                    </div>

                    <div class="delivery-info">
                        <h4 style="margin:0 0 10px;">
                            <i class="fas fa-clock"></i>
                            Estimated Delivery Time
                        </h4>
                        <div id="timeEstimate">
                            <p style="margin:0;color:#666;">2 - 5 working days after order confirmation</p>
                        </div>
                    </div>

                    <div style="margin-top:20px;padding-top:15px;border-top:1px solid #dbe9f6;">
                        <p style="margin:0;font-size:0.9rem;color:#666;line-height:1.5;">
                            <i class="fas fa-info-circle" style="color:#3498db;"></i>
                            Orders placed on weekends or public holidays will be processed on the next working day.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const subtotal = <?php echo json_encode((float)$subtotal); ?>;
    const hasFreeShippingVoucher = <?php echo $has_free_shipping_voucher ? 'true' : 'false'; ?>;
    const hasRm2Voucher = <?php echo $has_rm2_discount_voucher ? 'true' : 'false'; ?>;

    const serviceRadios = document.querySelectorAll('input[name="service_type"]');
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const deliveryFields = document.getElementById('deliveryFields');
    const pickupFields = document.getElementById('pickupFields');
    const cardFields = document.getElementById('cardFields');
    const onlineFields = document.getElementById('onlineFields');
    const deliveryFee = document.getElementById('deliveryFee');
    const deliveryFeeRow = document.getElementById('deliveryFeeRow');
    const orderTotal = document.getElementById('orderTotal');
    const totalAmount = document.getElementById('totalAmount');
    const timeEstimate = document.getElementById('timeEstimate');
    const freeShippingVoucherRow = document.getElementById('freeShippingVoucherRow');
    const rm2VoucherRow = document.getElementById('rm2VoucherRow');
    const freeVoucherCheckbox = document.getElementById('useFreeShippingVoucher');
    const rm2VoucherCheckbox = document.getElementById('useRm2Voucher');
    const showAddAddressBtn = document.getElementById('showAddAddressBtn');
    const inlineNewAddressBox = document.getElementById('inlineNewAddressBox');
    const newAddressRadio = document.getElementById('newAddressRadio');
    const cardNumber = document.getElementById('cardNumber');
    const expiryDate = document.getElementById('expiryDate');
    const cvv = document.getElementById('cvv');
    const newCardFields = document.getElementById('newCardFields');
    const saveCardBox = document.getElementById('saveCardBox');
    const onlineBankSelect = document.getElementById('onlineBankSelect');
    const onlineBankLoginFields = document.getElementById('onlineBankLoginFields');
    const onlineWalletLoginFields = document.getElementById('onlineWalletLoginFields');
    const onlineOtpBox = document.getElementById('onlineOtpBox');
    const onlineAccount = document.getElementById('onlineAccount');
    const onlinePassword = document.getElementById('onlinePassword');
    const onlinePhone = document.getElementById('onlinePhone');
    const onlinePin = document.getElementById('onlinePin');
    const onlineOtp = document.getElementById('onlineOtp');
    const onlineExpectedOtp = document.getElementById('onlineExpectedOtp');
    const generateOnlineOtpBtn = document.getElementById('generateOnlineOtpBtn');
    const onlineOtpNotice = document.getElementById('onlineOtpNotice');

    function getService() {
        const selected = document.querySelector('input[name="service_type"]:checked');
        return selected ? selected.value : 'delivery';
    }

    function getPayment() {
        const selected = document.querySelector('input[name="payment_method"]:checked');
        return selected ? selected.value : 'cash';
    }

    function isUsingSavedCard() {
        const selectedSaved = document.querySelector('input[name="saved_card_id"]:checked');
        return selectedSaved && selectedSaved.value !== '0';
    }

    function updateTotals() {
        const service = getService();
        const useFreeShipping = freeVoucherCheckbox && freeVoucherCheckbox.checked;
        const useRm2 = rm2VoucherCheckbox && rm2VoucherCheckbox.checked;

        let fee = 0;
        let freeShippingDiscount = 0;

        if (service === 'pickup') {
            fee = 0;
            deliveryFeeRow.querySelector('span:first-child').textContent = 'Pickup';
            deliveryFee.textContent = 'FREE';
            if (freeShippingVoucherRow) freeShippingVoucherRow.style.display = 'none';
        } else {
            fee = subtotal >= 100 ? 0 : 10;
            deliveryFeeRow.querySelector('span:first-child').textContent = 'Delivery Fee';

            if (hasFreeShippingVoucher && useFreeShipping && fee > 0) {
                freeShippingDiscount = fee;
                fee = 0;
                if (freeShippingVoucherRow) {
                    freeShippingVoucherRow.style.display = 'flex';
                    document.getElementById('freeShippingVoucherAmount').textContent = '-RM' + freeShippingDiscount.toFixed(2);
                }
            } else {
                if (freeShippingVoucherRow) freeShippingVoucherRow.style.display = 'none';
            }

            deliveryFee.textContent = fee === 0 ? 'FREE' : 'RM' + fee.toFixed(2);
        }

        const rm2Discount = (hasRm2Voucher && useRm2) ? Math.min(2, subtotal) : 0;

        if (rm2VoucherRow) {
            rm2VoucherRow.style.display = rm2Discount > 0 ? 'flex' : 'none';
        }

        const total = Math.max(0, subtotal + fee - rm2Discount);
        orderTotal.textContent = 'RM' + total.toFixed(2);
        totalAmount.textContent = total.toFixed(2);
    }

    function updateServiceFields() {
        const service = getService();

        deliveryFields.classList.remove('active');
        pickupFields.classList.remove('active');

        const guestAddress = document.querySelector('textarea[name="address"]');
        const newAddressFields = document.querySelectorAll('.new-address-field');

        if (service === 'delivery') {
            deliveryFields.classList.add('active');

            if (guestAddress) guestAddress.required = true;
            newAddressFields.forEach(field => {
                if (field.offsetParent !== null) {
                    field.required = true;
                }
            });

            timeEstimate.innerHTML = '<p style="margin:0;color:#666;">2 - 5 working days after order confirmation</p>';
        } else {
            pickupFields.classList.add('active');

            if (guestAddress) guestAddress.required = false;
            newAddressFields.forEach(field => field.required = false);

            timeEstimate.innerHTML = '<p style="margin:0;color:#666;">Ready for pickup within 1 - 2 working days</p>';
        }

        updateTotals();
    }

    function updatePaymentFields() {
        const payment = getPayment();

        cardFields.classList.remove('active');
        onlineFields.classList.remove('active');

        if (payment === 'card') {
            cardFields.classList.add('active');
        }

        if (payment === 'online') {
            onlineFields.classList.add('active');
            updateOnlineAuthFields();
        } else {
            clearOnlineAuthRequired();
        }
    }

    function updateSavedCardFields() {
        const usingSaved = isUsingSavedCard();

        if (newCardFields) {
            newCardFields.style.display = usingSaved ? 'none' : 'block';
        }

        if (saveCardBox) {
            saveCardBox.style.display = usingSaved ? 'none' : 'block';
        }
    }


    function selectedOnlineIsBank() {
        return onlineBankSelect && onlineBankSelect.value.indexOf('FPX') === 0;
    }

    function selectedOnlineIsWallet() {
        return onlineBankSelect && onlineBankSelect.value !== '' && !selectedOnlineIsBank();
    }

    function clearOnlineAuthRequired() {
        [onlineAccount, onlinePassword, onlinePhone, onlinePin, onlineOtp].forEach(field => {
            if (field) field.required = false;
        });
    }

    function resetOnlineOtp() {
        if (onlineExpectedOtp) onlineExpectedOtp.value = '';
        if (onlineOtp) onlineOtp.value = '';
        if (onlineOtpNotice) {
            onlineOtpNotice.style.display = 'none';
            onlineOtpNotice.textContent = '';
        }
    }

    function updateOnlineAuthFields() {
        const payment = getPayment();
        const isBank = selectedOnlineIsBank();
        const isWallet = selectedOnlineIsWallet();

        if (onlineBankLoginFields) onlineBankLoginFields.style.display = (payment === 'online' && isBank) ? 'block' : 'none';
        if (onlineWalletLoginFields) onlineWalletLoginFields.style.display = (payment === 'online' && isWallet) ? 'block' : 'none';
        if (onlineOtpBox) onlineOtpBox.style.display = (payment === 'online' && (isBank || isWallet)) ? 'block' : 'none';

        clearOnlineAuthRequired();

        if (payment === 'online' && isBank) {
            if (onlineAccount) onlineAccount.required = true;
            if (onlinePassword) onlinePassword.required = true;
            if (onlineOtp) onlineOtp.required = true;
        }

        if (payment === 'online' && isWallet) {
            if (onlinePhone) onlinePhone.required = true;
            if (onlinePin) onlinePin.required = true;
            if (onlineOtp) onlineOtp.required = true;
        }
    }

    if (showAddAddressBtn && inlineNewAddressBox) {
        showAddAddressBtn.addEventListener('click', function () {
            inlineNewAddressBox.classList.toggle('active');

            if (inlineNewAddressBox.classList.contains('active') && newAddressRadio) {
                newAddressRadio.checked = true;
            }

            updateServiceFields();
        });
    }

    serviceRadios.forEach(radio => radio.addEventListener('change', updateServiceFields));
    paymentRadios.forEach(radio => radio.addEventListener('change', updatePaymentFields));

    document.querySelectorAll('input[name="saved_card_id"]').forEach(radio => {
        radio.addEventListener('change', updateSavedCardFields);
    });

    if (freeVoucherCheckbox) freeVoucherCheckbox.addEventListener('change', updateTotals);
    if (rm2VoucherCheckbox) rm2VoucherCheckbox.addEventListener('change', updateTotals);

    if (onlineBankSelect) {
        onlineBankSelect.addEventListener('change', function () {
            resetOnlineOtp();
            updateOnlineAuthFields();
        });
    }

    if (onlineAccount) {
        onlineAccount.addEventListener('input', function () {
            let digits = this.value.replace(/\D/g, '').slice(0, 16);
            this.value = digits.replace(/(.{4})/g, '$1 ').trim();
            resetOnlineOtp();
        });
    }

    if (onlinePhone) {
        onlinePhone.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
            resetOnlineOtp();
        });
    }

    if (onlinePin) {
        onlinePin.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            resetOnlineOtp();
        });
    }

    if (onlineOtp) {
        onlineOtp.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    }

    if (generateOnlineOtpBtn) {
        generateOnlineOtpBtn.addEventListener('click', function () {
            const payment = getPayment();
            const isBank = selectedOnlineIsBank();
            const isWallet = selectedOnlineIsWallet();

            if (payment !== 'online' || (!isBank && !isWallet)) {
                alert('Please select an online payment method first.');
                return;
            }

            if (isBank) {
                const accountDigits = onlineAccount ? onlineAccount.value.replace(/\D/g, '') : '';
                const passwordValue = onlinePassword ? onlinePassword.value.trim() : '';

                if (accountDigits.length < 8 || accountDigits.length > 16) {
                    alert('Bank card or account number must be 8 to 16 digits.');
                    return;
                }

                if (passwordValue.length < 6) {
                    alert('Online banking password must be at least 6 characters.');
                    return;
                }
            }

            if (isWallet) {
                const phoneDigits = onlinePhone ? onlinePhone.value.replace(/\D/g, '') : '';
                const pinValue = onlinePin ? onlinePin.value.trim() : '';

                if (!/^01\d{8,9}$/.test(phoneDigits)) {
                    alert('Please enter a valid Malaysian phone number, for example 0123456789.');
                    return;
                }

                if (!/^\d{6}$/.test(pinValue)) {
                    alert('PIN must be 6 digits.');
                    return;
                }
            }

            const otp = String(Math.floor(100000 + Math.random() * 900000));
            if (onlineExpectedOtp) onlineExpectedOtp.value = otp;
            if (onlineOtpNotice) {
                onlineOtpNotice.style.display = 'block';
                onlineOtpNotice.innerHTML = 'Demo OTP generated: <strong>' + otp + '</strong>. Enter this OTP to continue.';
            }
        });
    }

    if (cardNumber) {
        cardNumber.addEventListener('input', function () {
            let digits = this.value.replace(/\D/g, '').slice(0, 16);
            this.value = digits.replace(/(.{4})/g, '$1 ').trim();
        });
    }

    if (expiryDate) {
        expiryDate.addEventListener('input', function () {
            clearInlineError(expiryDate);
            let digits = this.value.replace(/\D/g, '').slice(0, 4);

            if (digits.length >= 3) {
                this.value = digits.slice(0, 2) + '/' + digits.slice(2);
            } else {
                this.value = digits;
            }
        });
    }

    if (cvv) {
        cvv.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
    }

    function expiryIsFuture(expiryValue) {
        if (!/^(0[1-9]|1[0-2])\/(\d{2})$/.test(expiryValue)) {
            return false;
        }

        const parts = expiryValue.split('/');
        const month = parseInt(parts[0], 10);
        const year = 2000 + parseInt(parts[1], 10);

        const expiryDateObj = new Date(year, month - 1, 1);
        const now = new Date();
        const currentMonth = new Date(now.getFullYear(), now.getMonth(), 1);

        return expiryDateObj >= currentMonth;
    }

    function showInlineError(input, message) {
        if (!input) return;
        let errorBox = null;
        if (input.id === 'expiryDate') {
            errorBox = document.getElementById('expiryError');
        }
        if (!errorBox) {
            errorBox = input.parentElement.querySelector('.field-error');
            if (!errorBox) {
                errorBox = document.createElement('div');
                errorBox.className = 'field-error';
                input.parentElement.appendChild(errorBox);
            }
        }
        input.classList.add('error');
        errorBox.textContent = message;
        errorBox.style.display = 'block';
        input.focus();
    }

    function clearInlineError(input) {
        if (!input) return;
        input.classList.remove('error');
        let errorBox = null;
        if (input.id === 'expiryDate') errorBox = document.getElementById('expiryError');
        if (!errorBox) errorBox = input.parentElement.querySelector('.field-error');
        if (errorBox) {
            errorBox.textContent = '';
            errorBox.style.display = 'none';
        }
    }

    document.getElementById('checkoutForm').addEventListener('submit', function (e) {
        const payment = getPayment();

        if (payment === 'card') {
            const usingSaved = isUsingSavedCard();
            const cvvValue = cvv ? cvv.value.trim() : '';

            if (!/^\d{3,4}$/.test(cvvValue)) {
                e.preventDefault();
                alert('CVV must be 3 or 4 digits.');
                return;
            }

            if (!usingSaved) {
                const bank = document.querySelector('select[name="card_bank"]').value.trim();
                const holder = document.querySelector('input[name="card_name"]').value.trim();
                const number = cardNumber.value.replace(/\D/g, '');
                const expiry = expiryDate.value.trim();

                if (!bank) {
                    e.preventDefault();
                    alert('Please select your card bank.');
                    return;
                }

                if (!holder) {
                    e.preventDefault();
                    alert('Please enter the cardholder name.');
                    return;
                }

                if (number.length !== 16) {
                    e.preventDefault();
                    alert('Card number must be exactly 16 digits.');
                    return;
                }

                if (!expiryIsFuture(expiry)) {
                    e.preventDefault();
                    showInlineError(expiryDate, 'Expiry date must be MM/YY and cannot be earlier than the current month.');
                    return;
                }
            }
        }

        if (payment === 'online') {
            const onlineBank = document.querySelector('select[name="online_bank"]').value.trim();

            if (!onlineBank) {
                e.preventDefault();
                showInlineError(document.querySelector('select[name="online_bank"]'), 'Please select your online bank or eWallet.');
                return;
            }
        }

    });

    updateServiceFields();
    updatePaymentFields();
    updateSavedCardFields();
    updateOnlineAuthFields();
    updateTotals();
});
</script>

<?php include 'includes/footer.php'; ?>
