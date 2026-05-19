import html
import math
import re
from pathlib import Path
from typing import Any

import pandas as pd
from flask import Flask, flash, redirect, render_template, request, url_for
from werkzeug.utils import secure_filename

BASE_DIR = Path(__file__).resolve().parent
EXCEL_FOLDER = BASE_DIR / "excel_files"
ALLOWED_EXTENSIONS = {".xlsx", ".xls"}
RESULTS_PER_PAGE = 10

app = Flask(__name__)
app.config["SECRET_KEY"] = "replace-with-a-strong-secret-key"
app.config["UPLOAD_FOLDER"] = str(EXCEL_FOLDER)
app.config["MAX_CONTENT_LENGTH"] = 16 * 1024 * 1024  # 16MB upload limit

# Global in-memory pseudo database.
INDEX_DF: pd.DataFrame = pd.DataFrame()


def ensure_excel_folder() -> None:
    """Create excel folder if it does not exist."""
    EXCEL_FOLDER.mkdir(parents=True, exist_ok=True)


def allowed_file(filename: str) -> bool:
    """Check whether uploaded filename has a valid Excel extension."""
    return Path(filename).suffix.lower() in ALLOWED_EXTENSIONS


def load_excel_files_to_index() -> tuple[int, int]:
    """
    Load every Excel file into a single in-memory DataFrame.

    Returns:
        (loaded_files_count, skipped_files_count)
    """
    global INDEX_DF

    ensure_excel_folder()
    dataframes: list[pd.DataFrame] = []
    loaded_files = 0
    skipped_files = 0

    for file_path in EXCEL_FOLDER.iterdir():
        if not file_path.is_file() or file_path.suffix.lower() not in ALLOWED_EXTENSIONS:
            continue
        try:
            df = pd.read_excel(file_path)
            if df.empty:
                continue
            df["source_file"] = file_path.name
            dataframes.append(df)
            loaded_files += 1
        except Exception:
            # Skip invalid/corrupted files but keep app running.
            skipped_files += 1

    if dataframes:
        combined = pd.concat(dataframes, ignore_index=True)
        INDEX_DF = combined.fillna("")
    else:
        INDEX_DF = pd.DataFrame()

    return loaded_files, skipped_files


def filter_dataframe_by_keyword(df: pd.DataFrame, keyword: str) -> pd.DataFrame:
    """Perform case-insensitive search across all columns."""
    if df.empty:
        return df
    if not keyword.strip():
        return df

    escaped_keyword = re.escape(keyword.strip())
    mask = df.astype(str).apply(
        lambda col: col.str.contains(escaped_keyword, case=False, na=False, regex=True)
    ).any(axis=1)
    return df[mask]


def highlight_keyword(value: Any, keyword: str) -> str:
    """Safely highlight keyword in a string value using <mark>."""
    text = "" if value is None else str(value)
    escaped_text = html.escape(text)
    if not keyword.strip():
        return escaped_text

    pattern = re.compile(re.escape(keyword.strip()), re.IGNORECASE)
    return pattern.sub(lambda match: f"<mark>{match.group(0)}</mark>", escaped_text)


def dataframe_to_display_records(df: pd.DataFrame, keyword: str) -> tuple[list[str], list[dict[str, str]]]:
    """Convert DataFrame rows into template-friendly highlighted records."""
    if df.empty:
        return [], []

    columns = [str(col) for col in df.columns]
    records: list[dict[str, str]] = []

    for _, row in df.iterrows():
        rendered_row: dict[str, str] = {}
        for col in columns:
            rendered_row[col] = highlight_keyword(row[col], keyword)
        records.append(rendered_row)

    return columns, records


@app.route("/", methods=["GET"])
def index() -> str:
    query = request.args.get("q", "").strip()
    show_all = request.args.get("show_all", "").lower() in {"1", "true", "on", "yes"}
    page = request.args.get("page", 1, type=int)
    page = max(page, 1)

    if INDEX_DF.empty:
        filtered_df = INDEX_DF
    else:
        filtered_df = filter_dataframe_by_keyword(INDEX_DF, query)

    total_results = len(filtered_df)
    if show_all:
        page = 1
        total_pages = 1
        result_df = filtered_df
    else:
        total_pages = max(1, math.ceil(total_results / RESULTS_PER_PAGE)) if total_results else 1
        page = min(page, total_pages)
        start_idx = (page - 1) * RESULTS_PER_PAGE
        end_idx = start_idx + RESULTS_PER_PAGE
        result_df = filtered_df.iloc[start_idx:end_idx]

    columns, records = dataframe_to_display_records(result_df, query)

    return render_template(
        "index.html",
        query=query,
        columns=columns,
        records=records,
        total_results=total_results,
        current_page=page,
        total_pages=total_pages,
        has_prev=page > 1,
        has_next=page < total_pages,
        show_all=show_all,
    )


@app.route("/upload", methods=["GET", "POST"])
def upload() -> str:
    if request.method == "POST":
        if "excel_file" not in request.files:
            flash("No file part found in request.", "error")
            return redirect(url_for("upload"))

        file = request.files["excel_file"]
        if file.filename == "":
            flash("No file selected.", "error")
            return redirect(url_for("upload"))

        if not allowed_file(file.filename):
            flash("Invalid file type. Please upload .xlsx or .xls.", "error")
            return redirect(url_for("upload"))

        filename = secure_filename(file.filename)
        destination = EXCEL_FOLDER / filename
        file.save(destination)

        loaded_count, skipped_count = load_excel_files_to_index()
        flash(
            f"Upload successful. Index reloaded: {loaded_count} file(s) loaded, {skipped_count} file(s) skipped.",
            "success",
        )
        return redirect(url_for("index"))

    return render_template("upload.html")


@app.route("/reload", methods=["POST"])
def reload_index() -> str:
    loaded_count, skipped_count = load_excel_files_to_index()
    flash(
        f"Index reloaded: {loaded_count} file(s) loaded, {skipped_count} file(s) skipped.",
        "success",
    )
    return redirect(url_for("index"))


if __name__ == "__main__":
    load_excel_files_to_index()
    app.run(debug=True)
