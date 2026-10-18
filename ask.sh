#!/bin/bash
KEY=$(grep '^API_KEY=' .env | cut -d= -f2)
OUT=$(curl -s -X POST http://localhost:8001/chat -H "X-API-Key: $KEY" -H "Content-Type: application/json" -d "{\"client_id\":\"$1\",\"question\":\"$2\"}")
echo "$OUT" | python3 -m json.tool 2>/dev/null || echo "$OUT"
