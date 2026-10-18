#!/usr/bin/env python3
"""Evaluation harness: runs eval/questions.json against the live API and scores
correctness, citation accuracy, refusal accuracy and conflict detection. Failures are listed honestly.
Usage: python3 eval/run_eval.py   (API_KEY read from env or .env; ATLAS_URL defaults to http://localhost:8001)"""
import json
import os
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent
ENTITLED = {"C-01": {"R-101", "R-102", "R-103"}, "C-02": {"R-102", "R-103"}}


def load_key() -> str:
    if os.environ.get("API_KEY"):
        return os.environ["API_KEY"]
    env = ROOT.parent / ".env"
    if env.exists():
        for line in env.read_text().splitlines():
            if line.startswith("API_KEY="):
                return line.split("=", 1)[1].strip()
    sys.exit("API_KEY not found (set it in env or .env)")


def ask(base: str, key: str, client: str, q: str) -> dict:
    req = urllib.request.Request(
        base + "/chat", data=json.dumps({"client_id": client, "question": q}).encode(),
        headers={"Content-Type": "application/json", "X-API-Key": key})
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.loads(r.read())
    except urllib.error.HTTPError as e:
        return {"error": f"HTTP {e.code}", "answer": "", "citations": [], "conflicts": [], "derived_values": []}
    except Exception as e:  # noqa: BLE001
        return {"error": type(e).__name__, "answer": "", "citations": [], "conflicts": [], "derived_values": []}


def score(case: dict, resp: dict) -> dict:
    """Return {check_name: (passed, reason)} for the checks this case defines."""
    text = json.dumps(resp).lower()
    ans = resp.get("answer", "").lower()
    out = {}
    if resp.get("error"):
        return {"service": (False, resp["error"])}
    if "route" in case:
        out["route"] = (resp.get("route") == case["route"], f"route={resp.get('route')}")
    if "escalated" in case:
        out["escalation"] = (resp.get("escalated") == case["escalated"], f"escalated={resp.get('escalated')}")
    missing = [s for s in case.get("include", []) if s.lower() not in ans]
    if "include" in case:
        out["includes"] = (not missing, f"missing {missing}" if missing else "ok")
    leaked = [s for s in case.get("exclude", []) if s.lower() in text]
    if "exclude" in case:
        out["no_leak"] = (not leaked, f"leaked {leaked}" if leaked else "ok")
    if "min_citations" in case:
        cits = resp.get("citations", [])
        valid = all(c["report_id"] in ENTITLED[case["client"]] for c in cits)
        out["citations"] = (len(cits) >= case["min_citations"] and valid, f"{len(cits)} citations, entitled={valid}")
    if "min_conflicts" in case:
        n = len(resp.get("conflicts", []))
        out["conflict"] = (n >= case["min_conflicts"], f"{n} conflicts")
    if "min_derived" in case:
        n = len(resp.get("derived_values", []))
        out["derived"] = (n >= case["min_derived"], f"{n} derived values")
    return out


def main() -> None:
    base = os.environ.get("ATLAS_URL", "http://localhost:8001")
    key = load_key()
    cases = json.loads((ROOT / "questions.json").read_text())
    rows, by_cat, failures = [], {}, []
    for case in cases:
        resp = ask(base, key, case["client"], case["q"])
        checks = score(case, resp)
        ok = all(p for p, _ in checks.values())
        rows.append((case, ok, checks))
        cat = by_cat.setdefault(case["cat"], [0, 0])
        cat[0] += int(ok)
        cat[1] += 1
        if not ok:
            failures.append((case, checks, resp))
        print(f"{'PASS' if ok else 'FAIL'}  #{case['id']:<2} [{case['client']}] {case['q'][:70]}")
        time.sleep(4)  # stay inside free-tier rate limits

    total = sum(1 for _, ok, _ in rows if ok)
    lines = ["# Evaluation results", "", f"**{total}/{len(rows)} questions passed** against `{base}`.", "",
             "| Category | Passed | Total |", "|---|---|---|"]
    lines += [f"| {c} | {p} | {t} |" for c, (p, t) in sorted(by_cat.items())]
    lines += ["", "| # | Client | Category | Result | Detail |", "|---|---|---|---|---|"]
    for case, ok, checks in rows:
        detail = "; ".join(f"{k}: {r}" for k, (_, r) in checks.items())
        lines.append(f"| {case['id']} | {case['client']} | {case['cat']} | {'PASS' if ok else 'FAIL'} | {detail} |")
    lines += ["", "## Failures (for the analysis section)", ""]
    for case, checks, resp in failures:
        bad = "; ".join(f"{k}: {r}" for k, (p, r) in checks.items() if not p)
        lines += [f"### #{case['id']} [{case['client']}] {case['q']}", f"- Failed checks: {bad}",
                  f"- Answer (first 300 chars): {resp.get('answer', '')[:300]!r}", "- Why / fix: _write your analysis here_", ""]
    (ROOT / "results.md").write_text("\n".join(lines))
    print(f"\n{total}/{len(rows)} passed. Wrote eval/results.md")


if __name__ == "__main__":
    main()
