<?php
require_once __DIR__ . '/auth.php';
require_login();

require_once __DIR__ . '/catalog.php';

$BASE_URL = dirname($_SERVER['SCRIPT_NAME'], 3);
if ($BASE_URL === DIRECTORY_SEPARATOR) {
    $BASE_URL = '';
}

$items = load_catalog_items();
$categories = catalog_unique_values($items, 'category');
$currentUser = current_user();

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'item_name' => $_POST['item_name'] ?? '',
        'category' => $_POST['category'] ?? '',
        'brand' => $_POST['brand'] ?? '',
        'model' => $_POST['model'] ?? '',
        'unit' => $_POST['unit'] ?? 'Each',
        'unit_cost' => $_POST['price'] ?? '',
    ];

    if (trim($fields['item_name']) === '' || trim($fields['category']) === '' || normalize_catalog_price((string)$fields['unit_cost']) === '') {
        $message = ['type' => 'error', 'text' => 'Please provide item name, category, and price.'];
    } elseif (save_catalog_items(add_catalog_item($items, $fields))) {
        header('Location: index.php');
        exit;
    } else {
        $message = ['type' => 'error', 'text' => 'Could not save the item.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
</head>
<body>
    <header class="site-header glass">
        <div class="header-inner">
            <a href="dashboard.php" class="brand">
                <img src="<?php echo $BASE_URL; ?>/static/img/logo_montalban.png" alt="Company Logo" class="brand-logo">
                <div class="brand-text">
                    <span class="brand-title">Add Item</span>
                    <span class="brand-subtitle">Separate item entry page</span>
                </div>
            </a>
            <nav class="header-actions">
                <a href="dashboard.php" class="button ghost">Dashboard</a>
                <a href="logout.php" class="button ghost">Logout</a>
                <span class="header-user">Signed in as <strong><?php echo htmlspecialchars(ucwords(strtolower($currentUser['username'] ?? 'Admin'))); ?></strong></span>
            </nav>
        </div>
    </header>

    <main class="page-enter">
        <div class="container narrow glass">
            <div class="page-actions">
                <a href="dashboard.php" class="button secondary">Back to Dashboard</a>
            </div>
            <h1>Add New Item</h1>
            <p class="subtitle">Fill in the catalog form here and save items separately from the listing.</p>

            <?php if ($message): ?>
                <div class="alert <?php echo $message['type']; ?>"><?php echo htmlspecialchars($message['text']); ?></div>
            <?php endif; ?>

            <form method="POST" action="add_item.php" class="add-item-form card">
                <label>
                    Item Name
                    <input type="text" name="item_name" required>
                </label>
                <label>
                    Category
                    <input list="category-list" name="category" required placeholder="Choose or type a category">
                    <datalist id="category-list">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label>
                    Brand <span class="optional">(optional)</span>
                    <input type="text" name="brand" placeholder="Brand">
                </label>
                <label>
                    Model <span class="optional">(optional)</span>
                    <input type="text" name="model" placeholder="Model">
                </label>
                <label>
                    Unit
                    <select name="unit" required>
                        <option value="Each">Each</option>
                        <option value="Piece">Piece</option>
                        <option value="Box">Box</option>
                        <option value="Set">Set</option>
                        <option value="Pack">Pack</option>
                        <option value="Roll">Roll</option>
                        <option value="Sheet">Sheet</option>
                    </select>
                </label>
                <label>
                    Price
                    <input type="text" name="price" placeholder="0.00" required>
                </label>
                <div class="modal-actions">
                    <button type="submit" class="button">Save Item</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
