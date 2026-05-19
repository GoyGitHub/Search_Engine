<?php

function catalog_json_path(): string
{
    return dirname(__DIR__, 2) . '/excel_files/items.json';
}

function load_catalog_items(): array
{
    $jsonFile = catalog_json_path();
    if (!file_exists($jsonFile)) {
        return [];
    }

    $contents = file_get_contents($jsonFile);
    $items = json_decode($contents, true);
    return is_array($items) ? $items : [];
}

function save_catalog_items(array $items): bool
{
    $jsonData = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonData === false) {
        return false;
    }

    $dir = dirname(catalog_json_path());
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return file_put_contents(catalog_json_path(), $jsonData) !== false;
}

function normalize_catalog_price(string $value): string
{
    $normalized = str_replace([',', '₱', '$', ' '], '', trim($value));
    if ($normalized === '') {
        return '';
    }

    return is_numeric($normalized) ? (string) round((float)$normalized, 2) : '';
}

function add_catalog_item(array $items, array $fields): array
{
    $items[] = [
        'category' => trim($fields['category'] ?? 'General'),
        'brand' => trim($fields['brand'] ?? '') ?: 'Unknown',
        'item_name' => trim($fields['item_name'] ?? ''),
        'model' => trim($fields['model'] ?? ''),
        'unit' => trim($fields['unit'] ?? 'Each'),
        'unit_cost' => normalize_catalog_price((string)($fields['unit_cost'] ?? '')),
        'specs' => null,
        'source' => 'user_added',
    ];

    return $items;
}

function update_catalog_item_price(array $items, int $index, string $price): bool
{
    if (!isset($items[$index])) {
        return false;
    }

    $normalized = normalize_catalog_price($price);
    if ($normalized === '') {
        return false;
    }

    $items[$index]['unit_cost'] = $normalized;
    return save_catalog_items($items);
}

function catalog_unique_values(array $items, string $key): array
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
