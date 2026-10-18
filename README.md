# Project Atlas – Report Copilot (Stage 1)

Entitlement-aware RAG over market reports. Stack: FastAPI, MySQL 8, ChromaDB, Gemini, Docker.

## Run (about 5 minutes)
1. Install Docker Desktop. Get a free Gemini API key from https://aistudio.google.com
2. `cp .env.example .env` (Windows: copy the file) and fill in the values. Never commit `.env`.
3. `docker compose up --build -d`
4. Load the reports into the vector store (needs `API_KEY` from your .env):
   `curl -X POST http://localhost:8000/ingest -H "X-API-Key: YOUR_API_KEY"`
5. Ask a question:
   `curl -X POST http://localhost:8000/chat -H "X-API-Key: YOUR_API_KEY" -H "Content-Type: application/json" -d "{\"client_id\":\"C-01\",\"question\":\"What was the global smart water meter market size in 2024?\"}"`
6. Interactive docs: http://localhost:8000/docs

## Tests (no network needed)
`cd api && pip install pytest pyyaml pydantic-settings pymysql && python -m pytest tests` (the 5 chunking/injection tests run without Docker or an API key).

## Design so far
- **Entitlements** are enforced in the vector query (`where report_id in [...]`) before the LLM sees any text. Locked reports expose title/sector only, for upgrade offers.
- **Prompt injection**: instruction-like paragraphs are removed at ingestion, logged to `audit_log`, and retrieved text is fenced as untrusted data in the prompt.
- **DB users**: `atlas_app` (app tables) and `atlas_ro` (SELECT on reports/market_metrics only, never `clients`).
- **Citations** are kept only if they point to a chunk that was actually retrieved.

## Not done yet (Stage 2+)
Router, SQL tool, calculator, critic/conflict detection, derived values, real confidence scoring, GET /escalations, CI3 chat page, cache, evaluation set, architecture diagram.

## AI-assistant disclosure
Code scaffolding generated with Claude (Anthropic); reviewed and run by the author.
