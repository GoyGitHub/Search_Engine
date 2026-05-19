from pathlib import Path
import json
import mysql.connector

BASE_DIR = Path(__file__).resolve().parent
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'PROCUREMENT',
    'auth_plugin': 'mysql_native_password',
}

PRODUCTS = [
    {
        'category': 'RAM',
        'brand': 'Corsair',
        'item_name': 'Vengeance LPX 16GB DDR4 3200MHz',
        'model': 'CMK16GX4M2B3200C16',
        'unit': 'kit',
        'unit_cost': 85.99,
        'specs': {
            'capacity': '16GB (2 x 8GB)',
            'speed': '3200MHz',
            'type': 'DDR4',
            'latency': 'CL16',
        },
    },
    {
        'category': 'RAM',
        'brand': 'G.Skill',
        'item_name': 'Ripjaws V 32GB DDR4 3600MHz',
        'model': 'F4-3600C16D-32GVK',
        'unit': 'kit',
        'unit_cost': 164.99,
        'specs': {
            'capacity': '32GB (2 x 16GB)',
            'speed': '3600MHz',
            'type': 'DDR4',
            'latency': 'CL16',
        },
    },
    {
        'category': 'Motherboard',
        'brand': 'ASUS',
        'item_name': 'ROG Strix B550-F Gaming',
        'model': 'B550-F',
        'unit': 'piece',
        'unit_cost': 189.99,
        'specs': {
            'chipset': 'AMD B550',
            'socket': 'AM4',
            'memory': '4 x DDR4',
            'pci_express': 'PCIe 4.0',
        },
    },
    {
        'category': 'Motherboard',
        'brand': 'MSI',
        'item_name': 'MAG B660M Mortar',
        'model': 'B660M MORTAR',
        'unit': 'piece',
        'unit_cost': 154.99,
        'specs': {
            'chipset': 'Intel B660',
            'socket': 'LGA1700',
            'memory': '4 x DDR5',
            'form_factor': 'Micro-ATX',
        },
    },
    {
        'category': 'SSD',
        'brand': 'Samsung',
        'item_name': '970 EVO Plus 1TB',
        'model': 'MZ-V7S1T0B/AM',
        'unit': 'piece',
        'unit_cost': 119.99,
        'specs': {
            'type': 'NVMe',
            'capacity': '1TB',
            'read_speed': '3500MB/s',
            'write_speed': '3300MB/s',
        },
    },
    {
        'category': 'HDD',
        'brand': 'Western Digital',
        'item_name': 'Blue 2TB HDD',
        'model': 'WD20EZAZ',
        'unit': 'piece',
        'unit_cost': 54.99,
        'specs': {
            'type': '3.5-inch',
            'capacity': '2TB',
            'speed': '5400 RPM',
            'cache': '256MB',
        },
    },
    {
        'category': 'Mouse',
        'brand': 'Logitech',
        'item_name': 'MX Master 3',
        'model': '910-005620',
        'unit': 'piece',
        'unit_cost': 99.99,
        'specs': {
            'connection': 'Wireless',
            'dpi': '4000',
            'buttons': '7',
        },
    },
    {
        'category': 'Keyboard',
        'brand': 'Razer',
        'item_name': 'BlackWidow V3',
        'model': 'RZ03-03510100-R3M1',
        'unit': 'piece',
        'unit_cost': 129.99,
        'specs': {
            'switch_type': 'Green Mechanical',
            'connection': 'USB',
            'backlight': 'RGB',
        },
    },
    {
        'category': 'Printer',
        'brand': 'HP',
        'item_name': 'LaserJet Pro M404dn',
        'model': 'W1A53A',
        'unit': 'piece',
        'unit_cost': 289.99,
        'specs': {
            'type': 'Monochrome Laser',
            'network': 'Ethernet',
            'speed': '40 ppm',
        },
    },
    {
        'category': 'Stapler',
        'brand': 'Swingline',
        'item_name': 'Totally Smooth Stapler',
        'model': '74727',
        'unit': 'piece',
        'unit_cost': 16.99,
        'specs': {
            'capacity': '20 sheets',
            'finish': 'Matte Black',
        },
    },
    {
        'category': 'Bond Paper',
        'brand': 'Double A',
        'item_name': 'Premium Office Paper 80gsm',
        'model': 'DA80',
        'unit': 'ream',
        'unit_cost': 14.50,
        'specs': {
            'size': 'A4',
            'weight': '80 gsm',
            'pages': '500',
        },
    },
    {
        'category': 'Logbook',
        'brand': 'National',
        'item_name': 'Official Logbook',
        'model': 'NL-112',
        'unit': 'piece',
        'unit_cost': 11.75,
        'specs': {
            'pages': '200',
            'size': '8.5 x 11',
            'cover': 'Hardbound',
        },
    },
    {
        'category': 'Monitor',
        'brand': 'Dell',
        'item_name': 'UltraSharp 24 USB-C',
        'model': 'U2422H',
        'unit': 'piece',
        'unit_cost': 299.99,
        'specs': {
            'size': '24-inch',
            'resolution': '1920x1200',
            'panel': 'IPS',
        },
    },
]

CREATE_TABLE_SQL = '''
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    model VARCHAR(255) DEFAULT '',
    specs JSON DEFAULT NULL,
    unit VARCHAR(50) DEFAULT 'Each',
    unit_cost DECIMAL(12,2) DEFAULT 0.00,
    abc_class CHAR(1) NOT NULL DEFAULT 'C',
    source VARCHAR(150) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product (category, brand, model, item_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
'''

ABC_THRESHOLDS = [
    ('A', 5000.00),
    ('B', 1000.00),
    ('C', 0.00),
]


def get_abc_class(cost: float) -> str:
    for label, threshold in ABC_THRESHOLDS:
        if cost >= threshold:
            return label
    return 'C'


def create_schema(connection):
    cursor = connection.cursor()
    cursor.execute('CREATE DATABASE IF NOT EXISTS PROCUREMENT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')
    connection.database = 'PROCUREMENT'
    cursor.execute(CREATE_TABLE_SQL)
    cursor.close()


def insert_product(connection, product):
    cursor = connection.cursor()
    sql = '''
    INSERT INTO products (category, brand, item_name, model, specs, unit, unit_cost, abc_class, source)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
        specs = VALUES(specs),
        unit = VALUES(unit),
        unit_cost = VALUES(unit_cost),
        abc_class = VALUES(abc_class),
        source = VALUES(source),
        updated_at = CURRENT_TIMESTAMP
    '''
    cursor.execute(sql, (
        product['category'],
        product['brand'],
        product['item_name'],
        product['model'],
        json.dumps(product['specs'], ensure_ascii=False) if product['specs'] else None,
        product['unit'],
        float(product['unit_cost']),
        product['abc_class'],
        product['source'],
    ))
    connection.commit()
    cursor.close()


def main():
    connection = mysql.connector.connect(**DB_CONFIG)
    create_schema(connection)
    for product in PRODUCTS:
        product['abc_class'] = get_abc_class(float(product['unit_cost']))
        product['source'] = 'sample_seed'
        insert_product(connection, product)
    connection.close()
    print(f'Successfully inserted {len(PRODUCTS)} sample procurement products into PROCUREMENT.products')


if __name__ == '__main__':
    main()
