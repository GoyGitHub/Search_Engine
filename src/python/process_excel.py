import json
import re
from pathlib import Path
from typing import Any

import pandas as pd

BASE_DIR = Path(__file__).resolve().parent
EXCEL_FOLDER = BASE_DIR.parent.parent / 'excel_files'
OUTPUT_JSON = EXCEL_FOLDER / 'items.json'

KNOWN_ITEM_KEYS = ['product_name', 'product', 'name', 'item', 'item_name', 'title']
KNOWN_CATEGORY_KEYS = ['category', 'type', 'item_type', 'group']
KNOWN_BRAND_KEYS = ['brand', 'manufacturer', 'vendor', 'maker']
KNOWN_MODEL_KEYS = ['model', 'sku', 'part_number', 'part no', 'series']
KNOWN_UNIT_KEYS = ['unit', 'quantity', 'qty', 'uom', 'measure']
KNOWN_COST_KEYS = ['unit_cost', 'price', 'cost', 'amount', 'rate', 'unitprice']
KNOWN_DESC_KEYS = ['description', 'details', 'notes']
INVALID_ITEM_CATEGORY_PATTERNS = [
    'major awards',
    'special awards',
    'consolation prize',
    'honorarium',
    'tokens',
    'award',
]


def normalize_text(value: Any) -> str:
    if value is None:
        return ''
    return str(value).strip()


def is_blacklisted_category(category: str) -> bool:
    cleaned = str(category).strip().lower()
    return any(pattern in cleaned for pattern in INVALID_ITEM_CATEGORY_PATTERNS)


def is_valid_item_name(item_name: str) -> bool:
    if not item_name:
        return False
    name = item_name.strip().lower()
    if name == 'total' or name.startswith('total '):
        return False
    return True


def parse_cost(value: Any) -> str:
    text = normalize_text(value)
    if text == '':
        return ''
    numeric = ''.join(ch for ch in text if ch.isdigit() or ch in '.-,')
    numeric = numeric.replace(',', '')
    if numeric == '':
        return text
    try:
        return str(float(numeric))
    except ValueError:
        return text


def get_first_value(row: dict[str, str], keys: list[str]) -> str:
    for key in keys:
        if key in row and row[key] != '':
            return row[key]
    return ''


def map_row_to_item(row: dict[str, str], source_file: str) -> dict[str, Any] | None:
    item_name = get_first_value(row, KNOWN_ITEM_KEYS)
    if not item_name:
        return None

    category = get_first_value(row, KNOWN_CATEGORY_KEYS) or 'General'
    brand = get_first_value(row, KNOWN_BRAND_KEYS) or 'Unknown'
    model = get_first_value(row, KNOWN_MODEL_KEYS)
    unit = get_first_value(row, KNOWN_UNIT_KEYS) or 'Each'
    unit_cost = parse_cost(get_first_value(row, KNOWN_COST_KEYS))

    specs = {}
    for column, value in row.items():
        if column in KNOWN_ITEM_KEYS + KNOWN_CATEGORY_KEYS + KNOWN_BRAND_KEYS + KNOWN_MODEL_KEYS + KNOWN_UNIT_KEYS + KNOWN_COST_KEYS + KNOWN_DESC_KEYS:
            continue
        if value != '':
            specs[column] = value

    description = get_first_value(row, KNOWN_DESC_KEYS)
    if description:
        specs['description'] = description

    return {
        'category': category,
        'brand': brand,
        'item_name': item_name,
        'model': model,
        'unit': unit,
        'unit_cost': unit_cost,
        'specs': specs or None,
        'source': source_file,
    }


def normalize_label(value: str) -> str:
    value = str(value).strip().lower()
    if value == '':
        return ''
    return re.sub(r'[^a-z0-9]+', '_', value)


def find_abc_sheet_names(excel_file: pd.ExcelFile) -> list[str]:
    return [name for name in excel_file.sheet_names if 'abc' in str(name).strip().lower()]


def clean_category(value: str) -> str:
    cleaned = str(value).strip()
    cleaned = re.sub(r'\s*\(\s*50\s*sets?\s*\)\s*', '', cleaned, flags=re.I)
    cleaned = re.sub(r'\s*\(\s*\d+\s*sets?\s*\)\s*', '', cleaned, flags=re.I)
    return cleaned.title()


