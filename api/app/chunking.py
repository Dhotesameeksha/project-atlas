"""Parse report files and split them into page-aware chunks (no heavy dependencies)."""
import re
from dataclasses import dataclass
from pathlib import Path

import yaml

PAGE_RE = re.compile(r"^## Page (\d+)\s*$", re.MULTILINE)


@dataclass
class Chunk:
    report_id: str
    title: str
    sector: str
    region: str
    year: int
    publisher: str
    page: int
    index: int
    text: str

    @property
    def uid(self) -> str:
        return f"{self.report_id}-p{self.page}-c{self.index}"

    def metadata(self) -> dict:
        return {
            "report_id": self.report_id,
            "title": self.title,
            "sector": self.sector,
            "region": self.region,
            "year": self.year,
            "publisher": self.publisher,
            "page": self.page,
        }


def parse_report(path: Path) -> tuple[dict, list[tuple[int, str]]]:
    """Return (front-matter metadata, [(page_number, page_text), ...])."""
    raw = path.read_text(encoding="utf-8")
    _, front, body = raw.split("---", 2)
    meta = yaml.safe_load(front)
    markers = list(PAGE_RE.finditer(body))
    pages = []
    for i, m in enumerate(markers):
        end = markers[i + 1].start() if i + 1 < len(markers) else len(body)
        pages.append((int(m.group(1)), body[m.end():end].strip()))
    return meta, pages


def split_paragraphs(text: str) -> list[str]:
    return [p.strip() for p in re.split(r"\n\s*\n", text) if p.strip()]


def pack_paragraphs(paragraphs: list[str], max_chars: int = 900) -> list[str]:
    """Greedily pack whole paragraphs into chunks of at most max_chars."""
    chunks, current = [], ""
    for p in paragraphs:
        if current and len(current) + len(p) + 2 > max_chars:
            chunks.append(current)
            current = p
        else:
            current = f"{current}\n\n{p}" if current else p
    if current:
        chunks.append(current)
    return chunks
