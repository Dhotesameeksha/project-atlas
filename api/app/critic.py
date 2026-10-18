"""Critic / fact-checker: verifies that every extracted number literally appears in the cited chunk,
detects conflicts between sources, and flags numbers in the final answer that no source supports."""
import re

NUM_RE = re.compile(r"\d+(?:,\d{3})*(?:\.\d+)?")
CITE_RE = re.compile(r"\[(R-\d+),\s*p\.?\s*(\d+)\]")


def norm_metric(m) -> str:
    m = str(m).lower()
    if "cagr" in m or "growth" in m:
        return "cagr"
    if "share" in m:
        return "regional_share"
    if "size" in m or "value" in m or "revenue" in m:
        return "market_size"
    return m.strip() or "unknown"


def norm_region(r) -> str:
    r = str(r).lower()
    if "apac" in r or "asia" in r:
        return "APAC"
    if "global" in r or "world" in r or not r.strip():
        return "Global"
    return str(r).strip().title()


def numbers_in(text: str) -> list[float]:
    return [float(n.replace(",", "")) for n in NUM_RE.findall(text)]


def normalize_claim(c: dict) -> dict | None:
    try:
        return {
            "report_id": str(c["report_id"]).strip().upper(),
            "page": int(c["page"]),
            "metric": norm_metric(c.get("metric", "")),
            "region": norm_region(c.get("region", "Global")),
            "period": str(c.get("period", "")).strip(),
            "value": float(c["value"]),
            "unit": str(c.get("unit", "")).strip(),
            "scope": str(c.get("scope", "")).strip(),
        }
    except (KeyError, TypeError, ValueError):
        return None


def verify_claims(claims: list[dict], hits: list[dict], entitled: list[str]) -> tuple[list[dict], list[dict]]:
    """A claim is verified only if its report is entitled and its number appears in the cited chunk."""
    by_page: dict[tuple[str, int], str] = {}
    for h in hits:
        key = (h["meta"]["report_id"], int(h["meta"]["page"]))
        by_page[key] = by_page.get(key, "") + " " + h["text"]
    verified, unverified = [], []
    for raw in claims:
        c = normalize_claim(raw) if isinstance(raw, dict) else None
        if not c:
            continue
        text = by_page.get((c["report_id"], c["page"]), "")
        if c["report_id"] in entitled and any(abs(n - c["value"]) < 1e-9 for n in numbers_in(text)):
            verified.append(c)
        else:
            unverified.append(c)
    return verified, unverified


def _years(period: str) -> list[str]:
    return re.findall(r"\d{4}", str(period))


def detect_conflicts(verified: list[dict]) -> list[dict]:
    """Group the same metric/region across different reports; differing values or windows = conflict."""
    groups: dict[tuple, list[dict]] = {}
    for c in verified:
        key = (c["metric"], c["region"]) if c["metric"] == "cagr" else (c["metric"], c["region"], c["period"])
        groups.setdefault(key, []).append(c)
    conflicts = []
    for key, items in groups.items():
        reports = {i["report_id"] for i in items}
        if len(reports) < 2:
            continue
        values = {i["value"] for i in items}
        periods = {i["period"] for i in items}
        if len(values) == 1 and len(periods) == 1:
            continue
        kind = "different_values" if len(periods) == 1 else "different_periods"
        conflicts.append({
            "metric": key[0], "region": key[1],
            "period": items[0]["period"] if len(periods) == 1 else "varies",
            "type": kind,
            "values": [{"report_id": i["report_id"], "page": i["page"], "value": i["value"], "unit": i["unit"],
                        "period": i["period"], "scope": i["scope"]} for i in sorted(items, key=lambda x: x["report_id"])],
            "note": "Sources differ in scope and/or forecast window. Values are shown side by side, never averaged.",
        })
    return conflicts


def allowed_numbers(verified: list[dict], derived: list[dict], question: str, pages: set[int]) -> list[float]:
    allowed = [c["value"] for c in verified] + numbers_in(question) + [float(p) for p in pages]
    for d in derived:
        allowed += [d.get("result", 0.0), float(d.get("n_periods", 0))]
        allowed += [v for v in d.get("inputs", {}).values() if isinstance(v, (int, float))]
        for s in d.get("sensitivity", []):
            allowed += [s.get("result", 0.0), float(s.get("n_periods", 0))]
    return allowed


def unsupported_numbers(answer: str, allowed: list[float]) -> list[str]:
    text = re.sub(r"R-\d+", " ", CITE_RE.sub(" ", answer))
    bad = []
    for m in NUM_RE.findall(text):
        v = float(m.replace(",", ""))
        if v.is_integer() and (1900 <= v <= 2100 or v <= 12):
            continue
        if any(abs(v - a) <= max(0.05, abs(a) * 0.01) for a in allowed):
            continue
        bad.append(m)
    return bad
