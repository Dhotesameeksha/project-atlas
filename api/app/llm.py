"""Thin wrapper around the Gemini API (embeddings + generation)."""
from functools import lru_cache

from google import genai
from google.genai import types

from .config import get_settings


@lru_cache
def _client() -> genai.Client:
    s = get_settings()
    return genai.Client(
        api_key=s.gemini_api_key,
        http_options=types.HttpOptions(timeout=s.request_timeout_s * 1000),
    )


def embed(texts: list[str], task_type: str) -> list[list[float]]:
    s = get_settings()
    out: list[list[float]] = []
    for i in range(0, len(texts), 50):
        resp = _client().models.embed_content(
            model=s.embed_model,
            contents=texts[i:i + 50],
            config=types.EmbedContentConfig(task_type=task_type, output_dimensionality=s.embed_dim),
        )
        out.extend(e.values for e in resp.embeddings)
    return out


def generate(system: str, user: str) -> tuple[str, dict]:
    s = get_settings()
    resp = _client().models.generate_content(
        model=s.llm_model,
        contents=user,
        config=types.GenerateContentConfig(
            system_instruction=system, temperature=0.0, max_output_tokens=s.max_output_tokens,
        ),
    )
    um = resp.usage_metadata
    usage = {
        "prompt_tokens": getattr(um, "prompt_token_count", 0) or 0,
        "output_tokens": getattr(um, "candidates_token_count", 0) or 0,
    }
    return (resp.text or "").strip(), usage
