<?php

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['add_category'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!empty($name)) {

            $check_sql = "SELECT id FROM categories WHERE name = '$name'";
            $check_result = mysqli_query($conn, $check_sql);
            
            if(mysqli_num_rows($check_result) > 0) {
                $error = "Category '$name' already exists.";
            } else {
                $sql = "INSERT INTO categories (name, description, is_active) VALUES ('$name', '$description', $is_active)";
                
                if (mysqli_query($conn, $sql)) {
                    $message = "Category '$name' added successfully!";
                } else {
                    $error = "Error adding category: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "Category name is required.";
        }
    }

    if (isset($_POST['update_category'])) {
        $id = (int)$_POST['category_id'];
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!empty($name)) {

            $check_sql = "SELECT id FROM categories WHERE name = '$name' AND id != $id";
            $check_result = mysqli_query($conn, $check_sql);
            
            if(mysqli_num_rows($check_result) > 0) {
                $error = "Category '$name' already exists.";
            } else {
                $sql = "UPDATE categories SET name = '$name', description = '$description', is_active = $is_active WHERE id = $id";
                
                if (mysqli_query($conn, $sql)) {
                    $message = "Category updated successfully!";
                } else {
                    $error = "Error updating category: " . mysqli_error($conn);
                }
            }
        }
    }

    if (isset($_POST['delete_category'])) {
        $id = (int)$_POST['category_id'];

        $check_sql = "SELECT COUNT(*) as product_count FROM products WHERE category_id = $id";
        $check_result = mysqli_query($conn, $check_sql);
        $check_data = mysqli_fetch_assoc($check_result);
        
        if ($check_data['product_count'] > 0) {
            $error = "Cannot delete category because it has " . $check_data['product_count'] . " product(s) associated with it. Please reassign or delete the products first.";
        } else {
            $sql = "DELETE FROM categories WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                $message = "Category deleted successfully!";
            } else {
                $error = "Error deleting category: " . mysqli_error($conn);
            }
        }
    }

    if (isset($_POST['toggle_status'])) {
        $id = (int)$_POST['category_id'];

        $status_sql = "SELECT is_active FROM categories WHERE id = $id";
        $status_result = mysqli_query($conn, $status_sql);
        $status_data = mysqli_fetch_assoc($status_result);
        $new_status = $status_data['is_active'] ? 0 : 1;
        
        $sql = "UPDATE categories SET is_active = $new_status WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            $status_text = $new_status ? 'activated' : 'deactivated';
            $message = "Category $status_text successfully!";
        } else {
            $error = "Error updating category status: " . mysqli_error($conn);
        }
    }
}

$sql = "SELECT * FROM categories ORDER BY is_active DESC, name ASC";
$categories_result = mysqli_query($conn, $sql);

if (!$categories_result) {
    die("Database query failed: " . mysqli_error($conn));
}

$page_title = "Manage Categories";
$show_sidebar = true;
$current_page = 'admin/manage-categories.php';

require_once '../includes/header.php';
?>

