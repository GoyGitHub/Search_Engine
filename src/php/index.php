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

// Handle add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $item_json = $_POST['item_json'] ?? null;
    $variation_json = $_POST['variation_json'] ?? null;
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($item_json) {
        $item = json_decode($item_json, true);
        $variation = $variation_json ? json_decode($variation_json, true) : null;
        add_to_cart($item, $quantity, $variation);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Item added to cart!'];
    }
    
    header('Location: index.php?' . buildQueryString());
    exit;
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

function filter_items(array $items, string $query, string $category, string $brand): array
{
    $query = strtolower(trim($query));
    $category = trim($category);
    $brand = trim($brand);
    if ($query === '' && $category === '' && $brand === '') {
        return $items;
    }

    $filtered = [];
    foreach ($items as $item) {
        $haystack = strtolower(
            implode(' ', [
                $item['item_name'] ?? '',
                $item['brand'] ?? '',
                $item['category'] ?? '',
                $item['model'] ?? '',
                is_array($item['specs']) ? json_encode($item['specs']) : ($item['specs'] ?? ''),
            ])
        );

        $matchesQuery = $query === '' || strpos($haystack, $query) !== false;
        $matchesCategory = $category === '' || ($item['category'] ?? '') === $category;
        $matchesBrand = $brand === '' || ($item['brand'] ?? '') === $brand;

        if ($matchesQuery && $matchesCategory && $matchesBrand) {
            $filtered[] = $item;
        }
    }
    return $filtered;
}

$items = load_items_from_json();
$query = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$brand = trim($_GET['brand'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$resultsPerPage = 20;

$categories = unique_values($items, 'category');
$brands = unique_values($items, 'brand');

$filteredItems = filter_items($items, $query, $category, $brand);
$totalResults = count($filteredItems);
$totalPages = max(1, (int)ceil($totalResults / $resultsPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $resultsPerPage;
$displayItems = array_slice($filteredItems, $offset, $resultsPerPage);

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
                <a href="dashboard.php" class="button secondary icon-btn"><span>↩</span> Back to Dashboard</a>
            </div>

            <section class="hero-grid">
                <div class="hero-copy">
                    <h1>Excel Item Table</h1>
                    <p class="subtitle">All rows are loaded directly from uploaded Excel files. Prices are set per item because costs may vary.</p>
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
                    <button type="submit" class="button icon-btn"><span>🔎</span> Filter</button>
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

            <?php if (!empty($displayItems)): ?>
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
                            <?php foreach ($displayItems as $item): 
                                $variations = get_item_variations($item);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['brand'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['model'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                    <td><?php echo $item['unit_cost'] !== '' ? formatCurrency($item['unit_cost']) : '<span class="placeholder">No price</span>'; ?></td>
                                    <td class="action-cell">
                                        <button class="button sm primary icon-btn add-to-cart-btn" 
                                                data-item-json='<?php echo htmlspecialchars(json_encode($item)); ?>'
                                                data-variations='<?php echo htmlspecialchars(json_encode($variations)); ?>'
                                                title="Add to cart">
                                            <span>+</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="index.php?<?php echo buildQueryString(['page' => $page - 1]); ?>" class="button icon-btn"><span>⬅</span> Previous</a>
                    <?php else: ?>
                        <span class="button disabled">Previous</span>
                    <?php endif; ?>
                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="index.php?<?php echo buildQueryString(['page' => $page + 1]); ?>" class="button icon-btn">Next <span>➡</span></a>
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

    <!-- Add to Cart Modal -->
    <div id="addToCartModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Add Item to Cart</h2>
            
            <div id="modalItemInfo"></div>
            
            <form id="addToCartForm" method="POST">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="item_json" id="modalItemJson">
                <input type="hidden" name="variation_json" id="modalVariationJson">
                
                <div id="variationsContainer" class="form-group" style="display: none;">
                    <label for="variationSelect">Select Variation</label>
                    <select id="variationSelect" class="form-input">
                        <option value="">-- Choose variation --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quantityInput">Quantity</label>
                    <input type="number" id="quantityInput" name="quantity" value="1" min="1" class="form-input" required>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="button primary">Add to Cart</button>
                    <button type="button" class="button secondary modal-close-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php render_footer($BASE_URL); ?>

    <script>
        // Modal handling
        const modal = document.getElementById('addToCartModal');
        const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');
        let currentVariations = [];
        
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });
        
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Add to cart button handlers
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const itemJson = this.dataset.itemJson;
                const variationsJson = this.dataset.variations;
                
                const item = JSON.parse(itemJson);
                currentVariations = JSON.parse(variationsJson);
                
                // Show item info
                const infoHtml = `
                    <div class="item-details">
                        <p><strong>${item.item_name}</strong></p>
                        <p class="text-muted">Category: ${item.category}</p>
                        <p class="text-muted">Brand: ${item.brand}</p>
                        <p class="text-muted">Unit: ${item.unit}</p>
                        <p class="text-accent">Price: ₱${parseFloat(item.unit_cost).toFixed(2)}</p>
                    </div>
                `;
                
                document.getElementById('modalItemInfo').innerHTML = infoHtml;
                document.getElementById('modalItemJson').value = itemJson;
                
                // Handle variations
                const variationSelect = document.getElementById('variationSelect');
                const variationsContainer = document.getElementById('variationsContainer');
                
                if (currentVariations.length > 1) {
                    variationsContainer.style.display = 'block';
                    variationSelect.innerHTML = '<option value="">-- Choose variation --</option>';
                    currentVariations.forEach((v, idx) => {
                        const option = document.createElement('option');
                        option.value = JSON.stringify(v);
                        option.textContent = v.name;
                        variationSelect.appendChild(option);
                    });
                } else {
                    variationsContainer.style.display = 'none';
                    if (currentVariations.length === 1) {
                        document.getElementById('modalVariationJson').value = JSON.stringify(currentVariations[0]);
                    }
                }
                
                document.getElementById('quantityInput').value = '1';
                modal.style.display = 'block';
            });
        });
        
        // Form submission
        document.getElementById('addToCartForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const variationSelect = document.getElementById('variationSelect');
            if (variationSelect.style.display !== 'none' && !variationSelect.value) {
                alert('Please select a variation');
                return;
            }
            
            if (variationSelect.value) {
                document.getElementById('modalVariationJson').value = variationSelect.value;
            }
            
            this.submit();
        });
        
        document.getElementById('variationSelect').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('modalVariationJson').value = this.value;
            }
        });
    </script>
</body>
</html>
