#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "Health:"
curl -fsS http://127.0.0.1:8080/health
echo
echo "Ready:"
curl -fsS http://127.0.0.1:8080/ready
echo
echo "GPU:"
nvidia-smi --query-gpu=name,memory.used,memory.total,utilization.gpu --format=csv || true
echo "Containers:"
docker compose ps
