# Realtime Voice Assistant Architecture

The GPU server runs only AI inference and the Python gateway. Laravel, MySQL, Redis for the business app, and order logic remain on the existing backend.

Flow:

1. Flutter streams microphone PCM chunks to `wss://AI_DOMAIN/v1/voice`.
2. FastAPI applies VAD and detects barge-in.
3. faster-whisper transcribes utterances.
4. The agent uses Qwen for dialogue and calls Laravel AI tools for dynamic business data.
5. Laravel validates customer auth, ownership, availability, prices, fees, coupons, addresses, payment method, and order placement.
6. Kokoro TTS streams audio chunks back to Flutter.

Raw model ports are bound to localhost only. Nginx exposes HTTPS/WebSocket proxying on 443.

GPU target on a 24 GB RTX 4090:

- Qwen3 8B AWQ via vLLM: about 10-14 GB
- faster-whisper large-v3-turbo: about 3-5 GB
- Kokoro TTS: about 1-3 GB
- Reserve/CUDA overhead: remaining VRAM
