<?php

session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$page_title = "Manage Products";
$show_sidebar = true;
$current_page = 'admin/products.php';

$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   ORDER BY p.id DESC";
$products_result = mysqli_query($conn, $products_query);

require_once '../includes/header.php';
?>

<style>
    .admin-page {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #eee;
    }
    
    .page-header h1 {
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-add {
        background: linear-gradient(135deg, #ff6b6b 0%, #ff8e53 100%);
        color: white;
        padding: 12px 25px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        text-decoration: none;
        color: white;
    }
    
    .products-table-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border: 1px solid #f0f0f0;
    }
    
    .products-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .products-table th {
        background: #f8f9fa;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #eee;
    }
    
    .products-table td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }
    
    .products-table tr:hover {
        background: #f9f9f9;
    }
    
    .product-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 1.5rem;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .available-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .available-true {
        background: #d4edda;
        color: #155724;
    }
    
    .available-false {
        background: #f8d7da;
        color: #721c24;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-edit, .btn-delete {
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
    }
    
    .btn-edit {
        background: #fff3cd;
        color: #856404;
    }
    
    .btn-edit:hover {
        background: #ffeaa7;
        color: #856404;
        transform: translateY(-2px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        text-decoration: none;
    }
    
    .btn-delete {
        background: #f8d7da;
        color: #721c24;
    }
    
    .btn-delete:hover {
        background: #f5c6cb;
        color: #721c24;
        transform: translateY(-2px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        text-decoration: none;
    }
    
    .no-products {
        text-align: center;
        padding: 60px 20px;
    }
    
    .no-products i {
        color: #ddd;
        margin-bottom: 20px;
        display: block;
        font-size: 4rem;
    }
    
    .no-products h3 {
        color: #666;
        margin-bottom: 10px;
    }
    
    .no-products p {
        color: #888;
        margin-bottom: 20px;
    }
    
    
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease-out;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .alert i {
        font-size: 1.2rem;
    }
    
    @keyframes slideIn {
        from {
            transform: translateY(-10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.85rem;
    }
    
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    
    .status-active {
        color: #28a745;
    }
    
    .status-inactive {
        color: #6c757d;
    }
    
    .status-dot-active {
        background-color: #28a745;
    }
    
    .status-dot-inactive {
        background-color: #6c757d;
    }
    
    
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .stat-icon.products {
        background: rgba(255, 107, 107, 0.1);
        color: #ff6b6b;
    }
    
    .stat-icon.available {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .stat-icon.unavailable {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .stat-info h3 {
        margin: 0;
        font-size: 1.8rem;
        color: #333;
    }
    
    .stat-info p {
        margin: 5px 0 0;
        color: #666;
        font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
        .products-table-container {
            padding: 20px;
            overflow-x: auto;
        }
        
        .products-table {
            min-width: 800px;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .btn-add {
            width: 100%;
            justify-content: center;
        }
        
        .action-buttons {
            flex-direction: column;
            gap: 5px;
        }
        
        .btn-edit, .btn-delete {
            justify-content: center;
        }
        
        .quick-stats {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .admin-page {
            padding: 10px;
        }
        
        .products-table-container {
            padding: 15px;
        }
    }
</style>

<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-hamburger"></i> Manage Products</h1>
        <a href="add-product.php" class="btn-add">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>
    
    <?php 

    $total_products = mysqli_num_rows($products_result);
    $available_products = 0;
    $unavailable_products = 0;

    mysqli_data_seek($products_result, 0);
    while($product = mysqli_fetch_assoc($products_result)) {
        if ($product['available'] == 1) {
            $available_products++;
        } else {
            $unavailable_products++;
        }
    }

    mysqli_data_seek($products_result, 0);
    ?>
    
    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="stat-card">
            <div class="stat-icon products">
                <i class="fas fa-hamburger"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_products; ?></h3>
                <p>Total Products</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon available">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $available_products; ?></h3>
                <p>Available Products</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon unavailable">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $unavailable_products; ?></h3>
                <p>Unavailable Products</p>
            </div>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php 
                echo $message_type === 'success' ? 'check-circle' : 
                    ($message_type === 'danger' ? 'exclamation-circle' : 
                    ($message_type === 'warning' ? 'exclamation-triangle' : 'info-circle')); 
            ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="products-table-container">
        <?php if (mysqli_num_rows($products_result) > 0): ?>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo $product['id']; ?></strong>
                            </td>
                            <td>
                                <div class="product-image">
                                    <?php if ($product['image'] && $product['image'] !== 'default.jpg' && file_exists("../images/products/" . $product['image'])): ?>
                                        <img src="../images/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-hamburger"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                <small style="color: #666;">
                                    <?php 
                                    $description = $product['description'];
                                    echo strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description;
                                    ?>
                                </small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                            </td>
                            <td>
                                <strong>RM<?php echo number_format($product['price'], 2); ?></strong>
                            </td>
                            <td>
                                <span class="available-badge available-<?php echo $product['available'] ? 'true' : 'false'; ?>">
                                    <?php echo $product['available'] ? 'Available' : 'Out of Stock'; ?>
                                </span>
                                <?php if ($product['category_name'] && isset($product['category_active']) && $product['category_active'] == 0): ?>
                                    <br>
                                    <small class="status-indicator status-inactive">
                                        <span class="status-dot status-dot-inactive"></span>
                                        Category Inactive
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete-product.php?id=<?php echo $product['id']; ?>" 
                                       onclick="return confirmDelete('<?php echo addslashes($product['name']); ?>')" 
                                       class="btn-delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-products">
                <i class="fas fa-hamburger"></i>
                <h3>No products found</h3>
                <p>Start by adding your first product to the menu</p>
                <a href="add-product.php" class="btn-add" style="display: inline-flex;">
                    <i class="fas fa-plus"></i> Add Your First Product
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>

function confirmDelete(productName) {
    return confirm('Are you sure you want to delete "' + productName + '"?\n\nThis action cannot be undone.');
}

document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.btn-delete');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const productName = this.closest('tr').querySelector('strong').textContent;
            if (!confirm('Are you sure you want to delete "' + productName + '"?\n\nThis action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    const tableRows = document.querySelectorAll('.products-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = (index * 0.05) + 's';
        row.classList.add('fade-in');
    });

    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, 300);
        }, 5000);
    }
});

const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-in {
        animation: fadeIn 0.3s ease-out forwards;
        opacity: 0;
    }
    
    .products-table tbody tr {
        transition: background-color 0.2s;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    
    .stat-card:hover {
        animation: pulse 2s infinite;
    }
`;
document.head.appendChild(style);
</script>

<?php require_once '../includes/footer.php'; ?>