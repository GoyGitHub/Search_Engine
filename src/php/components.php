<?php
// Header component - include this at the top of every page

function render_header($BASE_URL, $currentUser, $pageTitle = "")
{
    // Get cart stats for header display
    require_once __DIR__ . '/cart.php';
    $cart_stats = get_cart_stats();
    $cart_count = $cart_stats['item_count'];
    
    $display_title = $pageTitle ? htmlspecialchars($pageTitle) : "Excel Item Catalog";
    ?>
    <header class="site-header glass">
        <div class="header-inner">
            <a href="<?php echo $BASE_URL; ?>/src/php/index.php" class="brand">
                <img src="<?php echo $BASE_URL; ?>/static/img/logo_montalban.png" alt="Company Logo" class="brand-logo">
                <div class="brand-text">
                    <span class="brand-title"><?php echo $display_title; ?></span>
                    <span class="brand-subtitle">Procurement System</span>
                </div>
            </a>
            <nav class="header-actions">
                <a href="<?php echo $BASE_URL; ?>/src/php/index.php" class="nav-link">
                    <span>📋</span> Catalog
                </a>
                <a href="<?php echo $BASE_URL; ?>/src/php/cart_page.php" class="nav-link cart-link">
                    <span>🛒</span> Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo $BASE_URL; ?>/src/php/abc_generator.php" class="nav-link">
                    <span>📊</span> ABC Documents
                </a>
                <a href="<?php echo $BASE_URL; ?>/src/php/dashboard.php" class="nav-link">
                    <span>📈</span> Dashboard
                </a>
                <a href="<?php echo $BASE_URL; ?>/src/php/logout.php" class="button ghost">Logout</a>
                <span class="header-user">
                    <span><?php echo htmlspecialchars(ucwords(strtolower($currentUser['username'] ?? 'User'))); ?></span>
                </span>
            </nav>
        </div>
    </header>
    <?php
}

function render_footer($BASE_URL)
{
    ?>
    <footer class="site-footer glass">
        <div class="footer-inner">
            <div class="footer-section">
                <h4>About</h4>
                <p>Procurement catalog system for efficient item management and ordering.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo $BASE_URL; ?>/src/php/index.php">Catalog</a></li>
                    <li><a href="<?php echo $BASE_URL; ?>/src/php/cart_page.php">Cart</a></li>
                    <li><a href="<?php echo $BASE_URL; ?>/src/php/abc_generator.php">ABC Documents</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Help</h4>
                <ul>
                    <li><a href="<?php echo $BASE_URL; ?>/src/php/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo $BASE_URL; ?>/src/php/logout.php">Logout</a></li>
                </ul>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Procurement System. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <?php
}
?>
