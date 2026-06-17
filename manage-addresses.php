<?php

require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header('Location: login.php?redirect=manage-addresses');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$page_title = 'Manage Address';
$show_sidebar = true;


$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'user_addresses'");
$table_exists = $table_check && mysqli_num_rows($table_check) > 0;

if (!$table_exists) {
    include 'includes/header.php';
    ?>
    <div style="background:#fff4e5;color:#8a5a00;padding:20px;border-radius:12px;border-left:5px solid #f39c12;">
        <h2 style="margin-top:0;">Address table not found</h2>
        <p>Please import <strong>create_user_addresses_table.sql</strong> into phpMyAdmin first.</p>
        <a href="profile.php" class="btn" style="background:#3498db;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;">Back to Profile</a>
    </div>
    <?php
    include 'includes/footer.php';
    exit();
}

$message = '';
$error = '';

function clean_input($conn, $value) {
    return mysqli_real_escape_string($conn, trim($value ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $address_id = (int)($_POST['address_id'] ?? 0);
        $label = clean_input($conn, $_POST['label'] ?? 'Address');
        $receiver_name = clean_input($conn, $_POST['receiver_name'] ?? '');
        $phone = clean_input($conn, $_POST['phone'] ?? '');
        $address_line = clean_input($conn, $_POST['address_line'] ?? '');
        $city = clean_input($conn, $_POST['city'] ?? '');
        $postcode = clean_input($conn, $_POST['postcode'] ?? '');
        $state = clean_input($conn, $_POST['state'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        if ($address_line === '') {
            $error = 'Address is required.';
        } else {
            if ($is_default) {
                mysqli_query($conn, "UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
            }

            if ($address_id > 0) {
                $sql = "
                    UPDATE user_addresses
                    SET
                        label = '$label',
                        receiver_name = '$receiver_name',
                        phone = '$phone',
                        address_line = '$address_line',
                        city = '$city',
                        postcode = '$postcode',
                        state = '$state',
                        is_default = $is_default
                    WHERE id = $address_id
                    AND user_id = $user_id
                ";
            } else {
                $sql = "
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
                        $user_id,
                        '$label',
                        '$receiver_name',
                        '$phone',
                        '$address_line',
                        '$city',
                        '$postcode',
                        '$state',
                        $is_default,
                        NOW()
                    )
                ";
            }

            if (mysqli_query($conn, $sql)) {
                $message = 'Address saved successfully.';
            } else {
                $error = 'Unable to save address: ' . mysqli_error($conn);
            }
        }
    }

    if ($action === 'delete') {
        $address_id = (int)($_POST['address_id'] ?? 0);
        mysqli_query($conn, "DELETE FROM user_addresses WHERE id = $address_id AND user_id = $user_id");
        $message = 'Address deleted.';
    }

    if ($action === 'default') {
        $address_id = (int)($_POST['address_id'] ?? 0);
        mysqli_query($conn, "UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
        mysqli_query($conn, "UPDATE user_addresses SET is_default = 1 WHERE id = $address_id AND user_id = $user_id");
        $message = 'Default address updated.';
    }
}

$edit_address = null;
$edit_id = (int)($_GET['edit'] ?? 0);

if ($edit_id > 0) {
    $edit_result = mysqli_query($conn, "SELECT * FROM user_addresses WHERE id = $edit_id AND user_id = $user_id LIMIT 1");
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_address = mysqli_fetch_assoc($edit_result);
    }
}

$addresses = [];
$address_result = mysqli_query($conn, "SELECT * FROM user_addresses WHERE user_id = $user_id ORDER BY is_default DESC, id DESC");

if ($address_result) {
    while ($row = mysqli_fetch_assoc($address_result)) {
        $addresses[] = $row;
    }
}

include 'includes/header.php';
?>

<style>
.address-page-wrap {
    max-width: 1150px;
    margin: 0 auto;
}

.address-header {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
    border-left: 5px solid #3498db;
    padding: 28px;
    border-radius: 14px;
    box-shadow: 0 5px 18px rgba(0,0,0,0.06);
    margin-bottom: 25px;
}

.address-header h1 {
    margin: 0 0 8px;
    color: #1e3554;
}

.address-header p {
    margin: 0;
    color: #666;
}

.address-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.address-card, .address-form-card {
    background: #fff;
    border: 1px solid #dbe9f6;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 5px 18px rgba(52,152,219,0.08);
}

.address-card {
    margin-bottom: 16px;
}

.address-title {
    font-weight: 800;
    color: #1e3554;
    font-size: 1.1rem;
    margin-bottom: 8px;
}

.default-badge {
    display: inline-block;
    margin-left: 8px;
    background: #3498db;
    color: white;
    font-size: 0.72rem;
    padding: 3px 8px;
    border-radius: 999px;
}

.address-text {
    color: #555;
    line-height: 1.6;
    margin-bottom: 16px;
}

.address-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.address-btn {
    border: none;
    border-radius: 8px;
    padding: 9px 14px;
    cursor: pointer;
    font-weight: 700;
    text-decoration: none;
    display: inline-block;
}

.address-btn-blue {
    background: #3498db;
    color: white;
}

.address-btn-light {
    background: #eaf4fd;
    color: #3498db;
}

.address-btn-danger {
    background: #fcebea;
    color: #e74c3c;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    color: #333;
    font-weight: 700;
}

.form-control {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid #dbe9f6;
    border-radius: 8px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
}

.alert-success, .alert-error {
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.alert-success {
    background: #eafaf1;
    color: #1e8449;
}

.alert-error {
    background: #fdecea;
    color: #c0392b;
}

@media (max-width: 900px) {
    .address-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="address-page-wrap">
    <div class="address-header">
        <h1>Manage Address</h1>
        <p>Add, edit and choose your default delivery address for faster checkout.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="address-grid">
        <div>
            <h2 style="color:#1e3554;margin-top:0;">Saved Addresses</h2>

            <?php if (empty($addresses)): ?>
                <div class="address-card">
                    <div class="address-title">No address yet</div>
                    <div class="address-text">Add your first delivery address using the form.</div>
                </div>
            <?php else: ?>
                <?php foreach ($addresses as $address): ?>
                    <div class="address-card">
                        <div class="address-title">
                            <?php echo htmlspecialchars($address['label'] ?: 'Address'); ?>
                            <?php if ($address['is_default']): ?>
                                <span class="default-badge">Default</span>
                            <?php endif; ?>
                        </div>

                        <div class="address-text">
                            <strong><?php echo htmlspecialchars($address['receiver_name']); ?></strong>
                            <?php if (!empty($address['phone'])): ?>
                                · <?php echo htmlspecialchars($address['phone']); ?>
                            <?php endif; ?>
                            <br>
                            <?php echo nl2br(htmlspecialchars($address['address_line'])); ?>
                            <?php if (!empty($address['postcode']) || !empty($address['city'])): ?>
                                <br><?php echo htmlspecialchars(trim($address['postcode'] . ' ' . $address['city'])); ?>
                            <?php endif; ?>
                            <?php if (!empty($address['state'])): ?>
                                , <?php echo htmlspecialchars($address['state']); ?>
                            <?php endif; ?>
                        </div>

                        <div class="address-actions">
                            <a class="address-btn address-btn-light" href="manage-addresses.php?edit=<?php echo (int)$address['id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </a>

                            <?php if (!$address['is_default']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="default">
                                    <input type="hidden" name="address_id" value="<?php echo (int)$address['id']; ?>">
                                    <button type="submit" class="address-btn address-btn-blue">Set Default</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this address?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="address_id" value="<?php echo (int)$address['id']; ?>">
                                <button type="submit" class="address-btn address-btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div>
            <div class="address-form-card">
                <h2 style="color:#1e3554;margin-top:0;">
                    <?php echo $edit_address ? 'Edit Address' : 'Add New Address'; ?>
                </h2>

                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="address_id" value="<?php echo $edit_address ? (int)$edit_address['id'] : 0; ?>">

                    <div class="form-group">
                        <label>Address Label</label>
                        <input type="text" name="label" class="form-control" placeholder="Home, Hostel, Office" value="<?php echo htmlspecialchars($edit_address['label'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Receiver Name</label>
                        <input type="text" name="receiver_name" class="form-control" placeholder="Receiver name" value="<?php echo htmlspecialchars($edit_address['receiver_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="0123456789" value="<?php echo htmlspecialchars($edit_address['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Address *</label>
                        <textarea name="address_line" class="form-control" rows="4" required placeholder="House number, street, area"><?php echo htmlspecialchars($edit_address['address_line'] ?? ''); ?></textarea>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($edit_address['city'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Postcode</label>
                            <input type="text" name="postcode" class="form-control" value="<?php echo htmlspecialchars($edit_address['postcode'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($edit_address['state'] ?? ''); ?>">
                    </div>

                    <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;cursor:pointer;">
                        <input type="checkbox" name="is_default" value="1" <?php echo !empty($edit_address['is_default']) ? 'checked' : ''; ?>>
                        <span>Set as default address</span>
                    </label>

                    <button type="submit" class="address-btn address-btn-blue" style="width:100%;padding:13px;">
                        <i class="fas fa-save"></i> Save Address
                    </button>

                    <?php if ($edit_address): ?>
                        <a href="manage-addresses.php" class="address-btn address-btn-light" style="width:100%;text-align:center;margin-top:10px;">
                            Cancel Edit
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
