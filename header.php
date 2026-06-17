
<?php

require_once 'config.php';


$current_dir   = dirname($_SERVER['PHP_SELF']);
$is_admin_page = (strpos($current_dir, 'admin') !== false);
$current_page  = basename($_SERVER['PHP_SELF']);


$root_path  = $is_admin_page ? '../' : '';
$admin_path = $is_admin_page ? '' : 'admin/';


$cart_count   = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
$user_type    = $_SESSION['user_type'] ?? 'user';
$is_admin     = ($is_logged_in && in_array($user_type, ['admin', 'superadmin']));


if (!isset($page_title)) {
    $page_title = 'SmartWrite Stationery Store';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title><?php echo $page_title; ?></title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<?php
if ($is_admin_page) {
    echo '<link rel="stylesheet" href="../css/style.css">';
} else {
    echo '<link rel="stylesheet" href="css/style.css">';
}
?>

    
<style>


.guest-options-container{
    position:relative;
    display:inline-block;
}

.guest-options-popup{
    display:none;
    position:absolute;
    top:100%;
    right:0;
    background:#fff;
    border-radius:10px;
    box-shadow:0 5px 15px rgba(0,0,0,0.08);
    padding:15px;
    min-width:200px;
    z-index:1000;
    margin-top:10px;
}

.guest-options-popup.active{
    display:block;
}

.guest-options-popup::before{
    content:'';
    position:absolute;
    top:-10px;
    right:20px;
    border-left:10px solid transparent;
    border-right:10px solid transparent;
    border-bottom:10px solid #fff;
}


.admin-badge{
    background:#3498db;
    color:#fff;
    padding:3px 8px;
    border-radius:12px;
    font-size:0.72rem;
    font-weight:600;
    margin-left:6px;
}


.user-menu-container{
    position:relative;
    display:inline-block;
}

.user-dropdown{
    display:flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
    padding:8px 12px;
    border-radius:30px;
    background:#5dade2;
    transition:0.3s;
}

.user-dropdown:hover{
    background:#ebf5fb;
}

.user-dropdown-menu{
    display:none;
    position:absolute;
    top:100%;
    right:0;
    background:#fff;
    min-width:220px;
    border-radius:10px;
    box-shadow:0 5px 15px rgba(0,0,0,0.08);
    padding:10px 0;
    z-index:1001;
    margin-top:8px;
}

.user-menu-container:hover .user-dropdown-menu{
    display:block;
}

.user-dropdown-menu::before{
    content:'';
    position:absolute;
    top:-10px;
    right:18px;
    border-left:10px solid transparent;
    border-right:10px solid transparent;
    border-bottom:10px solid #fff;
}

.user-dropdown-menu a{
    display:block;
    padding:10px 15px;
    text-decoration:none;
    color:#555;
    font-size:0.95rem;
    transition:0.3s;
}

.user-dropdown-menu a:hover{
    background:#f8f9fa;
    color:#3498db;
}

        
        .user-dropdown-menu a i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
         .user-dropdown-menu a i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .dropdown-divider {
            height: 1px;
            background: #eee;
            margin: 5px 0;
        }
        
        
        .user-avatar {
            width: 30px;
            height: 30px;
            background: #3498db;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
    </style>
    
</head>
<body>
    <header>
        <div class="header-top">
            <div class="container">
                <div class="contact-info">
                    <span><i class="fas fa-phone"></i> +60 11 3347 3876</span>
                    <span><i class="fas fa-clock"></i> Mon-Sun: 9AM - 9PM</span>
                </div>
            </div>
        </div>
        
        <div class="main-header">
            <div class="container">
                <div class="header-content">
                    <?php

                    if ($is_admin && $is_admin_page) {
                        echo '<a href="dashboard.php" class="logo">';
                    } elseif ($is_admin_page) {
                        echo '<a href="../index.php" class="logo">';
                    } else {
                        echo '<a href="index.php" class="logo">';
                    }
                    ?>
                        <div class="logo-img">
                          <i class="fas fa-pen-fancy"></i>
                        </div>
                        <div class="logo-text">
                           <h1>SmartWrite Stationery</h1>
                           <p>Quality Stationery for Every Need</p>
                        </div>
                    </a>
                    
                    <div class="search-bar">
                        <form action="<?php echo $root_path; ?>search.php" method="GET">
                            <input type="text" name="q" placeholder="Search pens, notebooks, art supplies...">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                    
                    <div class="user-actions">
                        <div class="cart-icon">
                            <?php if (!$is_admin || !$is_admin_page): ?>
                                <?php
                                if ($is_admin_page) {
                                    echo '<a href="../cart.php">';
                                } else {
                                    echo '<a href="cart.php">';
                                }
                                ?>
                                    <i class="fas fa-shopping-cart"></i>
                                    <?php if ($cart_count > 0): ?>
                                        <span class="cart-count"><?php echo $cart_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($is_logged_in): ?>
                            <div class="user-menu-container">
                                <div class="user-dropdown">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                    </div>
                                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                    <?php if ($is_admin): ?>
                                        <span class="admin-badge">ADMIN</span>
                                    <?php endif; ?>
                                    <i class="fas fa-chevron-down" style="font-size: 0.8rem;"></i>
                                </div>
                                
                                <div class="user-dropdown-menu">
                                    <?php if ($is_admin): ?>
                                        <a href="<?php echo $admin_path; ?>dashboard.php">
                                            <i class="fas fa-chart-line"></i> Admin Dashboard
                                        </a>
                                        <a href="<?php echo $root_path; ?>profile.php">
                                            <i class="fas fa-user-cog"></i> Admin Profile
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a href="<?php echo $admin_path; ?>products.php">
                                            <i class="fas fa-hamburger"></i> Manage Products
                                        </a>
                                        <a href="<?php echo $admin_path; ?>manage-orders.php">
                                            <i class="fas fa-shopping-bag"></i> Manage Orders
                                        </a>
                                        <a href="<?php echo $admin_path; ?>manage-users.php">
                                            <i class="fas fa-users"></i> Manage Users
                                        </a>
                                        <a href="<?php echo $admin_path; ?>manage-contacts.php">
                                            <i class="fas fa-envelope"></i> Manage Messages
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a href="<?php echo $root_path; ?>dashboard.php">
                                            <i class="fas fa-user"></i> User Dashboard
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo $root_path; ?>dashboard.php">
                                            <i class="fas fa-user"></i> My Dashboard
                                        </a>
                                        <a href="<?php echo $root_path; ?>profile.php">
                                            <i class="fas fa-user-edit"></i> My Profile
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a href="<?php echo $root_path; ?>orders.php">
                                            <i class="fas fa-shopping-bag"></i> My Orders
                                        </a>
                                        <a href="<?php echo $root_path; ?>my-messages.php">
                                            <i class="fas fa-envelope"></i> My Messages
                                        </a>
                                    <?php endif; ?>
                                    
                                    <div class="dropdown-divider"></div>
                                    <?php
                                    if ($is_admin_page) {
                                        echo '<a href="../logout.php">';
                                    } else {
                                        echo '<a href="logout.php">';
                                    }
                                    ?>
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </div>
                            </div>
                            
                       
<?php else: ?>

<div class="guest-options-container">

    <button class="login-btn" id="guestButton">
        <i class="fas fa-user"></i>
        Guest
    </button>

    <div class="guest-options-popup" id="guestOptions">

        <div style="text-align:center; margin-bottom:12px;">

            <i class="fas fa-user-circle"
               style="font-size:2rem;
               color:#3498db;
               margin-bottom:10px;">
            </i>

            <p style="margin:0; font-weight:700;">
                Guest User
            </p>

            <p style="
                margin:6px 0 15px;
                color:#666;
                font-size:0.9rem;">
                Sign in for a better shopping experience
            </p>

        </div>

        <?php if ($is_admin_page): ?>

            <a href="../login.php"
               class="btn"
               style="
               display:block;
               text-align:center;
               margin-bottom:10px;
               background:#3498db;">

                <i class="fas fa-sign-in-alt"></i>
                Login
            </a>

            <a href="../register.php"
               class="btn"
               style="
               display:block;
               text-align:center;
               background:#6c757d;">

                <i class="fas fa-user-plus"></i>
                Register
            </a>

        <?php else: ?>

            <a href="login.php"
               class="btn"
               style="
               display:block;
               text-align:center;
               margin-bottom:10px;
               background:#3498db;">

                <i class="fas fa-sign-in-alt"></i>
                Login
            </a>

            <a href="register.php"
               class="btn"
               style="
               display:block;
               text-align:center;
               background:#6c757d;">

                <i class="fas fa-user-plus"></i>
                Register
            </a>

        <?php endif; ?>

        <p style="
            text-align:center;
            margin-top:12px;
            font-size:0.8rem;
            color:#666;">

            Or continue browsing as guest

        </p>

    </div>

</div>

<?php endif; ?>

</div>
</div>
</div>
</div>
        
        <nav>
            <div class="container nav-container">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="nav-links" id="navLinks">
                    <?php

                    function createNavLink($page, $title, $icon, $current_page, $is_admin_page, $is_admin = false) {
                        $is_active = ($current_page == $page) ? 'active' : '';
                        
                        if ($is_admin && $is_admin_page) {

                            switch($title) {
                                case 'Home': $page = 'dashboard.php'; break;
                                case 'Menu': $page = 'products.php'; break;
                                case 'About Us': $page = 'manage-users.php'; break;
                                case 'Contact': $page = 'manage-contacts.php'; break;
                                case 'Track Order': $page = 'profile.php'; break;
                                case 'Admin Profile': $page = 'profile.php'; break;
                            }
                            echo '<li><a href="' . $page . '" class="' . $is_active . '"><i class="' . $icon . '"></i> ' . $title . '</a></li>';
                        } else {

                            if ($is_admin_page) {
                                echo '<li><a href="../' . $page . '" class="' . $is_active . '"><i class="' . $icon . '"></i> ' . $title . '</a></li>';
                            } else {
                                echo '<li><a href="' . $page . '" class="' . $is_active . '"><i class="' . $icon . '"></i> ' . $title . '</a></li>';
                            }
                        }
                    }
                    
                    if ($is_admin && $is_admin_page) {
                        createNavLink('dashboard.php', 'Dashboard', 'fas fa-chart-line', $current_page, $is_admin_page, $is_admin);
                        createNavLink('products.php', 'Products', 'fas fa-pen-fancy', $current_page, $is_admin_page, $is_admin);
                        createNavLink('manage-orders.php', 'Orders', 'fas fa-shopping-bag', $current_page, $is_admin_page, $is_admin);
                        createNavLink('manage-users.php', 'Users', 'fas fa-users', $current_page, $is_admin_page, $is_admin);
                        createNavLink('manage-contacts.php', 'Messages', 'fas fa-envelope', $current_page, $is_admin_page, $is_admin);
                    } else {
                        createNavLink('index.php', 'Home', 'fas fa-home', $current_page, $is_admin_page);
                        createNavLink('menu.php', 'Products', 'fas fa-pen-fancy', $current_page, $is_admin_page);
                        createNavLink('about.php', 'About Us', 'fas fa-info-circle', $current_page, $is_admin_page);

                        if ($is_logged_in && !$is_admin) {
                            createNavLink('contact.php', 'Contact', 'fas fa-envelope', $current_page, $is_admin_page);
                        }

                        if ($is_logged_in && !$is_admin) {
                            createNavLink('track.php', 'Track Order', 'fas fa-map-marker-alt', $current_page, $is_admin_page);
                        }

                        if ($is_logged_in && !$is_admin) {
                            $msg_link = $is_admin_page ? '../my-messages.php' : 'my-messages.php';

                            $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'messages'");
                            if (mysqli_num_rows($check_table) > 0) {
                                $user_id_safe = intval($_SESSION['user_id']);
                                $msg_sql = "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = $user_id_safe AND is_read = 0";
                                $msg_res = mysqli_query($conn, $msg_sql);
                                $unread_count = 0;
                                if ($msg_res) {
                                    $row = mysqli_fetch_assoc($msg_res);
                                    $unread_count = $row['unread'];
                                }
                                
                                $badge = $unread_count > 0 ? " <span style='background:#ff6b6b; color:white; padding:2px 6px; border-radius:10px; font-size:0.7em;'>$unread_count</span>" : "";
                                echo "<li><a href='$msg_link' " . ($current_page == 'my-messages.php' ? 'class="active"' : '') . "><i class='fas fa-envelope'></i> Messages$badge</a></li>";
                            }
                        }
                    }
                    ?>
                </ul>
                
                <div class="nav-cta">
                    <?php
                    if ($is_admin && $is_admin_page) {
                        echo '<a href="../index.php" class="order-now-btn">';
                        echo '<i class="fas fa-home"></i> View Site</a>';
                    } else {
                        if ($is_admin_page) {
                            echo '<a href="../menu.php" class="order-now-btn">';
                        } else {
                            echo '<a href="menu.php" class="order-now-btn">';
                        }
                        echo '<i class="fas fa-bolt"></i> Shop Now</a>';
                    }
                    ?>
                </div>
            </div>
        </nav>
    </header>

    <div class="main-wrapper <?php echo (!isset($hide_sidebar) || $hide_sidebar === false) ? 'with-sidebar' : 'without-sidebar'; ?>">
        <?php 
        if (!isset($hide_sidebar) || $hide_sidebar === false): 
            if ($is_admin_page) {

                if (file_exists('../includes/sidebar.php')) include '../includes/sidebar.php';
            } else {
                if (file_exists('includes/sidebar.php')) include 'includes/sidebar.php';
            }
        endif; 
        ?>
        
        <main class="content">





