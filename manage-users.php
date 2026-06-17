<?php

session_start();

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/config.php';

$current_is_superadmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'superadmin');
$allowed_user_types = $current_is_superadmin ? ['user', 'admin'] : ['user'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {

        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);

        if (!in_array($user_type, $allowed_user_types)) {
            $error = "Only Super Admin can add admin accounts.";
        }

        $check_sql = "SELECT * FROM users WHERE email = '$email' OR username = '$username'";
        if (!isset($error)) {
            $check_result = mysqli_query($conn, $check_sql);
        
            if (mysqli_num_rows($check_result) > 0) {
            $error = "User with this email or username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, user_type) 
                    VALUES ('$username', '$email', '$hashed_password', '$user_type')";
            
            if (mysqli_query($conn, $sql)) {
                $message = "User added successfully!";
            } else {
                $error = "Error adding user: " . mysqli_error($conn);
            }
            }
        }
    }
    
    if (isset($_POST['update_user'])) {

        $user_id = $_POST['user_id'];
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
        $password = $_POST['password'] ?? '';

        $target_result = mysqli_query($conn, "SELECT user_type FROM users WHERE id = " . intval($user_id));
        $target_user = $target_result ? mysqli_fetch_assoc($target_result) : null;

        if (!$target_user) {
            $error = "User not found.";
        } elseif (($user_type === 'admin' || $target_user['user_type'] === 'admin' || $target_user['user_type'] === 'superadmin') && !$current_is_superadmin) {
            $error = "Only Super Admin can create or edit admin accounts.";
        } elseif ($user_type === 'superadmin') {
            $error = "Super Admin role cannot be assigned from this page.";
        }
        
        if (!isset($error) && !empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET 
                    username = '$username', 
                    email = '$email', 
                    user_type = '$user_type',
                    password = '$hashed_password'
                    WHERE id = $user_id";
        } elseif (!isset($error)) {
            $sql = "UPDATE users SET 
                    username = '$username', 
                    email = '$email', 
                    user_type = '$user_type'
                    WHERE id = $user_id";
        }
        
        if (!isset($error)) {
            if (mysqli_query($conn, $sql)) {
                $message = "User updated successfully!";
            } else {
                $error = "Error updating user: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['delete_user'])) {

        $user_id = $_POST['user_id'];
        
        $target_result = mysqli_query($conn, "SELECT user_type FROM users WHERE id = " . intval($user_id));
        $target_user = $target_result ? mysqli_fetch_assoc($target_result) : null;

        if ($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } elseif ($target_user && in_array($target_user['user_type'], ['admin', 'superadmin']) && !$current_is_superadmin) {
            $error = "Only Super Admin can delete admin accounts.";
        } else {
            $sql = "DELETE FROM users WHERE id = $user_id";
            if (mysqli_query($conn, $sql)) {
                $message = "User deleted successfully!";
            } else {
                $error = "Error deleting user: " . mysqli_error($conn);
            }
        }
    }
}

$search = $_GET['search'] ?? '';
$user_type = $_GET['user_type'] ?? '';

$sql = "SELECT * FROM users WHERE 1=1";
if (!empty($search)) {
    $sql .= " AND (username LIKE '%$search%' OR email LIKE '%$search%')";
}
if (!empty($user_type)) {
    $sql .= " AND user_type = '$user_type'";
}
$sql .= " ORDER BY created_at DESC";

$users = mysqli_query($conn, $sql);

$stats_sql = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN user_type IN ('admin','superadmin') THEN 1 ELSE 0 END) as admin_count,
                SUM(CASE WHEN user_type = 'user' THEN 1 ELSE 0 END) as user_count,
                DATE(created_at) as signup_date
              FROM users 
              GROUP BY DATE(created_at)
              ORDER BY signup_date DESC
              LIMIT 7";
$stats_result = mysqli_query($conn, $stats_sql);

$page_title = "Manage Users";
$show_sidebar = true;
$current_page = 'admin/manage-users.php';

include '../includes/header.php';
?>

<style>

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #cce5ff;
    color: #004085;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.table-responsive {
    overflow-x: auto;
    border-radius: 10px;
    border: 1px solid #eee;
}

.btn {
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    transition: all 0.3s;
}

.btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 0.9rem;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 10px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.modal-header h2 {
    margin: 0;
    color: #333;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #666;
    cursor: pointer;
    padding: 5px;
}

.close-btn:hover {
    color: #333;
}

.user-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.stat-card h3 {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    color: #666;
}

.stat-card .stat-value {
    font-size: 1.8rem;
    font-weight: bold;
    margin: 0;
}


