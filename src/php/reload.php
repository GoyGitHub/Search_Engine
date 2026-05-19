<?php
require_once __DIR__ . '/auth.php';
require_admin();

$pythonBase = dirname(__DIR__, 2) . '/src/python';
$importScript = $pythonBase . '/process_excel.py';

$importOutput = shell_exec('python "' . $importScript . '" 2>&1');

$_SESSION['message'] = [
    'type' => 'success',
    'text' => 'Reload completed. ' . trim($importOutput),
];

header('Location: index.php');
exit;
