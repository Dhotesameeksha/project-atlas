"""Gemini wrapper: embeddings + generation. Retries transient errors; on quota exhaustion (429)
or unknown model (404) it falls back to the next model in LLM_FALLBACK_MODELS."""
import json
import logging
import re
import time
from functools import lru_cache

from google import genai
from google.genai import errors, types

from .config import get_settings

log = logging.getLogger("atlas.llm")
RETRY_CODES = {500, 502, 503, 504}
_EXHAUSTED: dict[str, float] = {}


@lru_cache
def _client() -> genai.Client:
    s = get_settings()
    return genai.Client(
        api_key=s.gemini_api_key,
        http_options=types.HttpOptions(timeout=s.request_timeout_s * 1000),
    )


def _with_retry(fn, attempts: int = 3):
    delay = 1.0
    for i in range(attempts):
        try:
            return fn()
        except (errors.APIError, ValueError) as exc:
            retryable = isinstance(exc, ValueError) or getattr(exc, "code", None) in RETRY_CODES
            if retryable and i < attempts - 1:
                time.sleep(delay)
                delay *= 2
                continue
            raise


def embed(texts: list[str], task_type: str) -> list[list[float]]:
    s = get_settings()
    out: list[list[float]] = []
    for i in range(0, len(texts), 50):
        batch = texts[i:i + 50]
        resp = _with_retry(lambda: _client().models.embed_content(
            model=s.embed_model, contents=batch,
            config=types.EmbedContentConfig(task_type=task_type, output_dimensionality=s.embed_dim),
        ))
        out.extend(e.values for e in resp.embeddings)
    return out


def _models() -> list[str]:
    s = get_settings()
    extra = [m.strip() for m in s.llm_fallback_models.split(",") if m.strip()]
    return [s.llm_model] + [m for m in extra if m != s.llm_model]


def generate(system: str, user: str, max_tokens: int | None = None, json_mode: bool = False) -> tuple[str, dict]:
    s = get_settings()
    cfg = {"system_instruction": system, "temperature": 0.0,
           "max_output_tokens": max_tokens or s.max_output_tokens}
    if json_mode:
        cfg["response_mime_type"] = "application/json"
    last_exc = None
    for model in _models():
        if _EXHAUSTED.get(model, 0) > time.time():
            continue

        def call(m=model):
            resp = _client().models.generate_content(model=m, contents=user, config=types.GenerateContentConfig(**cfg))
            if not (resp.text or "").strip():
                raise ValueError("empty model response")
            return resp

        try:
            resp = _with_retry(call)
        except errors.APIError as exc:
            if getattr(exc, "code", None) in (429, 404):
                _EXHAUSTED[model] = time.time() + 900
                log.warning("model %s unavailable (code %s); trying next model", model, getattr(exc, "code", None))
                last_exc = exc
                continue
            raise
        um = resp.usage_metadata
        usage = {
            "prompt_tokens": getattr(um, "prompt_token_count", 0) or 0,
            "output_tokens": getattr(um, "candidates_token_count", 0) or 0,
        }
        return resp.text.strip(), usage
    raise (last_exc or RuntimeError("all configured models are exhausted"))


def parse_json(text: str):
    """Parse model JSON output, tolerating ``` fences and stray prose. Returns None on failure."""
    t = re.sub(r"^```(?:json)?|```$", "", text.strip(), flags=re.M).strip()
    try:
        return json.loads(t)
    except json.JSONDecodeError:
        pass
    m = re.search(r"(\[.*\]|\{.*\})", t, re.S)
    if m:
        try:
            return json.loads(m.group(1))
        except json.JSONDecodeError:
            return None
    return None
