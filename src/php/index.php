<?php
require_once __DIR__ . '/auth.php';
require_login();

require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/components.php';

$BASE_URL = dirname($_SERVER['SCRIPT_NAME'], 3);
if ($BASE_URL === DIRECTORY_SEPARATOR) {
    $BASE_URL = '';
}

$currentUser = current_user();

// Handle catalog and cart POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        $item_json = $_POST['item_json'] ?? null;
        $quantity = intval($_POST['quantity'] ?? 1);

        if ($item_json) {
            $item = json_decode($item_json, true);
            add_to_cart($item, $quantity);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Item added to cart!'];
        }
    } elseif (isset($_POST['update_price'])) {
        $items = load_catalog_items();
        $index = intval($_POST['item_index'] ?? -1);
        $price = (string)($_POST['unit_cost'] ?? '');

        if (update_catalog_item_price($items, $index, $price)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Price updated successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not update price. Enter a valid amount.'];
        }
    } elseif (isset($_POST['add_catalog_item'])) {
        $items = load_catalog_items();
        $newItem = [
            'item_name' => trim($_POST['item_name'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'brand' => trim($_POST['brand'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'unit' => trim($_POST['unit'] ?? 'Each'),
            'unit_cost' => (string)($_POST['unit_cost'] ?? ''),
        ];

        if ($newItem['item_name'] === '' || $newItem['category'] === '' || normalize_catalog_price($newItem['unit_cost']) === '') {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Item name, category, and price are required.'];
        } elseif (save_catalog_items(add_catalog_item($items, $newItem))) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'New item added to the catalog.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not save the new item.'];
        }
    }

    header('Location: index.php?' . buildQueryString());
    exit;
}

$items = load_catalog_items();
$query = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$brand = trim($_GET['brand'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$resultsPerPage = 20;

$categories = catalog_unique_values($items, 'category');
$brands = catalog_unique_values($items, 'brand');

$filteredEntries = [];
foreach ($items as $idx => $item) {
    $haystack = strtolower(
        implode(' ', [
            $item['item_name'] ?? '',
            $item['brand'] ?? '',
            $item['category'] ?? '',
            $item['model'] ?? '',
            is_array($item['specs'] ?? null) ? json_encode($item['specs']) : ($item['specs'] ?? ''),
        ])
    );
    $matchesQuery = $query === '' || strpos($haystack, strtolower($query)) !== false;
    $matchesCategory = $category === '' || ($item['category'] ?? '') === $category;
    $matchesBrand = $brand === '' || ($item['brand'] ?? '') === $brand;

    if ($matchesQuery && $matchesCategory && $matchesBrand) {
        $filteredEntries[] = ['index' => $idx, 'item' => $item];
    }
}

$totalResults = count($filteredEntries);
$totalPages = max(1, (int)ceil($totalResults / $resultsPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $resultsPerPage;
$displayEntries = array_slice($filteredEntries, $offset, $resultsPerPage);

function formatCurrency($value)
{
    if (is_numeric($value) && $value !== '') {
        return '₱ ' . number_format((float)$value, 2);
    }
    return htmlspecialchars((string)$value);
}

function renderSpecs($specs)
{
    if (empty($specs)) {
        return '';
    }
    if (!is_array($specs)) {
        return '<p>' . htmlspecialchars($specs) . '</p>';
    }
    $html = '<ul class="spec-list">';
    foreach ($specs as $key => $value) {
        $html .= '<li><strong>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</strong>: ' . htmlspecialchars($value) . '</li>';
    }
    $html .= '</ul>';
    return $html;
}

function buildQueryString($overrides = [])
{
    $params = array_merge(
        [
            'q' => $_GET['q'] ?? '',
            'category' => $_GET['category'] ?? '',
            'brand' => $_GET['brand'] ?? '',
        ],
        $overrides
    );
    return http_build_query($params);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Catalog - Procurement System</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
    <script src="<?php echo $BASE_URL; ?>/static/accessibility.js" defer></script>
</head>
<body>
    <?php render_header($BASE_URL, $currentUser, "Item Catalog"); ?>

    <main class="page-enter">
        <div class="container glass">
            <div class="page-actions">
                <a href="dashboard.php" class="button secondary">Back to Dashboard</a>
            </div>

            <section class="hero-grid catalog-hero">
                <div class="hero-copy">
                    <h1>Item Catalog</h1>
                    <p class="subtitle">Browse procurement items by category, update prices, add new items, and build your cart for export.</p>
                </div>
                <div class="hero-side">
                    <button type="button" class="button" id="openAddItemBtn">Add New Item</button>
                </div>
            </section>

            <div class="top-actions">
                <form action="index.php" method="GET" class="search-form filter-form">
                    <input type="text" name="q" placeholder="Search items, brands, or specs..." value="<?php echo htmlspecialchars($query); ?>">
                    <select name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $value): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $value === $category ? 'selected' : ''; ?>><?php echo htmlspecialchars($value); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="brand" onchange="this.form.submit()">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $value): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $value === $brand ? 'selected' : ''; ?>><?php echo htmlspecialchars($value); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button">Apply Filters</button>
                </form>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert <?php echo $_SESSION['message']['type']; ?>">
                    <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <div class="results-info">
                <strong><?php echo $totalResults; ?></strong> item(s) found
                <?php if ($query): ?>for "<strong><?php echo htmlspecialchars($query); ?></strong>"<?php endif; ?>
                <?php if ($category): ?> in <strong><?php echo htmlspecialchars($category); ?></strong><?php endif; ?>
            </div>

            <?php if (!empty($displayEntries)): ?>
                <div class="table-wrapper">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayEntries as $entry):
                                $item = $entry['item'];
                                $itemIndex = $entry['index'];
                                $priceValue = is_numeric($item['unit_cost'] ?? '') ? (float)$item['unit_cost'] : '';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['brand'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['model'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                    <td class="price-cell">
                                        <form method="POST" class="inline-price-form">
                                            <input type="hidden" name="update_price" value="1">
                                            <input type="hidden" name="item_index" value="<?php echo (int)$itemIndex; ?>">
                                            <input type="number" name="unit_cost" class="price-input" min="0" step="0.01"
                                                   value="<?php echo $priceValue !== '' ? htmlspecialchars((string)$priceValue) : ''; ?>"
                                                   placeholder="0.00" required>
                                            <button type="submit" class="button sm secondary">Save</button>
                                        </form>
                                    </td>
                                    <td class="action-cell">
                                        <button class="button sm primary add-to-cart-btn"
                                                data-item-json='<?php echo htmlspecialchars(json_encode($item)); ?>'
                                                title="Add to cart">Add</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="index.php?<?php echo buildQueryString(['page' => $page - 1]); ?>" class="button secondary">Previous</a>
                    <?php else: ?>
                        <span class="button disabled">Previous</span>
                    <?php endif; ?>
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="index.php?<?php echo buildQueryString(['page' => $page + 1]); ?>" class="button secondary">Next</a>
                    <?php else: ?>
                        <span class="button disabled">Next</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    No items matched your filters. Upload Excel files to display the table here.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Item Modal -->
    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" data-modal="addItemModal">&times;</span>
            <h2>Add Catalog Item</h2>
            <form method="POST" class="add-item-form">
                <input type="hidden" name="add_catalog_item" value="1">
                <label>
                    Item Name
                    <input type="text" name="item_name" class="form-input" required>
                </label>
                <label>
                    Category
                    <input list="category-list" name="category" class="form-input" required placeholder="e.g. Office Supplies">
                    <datalist id="category-list">
                        <?php foreach ($categories as $value): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label>
                    Brand <span class="optional">(optional)</span>
                    <input type="text" name="brand" class="form-input">
                </label>
                <label>
                    Model <span class="optional">(optional)</span>
                    <input type="text" name="model" class="form-input">
                </label>
                <label>
                    Unit
                    <select name="unit" class="form-input" required>
                        <option value="Each">Each</option>
                        <option value="PCS">PCS</option>
                        <option value="PAX">PAX</option>
                        <option value="Box">Box</option>
                        <option value="Set">Set</option>
                        <option value="Pack">Pack</option>
                        <option value="Roll">Roll</option>
                        <option value="REAM">REAM</option>
                    </select>
                </label>
                <label>
                    Unit Price
                    <input type="number" name="unit_cost" class="form-input" min="0" step="0.01" required>
                </label>
                <div class="modal-actions">
                    <button type="submit" class="button primary">Save Item</button>
                    <button type="button" class="button secondary modal-close-btn" data-modal="addItemModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add to Cart Modal -->
    <div id="addToCartModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" data-modal="addToCartModal">&times;</span>
            <h2>Add Item to Cart</h2>
            
            <div id="modalItemInfo"></div>
            
            <form id="addToCartForm" method="POST">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="item_json" id="modalItemJson">
                
                <div class="form-group">
                    <label for="quantityInput">Quantity</label>
                    <input type="number" id="quantityInput" name="quantity" value="1" min="1" class="form-input" required>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="button primary">Add to Cart</button>
                    <button type="button" class="button secondary modal-close-btn" data-modal="addToCartModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php render_footer($BASE_URL); ?>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        document.querySelectorAll('.modal-close, .modal-close-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.modal;
                if (modalId) closeModal(modalId);
            });
        });

        ['addToCartModal', 'addItemModal'].forEach(id => {
            const modal = document.getElementById(id);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal(id);
            });
        });

        document.getElementById('openAddItemBtn').addEventListener('click', () => openModal('addItemModal'));

        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const item = JSON.parse(this.dataset.itemJson);
                const infoHtml = `
                    <div class="item-details">
                        <p><strong>${item.item_name}</strong></p>
                        <p class="text-muted">Category: ${item.category}</p>
                        <p class="text-muted">Brand: ${item.brand || '—'}</p>
                        <p class="text-muted">Unit: ${item.unit}</p>
                        <p class="text-accent">Price: ₱${parseFloat(item.unit_cost || 0).toFixed(2)}</p>
                    </div>
                `;
                document.getElementById('modalItemInfo').innerHTML = infoHtml;
                document.getElementById('modalItemJson').value = this.dataset.itemJson;
                document.getElementById('quantityInput').value = '1';
                openModal('addToCartModal');
            });
        });
    </script>
</body>
</html>
