<?php

session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Add New Product - Admin Dashboard";

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $available = isset($_POST['available']) ? 1 : 0;

    if (empty($name) || $price <= 0) {
        $error_message = "Product name and valid price are required.";
    } else {

        $check_sql = "SELECT id FROM products WHERE name = '$name'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "A product with the name '$name' already exists. Please choose a different name.";
        } else {

            $image_name = 'default.jpg';
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                $file_type = $_FILES['image']['type'];
                $file_size = $_FILES['image']['size'];
                
                if (in_array($file_type, $allowed_types) && $file_size <= 5242880) { // 5MB max
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $image_name = uniqid() . '.' . $file_extension;
                    $upload_path = '../images/products/' . $image_name;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {

                    } else {
                        $error_message = "Failed to upload image file.";
                    }
                } else {
                    $error_message = "Invalid file type or file too large (max 5MB).";
                }
            }
            
            if (empty($error_message)) {

                $sql = "INSERT INTO products (name, description, price, category_id, image, available) 
                        VALUES ('$name', '$description', $price, $category_id, '$image_name', $available)";
                
                if (mysqli_query($conn, $sql)) {
                    $success_message = "Product added successfully!";

                    $_POST = array();
                } else {
                    $error_message = "Error: " . mysqli_error($conn);
                }
            }
        }
    }
}

$categories = [];
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_sql);
if ($categories_result) {
    while($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container dashboard-container">
    <div class="content">
        <div class="dashboard-section">
            <div class="section-header">
                <h1><i class="fas fa-plus-circle"></i> Add New Product</h1>
                <a href="products.php" class="btn" style="background: #6c757d;">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <h4>Error!</h4>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4>Success!</h4>
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (empty($categories)): ?>
                <div class="alert alert-warning" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <h4>No Categories Found!</h4>
                        <p>Please add categories first in the database.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="auth-card" style="max-width: 800px; margin: 0 auto;">
                <form action="" method="POST" enctype="multipart/form-data" class="auth-form" id="addProductForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Left Column -->
                        <div>
                            <div class="form-group">
                                <label for="name">Product Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                       required oninput="checkProductName()">
                                <div id="name-feedback" style="display: none; margin-top: 5px;">
                                    <small class="text-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span id="name-feedback-text"></span>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">Category *</label>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Price (RM) *</label>
                                <input type="number" id="price" name="price" class="form-control" 
                                       value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" 
                                       step="0.01" min="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="available" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" id="available" name="available" value="1" 
                                           <?php echo (!isset($_POST['available']) || $_POST['available'] == 1) ? 'checked' : ''; ?>>
                                    Available for Sale
                                </label>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-control" 
                                          rows="5"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Product Image</label>
                                <input type="file" id="image" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Max file size: 5MB. Allowed types: JPG, PNG, GIF, WebP</small>
                                <div id="imagePreview" style="margin-top: 10px; display: none;">
                                    <img id="previewImage" src="#" alt="Preview" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-block" id="submit-btn">
                            <i class="fas fa-save"></i> Add Product
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="project-info" style="margin-top: 30px;">
                <h3><i class="fas fa-info-circle"></i> Quick Tips</h3>
                <ul style="list-style-type: none; padding-left: 10px;">
                    <li><i class="fas fa-check text-success"></i> Use clear, descriptive product names</li>
                    <li><i class="fas fa-check text-success"></i> Upload high-quality images (recommended: 800x600px)</li>
                    <li><i class="fas fa-check text-success"></i> Include detailed descriptions for better customer understanding</li>
                    <li><i class="fas fa-check text-success"></i> Regularly update product availability</li>
                </ul>
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

function checkProductName() {
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
        body: 'name=' + encodeURIComponent(productName)
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

document.getElementById('addProductForm').addEventListener('submit', function(e) {
    const price = document.getElementById('price').value;
    const name = document.getElementById('name').value;
    const category = document.getElementById('category_id').value;
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
    
    if (!category) {
        e.preventDefault();
        alert('Please select a category.');
        return false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>