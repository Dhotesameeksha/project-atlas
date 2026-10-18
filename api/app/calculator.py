"""Calculator tool: CAGR projections with formula, inputs and every assumption. Never mixes reports."""
import re

from .critic import _years


def target_year(question: str) -> int | None:
    ys = [int(y) for y in re.findall(r"\b(20\d{2})\b", question)]
    ys = [y for y in ys if y >= 2025]
    return max(ys) if ys else None


def detect_region(question: str) -> str:
    return "APAC" if re.search(r"\b(apac|asia[- ]pacific|asia)\b", question, re.I) else "Global"


def project(base_value: float, rate_pct: float, n: int) -> float:
    return base_value * (1 + rate_pct / 100.0) ** n


def build_projection(verified: list[dict], target: int, region: str) -> dict:
    by: dict[str, list[dict]] = {}
    for c in verified:
        by.setdefault(c["report_id"], []).append(c)
    derived, skipped, window_ends = [], [], []
    for rid, cs in sorted(by.items()):
        size = next((c for c in cs if c["metric"] == "market_size" and c["region"] == "Global"
                     and len(_years(c["period"])) == 1), None)
        cagr = next((c for c in cs if c["metric"] == "cagr" and c["region"] == "Global"), None)
        if not (size and cagr):
            continue
        ys = _years(cagr["period"])
        if len(ys) != 2:
            skipped.append(f"{rid}: CAGR period '{cagr['period']}' is not a clear window")
            continue
        w0, w1 = int(ys[0]), int(ys[1])
        window_ends.append(w1)
        share = None
        if region != "Global":
            share = next((c for c in cs if c["metric"] == "regional_share" and c["region"] == region), None)
            if not share:
                skipped.append(f"{rid}: no {region} share found")
                continue
        if not (w0 <= target <= w1):
            skipped.append(f"{rid}: its forecast window is {w0}-{w1}, so {target} is outside it (no extrapolation made)")
            continue
        base_year = int(_years(size["period"])[0])
        base = size["value"] * (share["value"] / 100.0 if share else 1.0)
        n = target - base_year
        result = project(base, cagr["value"], n)
        inputs = {"global_market_size_usd_b": size["value"], "cagr_pct": cagr["value"],
                  "base_value_usd_b": round(base, 2), "base_year": base_year, "target_year": target}
        sources = [{"metric": "market_size", "report_id": rid, "page": size["page"]},
                   {"metric": "cagr", "report_id": rid, "page": cagr["page"]}]
        assumptions = [f"The stated CAGR ({cagr['value']}%) is applied every year from the {base_year} base year to {target} (n = {n}).",
                       f"Base figure scope: {size['scope'] or 'as stated in the report'}."]
        formula = f"{target} value = {base_year} base value x (1 + CAGR)^n"
        if share:
            inputs["regional_share_pct"] = share["value"]
            sources.append({"metric": "regional_share", "report_id": rid, "page": share["page"]})
            assumptions.insert(0, f"{region} keeps its {base_year} share ({share['value']}%) of the global market through {target}.")
            formula = f"{region} {target} value = Global {base_year} size x {region} share x (1 + CAGR)^n"
        sensitivity = []
        if base_year < w0:
            n_alt = target - w0
            assumptions.append(f"Ambiguity: the report states its forecast period as {w0}-{w1} but publishes no {w0} market size, "
                               f"so {base_year} is used as the base year. If growth only starts after {w0}, n would be {n_alt}.")
            sensitivity.append({"n_periods": n_alt, "result": round(project(base, cagr["value"], n_alt), 2),
                                "assumption": f"No growth between {base_year} and {w0}"})
        derived.append({
            "label": "derived estimate (not a quoted figure)", "report_id": rid,
            "metric": f"{region} market size {target}", "formula": formula,
            "inputs": inputs, "assumptions": assumptions, "n_periods": n,
            "result": round(result, 2), "unit": "USD B", "sensitivity": sensitivity, "sources": sources,
        })
    return {"derived": derived, "skipped": skipped, "max_window_end": max(window_ends) if window_ends else None}
