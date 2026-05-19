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

function unique_values(array $items, string $key): array
{
    $values = [];
    foreach ($items as $itemsRow) {
        $value = trim((string)($itemsRow[$key] ?? ''));
        if ($value !== '' && !in_array($value, $values, true)) {
            $values[] = $value;
        }
    }
    sort($values);
    return $values;
}

function most_common_categories(array $items, int $limit = 5): array
{
    $counts = [];
    foreach ($items as $item) {
        $category = trim((string)($item['category'] ?? 'Uncategorized'));
        if ($category === '') {
            $category = 'Uncategorized';
        }
        $counts[$category] = ($counts[$category] ?? 0) + 1;
    }
    arsort($counts);
    return array_slice($counts, 0, $limit, true);
}

$items = load_items_from_json();
$totalItems = count($items);
$categories = unique_values($items, 'category');
$brands = unique_values($items, 'brand');
$topCategories = most_common_categories($items, 6);

$recentItems = array_slice(array_reverse($items), 0, 5);
$currentUser = current_user();

$metrics = [
    ['label' => 'Total Items', 'value' => $totalItems],
    ['label' => 'Categories', 'value' => count($categories)],
    ['label' => 'Brands', 'value' => count($brands)],
    ['label' => 'Excel Files', 'value' => 1],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
</head>
<body>
    <header class="site-header glass">
        <div class="header-inner">
            <a href="dashboard.php" class="brand">
                <img src="<?php echo $BASE_URL; ?>/static/img/logo_montalban.png" alt="Company Logo" class="brand-logo">
                <div class="brand-text">
                    <span class="brand-title">Procurement Dashboard</span>
                    <span class="brand-subtitle">Manage catalog actions from one place</span>
                </div>
            </a>
            <nav class="header-actions">
                <a href="dashboard.php" class="button ghost active">Dashboard</a>
                <a href="logout.php" class="button ghost">Logout</a>
                <span class="header-user">Signed in as <strong><?php echo htmlspecialchars(ucwords(strtolower($currentUser['username'] ?? 'Admin'))); ?></strong></span>
            </nav>
        </div>
    </header>

    <main class="page-enter">
        <div class="container glass dashboard-shell">
            <section class="dashboard-hero">
                <div>
                    <p class="overline">Welcome back</p>
                    <h1>Procurement Control Center</h1>
                    <p class="subtitle">A modern dashboard for your item catalog, upload pipeline, and quick actions.</p>
                </div>
                <div class="dashboard-actions">
                    <a href="add_item.php" class="button icon-btn"><span>➕</span> Add Item</a>
                    <a href="index.php" class="button secondary icon-btn"><span>📋</span> View Items</a>
                    <a href="upload.php" class="button ghost icon-btn"><span>📤</span> Upload Sheet</a>
                </div>
            </section>

            <div class="metrics-grid">
                <?php foreach ($metrics as $metric): ?>
                    <div class="metric-card">
                        <small><?php echo htmlspecialchars($metric['label']); ?></small>
                        <span><?php echo htmlspecialchars((string)$metric['value']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="dashboard-grid">
                <section class="dashboard-card">
                    <h2>Top Categories</h2>
                    <ul>
                        <?php foreach ($topCategories as $category => $count): ?>
                            <li><strong><?php echo htmlspecialchars($category); ?></strong><span><?php echo htmlspecialchars((string)$count); ?> items</span></li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="dashboard-card">
                    <h2>Recent Items</h2>
                    <div class="recent-list">
                        <?php if (empty($recentItems)): ?>
                            <p>No items yet.</p>
                        <?php else: ?>
                            <?php foreach ($recentItems as $item): ?>
                                <div class="recent-row">
                                    <strong><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></strong>
                                    <span><?php echo htmlspecialchars($item['category'] ?? ''); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
