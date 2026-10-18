# Project Atlas — Demo Guide (3–5 Minutes)

## Before recording

- Start the required Docker Compose services.
- Open the CodeIgniter chat interface and confirm the page loads.
- Keep API keys, database passwords, and other secrets out of the recording.
- Prepare the synthetic reports and client access scenarios.
- Only demonstrate behaviors that work in the current environment.
- Do not claim evaluation success before running and reviewing the evaluation.

## Demo flow

### 1. Introduction and architecture (30–45 seconds)

Introduce Project Atlas as an AI report copilot for corporate market research.
Briefly show the CodeIgniter interface, FastAPI backend, MySQL database,
ChromaDB vector store, and agent workflow.

### 2. Normal report question (40–50 seconds)

Select client C-01 and ask:

"What was the global smart water meter market size in 2024?"

Show the response, source citations, and confidence information if available.
Explain that answers should be grounded in authorized report evidence.

### 3. Conflicting figures (40–50 seconds)

Ask a question that compares the smart water meter market figures from R-101
and R-102.

Explain that the reports use different scopes, so the values must remain
separate rather than being averaged or treated as equivalent. Show citations
and scope information if returned by the application.

### 4. Entitlement boundary (30–40 seconds)

Switch to client C-02 and ask about the protected smart water meter market
figures in R-101.

Show that protected figures must not be disclosed to a client without access.
If the observed behavior differs, record it as a defect rather than presenting
the expected behavior as a successful result.

### 5. Calculation and assumptions (30–40 seconds)

Ask for an APAC market projection within the supported forecast period.
Show the formula, source figures, and assumptions if returned. Explain any
base-year ambiguity and do not present a projection beyond the supported
forecast period as a verified fact.

### 6. Safety and escalation (30–45 seconds)

Demonstrate a prompt-injection attempt, an out-of-scope question, or an
unsupported projection such as a 2035 forecast.

Explain the expected behavior: ignore malicious instructions embedded in
report content, refuse unsafe or out-of-scope requests, and escalate questions
that cannot be answered reliably.

### 7. Close (15–20 seconds)

Show the evaluation question set and explain that evaluation results will be
reported honestly, including failures and planned fixes.

## Recording checklist

- Keep the demo within 3–5 minutes.
- Use synthetic data only.
- Do not show secrets or private client information.
- Do not invent successful outputs or hide observed failures.
- Distinguish implemented features from planned improvements.
