# Swado Realtime Voice AI

This folder contains the deployable GPU-server voice assistant gateway.

It includes:

- FastAPI gateway with `/health`, `/ready`, `/v1/stt`, `/v1/chat`, `/v1/tts`, and `WS /v1/voice`.
- Redis-backed voice sessions.
- Laravel AI tool client.
- faster-whisper, Qwen/vLLM, Kokoro, and VAD provider abstractions.
- Docker Compose for gateway, Redis, and local Qwen serving.
- Deployment, benchmark, and server inspection scripts.

The gateway intentionally stores only session state and a temporary AI cart. Laravel remains authoritative for prices, availability, addresses, totals, payment choices, coupons, order placement, cancellation, and tracking.

Copy `.env.example` to `.env` on the GPU server and fill the required secrets before deployment.
