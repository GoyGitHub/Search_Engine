<?php
require_once __DIR__ . '/auth.php';
require_login();

$BASE_URL = dirname($_SERVER['SCRIPT_NAME'], 3);
if ($BASE_URL === DIRECTORY_SEPARATOR) {
    $BASE_URL = '';
}

function load_items_from_json(): array
{
    $jsonFile = dirname(__DIR__, 2) . '/excel_files/items.json';
    if (!file_exists($jsonFile)) {
        return [];
    }

    $contents = file_get_contents($jsonFile);
    $items = json_decode($contents, true);
    return is_array($items) ? $items : [];
}

function save_items_to_json(array $items): bool
{
    $jsonFile = dirname(__DIR__, 2) . '/excel_files/items.json';
    $jsonData = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $jsonData !== false && file_put_contents($jsonFile, $jsonData) !== false;
}

function unique_values(array $items, string $key): array
{
    $values = [];
    foreach ($items as $item) {
        $value = trim((string)($item[$key] ?? ''));
        if ($value !== '' && !in_array($value, $values, true)) {
            $values[] = $value;
        }
    }
    sort($values);
    return $values;
}

function normalize_price(string $value): string
{
    $normalized = str_replace([',', '₱', '$'], '', trim($value));
    if ($normalized === '') {
        return '';
    }
    return is_numeric($normalized) ? (string) round((float)$normalized, 2) : '';
}

$items = load_items_from_json();
$categories = unique_values($items, 'category');
$currentUser = current_user();

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newItemName = trim($_POST['item_name'] ?? '');
    $newCategory = trim($_POST['category'] ?? 'General');
    $newBrand = trim($_POST['brand'] ?? '');
    $newModel = trim($_POST['model'] ?? '');
    $newUnit = trim($_POST['unit'] ?? 'Each');
    $newPrice = normalize_price($_POST['price'] ?? '');

    if ($newItemName === '' || $newCategory === '' || $newUnit === '' || $newPrice === '') {
        $message = ['type' => 'error', 'text' => 'Please provide item name, category, unit, and price.'];
    } else {
        $items[] = [
            'category' => $newCategory,
            'brand' => $newBrand ?: 'Unknown',
            'item_name' => $newItemName,
            'model' => $newModel,
            'unit' => $newUnit,
            'unit_cost' => $newPrice,
            'specs' => null,
            'source' => 'user_added',
        ];

        if (save_items_to_json($items)) {
            $message = ['type' => 'success', 'text' => 'Item added successfully.'];
            $categories = unique_values($items, 'category');
        } else {
            $message = ['type' => 'error', 'text' => 'Could not save the item.'];
        }
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
                <a href="dashboard.php" class="button secondary icon-btn"><span>↩</span> Back to Dashboard</a>
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
                    <button type="submit" class="button icon-btn"><span>➕</span> Save Item</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
