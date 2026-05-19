<?php
/**
 * Build cart payload and invoke Python Excel generator.
 */

function build_excel_cart_items(array $cart): array
{
    $items = [];
    foreach ($cart as $item_data) {
        $item = $item_data['item'];
        $items[] = [
            'category' => $item['category'] ?? 'Uncategorized',
            'item_name' => $item['item_name'] ?? '',
            'quantity' => (int)($item_data['quantity'] ?? 1),
            'unit' => $item['unit'] ?? '',
            'unit_cost' => (float)($item['unit_cost'] ?? 0),
            'brand' => $item['brand'] ?? '',
            'model' => $item['model'] ?? '',
        ];
    }

    usort($items, function ($a, $b) {
        $cat = strcasecmp($a['category'], $b['category']);
        if ($cat !== 0) {
            return $cat;
        }
        return strcasecmp($a['item_name'], $b['item_name']);
    });

    return $items;
}

function generate_budget_excel_file(array $cart, string $department, string $project_title, string $revised_date): array
{
    $projectRoot = dirname(__DIR__, 2);
    $templateCandidates = [
        $projectRoot . '/templates/ABC_TOURISM_NATIONAL_ARTS_MONTH.xlsx',
        $projectRoot . '/excel_files/ABC TOURISM NATIONAL ARTS MONTH.xlsx',
    ];
    $templatePath = null;
    foreach ($templateCandidates as $candidate) {
        if (is_file($candidate)) {
            $templatePath = $candidate;
            break;
        }
    }

    $payload = [
        'department' => $department,
        'project_title' => $project_title,
        'revised_date' => $revised_date,
        'items' => build_excel_cart_items($cart),
        'sheet_name' => 'ABC (2)',
    ];
    if ($templatePath) {
        $payload['template_path'] = $templatePath;
    }

    $jsonPath = tempnam(sys_get_temp_dir(), 'cart_payload_');
    $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'budget_' . uniqid('', true) . '.xlsx';
    $scriptPath = dirname(__DIR__) . '/python/generate_budget_excel.py';

    if ($jsonPath === false) {
        return ['ok' => false, 'error' => 'Could not create temporary payload file.'];
    }

    file_put_contents($jsonPath, json_encode($payload, JSON_UNESCAPED_UNICODE));

    $pythonCandidates = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
        ? ['python', 'py', 'python3']
        : ['python3', 'python'];

    $output = [];
    $exitCode = 1;
    foreach ($pythonCandidates as $pythonCmd) {
        $command = sprintf(
            '%s %s %s %s 2>&1',
            escapeshellarg($pythonCmd),
            escapeshellarg($scriptPath),
            escapeshellarg($jsonPath),
            escapeshellarg($outputPath)
        );
        exec($command, $output, $exitCode);
        if ($exitCode === 0 && is_file($outputPath)) {
            break;
        }
    }
    @unlink($jsonPath);

    if ($exitCode !== 0 || !is_file($outputPath)) {
        return [
            'ok' => false,
            'error' => 'Excel generation failed. ' . trim(implode("\n", $output)),
        ];
    }

    return ['ok' => true, 'path' => $outputPath];
}

function stream_budget_excel_download(array $cart, string $department, string $project_title, string $revised_date): void
{
    $result = generate_budget_excel_file($cart, $department, $project_title, $revised_date);
    if (!$result['ok']) {
        $_SESSION['message'] = ['type' => 'error', 'text' => $result['error']];
        header('Location: abc_generator.php');
        exit;
    }

    $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $project_title);
    $filename = ($safeName ?: 'Procurement_Request') . '_' . date('Y-m-d') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($result['path']));
    readfile($result['path']);
    @unlink($result['path']);
    exit;
}
