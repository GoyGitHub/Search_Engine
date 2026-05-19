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


function most_common_categories(array $items, int $limit = 6): array
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

function format_dashboard_currency(float $value): string
{
    return '₱ ' . number_format($value, 2);
}

$items = load_catalog_items();
$totalItems = count($items);
$categories = catalog_unique_values($items, 'category');
$brands = catalog_unique_values($items, 'brand');
$topCategories = most_common_categories($items, 6);
$maxCategoryCount = $topCategories ? max($topCategories) : 1;

$recentItems = array_slice(array_reverse($items), 0, 6);
$currentUser = current_user();
$cartStats = get_cart_stats();
$displayName = htmlspecialchars(ucwords(strtolower($currentUser['username'] ?? 'User')));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Procurement System</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
</head>
<body class="dashboard-page">
    <?php render_header($BASE_URL, $currentUser, "Dashboard"); ?>

    <main class="page-enter">
        <div class="dashboard-layout">
            <section class="dashboard-welcome">
                <div class="dashboard-welcome-copy">
                    <p class="overline">Procurement overview</p>
                    <h1>Good day, <?php echo $displayName; ?></h1>
                    <p class="subtitle">Monitor catalog health, cart activity, and budget exports from one place.</p>
                </div>
                <div class="dashboard-welcome-actions">
                    <a href="index.php" class="button">Open Catalog</a>
                    <a href="abc_generator.php" class="button secondary">Generate Excel</a>
                </div>
            </section>

            <section class="metrics-grid dashboard-metrics">
                <article class="metric-card accent-catalog">
                    <p class="metric-label">Catalog items</p>
                    <p class="metric-value"><?php echo number_format($totalItems); ?></p>
                    <p class="metric-hint">Available in the master list</p>
                </article>
                <article class="metric-card accent-category">
                    <p class="metric-label">Categories</p>
                    <p class="metric-value"><?php echo number_format(count($categories)); ?></p>
                    <p class="metric-hint">Used for Excel grouping</p>
                </article>
                <article class="metric-card accent-brand">
                    <p class="metric-label">Brands</p>
                    <p class="metric-value"><?php echo number_format(count($brands)); ?></p>
                    <p class="metric-hint">Across all catalog rows</p>
                </article>
                <article class="metric-card accent-cart">
                    <p class="metric-label">Cart total</p>
                    <p class="metric-value"><?php echo format_dashboard_currency((float)$cartStats['total_cost']); ?></p>
                    <p class="metric-hint"><?php echo (int)$cartStats['item_count']; ?> items · <?php echo (int)$cartStats['total_items']; ?> qty</p>
                </article>
            </section>

            <section class="dashboard-panels">
                <article class="dashboard-panel panel-wide">
                    <div class="panel-head">
                        <h2>Category distribution</h2>
                        <span class="panel-meta">Top groups in catalog</span>
                    </div>
                    <div class="bar-chart">
                        <?php if (empty($topCategories)): ?>
                            <p class="panel-empty">No category data yet. Upload a catalog to populate this chart.</p>
                        <?php else: ?>
                            <?php foreach ($topCategories as $category => $count): ?>
                                <?php $width = max(8, round(($count / $maxCategoryCount) * 100)); ?>
                                <div class="bar-row">
                                    <span class="bar-label"><?php echo htmlspecialchars($category); ?></span>
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width: <?php echo $width; ?>%;"></div>
                                    </div>
                                    <span class="bar-value"><?php echo (int)$count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="dashboard-panel panel-wide">
                    <div class="panel-head">
                        <h2>Recent catalog entries</h2>
                        <a href="index.php" class="panel-link">View all</a>
                    </div>
                    <?php if (empty($recentItems)): ?>
                        <p class="panel-empty">No catalog items loaded yet.</p>
                    <?php else: ?>
                        <div class="data-table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Unit</th>
                                        <th class="num">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentItems as $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item['category'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($item['brand'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                            <td class="num">
                                                <?php
                                                $cost = $item['unit_cost'] ?? '';
                                                echo is_numeric($cost) && $cost !== ''
                                                    ? format_dashboard_currency((float)$cost)
                                                    : '—';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </article>
            </section>
        </div>
    </main>

    <?php render_footer($BASE_URL); ?>
</body>
</html>
