<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/config.php';

$page_title = "My Profile";
$current_page = 'profile.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['update_profile'])) {

        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        }

        if (!$error) {
            $check_username = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmt = mysqli_prepare($conn, $check_username);
            mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "Username already taken.";
            }
            mysqli_stmt_close($stmt);
        }

        if (!$error) {
            $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = mysqli_prepare($conn, $check_email);
            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "Email already in use.";
            }
            mysqli_stmt_close($stmt);
        }

        if (!$error) {

            $image_update_sql = "";
            $upload_ok = true;
            $file_dest = "";

            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {

                $file = $_FILES['profile_image'];
                $file_name = $file['name'];
                $file_tmp = $file['tmp_name'];
                $file_size = $file['size'];

                $file_ext = explode('.', $file_name);
                $file_actual_ext = strtolower(end($file_ext));

                $allowed = array('jpg', 'jpeg', 'png', 'gif');

                if (in_array($file_actual_ext, $allowed)) {

                    if ($file_size < 5000000) {

                        $file_name_new = "profile_" . $user_id . "_" . uniqid() . "." . $file_actual_ext;
                        $file_dest = 'uploads/profile/' . $file_name_new;

                        if (!file_exists('uploads/profile/')) {
                            mkdir('uploads/profile/', 0777, true);
                        }

                        if (move_uploaded_file($file_tmp, $file_dest)) {

                            $safe_path = mysqli_real_escape_string($conn, $file_dest);
                            $image_update_sql = ", profile_image = '$safe_path'";

                            $_SESSION['profile_image'] = $file_dest;

                        } else {
                            $error = "Failed to upload image.";
                            $upload_ok = false;
                        }

                    } else {
                        $error = "File too large (max 5MB).";
                        $upload_ok = false;
                    }

                } else {
                    $error = "Invalid file type.";
                    $upload_ok = false;
                }
            }

            if ($upload_ok && !$error) {

                $sql = "UPDATE users 
                        SET username = ?, email = ?, phone = ? $image_update_sql 
                        WHERE id = ?";

                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $phone, $user_id);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['username'] = $username;
                    $message = "Profile updated successfully!";
                } else {
                    $error = "Error updating profile.";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }

    if (isset($_POST['change_password'])) {

        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if (password_verify($current_password, $row['password'])) {

            if ($new_password === $confirm_password) {

                if (strlen($new_password) >= 6) {

                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);

                    if (mysqli_stmt_execute($update_stmt)) {
                        $message = "Password changed successfully!";
                    } else {
                        $error = "Error updating password.";
                    }

                } else {
                    $error = "Password must be at least 6 characters.";
                }

            } else {
                $error = "Passwords do not match.";
            }

        } else {
            $error = "Current password is incorrect.";
        }
    }
}

$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

require_once 'includes/header.php';
?>

<div class="content">
    <h1><i class="fas fa-user-circle" style="color: #3498db;"></i> My Profile</h1>
    
    <?php if ($message): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="display:flex; flex-wrap:wrap; gap:30px;">
        
        <!-- LEFT PROFILE CARD -->
        <div style="flex:1; min-width:300px; background:white; padding:25px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05); text-align:center;">
            
            <div style="width:110px; height:110px; margin:0 auto 15px;">

                <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                    <img src="<?php echo $user['profile_image']; ?>" 
                         style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                <?php else: ?>
                    <div style="width:100%; height:100%; background:#3498db; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2.5rem; font-weight:bold;">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>

            </div>

            <h2 style="margin-bottom:5px;"><?php echo htmlspecialchars($user['username']); ?></h2>
            <p style="color:#666; margin-bottom:15px;">
                <?php echo ucfirst($user['user_type']); ?>
            </p>
            
            <div style="text-align:left; border-top:1px solid #eee; padding-top:15px;">
                
                <p><strong>Email:</strong><br>
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>

                <p><strong>Phone:</strong><br>
                    <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span style="color:#aaa;">Not provided</span>'; ?>
                </p>

                <p><strong>Member Since:</strong><br>
                    <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                </p>

            </div>
            
            <div style="display:flex; gap:10px; margin-top:15px;">
                <button onclick="openEditModal()" 
                        style="flex:1; background:#3498db; color:white; border:none; padding:10px; border-radius:5px;">
                    Edit
                </button>

                <button onclick="openPasswordModal()" 
                        style="flex:1; background:#6c757d; color:white; border:none; padding:10px; border-radius:5px;">
                    Password
                </button>
            </div>

        </div>
        
        <!-- RIGHT STATS -->
        <div style="flex:1; min-width:300px;">
            <div style="background:white; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-bottom:20px;">
                
                <h3 style="margin-bottom:15px;">Account Statistics</h3>
                
                <?php
                $stats_sql = "SELECT COUNT(*) as total_orders, SUM(total_price) as total_spent 
                              FROM orders WHERE user_id = $user_id";
                $stats_res = mysqli_query($conn, $stats_sql);
                $stats = mysqli_fetch_assoc($stats_res);
                ?>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    
                    <div style="background:#f5f7fa; padding:15px; text-align:center; border-radius:8px;">
                        <i class="fas fa-shopping-cart" style="color:#3498db;"></i>
                        <h4><?php echo $stats['total_orders']; ?></h4>
                        <small>Total Orders</small>
                    </div>
                    
                    <div style="background:#f5f7fa; padding:15px; text-align:center; border-radius:8px;">
                        <i class="fas fa-wallet" style="color:#2ecc71;"></i>
                        <h4>RM<?php echo number_format((float)$stats['total_spent'], 2); ?></h4>
                        <small>Total Spent</small>
                    </div>

                </div>
            </div>

            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <h3 style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Quick Actions</h3>

    <a href="orders.php" style="display:flex; justify-content:space-between; padding:12px; border:1px solid #eee; border-radius:8px; margin-bottom:10px; text-decoration:none; color:#333;">
        <span><i class="fas fa-list" style="color:#3498db;"></i> Order History</span>
        <i class="fas fa-chevron-right" style="color:#ccc;"></i>
    </a>

    <a href="track.php" style="display:flex; justify-content:space-between; padding:12px; border:1px solid #eee; border-radius:8px; margin-bottom:10px; text-decoration:none; color:#333;">
        <span><i class="fas fa-map-marker-alt" style="color:#3498db;"></i> Track Order</span>
        <i class="fas fa-chevron-right" style="color:#ccc;"></i>
    </a>

    <a href="my-messages.php" style="display:flex; justify-content:space-between; padding:12px; border:1px solid #eee; border-radius:8px; text-decoration:none; color:#333;">
        <span><i class="fas fa-envelope" style="color:#3498db;"></i> Messages</span>
        <i class="fas fa-chevron-right" style="color:#ccc;"></i>
    </a>
