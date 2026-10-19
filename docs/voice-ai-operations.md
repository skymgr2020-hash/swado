# Swado Voice AI Operations

## Required Credentials

- `AI_SERVICE_SECRET` in Laravel `.env` and the gateway `.env`; both values must match.
- Gemini API key in Admin > Settings > Voice AI when using `Gemini Live` as primary or fallback.
- Self-hosted gateway service access: SSH to the GPU host that owns `/opt/voice-ai`, plus permission to restart `voice-ai-gateway` and `voice-ai-tunnel`.
- Production web SSH or DirectAdmin access for Laravel deploys and migrations.
- Customer app build/release credentials when shipping the Flutter app to Play Store/App Store.

## Runtime Flow

1. Flutter requests `POST /api/ai/session-token` with Sanctum auth.
2. Laravel checks admin enablement and user allowance, then returns a short-lived voice token and safe gateway config.
3. Flutter connects to `wss://<gateway>/v1/voice` and sends `session.start` plus PCM audio chunks.
4. The gateway fetches internal settings from `GET /api/ai/settings`, selects the configured provider, pins the session provider in Redis, and streams responses back.
5. The gateway records usage through `POST /api/ai/usage`; Laravel decrements allowance server-side.
6. Successful AI-created orders recharge allowance once using `voice_last_recharged_order_id`.

## Provider Notes

- Self-hosted remains the default working provider and uses local Whisper, Qwen/vLLM, Kokoro, Redis, and Laravel tools.
- Gemini Live is available only after the admin saves a Gemini API key. The key is encrypted in Laravel settings and is never sent to Flutter.
- If the primary provider is unavailable and fallback is configured, the gateway pins the session to fallback for the lifetime of that session.

## Current Deployment Note

Laravel admin/API/schema changes can be deployed through the production web account. Gateway deploy requires the GPU SSH endpoint to be reachable because the web account does not have `/opt/voice-ai` access.
