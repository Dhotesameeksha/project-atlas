"""Read-only SQL tool. Defence in depth: (1) validator, (2) read-only DB user limited to `reports`,
(3) row limit, (4) query timeout. Writes and sensitive tables cannot run even if the LLM is tricked."""
import re

ALLOWED_TABLES = {"reports"}
FORBIDDEN_WORDS = re.compile(
    r"\b(insert|update|delete|drop|alter|truncate|create|grant|revoke|replace|call|exec|execute|handler|"
    r"load_file|outfile|dumpfile|sleep|benchmark|information_schema|performance_schema|mysql|sys)\b", re.I)
SENSITIVE_TABLES = re.compile(r"\b(clients|client_purchases|escalations|audit_log|market_metrics)\b", re.I)

SQL_SYSTEM = """You translate a question into ONE MySQL SELECT statement over this single table:
reports(id, title, publisher, published, sector, access)   -- access is 'paid' or 'public_excerpt'
Output ONLY the SQL. No markdown, no explanation. Use no other table."""

TEMPLATES = [
    (r"which sector has (the )?(most|highest|largest)|(most|largest) (number of )?reports.*sector|reports (per|by) sector",
     "SELECT sector, COUNT(*) AS report_count FROM reports GROUP BY sector ORDER BY report_count DESC"),
    (r"how many reports",
     "SELECT COUNT(*) AS report_count FROM reports"),
]


def _row_cap() -> int:
    try:
        from .config import get_settings
        return get_settings().sql_row_limit
    except Exception:  # config unavailable (e.g. offline unit tests)
        return 50


def validate_sql(sql: str) -> tuple[bool, str]:
    """Return (ok, cleaned_sql_or_reason)."""
    s = re.sub(r"^```(?:sql)?|```$", "", sql.strip(), flags=re.M | re.I).strip().rstrip(";").strip()
    if not s:
        return False, "empty query"
    if ";" in s:
        return False, "multiple statements are not allowed"
    if "--" in s or "/*" in s or "#" in s:
        return False, "comments are not allowed"
    if not re.match(r"(?is)^select\b", s):
        return False, "only SELECT statements are allowed"
    if FORBIDDEN_WORDS.search(s):
        return False, "forbidden keyword"
    if SENSITIVE_TABLES.search(s):
        return False, "table not allowed"
    if re.search(r"(?i)\bfrom\s+[`\w]+\s*,", s):
        return False, "comma joins are not allowed"
    tables = re.findall(r"(?i)\b(?:from|join)\s+`?([a-zA-Z_]\w*)`?", s)
    if not tables or any(t.lower() not in ALLOWED_TABLES for t in tables):
        return False, "table not allowed"
    cap = _row_cap()
    m = re.search(r"(?i)\blimit\s+(\d+)", s)
    if not m:
        s += f" LIMIT {cap}"
    elif int(m.group(1)) > cap:
        s = re.sub(r"(?i)\blimit\s+\d+", f"LIMIT {cap}", s)
    return True, s


def template_for(question: str) -> str | None:
    for pat, sql in TEMPLATES:
        if re.search(pat, question, re.I):
            return sql
    return None


def generate_sql(question: str) -> tuple[str, dict]:
    t = template_for(question)
    if t:
        return t, {"prompt_tokens": 0, "output_tokens": 0}
    from . import llm
    text, usage = llm.generate(SQL_SYSTEM, question, max_tokens=300)
    return text, usage


def run_readonly(sql: str) -> list[dict]:
    from . import db
    from .config import get_settings
    s = get_settings()
    with db.connect(readonly=True, timeout=s.sql_timeout_s) as conn, conn.cursor() as cur:
        cur.execute("SET SESSION MAX_EXECUTION_TIME=%s", (s.sql_timeout_s * 1000,))
        cur.execute(sql)
        rows = cur.fetchmany(s.sql_row_limit)
    return [{k: (v if isinstance(v, (int, float, str, type(None))) else str(v)) for k, v in r.items()} for r in rows]


def format_rows(rows: list[dict]) -> str:
    if not rows:
        return "The query returned no rows."
    return "\n".join("- " + ", ".join(f"{k}: {v}" for k, v in r.items()) for r in rows)
