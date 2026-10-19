# What I would do with one more week

**1. Make retrieval and answers better measured.** Grow the evaluation set from 26 to 100+ questions, including
adversarial rephrasings (my own review found a router miss on "which clients bought R-101?"), then run it in CI on every
commit. Calibrate the confidence score on labelled answers instead of the current 0.4 / 0.6 heuristic.

**2. Harder injection and entitlement defences.** Today injection handling is pattern-based plus the "writer only sees
verified facts" design. I would add a small classifier, canary tokens and output validators, and replace the
`client_id` in the request body with a signed token from the portal session so a caller cannot ask as someone else.

**3. Real documents.** Ingest actual PDFs (tables and charts, multimodal), use full-length reports, and add hybrid
search (BM25 + vectors) with a re-ranker. Better page-level citations and highlighting in the chat page.

**4. Router and agent.** Keep the rules as a veto but add an LLM router as a second opinion for unusual phrasing, and let
the agent ask one clarifying question (e.g. "global or APAC?") instead of escalating.

**5. Performance and cost.** Redis semantic cache, streaming responses, and Langfuse traces with p50/p95 latency and cost
per question. Measure the 30 second target properly under load; today latency is logged but not benchmarked.

**6. Operations.** An analyst inbox for escalations with email/Slack notification and status tracking, rate limiting,
secrets in a manager, a paid LLM tier (the free tier's 20 requests/day/model shaped several design choices), and a
verified deployment of the default `docker-compose.yml` on a clean VM with a public URL.
