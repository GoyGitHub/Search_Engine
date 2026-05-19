<?php
require_once __DIR__ . '/auth.php';
require_login();

$BASE_URL = dirname($_SERVER['SCRIPT_NAME'], 3);
if ($BASE_URL === DIRECTORY_SEPARATOR) {
    $BASE_URL = '';
}

$currentUser = current_user();

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
    <title>Excel Item Table</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
    <script src="<?php echo $BASE_URL; ?>/static/accessibility.js" defer></script>
</head>
<body>
    <header class="site-header glass">
        <div class="header-inner">
            <a href="index.php" class="brand">
                <img src="<?php echo $BASE_URL; ?>/static/img/logo_montalban.png" alt="Company Logo" class="brand-logo">
                <div class="brand-text">
                    <span class="brand-title">Excel Item Catalog</span>
                    <span class="brand-subtitle">Loaded from uploaded spreadsheets</span>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayItems as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['brand'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['model'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                    <td><?php echo $item['unit_cost'] !== '' ? formatCurrency($item['unit_cost']) : '<span class="placeholder">No price</span>'; ?></td>
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
</body>
</html>
