"""Rule-based request router. Deterministic on purpose: unsafe requests never reach an LLM."""
import re
from dataclasses import dataclass

UNSAFE = [
    r"\b(ignore|disregard|forget|override)\b.{0,30}\b(instructions|rules|prompt|guidelines)\b",
    r"\b(reveal|show|print|repeat|output)\b.{0,20}\b(system prompt|your prompt|your instructions)\b",
    r"\b(list|show|give|print|dump|reveal|share|send)\b.{0,40}\b(client|customer)s?\b.{0,30}\b(e-?mails?|phone|contact|data|table|details)\b",
    r"\bcontact[_ ]e-?mails?\b",
    r"\b(clients?|customers?)\s+table\b",
    r"\b(what|which)\b.{0,40}\b(other|another)\s+(clients?|customers?|compan\w+)\b",
    r"\b(northwind|blue delta)\b.{0,40}\b(bought|purchased|buy|reports?|owns?)\b",
    r"\bpersonal\s+(data|information)\b",
    r"\b(delete|drop|truncate)\b.{0,40}\b(table|rows?|records?|database|reports?)\b",
    r"\b(insert|update)\b.{0,30}\b(into|table|rows?|records?)\b",
    r"\balter\s+table\b",
    r"\bgrant\s+\w+",
    r"\bjailbreak\b",
    r"\byou are now\b",
]
OUT_OF_SCOPE = [
    r"\b(election|elections|politic\w*|prime minister|voting|parliament|who will win)\b",
    r"\b(weather|recipe|joke|movie|cricket|football|bitcoin|crypto|horoscope)\b",
]
AGGREGATE = [
    r"\bhow many (reports|sectors|publishers)\b",
    r"\bnumber of (reports|sectors)\b",
    r"\b(most|fewest|least|largest)\s+reports\b",
    r"\bwhich sector has\b",
    r"\breports? (per|by) (sector|publisher)\b",
    r"\blist (all )?(the )?(available )?reports\b",
    r"\b(latest|newest|oldest) report\b",
]
CALCULATION = [
    r"\b(worth|projected|projection|forecasts?|estimate|cagr|expected to be|grow to)\b.*\b20(2[5-9]|[3-9]\d)\b",
    r"\bwill\b.{0,60}\b20(2[5-9]|[3-9]\d)\b",
    r"\b(in|by)\s+20(2[6-9]|[3-9]\d)\b",
]
COMPARISON = [
    r"\btrust\b", r"\bcompare\b", r"\bversus\b", r"\bvs\.?\b",
    r"\bdifference between\b", r"\bwhich (report|source) (is|should|has)\b",
    r"\bmore (reliable|accurate)\b",
]


@dataclass
class Route:
    name: str
    reason: str


def _hit(patterns: list[str], text: str) -> str | None:
    for p in patterns:
        if re.search(p, text, re.IGNORECASE | re.DOTALL):
            return p
    return None


def classify(question: str) -> Route:
    q = question.strip()
    for name, pats in [("unsafe", UNSAFE), ("out_of_scope", OUT_OF_SCOPE),
                       ("aggregate", AGGREGATE), ("calculation", CALCULATION),
                       ("comparison", COMPARISON)]:
        p = _hit(pats, q)
        if p:
            return Route(name, f"matched {name} rule")
    return Route("factual", "default lookup")
