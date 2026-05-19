<?php
// Cart management functions

function init_cart()
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function validate_cart_item($item, $quantity)
{
    if (empty($item['item_name']) || empty($item['unit_cost']) || $quantity <= 0) {
        return false;
    }
    return true;
}

function add_to_cart($item, $quantity = 1)
{
    init_cart();

    if (!validate_cart_item($item, $quantity)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid item or quantity.'];
        return;
    }

    $item_key = create_cart_item_key($item);

    if (isset($_SESSION['cart'][$item_key])) {
        $_SESSION['cart'][$item_key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$item_key] = [
            'item' => $item,
            'quantity' => $quantity,
            'added_at' => date('Y-m-d H:i:s'),
        ];
    }
}

function remove_from_cart($item_key)
{
    init_cart();
    if (isset($_SESSION['cart'][$item_key])) {
        unset($_SESSION['cart'][$item_key]);
    }
}

function update_cart_quantity($item_key, $quantity)
{
    init_cart();
    if (isset($_SESSION['cart'][$item_key])) {
        if ($quantity <= 0) {
            remove_from_cart($item_key);
        } else {
            $_SESSION['cart'][$item_key]['quantity'] = $quantity;
        }
    }
}

function get_cart()
{
    init_cart();
    return $_SESSION['cart'];
}

function clear_cart()
{
    $_SESSION['cart'] = [];
}

function create_cart_item_key($item)
{
    return md5($item['item_name'] . '|' . $item['category'] . '|' . $item['brand']);
}

function get_cart_stats()
{
    $cart = get_cart();
    $total_items = 0;
    $total_cost = 0;
    $categories = [];
    
    foreach ($cart as $item_data) {
        $item = $item_data['item'];
        $qty = $item_data['quantity'];
        $total_items += $qty;
        $unit_cost = floatval($item['unit_cost'] ?? 0);
        $total_cost += $unit_cost * $qty;
        
        $category = $item['category'] ?? 'Uncategorized';
        if (!isset($categories[$category])) {
            $categories[$category] = 0;
        }
        $categories[$category] += $qty;
    }
    
    return [
        'total_items' => $total_items,
        'total_cost' => $total_cost,
        'categories' => $categories,
        'item_count' => count($cart),
    ];
}

function get_item_variations($item)
{
    // Define variations based on category and item type
    $category = $item['category'] ?? '';
    $item_name = $item['item_name'] ?? '';
    $unit = $item['unit'] ?? '';
    
    $variations = [];
    
    switch ($category) {
        case 'Art Materials':
            // Size/type variations for art materials
            if (in_array($item_name, ['CRAYONS', 'MARKERS', 'PENCIL'])) {
                $variations = [
                    ['name' => 'Standard', 'type' => 'standard'],
                    ['name' => 'Premium', 'type' => 'premium', 'price_modifier' => 1.2],
                    ['name' => 'Bulk', 'type' => 'bulk', 'price_modifier' => 0.8],
                ];
            } elseif ($item_name === 'NOTEBOOK') {
                $variations = [
                    ['name' => 'A5 (Small)', 'type' => 'small'],
                    ['name' => 'A4 (Medium)', 'type' => 'medium'],
                    ['name' => 'A3 (Large)', 'type' => 'large', 'price_modifier' => 1.3],
                ];
            } elseif ($item_name === 'CRAFT PAPER') {
                $variations = [
                    ['name' => 'Thin (60gsm)', 'type' => 'thin'],
                    ['name' => 'Medium (100gsm)', 'type' => 'medium'],
                    ['name' => 'Thick (160gsm)', 'type' => 'thick', 'price_modifier' => 1.5],
                ];
            }
            break;
            
        case 'Office Supplies':
            $variations = [
                ['name' => 'Standard', 'type' => 'standard'],
                ['name' => 'Premium', 'type' => 'premium', 'price_modifier' => 1.15],
            ];
            break;
            
        default:
            $variations = [
                ['name' => 'Standard', 'type' => 'standard'],
            ];
            break;
    }
    
    return $variations;
}

function get_abc_classification($items)
{
    // Classify items into ABC (Always, Better, Critical) based on cost and frequency
    if (empty($items)) {
        return ['A' => [], 'B' => [], 'C' => []];
    }
    
    // Calculate total cost by item
    $itemCosts = [];
    foreach ($items as $item_data) {
        $item = $item_data['item'];
        $qty = $item_data['quantity'];
        $unit_cost = floatval($item['unit_cost'] ?? 0);
        $total = $unit_cost * $qty;
        
        $key = $item['item_name'];
        if (!isset($itemCosts[$key])) {
            $itemCosts[$key] = ['item' => $item, 'total' => 0, 'count' => 0];
        }
        $itemCosts[$key]['total'] += $total;
        $itemCosts[$key]['count']++;
    }
    
    // Sort by total cost
    uasort($itemCosts, function ($a, $b) {
        return $b['total'] <=> $a['total'];
    });
    
    $totalCost = array_sum(array_column($itemCosts, 'total'));
    
    // ABC classification: A = 80%, B = 15%, C = 5%
    $classification = ['A' => [], 'B' => [], 'C' => []];
    $runningCost = 0;
    $aThreshold = $totalCost * 0.80;
    $bThreshold = $totalCost * 0.95;
    
    foreach ($itemCosts as $name => $data) {
        $runningCost += $data['total'];
        
        if ($runningCost <= $aThreshold) {
            $classification['A'][] = array_merge($data, ['name' => $name]);
        } elseif ($runningCost <= $bThreshold) {
            $classification['B'][] = array_merge($data, ['name' => $name]);
        } else {
            $classification['C'][] = array_merge($data, ['name' => $name]);
        }
    }
    
    return $classification;
}
?>
