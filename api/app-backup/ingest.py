"""Ingestion: parse reports -> strip/log injections -> chunk -> embed -> store with metadata."""
import json
import logging
from pathlib import Path

from .chunking import Chunk, pack_paragraphs, parse_report, split_paragraphs
from .guards import strip_injections

log = logging.getLogger("atlas.ingest")


def build_chunks(path: Path) -> tuple[list[Chunk], list[dict]]:
    meta, pages = parse_report(path)
    chunks: list[Chunk] = []
    injections: list[dict] = []
    for page_no, text in pages:
        clean, flagged = strip_injections(split_paragraphs(text))
        for f in flagged:
            injections.append({"report_id": meta["id"], "page": page_no, "snippet": f[:200]})
        for i, piece in enumerate(pack_paragraphs(clean)):
            chunks.append(Chunk(
                report_id=meta["id"], title=meta["title"], sector=meta["sector"],
                region=meta["region"], year=int(meta["year"]), publisher=meta["publisher"],
                page=page_no, index=i, text=piece,
            ))
    return chunks, injections


def ingest_all(reports_dir: str | None = None) -> dict:
    from . import db, llm, store  # imported lazily so chunking/guards can be tested offline
    from .config import get_settings

    s = get_settings()
    folder = Path(reports_dir or s.reports_dir)
    col = store.collection()
    summary = {"reports": 0, "chunks": 0, "injections_detected": 0}
    for path in sorted(folder.glob("R-*.md")):
        chunks, injections = build_chunks(path)
        if not chunks:
            continue
        for inj in injections:
            log.warning(json.dumps({"event": "injection_detected", **inj}))
            db.log_event(None, "injection_detected", inj)
        rid = chunks[0].report_id
        col.delete(where={"report_id": rid})  # idempotent re-ingest
        vectors = llm.embed([c.text for c in chunks], task_type="RETRIEVAL_DOCUMENT")
        col.add(
            ids=[c.uid for c in chunks],
            documents=[c.text for c in chunks],
            metadatas=[c.metadata() for c in chunks],
            embeddings=vectors,
        )
        summary["reports"] += 1
        summary["chunks"] += len(chunks)
        summary["injections_detected"] += len(injections)
    return summary


if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    print(json.dumps(ingest_all(), indent=2))
