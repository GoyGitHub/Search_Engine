# Excel File Search Engine System (No Database, In-Memory Indexing)

This project is a Flask-based web application that reads and searches multiple Excel files without using any database.

## Folder Structure

```text
Search_Engine/
├─ app.py
├─ requirements.txt
├─ README.md
├─ excel_files/           # Put your .xlsx / .xls files here
├─ static/
│  └─ style.css
└─ templates/
   ├─ index.html
   └─ upload.html
```

## Features

- Load all Excel files from `excel_files` at startup
- Merge into one in-memory pandas DataFrame (pseudo database)
- Add `source_file` field to each row
- Search keyword across all columns (case-insensitive)
- Highlight keyword matches with `<mark>`
- Paginated result table (10 records per page)
- Upload new Excel files from web interface
- Reload button for manual reindexing
- Basic validation for invalid file types

## Installation

1. Open terminal in project root.
2. Create and activate virtual environment (recommended):

### Windows (PowerShell)

```powershell
python -m venv .venv
.venv\Scripts\Activate.ps1
```

3. Install dependencies:

```powershell
pip install -r requirements.txt
```

## Run the Application

```powershell
python app.py
```

Open:

- [http://127.0.0.1:5000](http://127.0.0.1:5000)

## How to Use

1. Put Excel files in `excel_files` folder, or upload from `/upload`.
2. Use the search bar on the home page to find any keyword.
3. Navigate pages using `Previous` / `Next`.
4. Click `Reload Index` if files were added manually.

## Notes

- No SQL database is used.
- Data is loaded into memory once and reused for searches.
- Large Excel volumes may require more RAM.
- Change `SECRET_KEY` in `app.py` before production deployment.
