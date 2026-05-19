<?php
require_once __DIR__ . '/auth.php';
require_admin();

$BASE_URL = dirname($_SERVER['SCRIPT_NAME'], 3);
if ($BASE_URL === DIRECTORY_SEPARATOR) {
    $BASE_URL = '';
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $message = ['type' => 'error', 'text' => 'No file uploaded or upload error.'];
    } else {
        $file = $_FILES['excel_file'];
        $filename = basename($file['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $message = ['type' => 'error', 'text' => 'Invalid file type. Please upload .xlsx or .xls.'];
        } else {
            $uploadDir = dirname(__DIR__, 2) . '/excel_files/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $pythonBase = dirname(__DIR__, 2) . '/src/python';
                $importScript = $pythonBase . '/process_excel.py';

                $importOutput = shell_exec('python "' . $importScript . '" 2>&1');
                $message = [
                    'type' => 'success',
                    'text' => 'Upload successful. ' . trim($importOutput),
                ];
            } else {
                $message = ['type' => 'error', 'text' => 'Failed to save file.'];
            }
        }
    }

    $_SESSION['message'] = $message;
    header('Location: upload.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Excel File</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
    <script src="<?php echo $BASE_URL; ?>/static/accessibility.js" defer></script>
</head>
<body>
    <header class="site-header glass">
        <div class="header-inner">
            <a href="index.php" class="brand">
                <img src="<?php echo $BASE_URL; ?>/static/img/logo_montalban.png" alt="Company Logo" class="brand-logo">
                <div class="brand-text">
                    <span class="brand-title">Excel Upload</span>
                    <span class="brand-subtitle">Add new spreadsheet items for display</span>
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
            <h1>Upload Excel File</h1>
            <p class="subtitle">Only admins may upload Excel files for the shared item table.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert <?php echo $_SESSION['message']['type']; ?>"><?php echo $_SESSION['message']['text']; ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <form action="upload.php" method="POST" enctype="multipart/form-data" class="upload-form card">
                <input type="file" name="excel_file" accept=".xlsx,.xls" required>
                <button type="submit" class="button icon-btn"><span>📥</span> Upload and Refresh</button>
            </form>
        </div>
    </main>
</body>
</html>
