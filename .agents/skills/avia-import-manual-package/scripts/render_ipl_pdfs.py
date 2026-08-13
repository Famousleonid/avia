#!/usr/bin/env python3
"""Discover and render numbered AVIA IPL PDFs without modifying sources."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
from datetime import datetime, timezone
from pathlib import Path

import pypdfium2 as pdfium


FIG_PDF = re.compile(r"^(?P<fig>\d+[A-Za-z]?)\.pdf$", re.IGNORECASE)
FIG_CSV = re.compile(r"^(?P<fig>\d+[A-Za-z]?)\.csv$", re.IGNORECASE)


def natural_fig_key(value: str) -> tuple[int, str]:
    match = re.fullmatch(r"(\d+)([A-Za-z]?)", value)
    if not match:
        return (10**9, value.upper())
    return (int(match.group(1)), match.group(2).upper())


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def file_record(path: Path) -> dict[str, object]:
    stat = path.stat()
    return {
        "name": path.name,
        "path": str(path.resolve()),
        "size": stat.st_size,
        "sha256": sha256(path),
    }


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Create a source manifest and optionally render numbered IPL PDFs at 300 DPI."
    )
    parser.add_argument("--source-dir", required=True)
    parser.add_argument("--output-dir", required=True)
    parser.add_argument("--manual-number", required=True)
    parser.add_argument("--dpi", type=int, default=300)
    parser.add_argument("--manifest-only", action="store_true")
    args = parser.parse_args()

    if not re.fullmatch(r"\d{2}-\d{2}-\d{2}", args.manual_number.strip()):
        raise SystemExit("manual number must use NN-NN-NN format")
    if args.dpi < 150 or args.dpi > 600:
        raise SystemExit("dpi must be between 150 and 600")

    source_dir = Path(args.source_dir).resolve()
    output_dir = Path(args.output_dir).resolve()
    if not source_dir.is_dir():
        raise SystemExit(f"source directory not found: {source_dir}")
    output_dir.mkdir(parents=True, exist_ok=True)

    pdf_by_fig: dict[str, Path] = {}
    csv_by_fig: dict[str, Path] = {}
    workbooks: list[Path] = []

    for path in source_dir.iterdir():
        if not path.is_file():
            continue
        pdf_match = FIG_PDF.fullmatch(path.name)
        csv_match = FIG_CSV.fullmatch(path.name)
        if pdf_match:
            fig = f"{int(re.match(r'\d+', pdf_match.group('fig')).group())}{pdf_match.group('fig')[len(re.match(r'\d+', pdf_match.group('fig')).group()):].upper()}"
            if fig in pdf_by_fig:
                raise SystemExit(f"duplicate FIG PDF for {fig}: {pdf_by_fig[fig].name}, {path.name}")
            pdf_by_fig[fig] = path
        elif csv_match:
            fig = f"{int(re.match(r'\d+', csv_match.group('fig')).group())}{csv_match.group('fig')[len(re.match(r'\d+', csv_match.group('fig')).group()):].upper()}"
            if fig in csv_by_fig:
                raise SystemExit(f"duplicate FIG CSV for {fig}: {csv_by_fig[fig].name}, {path.name}")
            csv_by_fig[fig] = path
        elif path.suffix.lower() in {".xls", ".xlsx"} and not path.name.startswith("~$"):
            workbooks.append(path)

    if not pdf_by_fig:
        raise SystemExit("no numbered FIG PDFs found (expected names such as 1.pdf)")
    if not workbooks:
        raise SystemExit("no .xls/.xlsx workbook found")

    pdf_records: list[dict[str, object]] = []
    for fig in sorted(pdf_by_fig, key=natural_fig_key):
        path = pdf_by_fig[fig]
        document = pdfium.PdfDocument(str(path))
        record = file_record(path)
        record.update({"fig": fig, "pages": len(document), "rendered": []})

        if not args.manifest_only:
            fig_dir = output_dir / "rendered" / f"fig-{fig}"
            fig_dir.mkdir(parents=True, exist_ok=True)
            scale = args.dpi / 72.0
            for index in range(len(document)):
                page = document[index]
                bitmap = page.render(scale=scale, rotation=0)
                image = bitmap.to_pil()
                target = fig_dir / f"page-{index + 1:03d}.png"
                image.save(target, format="PNG", optimize=True)
                record["rendered"].append(str(target))
                page.close()
        document.close()
        pdf_records.append(record)

    manifest = {
        "schema_version": 1,
        "created_at_utc": datetime.now(timezone.utc).isoformat(),
        "manual_number": args.manual_number.strip(),
        "source_dir": str(source_dir),
        "dpi": args.dpi,
        "manifest_only": bool(args.manifest_only),
        "pdfs": pdf_records,
        "workbooks": [file_record(path) for path in sorted(workbooks)],
        "per_fig_csvs": [
            {"fig": fig, **file_record(csv_by_fig[fig])}
            for fig in sorted(csv_by_fig, key=natural_fig_key)
        ],
        "missing_csv_figs": [
            fig for fig in sorted(pdf_by_fig, key=natural_fig_key) if fig not in csv_by_fig
        ],
    }
    manifest_path = output_dir / "source-manifest.json"
    manifest_path.write_text(json.dumps(manifest, indent=2), encoding="utf-8")
    print(json.dumps({
        "manifest": str(manifest_path),
        "pdf_count": len(pdf_records),
        "page_count": sum(int(item["pages"]) for item in pdf_records),
        "workbook_count": len(workbooks),
        "per_fig_csv_count": len(csv_by_fig),
        "missing_csv_figs": manifest["missing_csv_figs"],
    }))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
