<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: products.php");
    exit();
}

$sql = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$product_data = mysqli_fetch_assoc($result);

if (!$product_data) {
    header("Location: products.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $available = isset($_POST['available']) ? 1 : 0;
    
    if (empty($name) || $price <= 0) {
        $error = "Name and Price are required.";
    } else {

        $check_sql = "SELECT id FROM products WHERE name = '" . mysqli_real_escape_string($conn, $name) . "' AND id != $id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "A product with the name '$name' already exists. Please choose a different name.";
        } else {
            $new_image = $product_data['image'];
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_image = uniqid() . '.' . $filetype;
                    $target_dir = "../images/products/";
                    
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    if ($product_data['image'] && $product_data['image'] != 'default.jpg' && file_exists($target_dir . $product_data['image'])) {
                        unlink($target_dir . $product_data['image']);
                    }
                    
                    move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_image);
                } else {
                    $error = "Invalid file type. Allowed: JPG, JPEG, PNG, GIF. Keeping current image.";
                    $new_image = $product_data['image'];
                }
            }
            
            if (empty($error)) {
                $update_sql = "UPDATE products SET 
                              name = '" . mysqli_real_escape_string($conn, $name) . "',
                              description = '" . mysqli_real_escape_string($conn, $description) . "',
                              price = $price,
                              category_id = $category_id,
                              available = $available,
                              image = '" . mysqli_real_escape_string($conn, $new_image) . "'
                              WHERE id = $id";
                
                if (mysqli_query($conn, $update_sql)) {
                    $success = "Product updated successfully!";

                    $product_data['name'] = $name;
                    $product_data['description'] = $description;
                    $product_data['price'] = $price;
                    $product_data['category_id'] = $category_id;
                    $product_data['available'] = $available;
                    $product_data['image'] = $new_image;
                } else {
                    $error = "Update failed: " . mysqli_error($conn);
                }
            }
        }
    }
}

$categories = [];
$cats_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
if ($cats_result) {
    while($cat = mysqli_fetch_assoc($cats_result)) {
        $categories[] = $cat;
    }
}

$page_title = "Edit Product: " . $product_data['name'];
$show_sidebar = true;
$current_page = 'admin/products.php';

$edit_product = $product_data;

require_once '../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="content">
        <div class="dashboard-section">
            <div class="section-header">
                <h1><i class="fas fa-edit"></i> Edit Product: <?php echo htmlspecialchars($edit_product['name']); ?></h1>
                <a href="products.php" class="btn" style="background: #6c757d;">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="auth-card" style="max-width: 800px; margin: 0 auto;">
                <form method="POST" enctype="multipart/form-data" class="auth-form" id="editProductForm">
                    <input type="hidden" id="current_id" value="<?php echo $id; ?>">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Left Column -->
                        <div>
                            <div class="form-group">
                                <label for="name">Product Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($edit_product['name']); ?>" 
                                       required oninput="checkProductName(<?php echo $id; ?>)">
                                <div id="name-feedback" style="display: none; margin-top: 5px;">
                                    <small class="text-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span id="name-feedback-text"></span>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($edit_product['description']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="available" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" id="available" name="available" value="1" 
                                           <?php echo ($edit_product['available'] == 1) ? 'checked' : ''; ?>>
                                    Available for Sale
                                </label>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <label for="price">Price (RM) *</label>
                                    <input type="number" id="price" name="price" class="form-control" 
                                           value="<?php echo number_format($edit_product['price'], 2, '.', ''); ?>" 
                                           step="0.01" min="0.01" required>
                                </div>
                                
                                <div>
                                    <label for="category_id">Category *</label>
                                    <select id="category_id" name="category_id" class="form-control" required>
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo ($cat['id'] == $edit_product['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Product Image</label>
                                
                                <?php if ($edit_product['image'] && $edit_product['image'] != 'default.jpg'): ?>
                                    <div style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">Current Image:</label>
                                        <img src="../images/products/<?php echo $edit_product['image']; ?>" 
                                             style="max-width: 150px; border-radius: 5px; border: 1px solid #ddd; padding: 5px;">
                                    </div>
                                <?php endif; ?>
                                
                                <input type="file" id="image" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image. Max file size: 5MB</small>
                                
                                <div id="imagePreview" style="margin-top: 10px; display: none;">
                                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">New Image Preview:</label>
                                    <img id="previewImage" src="#" alt="Preview" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <div style="display: flex; gap: 15px;">
                            <button type="submit" class="btn" style="flex: 1; background: #28a745;" id="submit-btn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="products.php" class="btn" style="flex: 1; background: #6c757d; text-decoration: none; text-align: center;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

document.getElementById('image').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    const file = e.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

function checkProductName(currentId) {
    const nameInput = document.getElementById('name');
    const feedback = document.getElementById('name-feedback');
    const feedbackText = document.getElementById('name-feedback-text');
    const submitBtn = document.getElementById('submit-btn');
    const productName = nameInput.value.trim();
    
    if (productName.length < 2) {
        feedback.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        return;
    }

    fetch('check-product-name.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'name=' + encodeURIComponent(productName) + '&current_id=' + currentId
    })
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            feedbackText.textContent = 'A product with this name already exists. Please choose a different name.';
            feedback.style.display = 'block';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
        } else {
            feedback.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        }
    })
    .catch(error => {
        console.error('Error checking product name:', error);
    });
}

document.getElementById('editProductForm').addEventListener('submit', function(e) {
    const price = document.getElementById('price').value;
    const name = document.getElementById('name').value;
    const submitBtn = document.getElementById('submit-btn');
    
    if (submitBtn.disabled) {
        e.preventDefault();
        alert('Cannot submit form. Product name already exists.');
        return false;
    }
    
    if (price <= 0) {
        e.preventDefault();
        alert('Price must be greater than 0.');
        return false;
    }
    
    if (name.trim().length < 2) {
        e.preventDefault();
        alert('Product name must be at least 2 characters long.');
        return false;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>