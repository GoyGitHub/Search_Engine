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
                <a href="<?php echo $BASE_URL; ?>/src/php/index.php" class="header-nav-btn">Catalog</a>
                <a href="<?php echo $BASE_URL; ?>/src/php/cart_page.php" class="header-nav-btn">
                    Cart<?php if ($cart_count > 0): ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?>
                </a>
                <a href="<?php echo $BASE_URL; ?>/src/php/abc_generator.php" class="header-nav-btn">Budget Excel</a>
                <a href="<?php echo $BASE_URL; ?>/src/php/dashboard.php" class="header-nav-btn">Dashboard</a>
                <a href="<?php echo $BASE_URL; ?>/src/php/logout.php" class="header-nav-btn">Logout</a>
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
    <footer class="site-footer">
        <p>&copy; 2026 Procurement System. All rights reserved.</p>
    </footer>
    <?php
}
?>
