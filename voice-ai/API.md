# Voice AI API

## WebSocket

Endpoint: `wss://AI_DOMAIN/v1/voice`

Client audio format: 16 kHz, mono, signed 16-bit PCM, base64 encoded in JSON.

Incoming events:

```json
{"type":"session.start","token":"LARAVEL_ISSUED_VOICE_TOKEN","user_id":"123"}
{"type":"audio.chunk","audio":"BASE64_PCM16"}
{"type":"audio.end"}
{"type":"session.end"}
```

Outgoing events:

```json
{"type":"session.started","session_id":"...","audio":{"format":"pcm16","sample_rate":16000,"channels":1}}
{"type":"speech.start"}
{"type":"transcript.final","text":"Ek chicken biryani add karo","latency_ms":421}
{"type":"assistant.text.delta","text":"Chicken Biryani Rs 229 available hai. Cart mein add kar diya."}
{"type":"assistant.audio.chunk","audio":"BASE64_PCM16","format":"pcm16","sample_rate":16000}
{"type":"assistant.audio.end"}
{"type":"assistant.interrupted"}
{"type":"error","code":"auth_required","message":"Missing token"}
```

## HTTP

`GET /health` verifies the process.

`GET /ready` verifies Redis and local runtime readiness.

`POST /v1/stt` accepts `{ "audio": "BASE64_PCM16", "language": "hi" }`.

`POST /v1/chat` accepts `{ "session_id": "...", "text": "Total kitna hua?" }` with `Authorization: Bearer LARAVEL_ISSUED_VOICE_TOKEN`.

`POST /v1/tts` accepts `{ "text": "Hello" }`.
