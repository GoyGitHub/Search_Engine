import re
import time
from pathlib import Path
from typing import Optional

import mysql.connector
import requests
from bs4 import BeautifulSoup

BASE_DIR = Path(__file__).resolve().parent
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'PROCUREMENT',
    'auth_plugin': 'mysql_native_password',
}

HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36'
}

SEARCH_SOURCES = [
    {
        'name': 'DataBlitz',
        'url': 'https://www.datablitz.com.ph/search?search_query={query}',
        'parser': 'parse_datablitz',
    },
    {
        'name': 'OfficeWarehouse',
        'url': 'https://officewarehouse.com.ph/search?search_query={query}',
        'parser': 'parse_officewarehouse',
    },
]


def get_connection() -> mysql.connector.connection.MySQLConnection:
    return mysql.connector.connect(**DB_CONFIG)


def normalize_query(text: str) -> str:
    return re.sub(r'\s+', '+', text.strip())


def extract_price(text: str) -> Optional[float]:
    if not text:
        return None
    numeric = re.sub(r'[^0-9.]', '', text)
    try:
        return float(numeric)
    except ValueError:
        return None


def parse_datablitz(html: str, base_url: str) -> Optional[tuple[float, str, str]]:
    soup = BeautifulSoup(html, 'html.parser')
    results = soup.select('div.product-item, div.product, .product-list-item')
    for item in results[:5]:
        price_text = item.select_one('.product-price, .price, .price-sales')
        link = item.select_one('a')
        if price_text and link:
            price = extract_price(price_text.get_text())
            if price is not None:
                return price, 'DataBlitz', base_url + link.get('href') if link.get('href') else base_url
    return None


def parse_officewarehouse(html: str, base_url: str) -> Optional[tuple[float, str, str]]:
    soup = BeautifulSoup(html, 'html.parser')
    results = soup.select('div.product-item, div.item, .product-card')
    for item in results[:5]:
        price_text = item.select_one('.product-price, .price, .price-now, .price-sales')
        link = item.select_one('a')
        if price_text and link:
            price = extract_price(price_text.get_text())
            if price is not None:
                return price, 'OfficeWarehouse', base_url + link.get('href') if link.get('href') else base_url
    return None


def fetch_price_for_product(query: str) -> Optional[tuple[float, str, str]]:
    search_query = normalize_query(query)
    for source in SEARCH_SOURCES:
        url = source['url'].format(query=search_query)
        try:
            response = requests.get(url, headers=HEADERS, timeout=15)
            if response.status_code != 200:
                continue
            parser = globals().get(source['parser'])
            if not parser:
                continue
            result = parser(response.text, url)
            if result:
                return result
        except requests.RequestException:
            continue
        time.sleep(1)
    return None


def sync_latest_prices() -> int:
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute('SELECT id, brand, item_name, model FROM products ORDER BY updated_at DESC LIMIT 200')
    products = cursor.fetchall()
    updated = 0

    for product in products:
        search_terms = [product['brand'], product['item_name'], product['model']]
        keyword = ' '.join(term for term in search_terms if term)
        if not keyword:
            continue

        price_data = fetch_price_for_product(keyword)
        if not price_data:
            continue

        price, source_name, source_url = price_data
        cursor.execute(
            '''
            UPDATE products
            SET latest_price = %s,
                price_source = %s,
                price_url = %s,
                last_price_updated = NOW()
            WHERE id = %s
            ''',
            (price, source_name, source_url, product['id'])
        )
        conn.commit()
        updated += 1

    cursor.close()
    conn.close()
    return updated


if __name__ == '__main__':
    updated_count = sync_latest_prices()
    print(f'Updated largest prices for {updated_count} products.')
