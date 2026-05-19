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
$cart = get_cart();
$cart_stats = get_cart_stats();
$abc_classification = get_abc_classification($cart);

// Handle document generation
$generated_doc = null;
$doc_name = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $abc_type = $_POST['abc_type'] ?? 'ABC_2';
    $doc_title = $_POST['doc_title'] ?? 'Procurement Request';
    
    if (empty($cart)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Cart is empty. Add items first.'];
    } else {
        $generated_doc = generate_abc_document($cart, $abc_type, $doc_title);
        $doc_name = $doc_title . '_' . date('Y-m-d_Hi') . '.xlsx';
        $_SESSION['message'] = ['type' => 'success', 'text' => 'ABC Document generated successfully!'];
    }
}

function generate_abc_document($cart, $abc_type, $title)
{
    // Create a structured document data
    $abc_classification = get_abc_classification($cart);
    
    $doc = [
        'title' => $title,
        'type' => $abc_type,
        'generated_date' => date('Y-m-d H:i:s'),
        'total_items' => 0,
        'total_cost' => 0,
        'items' => [],
        'classifications' => $abc_classification,
    ];
    
    // Compile items
    foreach ($cart as $item_data) {
        $item = $item_data['item'];
        $quantity = $item_data['quantity'];
        $unit_cost = floatval($item['unit_cost'] ?? 0);
        $total = $unit_cost * $quantity;
        
        $doc['items'][] = [
            'name' => $item['item_name'],
            'category' => $item['category'],
            'brand' => $item['brand'],
            'model' => $item['model'],
            'unit' => $item['unit'],
            'unit_cost' => $unit_cost,
            'quantity' => $quantity,
            'total' => $total,
        ];
        
        $doc['total_items'] += $quantity;
        $doc['total_cost'] += $total;
    }
    
    return $doc;
}

function formatCurrency($value)
{
    if (is_numeric($value) && $value !== '') {
        return '₱ ' . number_format((float)$value, 2);
    }
    return htmlspecialchars((string)$value);
}

function getAbcTypeLabel($type)
{
    $labels = [
        'ABC_2' => 'ABC 2 - Standard Procurement',
        'ABC_3' => 'ABC 3 - Detailed Procurement with Specs',
        'ABC_4' => 'ABC 4 - Advanced with Categorization',
    ];
    return $labels[$type] ?? $type;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABC Document Generator - Procurement System</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
    <script src="<?php echo $BASE_URL; ?>/static/accessibility.js" defer></script>
</head>
<body>
    <?php render_header($BASE_URL, $currentUser, "ABC Document Generator"); ?>

    <main class="page-enter">
        <div class="container glass">
            <div class="page-actions">
                <a href="cart_page.php" class="button secondary icon-btn"><span>⬅</span> Back to Cart</a>
            </div>

            <section class="hero-grid">
                <div class="hero-copy">
                    <h1>📊 ABC Document Generator</h1>
                    <p class="subtitle">Create procurement documents based on your cart items and ABC classification.</p>
                </div>
            </section>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert <?php echo $_SESSION['message']['type']; ?>">
                    <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <?php if (!empty($cart)): ?>
                <div class="abc-generator-container">
                    <!-- Generation Form -->
                    <section class="gen-form-section">
                        <h2>Generate Document</h2>
                        <form method="POST" class="generation-form">
                            <div class="form-group">
                                <label for="doc_title">Document Title</label>
                                <input type="text" id="doc_title" name="doc_title" 
                                       value="Procurement Request - <?php echo date('Y-m-d'); ?>" 
                                       class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label for="abc_type">Document Type</label>
                                <select id="abc_type" name="abc_type" class="form-input" required>
                                    <option value="ABC_2">ABC 2 - Standard (Items, Qty, Cost)</option>
                                    <option value="ABC_3">ABC 3 - Detailed (Includes Brand & Model)</option>
                                    <option value="ABC_4">ABC 4 - Advanced (With Classification)</option>
                                </select>
                            </div>

                            <div class="form-info">
                                <strong>Cart Summary:</strong>
                                <ul>
                                    <li>Total Items: <strong><?php echo $cart_stats['item_count']; ?></strong></li>
                                    <li>Total Quantity: <strong><?php echo $cart_stats['total_items']; ?></strong></li>
                                    <li>Total Cost: <strong><?php echo formatCurrency($cart_stats['total_cost']); ?></strong></li>
                                    <li>Categories: <strong><?php echo count($cart_stats['categories']); ?></strong></li>
                                </ul>
                            </div>

                            <button type="submit" name="generate" class="button primary" value="1">
                                <span>📄</span> Generate Document
                            </button>
                        </form>
                    </section>

                    <!-- Preview Section -->
                    <section class="gen-preview-section">
                        <h2>Cart Overview</h2>
                        
                        <!-- Items Table -->
                        <div class="preview-subsection">
                            <h3>Items Summary</h3>
                            <table class="preview-table">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Unit</th>
                                        <th>Unit Cost</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart as $item_data): 
                                        $item = $item_data['item'];
                                        $quantity = $item_data['quantity'];
                                        $unit_cost = floatval($item['unit_cost'] ?? 0);
                                        $total = $unit_cost * $quantity;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td><?php echo htmlspecialchars($item['brand']); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td class="num"><?php echo formatCurrency($unit_cost); ?></td>
                                            <td class="num"><?php echo $quantity; ?></td>
                                            <td class="num"><strong><?php echo formatCurrency($total); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- ABC Classification -->
                        <div class="preview-subsection">
                            <h3>ABC Classification</h3>
                            <div class="abc-grid-compact">
                                <?php 
                                $abc_labels = [
                                    'A' => 'Always (80% - High Priority)',
                                    'B' => 'Better (15% - Medium Priority)',
                                    'C' => 'Critical (5% - Low Priority)',
                                ];
                                
                                foreach (['A', 'B', 'C'] as $class): 
                                    $count = count($abc_classification[$class]);
                                    $total = array_sum(array_column($abc_classification[$class], 'total'));
                                    ?>
                                    <div class="abc-stat">
                                        <span class="class-letter"><?php echo $class; ?></span>
                                        <span class="class-label"><?php echo $abc_labels[$class]; ?></span>
                                        <span class="class-count"><?php echo $count; ?> items</span>
                                        <span class="class-total"><?php echo formatCurrency($total); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Category Breakdown -->
                        <div class="preview-subsection">
                            <h3>Category Breakdown</h3>
                            <table class="preview-table sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Items</th>
                                        <th>Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_stats['categories'] as $cat => $qty): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cat); ?></td>
                                            <td><?php echo array_reduce($cart, function($c, $i) use($cat) { 
                                                return $c + ($i['item']['category'] === $cat ? 1 : 0); 
                                            }, 0); ?></td>
                                            <td><?php echo $qty; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Your cart is empty. <a href="cart_page.php">Add items to cart first</a> to generate documents.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php render_footer($BASE_URL); ?>
</body>
</html>
