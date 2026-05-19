# Excel File Search Engine System (Python + PHP)

This project is a web application that reads Excel files using Python and displays/search data using PHP without using any database.

## Folder Structure

```text
Search_Engine/
├─ process_excel.py       # Python script to read Excel files and generate data.json
├─ data.json              # Generated data file (JSON)
├─ index.php              # Main search page (PHP)
├─ upload.php             # Upload page (PHP)
├─ reload.php             # Reload script (PHP)
├─ requirements.txt
├─ README.md
├─ excel_files/           # Put your .xlsx / .xls files here
├─ static/
│  └─ style.css
└─ templates/             # Old Flask templates (kept for reference)
   ├─ index.html
   └─ upload.html
```

## Features

- Python loads all Excel files from `excel_files` and saves to `data.json`
- PHP reads `data.json` for search and display
- Merge into one JSON structure (pseudo database)
- Add `source_file` field to each row
- Search keyword across all columns (case-insensitive)
- Highlight keyword matches with `<mark>`
- Paginated result table (10 records per page)
- Upload new Excel files from web interface
- Reload button for manual reindexing
- Basic validation for invalid file types

## Installation

1. Ensure Python and PHP are installed.
2. Install Python dependencies:

### Windows (PowerShell)

```powershell
pip install -r requirements.txt
```

3. Place the project in your web server's document root (e.g., XAMPP's htdocs).

## Setup

1. Run the Python script to process Excel files:

```powershell
python process_excel.py
```

This generates `data.json` from all Excel files in `excel_files/`.

## Run the Application

Open in your web browser:

- [http://localhost/index.php](http://localhost/index.php)

## How to Use

1. Put Excel files in `excel_files` folder, or upload from `upload.php`.
2. Use the search bar on `index.php` to find any keyword.
3. Navigate pages using `Previous` / `Next`.
4. Click `Reload Index` if files were added manually.

## Notes

- No SQL database is used.
- Data is stored in JSON file.
- Large Excel volumes may require more RAM and larger JSON files.
- Python handles Excel processing, PHP handles display for optimization.
