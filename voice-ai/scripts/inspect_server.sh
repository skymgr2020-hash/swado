#!/usr/bin/env bash
set -euo pipefail

uname -a || true
cat /etc/os-release || true
nvidia-smi || true
free -h || true
df -h || true
lscpu || true
nproc || true
docker --version || true
python3 --version || true
