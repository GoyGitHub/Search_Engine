<?php
require_once __DIR__ . '/auth.php';
require_login();

require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/components.php';

$BASE_URL = dirname($_SERVER['SCRIPT_NAME'], 3);
if ($BASE_URL === DIRECTORY_SEPARATOR) {
    $BASE_URL = '';
}

$currentUser = current_user();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    if ($action === 'remove') {
        $item_key = $_POST['item_key'] ?? null;
        if ($item_key) {
            remove_from_cart($item_key);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Item removed from cart.'];
        }
    } elseif ($action === 'update_quantity') {
        $item_key = $_POST['item_key'] ?? null;
        $quantity = intval($_POST['quantity'] ?? 1);
        if ($item_key) {
            update_cart_quantity($item_key, $quantity);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Quantity updated.'];
        }
    } elseif ($action === 'clear') {
        clear_cart();
        $_SESSION['message'] = ['type' => 'info', 'text' => 'Cart has been cleared.'];
    }
    
    header('Location: cart_page.php');
    exit;
}

$cart = get_cart();
$cart_stats = get_cart_stats();
$abc_classification = get_abc_classification($cart);

// Ensure proper layout for cart items
function render_cart_items($cart) {
    $output = '';
    foreach ($cart as $key => $item_data) {
        $item = $item_data['item'];
        $quantity = $item_data['quantity'];
        $variation = $item_data['variation'] ?? [];
        $output .= '<div class="cart-item">';
        $output .= '<div class="item-details">';
        $output .= '<h4>' . htmlspecialchars($item['item_name']) . '</h4>';
        $output .= '<p>Category: ' . htmlspecialchars($item['category']) . '</p>';
        $output .= '<p>Brand: ' . htmlspecialchars($item['brand']) . '</p>';
        if (!empty($variation)) {
            $output .= '<p>Variation: ' . htmlspecialchars(json_encode($variation)) . '</p>';
        }
        $output .= '<p>Quantity: ' . $quantity . '</p>';
        $output .= '<p>Price: ' . formatCurrency($item['unit_cost']) . '</p>';
        $output .= '</div>';
        $output .= '<div class="item-actions">';
        $output .= '<form method="POST">';
        $output .= '<input type="hidden" name="action" value="remove">';
        $output .= '<input type="hidden" name="item_key" value="' . htmlspecialchars($key) . '">';
        $output .= '<button type="submit" class="button danger">Remove</button>';
        $output .= '</form>';
        $output .= '</div>';
        $output .= '</div>';
    }
    return $output;
}

// Render cart items
$cart_items_html = render_cart_items($cart);

