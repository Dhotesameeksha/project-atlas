"""Atlas Report Copilot API (stage 2: LangGraph agent, guardrails, escalation, cache, cost logging)."""
import hmac
import json
import logging
import time
import uuid

from fastapi import Depends, FastAPI, Header, HTTPException

from . import agent, db, ingest
from .config import get_settings
from .models import ChatRequest, ChatResponse

logging.basicConfig(level=logging.INFO, format="%(message)s")
log = logging.getLogger("atlas.api")

app = FastAPI(title="Atlas Report Copilot API", version="0.2.0")
_CACHE: dict[str, tuple[float, dict]] = {}


def require_api_key(x_api_key: str = Header(default="")) -> None:
    if not hmac.compare_digest(x_api_key.encode(), get_settings().api_key.encode()):
        raise HTTPException(status_code=401, detail="Invalid or missing API key")


@app.get("/health")
def health() -> dict:
    return {"status": "ok"}


@app.post("/ingest", dependencies=[Depends(require_api_key)])
def run_ingest() -> dict:
    try:
        return ingest.ingest_all()
    except Exception as exc:
        log.exception("ingest failed")
        raise HTTPException(status_code=500, detail=f"Ingestion failed: {type(exc).__name__}") from exc


@app.get("/escalations", dependencies=[Depends(require_api_key)])
def escalations(limit: int = 100) -> list[dict]:
    return db.list_escalations(max(1, min(limit, 500)))


@app.post("/chat", response_model=ChatResponse, dependencies=[Depends(require_api_key)])
def chat(req: ChatRequest) -> ChatResponse:
    s = get_settings()
    t0, rid = time.time(), uuid.uuid4().hex[:8]
    try:
        if not db.client_exists(req.client_id):
            raise HTTPException(status_code=404, detail="Unknown client_id")
    except HTTPException:
        raise
    except Exception as exc:
        log.exception("database unavailable")
        raise HTTPException(status_code=503, detail="Database unavailable") from exc

    key = f"{req.client_id}|{' '.join(req.question.lower().split())}"  # per-client key: no cross-client leakage
    cached = _CACHE.get(key)
    if cached and time.time() - cached[0] < s.cache_ttl_s:
        log.info(json.dumps({"event": "chat", "request_id": rid, "client_id": req.client_id, "cache_hit": True}))
        return ChatResponse(**{**cached[1], "cached": True})

    try:
        out = agent.run(req.client_id, req.question)
    except Exception as exc:
        log.exception("chat failed")
        raise HTTPException(status_code=503, detail="Service unavailable") from exc

    usage = out.get("usage") or {}
    cost = (usage.get("prompt_tokens", 0) * s.price_in_per_m + usage.get("output_tokens", 0) * s.price_out_per_m) / 1e6
    log.info(json.dumps({
        "event": "chat", "request_id": rid, "client_id": req.client_id, "route": out.get("route"),
        "latency_ms": int((time.time() - t0) * 1000), "escalated": out.get("escalated", False),
        "confidence": out.get("confidence"), "est_cost_usd": round(cost, 6), **usage,
    }))
    payload = {
        "answer": out.get("answer", ""), "citations": out.get("citations", []),
        "confidence": out.get("confidence", 0.0), "conflicts": out.get("conflicts", []),
        "derived_values": out.get("derived", []), "escalated": out.get("escalated", False),
        "escalation_id": out.get("escalation_id"), "route": out.get("route"), "sql_query": out.get("sql_query"),
    }
    if not payload["escalated"] and out.get("route") != "error":
        _CACHE[key] = (time.time(), payload)
    return ChatResponse(**payload)