def parse_abc_sheet(file_path: Path, sheet_name: str, items: list[dict[str, Any]]) -> bool:
    df = pd.read_excel(file_path, sheet_name=sheet_name, dtype=str, header=None)
    df = df.fillna('')

    header_row_idx = None
    for i, row in df.iterrows():
        normalized = [str(cell).strip().upper() for cell in row.values]
        if any(cell in {'ITEM NO.', 'ITEM NO', 'ITEM #'} for cell in normalized):
            header_row_idx = i
            break

    if header_row_idx is None:
        return False

    headers = []
    for idx, header_value in enumerate(df.iloc[header_row_idx].values):
        label = normalize_label(header_value)
        headers.append(label if label else f'unnamed_{idx}')

    current_category = ''
    for _, row in df.iloc[header_row_idx + 1 :].iterrows():
        values = [str(cell).strip() for cell in row.values]
        if len(values) < 2:
            continue

        is_section_header = values[0] == '' and values[1] != '' and all(v == '' for v in values[2:])
        if is_section_header:
            current_category = clean_category(values[1]) or 'Office Supplies'
            continue

        if values[0] == '' and values[1] == '':
            continue

        if re.fullmatch(r'\(\d+\)', values[0]):
            continue

        if not re.fullmatch(r'\d+', values[0]):
            continue

        row_dict = {headers[i]: normalize_text(values[i]) for i in range(min(len(values), len(headers)))}
        item_name = row_dict.get('description') or row_dict.get('unnamed_1') or row_dict.get('item_name') or row_dict.get('item')
        if not is_valid_item_name(item_name):
            continue

        if is_blacklisted_category(current_category):
            continue

        unit_cost = parse_cost(row_dict.get('unit_cost') or row_dict.get('current_market_price') or row_dict.get('total_cost'))
        if unit_cost == '' and row_dict.get('unit_cost') == '0':
            unit_cost = '0'

        specs = {}
        if row_dict.get('qty'):
            specs['qty'] = row_dict.get('qty')
        if row_dict.get('total_cost'):
            specs['total_cost'] = row_dict.get('total_cost')
        if row_dict.get('current_market_price'):
            specs['current_market_price'] = row_dict.get('current_market_price')
        if row_dict.get('other_cost_factors'):
            specs['other_cost_factors'] = row_dict.get('other_cost_factors')

        items.append({
            'category': current_category or 'Office Supplies',
            'brand': 'Unknown',
            'item_name': item_name,
            'model': row_dict.get('model') or '',
            'unit': row_dict.get('unit') or '',
            'unit_cost': unit_cost,
            'specs': specs or None,
            'source': f'{file_path.name} / {sheet_name}',
        })

    return True


def load_excel_files_to_json() -> tuple[int, int]:
    EXCEL_FOLDER.mkdir(parents=True, exist_ok=True)
    items = []
    loaded_files = 0
    skipped_files = 0

    for file_path in EXCEL_FOLDER.iterdir():
        if not file_path.is_file() or file_path.suffix.lower() not in {'.xlsx', '.xls'}:
            continue
        if file_path.name.startswith('~$'):
            continue

        try:
            excel = pd.ExcelFile(file_path)
            abc_sheets = find_abc_sheet_names(excel)
            if not abc_sheets:
                skipped_files += 1
                continue

            sheet_loaded = False
            for sheet_name in abc_sheets:
                if parse_abc_sheet(file_path, sheet_name, items):
                    sheet_loaded = True
            if sheet_loaded:
                loaded_files += 1
            else:
                skipped_files += 1
        except Exception as exc:
            print(f'Could not parse {file_path.name}: {exc}')
            skipped_files += 1

    with OUTPUT_JSON.open('w', encoding='utf-8') as json_file:
        json.dump(items, json_file, ensure_ascii=False, indent=2)

    print(f'Exported {len(items)} item(s) from {loaded_files} Excel file(s).')
    return loaded_files, skipped_files


if __name__ == '__main__':
    load_excel_files_to_json()
