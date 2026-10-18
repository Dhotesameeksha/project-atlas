# Project Atlas — One-Week Improvement Plan

## Goal
Improve reliability, security, analyst workflows, and production readiness
without weakening report-entitlement controls or citation requirements.

| Day | Priority | Planned work | Deliverable |
|---|---|---|---|
| Day 1 | Critical | Expand evaluation for entitlement leakage, prompt injection, conflicting figures, unsupported claims, and out-of-scope requests. Analyse every failure. | Evaluation report with pass/fail results and documented fixes. |
| Day 2 | Critical | Add role-based access control, review audit-log coverage, introduce rate limits, and document API-key rotation. | Access-control rules, security tests, and a rotation procedure. |
| Day 3 | High | Improve analyst escalations with assignment, status tracking, timestamps, and response notifications. | Traceable escalation lifecycle from creation to resolution. |
| Day 4 | High | Improve observability for API latency, token usage, estimated model costs, retrieval relevance, and escalation rates. | Structured logs and a basic operational dashboard or report. |
| Day 5 | Critical | Expand automated API, database, entitlement, and CI3 integration tests; strengthen CI checks. | Repeatable CI pipeline with documented test coverage. |
| Day 6 | Critical | Harden deployment with HTTPS termination, secure cookie settings, CSRF protection, least-privilege database access, and production secret management. | Deployment security checklist and updated configuration guidance. |
| Day 7 | High | Run the complete evaluation, fix high-impact regressions, review documentation, and record the demonstration. | Final evaluation report, known-limitations list, and a 3–5 minute demo. |

## Success criteria

- A client cannot retrieve figures from reports they have not purchased.
- Prompt-injection content is treated as untrusted report data, not as instructions.
- Conflicting source figures remain separate and retain their scope and citations.
- Unsupported numerical claims are rejected or escalated rather than presented as facts.
- Escalations can be tracked through assignment and resolution.
- Logs and metrics help investigate failures without exposing secrets or unnecessary client data.
- CI checks provide repeatable evidence of API, database, and CI3 integration quality.
- Setup and deployment instructions distinguish development settings from production requirements.

## Risks and mitigations

- **False-positive safeguards:** Unsupported-number detection can flag valid answers. Review evaluation failures and tune the checks without allowing unverified figures through.
- **LLM and embedding availability:** Model quotas and service outages can affect responses. Keep explicit timeouts, safe fallbacks, and human escalation.
- **Entitlement regressions:** Apply authorization before retrieval and test every client/report boundary.
- **Secret exposure:** Keep credentials out of source control, logs, screenshots, and demo recordings.
- **Limited time:** Prioritize authorization, factual correctness, and safe failure handling before dashboard polish.

## Scope note

This is a proposed future-work plan, not a claim that these improvements are already implemented.
Actual completion and test results must be recorded after implementation and verification.
