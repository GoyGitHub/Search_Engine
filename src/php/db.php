<?php
function create_database_if_not_exists(): void
{
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $dsn = "mysql:host=$host;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS PROCUREMENT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (PDOException $e) {
        die('Database creation failed: ' . $e->getMessage());
    }
}

function ensure_schema(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
    );

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    model VARCHAR(255) DEFAULT '',
    specs JSON DEFAULT NULL,
    unit VARCHAR(50) DEFAULT 'Each',
    unit_cost DECIMAL(12,2) DEFAULT 0.00,
    latest_price DECIMAL(12,2) DEFAULT NULL,
    price_source VARCHAR(255) DEFAULT NULL,
    price_url VARCHAR(500) DEFAULT NULL,
    abc_class CHAR(1) NOT NULL DEFAULT 'C',
    source VARCHAR(150) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_price_updated TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY unique_product (category, brand, model, item_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
    );

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE username = :username');
    $stmt->execute(['username' => 'admin']);
    if ((int)$stmt->fetchColumn() === 0) {
        $passwordHash = password_hash('admin', PASSWORD_DEFAULT);
        $insert = $pdo->prepare('INSERT INTO accounts (username, password_hash, role) VALUES (:username, :password_hash, :role)');
        $insert->execute([
            'username' => 'admin',
            'password_hash' => $passwordHash,
            'role' => 'admin',
        ]);
    }

    $productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($productCount === 0) {
        seed_sample_products($pdo);
    }
}

function get_abc_class(float $unitCost): string
{
    if ($unitCost >= 5000.00) {
        return 'A';
    }
    if ($unitCost >= 1000.00) {
        return 'B';
    }
    return 'C';
}

function seed_sample_products(PDO $pdo): void
{
    $samples = [
        [
            'category' => 'RAM',
            'brand' => 'Corsair',
            'item_name' => 'Vengeance LPX 16GB DDR4 3200MHz',
            'model' => 'CMK16GX4M2B3200C16',
            'unit' => 'kit',
            'unit_cost' => 85.99,
            'specs' => ['capacity' => '16GB (2 x 8GB)', 'speed' => '3200MHz', 'type' => 'DDR4', 'latency' => 'CL16'],
        ],
        [
            'category' => 'Motherboard',
            'brand' => 'ASUS',
            'item_name' => 'ROG Strix B550-F Gaming',
            'model' => 'B550-F',
            'unit' => 'piece',
            'unit_cost' => 189.99,
            'specs' => ['chipset' => 'AMD B550', 'socket' => 'AM4', 'memory' => '4 x DDR4', 'pci_express' => 'PCIe 4.0'],
        ],
        [
            'category' => 'SSD',
            'brand' => 'Samsung',
            'item_name' => '970 EVO Plus 1TB',
            'model' => 'MZ-V7S1T0B/AM',
            'unit' => 'piece',
            'unit_cost' => 119.99,
            'specs' => ['type' => 'NVMe', 'capacity' => '1TB', 'read_speed' => '3500MB/s', 'write_speed' => '3300MB/s'],
        ],
        [
            'category' => 'Mouse',
            'brand' => 'Logitech',
            'item_name' => 'MX Master 3',
            'model' => '910-005620',
            'unit' => 'piece',
            'unit_cost' => 99.99,
            'specs' => ['connection' => 'Wireless', 'dpi' => '4000', 'buttons' => '7'],
        ],
        [
            'category' => 'Keyboard',
            'brand' => 'Razer',
            'item_name' => 'BlackWidow V3',
            'model' => 'RZ03-03510100-R3M1',
            'unit' => 'piece',
            'unit_cost' => 129.99,
            'specs' => ['switch_type' => 'Green Mechanical', 'connection' => 'USB', 'backlight' => 'RGB'],
        ],
        [
            'category' => 'Monitor',
            'brand' => 'Dell',
            'item_name' => 'UltraSharp 24 USB-C',
            'model' => 'U2422H',
            'unit' => 'piece',
            'unit_cost' => 299.99,
            'specs' => ['size' => '24-inch', 'resolution' => '1920x1200', 'panel' => 'IPS'],
        ],
        [
            'category' => 'Printer',
            'brand' => 'HP',
            'item_name' => 'LaserJet Pro M404dn',
            'model' => 'W1A53A',
            'unit' => 'piece',
            'unit_cost' => 289.99,
            'specs' => ['type' => 'Monochrome Laser', 'network' => 'Ethernet', 'speed' => '40 ppm'],
        ],
    ];

    $insert = $pdo->prepare(
        'INSERT INTO products (category, brand, item_name, model, specs, unit, unit_cost, abc_class, source) VALUES (:category, :brand, :item_name, :model, :specs, :unit, :unit_cost, :abc_class, :source)'
    );

    foreach ($samples as $product) {
        $insert->execute([
            'category' => $product['category'],
            'brand' => $product['brand'],
            'item_name' => $product['item_name'],
            'model' => $product['model'],
            'specs' => json_encode($product['specs'], JSON_UNESCAPED_UNICODE),
            'unit' => $product['unit'],
            'unit_cost' => $product['unit_cost'],
            'abc_class' => get_abc_class((float)$product['unit_cost']),
            'source' => 'sample_seed',
        ]);
    }
}

function get_db_connection(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        create_database_if_not_exists();

        $host = '127.0.0.1';
        $db = 'PROCUREMENT';
        $user = 'root';
        $pass = '';
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            ensure_schema($pdo);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}