</div>
</div>
</div>
</div>

<!-- EDIT PROFILE MODAL -->
<div id="editProfileModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">

    <div style="background:white; margin:8% auto; padding:25px; border-radius:10px; width:90%; max-width:450px;">

        <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
            <h2 style="margin:0;">Edit Profile</h2>
            <span onclick="closeEditModal()" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            
            <!-- IMAGE -->
            <div style="text-align:center; margin-bottom:15px;">

                <div style="width:90px; height:90px; margin:0 auto 10px; border-radius:50%; overflow:hidden; background:#f0f0f0;">
                    
                    <img id="imagePreview" 
                         src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : ''; ?>" 
                         style="width:100%; height:100%; object-fit:cover; display:<?php echo !empty($user['profile_image']) ? 'block' : 'none'; ?>;">

                    <div id="imagePlaceholder" 
                         style="display:<?php echo empty($user['profile_image']) ? 'flex' : 'none'; ?>; align-items:center; justify-content:center; height:100%; color:#bbb;">
                        <i class="fas fa-user"></i>
                    </div>

                </div>
                
                <label style="font-size:0.85rem; cursor:pointer; color:#3498db;">
                    Change Photo
                    <input type="file" name="profile_image" accept="image/*" style="display:none;" onchange="previewImage(this)">
                </label>
            </div>

            <!-- USERNAME -->
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                   required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">

            <!-- EMAIL -->
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                   required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">

            <!-- PHONE -->
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                   style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ddd; border-radius:5px;">

            <!-- BUTTON -->
            <button type="submit" name="update_profile" 
                    style="width:100%; padding:10px; background:#3498db; color:white; border:none; border-radius:5px;">
                Save Changes
            </button>

        </form>
    </div>
</div>


<!-- CHANGE PASSWORD MODAL -->
<div id="changePasswordModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">

    <div style="background:white; margin:8% auto; padding:25px; border-radius:10px; width:90%; max-width:450px;">

        <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
            <h2 style="margin:0;">Change Password</h2>
            <span onclick="closePasswordModal()" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        
        <form method="POST">

            <input type="password" name="current_password" required 
                   placeholder="Current Password"
                   style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">

            <input type="password" name="new_password" required 
                   placeholder="New Password (min 6 characters)"
                   style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">

            <input type="password" name="confirm_password" required 
                   placeholder="Confirm Password"
                   style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ddd; border-radius:5px;">

            <button type="submit" name="change_password"
                    style="width:100%; padding:10px; background:#3498db; color:white; border:none; border-radius:5px;">
                Update Password
            </button>

        </form>
    </div>
</div>


<!-- JAVASCRIPT -->
<script>

function openEditModal(){
    document.getElementById('editProfileModal').style.display = 'block';
}

function closeEditModal(){
    document.getElementById('editProfileModal').style.display = 'none';
}

function openPasswordModal(){
    document.getElementById('changePasswordModal').style.display = 'block';
}

function closePasswordModal(){
    document.getElementById('changePasswordModal').style.display = 'none';
}

function previewImage(input){
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = function(e){
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
            document.getElementById('imagePlaceholder').style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

window.onclick = function(e){
    if(e.target.id === 'editProfileModal'){
        closeEditModal();
    }
    if(e.target.id === 'changePasswordModal'){
        closePasswordModal();
    }
}

document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
        closeEditModal();
        closePasswordModal();
    }
});

window.onload = function(){
    <?php if ($error && isset($_POST['update_profile'])): ?>
        openEditModal();
    <?php elseif ($error && isset($_POST['change_password'])): ?>
        openPasswordModal();
    <?php endif; ?>
}

</script>

<?php require_once 'includes/footer.php'; ?>