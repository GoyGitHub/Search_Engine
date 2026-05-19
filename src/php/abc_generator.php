<?php
require_once __DIR__ . '/auth.php';
require_login();

require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/components.php';
require_once __DIR__ . '/excel_export.php';

$BASE_URL = dirname($_SERVER['SCRIPT_NAME'], 3);
if ($BASE_URL === DIRECTORY_SEPARATOR) {
    $BASE_URL = '';
}

$currentUser = current_user();
$cart = get_cart();
$cart_stats = get_cart_stats();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $department = trim($_POST['department'] ?? 'TOURISM');
    $project_title = trim($_POST['project_title'] ?? 'Procurement Request');
    $revised_date = trim($_POST['revised_date'] ?? date('Y-m-d'));

    if (empty($cart)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Cart is empty. Add items first.'];
        header('Location: abc_generator.php');
        exit;
    }

    stream_budget_excel_download($cart, $department, $project_title, $revised_date);
}

$items_by_category = [];
foreach ($cart as $item_data) {
    $category = $item_data['item']['category'] ?? 'Uncategorized';
    if (!isset($items_by_category[$category])) {
        $items_by_category[$category] = [];
    }
    $items_by_category[$category][] = $item_data;
}
ksort($items_by_category, SORT_NATURAL | SORT_FLAG_CASE);

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
    <title>Generate Budget Excel - Procurement System</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
    <script src="<?php echo $BASE_URL; ?>/static/accessibility.js" defer></script>
</head>
<body>
    <?php render_header($BASE_URL, $currentUser, "Generate Budget Excel"); ?>

    <main class="page-enter">
        <div class="container glass">
            <div class="page-actions">
                <a href="cart_page.php" class="button secondary">Back to Cart</a>
            </div>

            <section class="hero-grid">
                <div class="hero-copy">
                    <h1>Generate Budget Excel</h1>
                    <p class="subtitle">Uses the official ABC template. Cart items replace the worksheet list, grouped by category sections.</p>
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
                    <section class="gen-form-section">
                        <h2>Document Details</h2>
                        <form method="POST" class="generation-form">
                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" id="department" name="department"
                                       value="TOURISM" class="form-input" required
                                       placeholder="e.g. TOURISM, HR, ENGINEERING">
                            </div>

                            <div class="form-group">
                                <label for="project_title">Project / Request Title</label>
                                <input type="text" id="project_title" name="project_title"
                                       value="SUPPLY AND DELIVERY VARIOUS MATERIALS FOR NATIONAL ARTS MONTH OF 2026"
                                       class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label for="revised_date">Revised On</label>
                                <input type="date" id="revised_date" name="revised_date"
                                       value="<?php echo date('Y-m-d'); ?>" class="form-input" required>
                            </div>

                            <div class="form-info">
                                <strong>Cart Summary:</strong>
                                <ul>
                                    <li>Total line items: <strong><?php echo $cart_stats['item_count']; ?></strong></li>
                                    <li>Total quantity: <strong><?php echo $cart_stats['total_items']; ?></strong></li>
                                    <li>Total cost: <strong><?php echo formatCurrency($cart_stats['total_cost']); ?></strong></li>
                                    <li>Categories: <strong><?php echo count($cart_stats['categories']); ?></strong></li>
                                </ul>
                            </div>

                            <button type="submit" name="generate" class="button primary" value="1">
                                Download Excel File
                            </button>
                        </form>
                    </section>

                    <section class="gen-preview-section">
                        <h2>Preview by Category</h2>
                        <?php foreach ($items_by_category as $category => $category_items): ?>
                            <div class="preview-subsection">
                                <h3><?php echo htmlspecialchars($category); ?></h3>
                                <table class="preview-table sm">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>Unit</th>
                                            <th>Unit Cost</th>
                                            <th>Qty</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($category_items as $item_data):
                                            $item = $item_data['item'];
                                            $qty = (int)$item_data['quantity'];
                                            $unit_cost = (float)($item['unit_cost'] ?? 0);
                                            $total = $unit_cost * $qty;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                                <td class="num"><?php echo formatCurrency($unit_cost); ?></td>
                                                <td class="num"><?php echo $qty; ?></td>
                                                <td class="num"><strong><?php echo formatCurrency($total); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </section>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Your cart is empty. <a href="cart_page.php">Add items to cart first</a> to generate the Excel file.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php render_footer($BASE_URL); ?>
</body>
</html>