.admin-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.admin-header h1 {
    color: #333;
    margin-bottom: 10px;
}

.admin-header p {
    color: #666;
    margin: 0;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
</style>

<div class="admin-header">
    <h1><i class="fas fa-users"></i> Manage Users</h1>
    <p>Manage customer and admin accounts</p>
    <?php if (!$current_is_superadmin): ?>
        <p style="color:#856404;background:#fff3cd;padding:10px;border-radius:6px;margin-top:10px;">Only Super Admin can add, edit or delete admin accounts.</p>
    <?php endif; ?>
</div>

<?php if (isset($message)): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- User Statistics -->
<div class="user-stats-grid">
    <?php
    $total_users = mysqli_num_rows($users);
    $admin_count = 0;
    $user_count = 0;
    
    mysqli_data_seek($users, 0); // Reset pointer
    while($user = mysqli_fetch_assoc($users)) {
        if (in_array($user['user_type'], ['admin', 'superadmin'])) $admin_count++;
        if ($user['user_type'] == 'user') $user_count++;
    }
    mysqli_data_seek($users, 0); // Reset pointer again
    
    $new_this_week = mysqli_num_rows($stats_result);
    ?>
    
    <div class="stat-card" style="border-left: 4px solid #007bff;">
        <h3>Total Users</h3>
        <p class="stat-value"><?php echo $total_users; ?></p>
    </div>
    
    <div class="stat-card" style="border-left: 4px solid #28a745;">
        <h3>Users</h3>
        <p class="stat-value"><?php echo $user_count; ?></p>
    </div>
    
    <div class="stat-card" style="border-left: 4px solid #ff6b6b;">
        <h3>Admins</h3>
        <p class="stat-value"><?php echo $admin_count; ?></p>
    </div>
    
    <div class="stat-card" style="border-left: 4px solid #ffc107;">
        <h3>New This Week</h3>
        <p class="stat-value"><?php echo $new_this_week; ?></p>
    </div>
</div>

<!-- Search and Filter -->
<div style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
    <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
        <div class="form-group">
            <label>Search Users</label>
            <input type="text" name="search" placeholder="Username, Email..." class="form-control" 
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        
        <div class="form-group">
            <label>User Type</label>
            <select name="user_type" class="form-control">
                <option value="">All Users</option>
                <option value="user" <?php echo $user_type == 'user' ? 'selected' : ''; ?>>Users</option>
                <option value="admin" <?php echo $user_type == 'admin' ? 'selected' : ''; ?>>Admins</option>
                <option value="superadmin" <?php echo $user_type == 'superadmin' ? 'selected' : ''; ?>>Super Admin</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn" style="background: #007bff; color: white;">
                <i class="fas fa-search"></i> Search
            </button>
            <a href="manage-users.php" class="btn" style="background: #6c757d; color: white;">
                <i class="fas fa-redo"></i> Reset
            </a>
            <button type="button" class="btn" style="background: #28a745; color: white;" onclick="document.getElementById('addUserModal').style.display='flex'">
                <i class="fas fa-user-plus"></i> Add User
            </button>
        </div>
    </form>
</div>

<!-- Users Table -->
<div style="background: white; border-radius: 10px; padding: 20px; overflow-x: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse; min-width: 1000px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">ID</th>
                    <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Username</th>
                    <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Email</th>
                    <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">User Type</th>
                    <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Joined Date</th>
                    <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Status</th>
                    <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = mysqli_fetch_assoc($users)): 

                    $is_current_user = ($user['id'] == $_SESSION['user_id']);
                    $user_type_color = $user['user_type'] == 'superadmin' ? '#6f42c1' : ($user['user_type'] == 'admin' ? '#ff6b6b' : '#28a745');

                    $user_stats_sql = "SELECT 
                                        COUNT(o.id) as total_orders,
                                        SUM(o.total_price) as total_spent,
                                        MAX(o.order_date) as last_order_date
                                       FROM orders o 
                                       WHERE o.user_id = {$user['id']}";
                    $user_stats_result = mysqli_query($conn, $user_stats_sql);
                    $user_stats = mysqli_fetch_assoc($user_stats_result);
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px;">
                        <strong>#<?php echo $user['id']; ?></strong>
                    </td>
                    <td style="padding: 15px;">
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        <?php if ($is_current_user): ?>
                            <span style="background: #007bff; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; margin-left: 8px;">You</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px;">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </td>
                    <td style="padding: 15px;">
                        <span style="display: inline-block; padding: 5px 10px; border-radius: 20px; background: <?php echo $user_type_color; ?>20; color: <?php echo $user_type_color; ?>; font-weight: bold;">
                            <?php echo ucfirst($user['user_type']); ?>
                        </span>
                    </td>
                    <td style="padding: 15px;">
                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                    </td>
                    <td style="padding: 15px;">
                        <span style="display: inline-block; padding: 5px 10px; border-radius: 20px; background: #28a74520; color: #28a745; font-weight: bold;">
                            Active
                        </span>
                    </td>
                    <td style="padding: 15px;">
                        <div class="action-buttons">
                            <!-- Edit Button -->
                            <button type="button" class="btn edit-user-btn" 
                                    data-user-id="<?php echo $user['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                    data-user-type="<?php echo $user['user_type']; ?>"
                                    style="background: #007bff; color: white;">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            
                            <!-- Message Button -->
                            <a href="send-message.php?user_id=<?php echo $user['id']; ?>" class="btn" style="background: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-envelope"></i> Msg
                            </a>
                            
                            <!-- View Details Button -->
                            <button type="button" class="btn view-user-btn" 
                                    data-user-id="<?php echo $user['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                    data-user-type="<?php echo $user['user_type']; ?>"
                                    data-created-at="<?php echo $user['created_at']; ?>"
                                    data-last-login="<?php echo $user['last_login']; ?>"
                                    data-total-orders="<?php echo $user_stats['total_orders'] ?? 0; ?>"
                                    data-total-spent="<?php echo $user_stats['total_spent'] ?? 0; ?>"
                                    data-last-order="<?php echo $user_stats['last_order_date'] ?? 'Never'; ?>"
                                    style="background: #6c757d; color: white;">
                                <i class="fas fa-eye"></i> View
                            </button>
                            
                            <!-- Delete Button -->
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="delete_user" value="1">
                                <button type="submit" class="btn" 
                                        style="background: #dc3545; color: white;"
                                        <?php echo $is_current_user ? 'disabled title="Cannot delete your own account"' : ''; ?>>
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if (mysqli_num_rows($users) == 0): ?>
            <div style="text-align: center; padding: 40px 20px; color: #666;">
                <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                <h3>No Users Found</h3>
                <p>There are no users matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Add New User</h2>
            <button type="button" class="close-btn" onclick="document.getElementById('addUserModal').style.display='none'">
                &times;
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <small style="color: #666;">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label>User Type *</label>
                    <select name="user_type" class="form-control" required>
                        <option value="user">User</option>
                        <?php if ($current_is_superadmin): ?>
                            <option value="admin">Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="document.getElementById('addUserModal').style.display='none'" 
                            class="btn" style="background: #6c757d; color: white; margin-right: 10px;">
                        Cancel
                    </button>
                    <button type="submit" name="add_user" class="btn" style="background: #28a745; color: white;">
                        <i class="fas fa-save"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Edit User</h2>
            <button type="button" class="close-btn" onclick="document.getElementById('editUserModal').style.display='none'">
                &times;
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                <input type="hidden" name="update_user" value="1">
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" id="edit_username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>User Type *</label>
                    <select name="user_type" id="edit_user_type" class="form-control" required>
                        <option value="user">User</option>
                        <?php if ($current_is_superadmin): ?>
                            <option value="admin">Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Password (Leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                    <small style="color: #666;">Only enter if you want to change the password</small>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="document.getElementById('editUserModal').style.display='none'" 
                            class="btn" style="background: #6c757d; color: white; margin-right: 10px;">
                        Cancel
                    </button>
                    <button type="submit" class="btn" style="background: #007bff; color: white;">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Details Modal -->
<div id="viewUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-circle"></i> User Details</h2>
            <button type="button" class="close-btn" onclick="document.getElementById('viewUserModal').style.display='none'">
                &times;
            </button>
        </div>
        <div class="modal-body" id="userDetailsContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>

document.querySelectorAll('.edit-user-btn').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.getAttribute('data-user-id');
        const username = this.getAttribute('data-username');
        const email = this.getAttribute('data-email');
        const userType = this.getAttribute('data-user-type');
        
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_user_type').value = userType;
        
        document.getElementById('editUserModal').style.display = 'flex';
    });
});

