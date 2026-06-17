<!-- ====== SIDEBAR ====== -->
<aside class="sidebar">
    <style>
        .sidebar-submenu { margin-top: -8px; margin-bottom: 6px; padding-left: 14px; }
        .sidebar-submenu a { font-size: 0.88rem; padding: 8px 12px 8px 28px; opacity: 0.9; }
        .sidebar-submenu i { font-size: 0.48rem; }
    </style>
    <div class="sidebar-header">
        <h3><i class="fas fa-bars"></i> Quick Links</h3>
    </div>
    
    <ul class="sidebar-menu">
        <?php 
        $current_dir = dirname($_SERVER['PHP_SELF']);
        $is_admin_page = (strpos($current_dir, 'admin') !== false);
        
        if($is_logged_in): ?>

            <?php if(in_array($_SESSION['user_type'], ['admin', 'superadmin']) && $is_admin_page): ?>
                
                <li>
                    <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-house"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="manage-orders.php" class="<?php echo $current_page == 'manage-orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box-open"></i> <span>Orders</span>
                    </a>
                </li>
                <li class="sidebar-submenu">
                    <a href="manage-orders.php?status=pending"><i class="fas fa-circle" style="color:#f59e0b;"></i> <span>Pending</span></a>
                    <a href="manage-orders.php?status=processing"><i class="fas fa-circle" style="color:#3b82f6;"></i> <span>Processing</span></a>
                    <a href="manage-orders.php?status=completed"><i class="fas fa-circle" style="color:#22c55e;"></i> <span>Completed</span></a>
                    <a href="manage-orders.php?status=cancelled"><i class="fas fa-circle" style="color:#ef4444;"></i> <span>Cancelled</span></a>
                </li>
                <li>
                    <a href="products.php" class="<?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cart-shopping"></i> <span>Products</span>
                    </a>
                </li>
                <li>
                    <a href="manage-categories.php" class="<?php echo $current_page == 'manage-categories.php' ? 'active' : ''; ?>">
                        <i class="fas fa-folder-open"></i> <span>Categories</span>
                    </a>
                </li>
                <li>
                    <a href="manage-users.php" class="<?php echo $current_page == 'manage-users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="revenue-report.php" class="<?php echo $current_page == 'revenue-report.php' ? 'active' : ''; ?>">
                        <i class="fas fa-coins"></i> <span>Revenue Analytics</span>
                    </a>
                </li>
                <li>
                    <a href="manage-orders.php?status=cancelled">
                        <i class="fas fa-rotate-left"></i> <span>Refund Requests</span>
                    </a>
                </li>
                <li>
                    <a href="../profile.php">
                        <i class="fas fa-gear"></i> <span>Settings</span>
                    </a>
                </li>

            <?php else: ?>
                
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-chart-line"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="orders.php">
                        <i class="fas fa-history"></i> <span>Order History</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user-cog"></i> <span>Profile</span>
                    </a>
                </li>

            <?php endif; ?>

        <?php else: ?>
            
            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> <span>Login</span></a></li>
            <li><a href="register.php"><i class="fas fa-user-plus"></i> <span>Register</span></a></li>
            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> <span>View Cart</span></a></li>

        <?php endif; ?>
    </ul>
    
    <?php if(!($is_logged_in && in_array($_SESSION['user_type'], ['admin', 'superadmin']) && $is_admin_page)): ?>

    <div class="sidebar-divider"></div>
    
    <!-- CATEGORIES -->
    <div class="sidebar-header">
        <h3><i class="fas fa-filter"></i> Categories</h3>
    </div>
    
    <ul class="sidebar-menu">
        <?php
        $categories = [];
        $categories_sql = "SELECT * FROM categories ORDER BY name";
        $categories_result = mysqli_query($conn, $categories_sql);

        if ($categories_result && mysqli_num_rows($categories_result) > 0) {
            while($category = mysqli_fetch_assoc($categories_result)) {
                $categories[] = $category;
            }
        }

        if (empty($categories)) {
            $categories = [
                ['id' => 1, 'name' => 'Notebooks'],
                ['id' => 2, 'name' => 'Pens'],
                ['id' => 3, 'name' => 'Office Supplies'],
                ['id' => 4, 'name' => 'Accessories']
            ];
        }

        foreach ($categories as $category):
            $cat_url_name = strtolower(str_replace(' ', '', $category['name']));
            $menu_link = $is_admin_page ? '../menu.php' : 'menu.php';
        ?>
        <li>
            <a href="<?php echo $menu_link; ?>?category=<?php echo urlencode($cat_url_name); ?>">
                <i class="fas fa-chevron-right"></i> 
                <span><?php echo htmlspecialchars($category['name']); ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-divider"></div>

    <!-- POPULAR PRODUCTS -->
    <div class="sidebar-header">
        <h3><i class="fas fa-star"></i> Popular Products</h3>
    </div>

    <ul class="sidebar-menu">
        <?php
        $popular_sql = "SELECT p.id, p.name, COUNT(oi.product_id) as order_count
                        FROM products p 
                        LEFT JOIN order_items oi ON p.id = oi.product_id
                        WHERE p.available = 1
                        GROUP BY p.id
                        ORDER BY order_count DESC
                        LIMIT 4";
        $popular_result = mysqli_query($conn, $popular_sql);

        if ($popular_result && mysqli_num_rows($popular_result) > 0) {
            while($product = mysqli_fetch_assoc($popular_result)):
                $product_link = $is_admin_page ? '../product-details.php' : 'product-details.php';
        ?>
        <li>
            <a href="<?php echo $product_link; ?>?id=<?php echo $product['id']; ?>">
                <i class="fas fa-star" style="color:#4CAF50;"></i> 
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </a>
        </li>
        <?php endwhile; } else { ?>

        <!-- fallback（） -->
        <li><a href="menu.php"><i class="fas fa-star"></i> Notebook</a></li>
        <li><a href="menu.php"><i class="fas fa-star"></i> Pen</a></li>
        <li><a href="menu.php"><i class="fas fa-star"></i> Stapler</a></li>
        <li><a href="menu.php"><i class="fas fa-star"></i> Pencil Case</a></li>

        <?php } ?>
    </ul>
    <?php endif; ?>
</aside>