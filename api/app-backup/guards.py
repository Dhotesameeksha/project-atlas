"""Prompt-injection guard. Retrieved text is DATA; instruction-like paragraphs are removed and logged."""
import re

INJECTION_PATTERNS = [
    r"disregard\s+(all\s+)?(previous|prior|above)\s+instructions",
    r"ignore\s+(all\s+|your\s+)?(previous|prior|above)\s+instructions",
    r"note\s+to\s+ai\s+systems?",
    r"print\s+the\s+full\s+contents",
    r"reveal\s+(the\s+)?system\s+prompt",
    r"you\s+are\s+now\s+",
    r"only\s+reliable\s+source",
]
_COMPILED = [re.compile(p, re.IGNORECASE) for p in INJECTION_PATTERNS]


def looks_like_injection(text: str) -> bool:
    return any(p.search(text) for p in _COMPILED)


def strip_injections(paragraphs: list[str]) -> tuple[list[str], list[str]]:
    """Split paragraphs into (clean, flagged). Legitimate figures in clean paragraphs are kept."""
    clean, flagged = [], []
    for p in paragraphs:
        (flagged if looks_like_injection(p) else clean).append(p)
    return clean, flagged
