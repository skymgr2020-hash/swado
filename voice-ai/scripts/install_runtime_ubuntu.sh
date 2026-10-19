#!/usr/bin/env bash
set -euo pipefail

sudo apt-get update
sudo apt-get install -y ca-certificates curl gnupg ffmpeg nginx ufw

if ! command -v docker >/dev/null; then
  curl -fsSL https://get.docker.com | sudo sh
  sudo usermod -aG docker "$USER"
fi

if ! docker info 2>/dev/null | grep -qi nvidia; then
  echo "Install NVIDIA Container Toolkit before running GPU containers:"
  echo "https://docs.nvidia.com/datacenter/cloud-native/container-toolkit/latest/install-guide.html"
fi