document.querySelectorAll('.view-user-btn').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.getAttribute('data-user-id');
        const username = this.getAttribute('data-username');
        const email = this.getAttribute('data-email');
        const userType = this.getAttribute('data-user-type');
        const createdAt = this.getAttribute('data-created-at');
        const lastLogin = this.getAttribute('data-last-login');
        const totalOrders = this.getAttribute('data-total-orders');
        const totalSpent = this.getAttribute('data-total-spent');
        const lastOrder = this.getAttribute('data-last-order');

        const formatDate = (dateString) => {
            if (!dateString || dateString === 'Never') return dateString;
            const date = new Date(dateString);
            return date.toLocaleString();
        };

        const userTypeColor = userType === 'admin' ? '#ff6b6b' : '#28a745';

        const html = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;">
                <div>
                    <h3 style="margin-top: 0; color: #333; margin-bottom: 15px;">User Information</h3>
                    <div style="background: #f8f9fa; border-radius: 10px; padding: 20px;">
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #666;">Username:</strong><br>
                            <span style="font-size: 1.2rem; font-weight: bold; color: #333;">${username}</span>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #666;">Email:</strong><br>
                            <span style="color: #333;">${email}</span>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #666;">User Type:</strong><br>
                            <span style="display: inline-block; padding: 5px 10px; border-radius: 20px; background: ${userTypeColor}20; color: ${userTypeColor}; font-weight: bold; margin-top: 5px;">
                                ${userType.charAt(0).toUpperCase() + userType.slice(1)}
                            </span>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #666;">Account Created:</strong><br>
                            <span style="color: #333;">${formatDate(createdAt)}</span>
                        </div>
                        
                        <div>
                            <strong style="color: #666;">Last Login:</strong><br>
                            <span style="color: #333;">${formatDate(lastLogin)}</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 style="margin-top: 0; color: #333; margin-bottom: 15px;">Order Statistics</h3>
                    <div style="background: #f8f9fa; border-radius: 10px; padding: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 10px; border: 1px solid #eee;">
                                <div style="font-size: 2rem; font-weight: bold; color: #007bff;">${totalOrders}</div>
                                <div style="color: #666; font-size: 0.9rem;">Total Orders</div>
                            </div>
                            
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 10px; border: 1px solid #eee;">
                                <div style="font-size: 2rem; font-weight: bold; color: #28a745;">$${parseFloat(totalSpent).toFixed(2)}</div>
                                <div style="color: #666; font-size: 0.9rem;">Total Spent</div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #666;">Last Order:</strong><br>
                            <span style="color: #333;">${formatDate(lastOrder)}</span>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #666;">Average Order Value:</strong><br>
                            <span style="color: #333;">$${totalOrders > 0 ? (parseFloat(totalSpent) / parseInt(totalOrders)).toFixed(2) : '0.00'}</span>
                        </div>
                        
                        <div>
                            <strong style="color: #666;">Customer Since:</strong><br>
                            <span style="color: #333;">${new Date(createdAt).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders Section -->
            <div>
                <h3 style="color: #333; margin-bottom: 15px;">Recent Orders</h3>
                <div id="recentOrders">
                    <div style="text-align: center; padding: 20px; color: #666;">
                        <i class="fas fa-spinner fa-spin"></i> Loading orders...
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px; text-align: center;">
                <button type="button" onclick="document.getElementById('viewUserModal').style.display='none'" 
                        class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        `;

        document.getElementById('userDetailsContent').innerHTML = html;

        document.getElementById('viewUserModal').style.display = 'flex';

        loadRecentOrders(userId);
    });
});

function loadRecentOrders(userId) {
    const xhr = new XMLHttpRequest();

    xhr.open('GET', 'get_user_orders.php?user_id=' + userId, true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('recentOrders').innerHTML = xhr.responseText;
        } else {
            document.getElementById('recentOrders').innerHTML = `
                <div style="text-align: center; padding: 20px; color: #dc3545; background: #f8d7da; border-radius: 10px;">
                    <i class="fas fa-exclamation-circle"></i> Failed to load orders (HTTP ${xhr.status})
                </div>
            `;
        }
    };
    
    xhr.onerror = function() {
        document.getElementById('recentOrders').innerHTML = `
            <div style="text-align: center; padding: 20px; color: #dc3545; background: #f8d7da; border-radius: 10px;">
                <i class="fas fa-exclamation-circle"></i> Network error loading orders. Please try again.
            </div>
        `;
    };
    
    xhr.send();
}

window.addEventListener('click', function(event) {
    const addModal = document.getElementById('addUserModal');
    const editModal = document.getElementById('editUserModal');
    const viewModal = document.getElementById('viewUserModal');
    
    if (event.target == addModal) {
        addModal.style.display = 'none';
    }
    if (event.target == editModal) {
        editModal.style.display = 'none';
    }
    if (event.target == viewModal) {
        viewModal.style.display = 'none';
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.getElementById('addUserModal').style.display = 'none';
        document.getElementById('editUserModal').style.display = 'none';
        document.getElementById('viewUserModal').style.display = 'none';
    }
});
</script>

<?php include '../includes/footer.php'; ?>