function formatCurrency($value)
{
    if (is_numeric($value) && $value !== '') {
        return '₱ ' . number_format((float)$value, 2);
    }
    return htmlspecialchars((string)$value);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Procurement System</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
    <script src="<?php echo $BASE_URL; ?>/static/accessibility.js" defer></script>
</head>
<body>
    <?php render_header($BASE_URL, $currentUser, "Shopping Cart"); ?>

    <main class="page-enter">
        <div class="container glass">
            <div class="page-actions">
                <a href="index.php" class="button secondary icon-btn"><span>⬅</span> Back to Catalog</a>
            </div>

            <section class="hero-grid">
                <div class="hero-copy">
                    <h1>🛒 Shopping Cart</h1>
                    <p class="subtitle">Review and manage your selected items below.</p>
                </div>
            </section>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert <?php echo $_SESSION['message']['type']; ?>">
                    <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <div class="results-info">
                <strong><?php echo $cart_stats['item_count']; ?></strong> item(s) in cart
                | <strong><?php echo $cart_stats['total_items']; ?></strong> total quantity
                | <strong><?php echo formatCurrency($cart_stats['total_cost']); ?></strong> total cost
            </div>

            <?php if (!empty($cart)): ?>
                <div class="cart-container">
                    <!-- Categorized Items Section -->
                    <section class="cart-section">
                        <h2>Items by Category</h2>
                        <?php
                        $items_by_category = [];
                        foreach ($cart as $item_key => $item_data) {
                            $category = $item_data['item']['category'] ?? 'Uncategorized';
                            if (!isset($items_by_category[$category])) {
                                $items_by_category[$category] = [];
                            }
                            $items_by_category[$category][$item_key] = $item_data;
                        }
                        
                        foreach ($items_by_category as $category => $items): ?>
                            <div class="category-group">
                                <h3><?php echo htmlspecialchars($category); ?></h3>
                                <div class="cart-items-table">
                                    <?php foreach ($items as $item_key => $item_data): 
                                        $item = $item_data['item'];
                                        $variation = $item_data['variation'];
                                        $quantity = $item_data['quantity'];
                                        $unit_cost = floatval($item['unit_cost'] ?? 0);
                                        $item_total = $unit_cost * $quantity;
                                        ?>
                                        <div class="cart-item">
                                            <div class="item-info">
                                                <div class="item-header">
                                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                                    <?php if ($variation): ?>
                                                        <span class="variation-tag"><?php echo htmlspecialchars($variation['name']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="item-meta">
                                                    <span class="item-detail">Brand: <?php echo htmlspecialchars($item['brand'] ?? 'Unknown'); ?></span>
                                                    <span class="item-detail">Unit: <?php echo htmlspecialchars($item['unit'] ?? 'Each'); ?></span>
                                                    <span class="item-detail">Unit Cost: <?php echo formatCurrency($item['unit_cost']); ?></span>
                                                </div>
                                            </div>
                                            <div class="item-controls">
                                                <form method="POST" class="quantity-form">
                                                    <input type="hidden" name="action" value="update_quantity">
                                                    <input type="hidden" name="item_key" value="<?php echo htmlspecialchars($item_key); ?>">
                                                    <div class="quantity-control">
                                                        <label for="qty-<?php echo htmlspecialchars($item_key); ?>">Qty:</label>
                                                        <input type="number" id="qty-<?php echo htmlspecialchars($item_key); ?>" name="quantity" min="1" value="<?php echo $quantity; ?>" onchange="this.form.submit()">
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="item-price">
                                                <span class="total"><?php echo formatCurrency($item_total); ?></span>
                                            </div>
                                            <form method="POST" class="remove-form">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="item_key" value="<?php echo htmlspecialchars($item_key); ?>">
                                                <button type="submit" class="button danger sm">Remove</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <!-- ABC Classification Section -->
                    <section class="cart-section">
                        <h2>ABC Classification</h2>
                        <div class="abc-grid">
                            <?php 
                            $abc_labels = [
                                'A' => ['label' => 'Always', 'desc' => 'High priority items (80% of cost)', 'class' => 'abc-a'],
                                'B' => ['label' => 'Better', 'desc' => 'Medium priority items (15% of cost)', 'class' => 'abc-b'],
                                'C' => ['label' => 'Critical', 'desc' => 'Low priority items (5% of cost)', 'class' => 'abc-c'],
                            ];
                            
                            foreach (['A', 'B', 'C'] as $class): ?>
                                <div class="abc-card <?php echo $abc_labels[$class]['class']; ?>">
                                    <h3><?php echo $abc_labels[$class]['label']; ?> - <?php echo $class; ?></h3>
                                    <p class="desc"><?php echo $abc_labels[$class]['desc']; ?></p>
                                    <div class="items-list">
                                        <?php if (!empty($abc_classification[$class])): ?>
                                            <?php foreach ($abc_classification[$class] as $item_data): ?>
                                                <div class="abc-item">
                                                    <span class="name"><?php echo htmlspecialchars($item_data['name']); ?></span>
                                                    <span class="cost"><?php echo formatCurrency($item_data['total']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="empty">No items in this category</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Cart Actions -->
                    <section class="cart-actions">
                        <div class="summary">
                            <div class="summary-row">
                                <span>Total Items:</span>
                                <strong><?php echo $cart_stats['total_items']; ?></strong>
                            </div>
                            <div class="summary-row">
                                <span>Total Cost:</span>
                                <strong class="price"><?php echo formatCurrency($cart_stats['total_cost']); ?></strong>
                            </div>
                            <div class="summary-row">
                                <span>Categories:</span>
                                <strong><?php echo count($cart_stats['categories']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="actions-buttons">
                            <a href="abc_generator.php" class="button primary"><span>📄</span> Generate ABC Document</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="button danger" onclick="return confirm('Are you sure you want to clear the cart?');">Clear Cart</button>
                            </form>
                            <a href="index.php" class="button secondary">Continue Shopping</a>
                        </div>
                    </section>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Your cart is empty. <a href="index.php">Start adding items!</a></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php render_footer($BASE_URL); ?>
</body>
</html>