<div class="admin-page-container" style="padding: 20px;">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
        <div>
            <h1 style="color: #333; margin: 0;"><i class="fas fa-tags"></i> Manage Categories</h1>
            <p style="color: #666; margin: 5px 0 0;">Add, edit or remove food categories</p>
        </div>
        <button onclick="openAddModal()" class="btn" style="background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%); color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-plus"></i> Add New Category
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="background: white; border-radius: 10px; padding: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.08);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee; font-weight: 600; color: #555;">ID</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee; font-weight: 600; color: #555;">Category Name</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee; font-weight: 600; color: #555;">Description</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee; font-weight: 600; color: #555;">Status</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #eee; font-weight: 600; color: #555;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 

                    mysqli_data_seek($categories_result, 0);
                    if (mysqli_num_rows($categories_result) > 0): 
                    ?>
                        <?php while($cat = mysqli_fetch_assoc($categories_result)): 

                            $product_count_sql = "SELECT COUNT(*) as count FROM products WHERE category_id = " . $cat['id'];
                            $product_count_result = mysqli_query($conn, $product_count_sql);
                            $product_count_data = mysqli_fetch_assoc($product_count_result);
                            $product_count = $product_count_data['count'];

                            $status_badge = $cat['is_active'] ? 
                                '<span class="status-badge" style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">Active</span>' : 
                                '<span class="status-badge" style="background: #e2e3e5; color: #383d41; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">Inactive</span>';
                        ?>
                            <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; <?php echo !$cat['is_active'] ? 'background: #f8f9fa;' : ''; ?>">
                                <td style="padding: 15px; color: #666;">#<?php echo $cat['id']; ?></td>
                                <td style="padding: 15px;">
                                    <strong style="color: <?php echo $cat['is_active'] ? '#333' : '#999'; ?>;"><?php echo htmlspecialchars($cat['name']); ?></strong>
                                    <?php if ($product_count > 0): ?>
                                        <br>
                                        <small style="color: #20c997; font-size: 0.85rem;">
                                            <i class="fas fa-hamburger"></i> <?php echo $product_count; ?> product(s)
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; color: #666;"><?php echo htmlspecialchars($cat['description'] ?: 'No description'); ?></td>
                                <td style="padding: 15px;">
                                    <?php echo $status_badge; ?>
                                </td>
                                <td style="padding: 15px;">
                                    <button onclick="openEditModal(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', '<?php echo addslashes($cat['description']); ?>', <?php echo $cat['is_active']; ?>)" 
                                            class="btn" style="background: #ffc107; color: #333; padding: 8px 15px; font-size: 0.9rem; margin-right: 5px; border: none; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.3s;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <button type="submit" class="btn" style="background: <?php echo $cat['is_active'] ? '#6c757d' : '#20c997'; ?>; color: white; padding: 8px 15px; font-size: 0.9rem; margin-right: 5px; border: none; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.3s;">
                                            <i class="fas fa-<?php echo $cat['is_active'] ? 'ban' : 'check'; ?>"></i> <?php echo $cat['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" onsubmit="return confirmDelete(<?php echo $product_count; ?>);" style="display: inline;">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                        <input type="hidden" name="delete_category" value="1">
                                        <button type="submit" class="btn" style="background: #dc3545; color: white; padding: 8px 15px; font-size: 0.9rem; border: none; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.3s;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-tags" style="font-size: 3rem; margin-bottom: 15px; display: block; color: #ddd;"></i>
                                No categories found. Click "Add New Category" to create your first category.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 90%; max-width: 500px; padding: 30px; border-radius: 10px; position: relative; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
        <span onclick="closeAddModal()" style="position: absolute; right: 20px; top: 20px; cursor: pointer; font-size: 1.5rem; color: #999; transition: color 0.3s;">&times;</span>
        <h2 style="margin-top: 0; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-plus-circle" style="color: #20c997;"></i> Add New Category
        </h2>
        
        <form method="POST">
            <input type="hidden" name="add_category" value="1">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #555;">Category Name *</label>
                <input type="text" name="name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; box-sizing: border-box; transition: border 0.3s;" 
                       placeholder="e.g., Burgers, Drinks, Desserts" onfocus="this.style.borderColor='#20c997';" onblur="this.style.borderColor='#ddd';">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #555;">Description</label>
                <textarea name="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; box-sizing: border-box; resize: vertical; transition: border 0.3s;" 
                          placeholder="Optional: Describe this category" onfocus="this.style.borderColor='#20c997';" onblur="this.style.borderColor='#ddd';"></textarea>
            </div>
            <div style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="is_active" id="add_is_active" value="1" checked style="width: 18px; height: 18px;">
                <label for="add_is_active" style="font-weight: 600; color: #555;">Active Category</label>
                <small style="color: #666; margin-left: 5px;">(Inactive categories won't show in menus)</small>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeAddModal()" class="btn" style="flex: 1; background: #6c757d; color: white; padding: 12px; border: none; font-weight: 600; border-radius: 5px; cursor: pointer; transition: background 0.3s;">
                    Cancel
                </button>
                <button type="submit" class="btn" style="flex: 1; background: #20c997; color: white; padding: 12px; border: none; font-weight: 600; border-radius: 5px; cursor: pointer; transition: background 0.3s;">
                    Add Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 90%; max-width: 500px; padding: 30px; border-radius: 10px; position: relative; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
        <span onclick="closeEditModal()" style="position: absolute; right: 20px; top: 20px; cursor: pointer; font-size: 1.5rem; color: #999; transition: color 0.3s;">&times;</span>
        <h2 style="margin-top: 0; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-edit" style="color: #ffc107;"></i> Edit Category
        </h2>
        
        <form method="POST">
            <input type="hidden" name="update_category" value="1">
            <input type="hidden" name="category_id" id="edit_id">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #555;">Category Name *</label>
                <input type="text" name="name" id="edit_name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; box-sizing: border-box; transition: border 0.3s;" 
                       onfocus="this.style.borderColor='#ffc107';" onblur="this.style.borderColor='#ddd';">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #555;">Description</label>
                <textarea name="description" id="edit_description" rows="3" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; box-sizing: border-box; resize: vertical; transition: border 0.3s;" 
                          onfocus="this.style.borderColor='#ffc107';" onblur="this.style.borderColor='#ddd';"></textarea>
            </div>
            <div style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="width: 18px; height: 18px;">
                <label for="edit_is_active" style="font-weight: 600; color: #555;">Active Category</label>
                <small style="color: #666; margin-left: 5px;">(Inactive categories won't show in menus)</small>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeEditModal()" class="btn" style="flex: 1; background: #6c757d; color: white; padding: 12px; border: none; font-weight: 600; border-radius: 5px; cursor: pointer; transition: background 0.3s;">
                    Cancel
                </button>
                <button type="submit" class="btn" style="flex: 1; background: #ffc107; color: #333; padding: 12px; border: none; font-weight: 600; border-radius: 5px; cursor: pointer; transition: background 0.3s;">
                    Update Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>

    function openAddModal() {
        document.getElementById('addModal').style.display = 'flex';
    }
    
    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
    }

    function openEditModal(id, name, desc, isActive) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_description').value = desc;
        document.getElementById('edit_is_active').checked = (isActive == 1);
        document.getElementById('editModal').style.display = 'flex';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function confirmDelete(productCount) {
        if (productCount > 0) {
            return confirm('Warning: This category has ' + productCount + ' product(s) associated with it.\n\nIf you delete this category, all products in it will become uncategorized.\n\nAre you sure you want to delete it?');
        }
        return confirm('Are you sure you want to delete this category?');
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addModal')) {
            closeAddModal();
        }
        if (event.target == document.getElementById('editModal')) {
            closeEditModal();
        }
    }

    document.onkeydown = function(evt) {
        evt = evt || window.event;
        if (evt.keyCode == 27) {
            closeAddModal();
            closeEditModal();
        }
    };
</script>

<?php require_once '../includes/footer.php'; ?>