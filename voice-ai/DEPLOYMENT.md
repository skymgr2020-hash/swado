# Deployment

## Required Credentials

- GPU server SSH host/IP, username, SSH key or password, and sudo access.
- AI domain name, for example `ai.swado.online`.
- DNS access to create `A ai.swado.online GPU_SERVER_IP`.
- Laravel production API base URL.
- A new shared `AI_SERVICE_SECRET`, set on Laravel and the AI gateway.
- A test customer account/token in Laravel to mint short-lived `/api/ai/session-token` voice tokens for end-to-end testing.
- Hugging Face token if the chosen Qwen/Whisper/Kokoro model requires gated downloads.
- Optional: registry credentials if images are pulled from a private registry.

## Server Commands

```bash
cd /opt/voice-ai
cp .env.example .env
nano .env
./scripts/inspect_server.sh
./scripts/install_runtime_ubuntu.sh
./scripts/deploy.sh
```

Nginx:

```bash
sudo cp config/nginx.conf.example /etc/nginx/sites-available/voice-ai
sudo sed -i 's/AI_DOMAIN/ai.swado.online/g' /etc/nginx/sites-available/voice-ai
sudo ln -s /etc/nginx/sites-available/voice-ai /etc/nginx/sites-enabled/voice-ai
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx -d ai.swado.online
```

Firewall, after confirming SSH works:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

## Operations

```bash
docker compose up -d
docker compose stop
docker compose restart gateway
docker compose ps
docker compose logs -f gateway
./scripts/deploy.sh
./scripts/benchmark.sh
```
