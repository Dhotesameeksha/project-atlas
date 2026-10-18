"""MySQL helpers. The app user can read/write app tables; SQL-tool queries (stage 2) use the read-only user."""
import json
from contextlib import contextmanager

import pymysql
import pymysql.cursors

from .config import get_settings


@contextmanager
def connect(readonly: bool = False, timeout: int = 10):
    s = get_settings()
    user = s.mysql_ro_user if readonly else s.mysql_app_user
    password = s.mysql_ro_password if readonly else s.mysql_app_password
    conn = pymysql.connect(
        host=s.mysql_host, port=s.mysql_port, user=user, password=password,
        database=s.mysql_database, cursorclass=pymysql.cursors.DictCursor,
        autocommit=True, connect_timeout=5, read_timeout=timeout, charset="utf8mb4",
    )
    try:
        yield conn
    finally:
        conn.close()


def client_exists(client_id: str) -> bool:
    with connect() as c, c.cursor() as cur:
        cur.execute("SELECT 1 FROM clients WHERE id=%s", (client_id,))
        return cur.fetchone() is not None


def entitled_reports(client_id: str) -> list[str]:
    """Reports this client may read: purchased + public excerpts."""
    with connect() as c, c.cursor() as cur:
        cur.execute(
            "SELECT report_id AS id FROM client_purchases WHERE client_id=%s "
            "UNION SELECT id FROM reports WHERE access='public_excerpt'",
            (client_id,),
        )
        return sorted(r["id"] for r in cur.fetchall())


def locked_reports(client_id: str) -> list[dict]:
    """Metadata only (never content/figures) for reports the client has NOT bought, for upgrade offers."""
    entitled = entitled_reports(client_id)
    with connect() as c, c.cursor() as cur:
        if entitled:
            marks = ",".join(["%s"] * len(entitled))
            cur.execute(f"SELECT id,title,sector FROM reports WHERE id NOT IN ({marks})", entitled)
        else:
            cur.execute("SELECT id,title,sector FROM reports")
        return list(cur.fetchall())


def log_event(client_id: str | None, event_type: str, detail: dict) -> None:
    with connect() as c, c.cursor() as cur:
        cur.execute(
            "INSERT INTO audit_log (client_id,event_type,detail) VALUES (%s,%s,%s)",
            (client_id, event_type, json.dumps(detail)),
        )


def create_escalation(client_id: str, question: str, reason: str, confidence: float | None) -> int:
    with connect() as c, c.cursor() as cur:
        cur.execute(
            "INSERT INTO escalations (client_id,question,reason,confidence) VALUES (%s,%s,%s,%s)",
            (client_id, question, reason, confidence),
        )
        return cur.lastrowid


def list_escalations(limit: int = 100) -> list[dict]:
    with connect() as c, c.cursor() as cur:
        cur.execute(
            "SELECT id, client_id, question, reason, confidence, status, created_at "
            "FROM escalations ORDER BY id DESC LIMIT %s", (limit,))
        rows = cur.fetchall()
    for r in rows:
        r["confidence"] = float(r["confidence"]) if r["confidence"] is not None else None
        r["created_at"] = str(r["created_at"])
    return rows
