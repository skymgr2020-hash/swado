#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f .env ]]; then
  echo "Missing .env. Create it from .env.example and fill secrets." >&2
  exit 1
fi

command -v docker >/dev/null
docker compose version >/dev/null

docker compose pull || true
docker compose build gateway
docker compose up -d redis llm gateway

for i in {1..60}; do
  if curl -fsS http://127.0.0.1:8080/health >/dev/null; then
    echo "Gateway healthy"
    exit 0
  fi
  sleep 2
done

echo "Gateway failed health check" >&2
docker compose logs --tail=120 gateway >&2
exit 1
