import asyncio
import contextlib
import json
import math
import time
import uuid
from typing import Any

from fastapi import FastAPI, Header, HTTPException, WebSocket, WebSocketDisconnect
from fastapi.responses import ORJSONResponse

from .agent import VoiceAgent
from .config import get_settings
from .gemini_live import GeminiLiveBridge
from .laravel_tools import LaravelToolClient
from .logging_config import configure_logging
from .providers import FasterWhisperProvider, KokoroProvider, LLMProvider, SileroVad, b64_pcm, parse_b64_audio
from .sessions import SessionStore, VoiceSession

settings = get_settings()
configure_logging(settings.log_level)

app = FastAPI(default_response_class=ORJSONResponse, title="Swado Voice AI Gateway")
sessions = SessionStore(settings.redis_url, settings.session_ttl_seconds)
laravel = LaravelToolClient(str(settings.laravel_api_url), settings.laravel_ai_secret)
llm = LLMProvider(settings)
agent = VoiceAgent(laravel, llm)
stt = FasterWhisperProvider(settings)
tts = KokoroProvider(settings)
vad = SileroVad()
_runtime_cache: dict[str, Any] = {"expires_at": 0.0, "data": {}}


@app.get("/health")
@app.get("/voice-ai/health")
async def health():
    runtime = await _runtime_settings()
    return {"status": "ok", "gpu": _gpu_available(), "providers": _provider_health(runtime)}


@app.get("/ready")
@app.get("/voice-ai/ready")
async def ready():
    redis_ok = await sessions.ping()
    runtime = await _runtime_settings()
    selected, _ = _select_provider(runtime)
    return {"status": "ok" if redis_ok and selected else "degraded", "redis": redis_ok, "gpu": _gpu_available(), "selected_provider": selected}


@app.get("/providers/health")
@app.get("/voice-ai/providers/health")
async def providers_health():
    runtime = await _runtime_settings(force=True)
    return {"success": True, "providers": _provider_health(runtime), "routing": _routing(runtime)}


@app.post("/v1/stt")
@app.post("/voice-ai/v1/stt")
async def transcribe(payload: dict):
    pcm = parse_b64_audio(payload["audio"])
    text = await stt.transcribe_pcm16(pcm, payload.get("language"))
    return {"success": True, "text": text}


@app.post("/v1/chat")
@app.post("/voice-ai/v1/chat")
async def chat(payload: dict, authorization: str = Header(default="")):
    token = _bearer(authorization)
    session_id = payload.get("session_id")
    session = await sessions.get(session_id) if session_id else await sessions.create("api")
    if session is None:
        raise HTTPException(status_code=404, detail="session not found")
    session.provider = session.provider or "self_hosted"
    text, session = await agent.handle_text(payload["text"], session, token, payload.get("request_id"))
    await sessions.save(session)
    return {"success": True, "session_id": session.session_id, "provider": session.provider, "text": text, "cart": session.cart}


@app.post("/v1/tts")
@app.post("/voice-ai/v1/tts")
async def speak(payload: dict):
    chunks = []
    async for chunk in tts.synthesize_stream(payload["text"], payload.get("language")):
        chunks.append(chunk)
    return {"success": True, "audio": b64_pcm(b"".join(chunks)), "format": "pcm16", "sample_rate": settings.audio_sample_rate}


@app.websocket("/v1/voice")
@app.websocket("/voice-ai/v1/voice")
async def voice_socket(ws: WebSocket):
    await ws.accept()
    user_token = ""
    session: VoiceSession | None = None
    runtime: dict[str, Any] = {}
    audio_buffer = bytearray()
    speaking_task: asyncio.Task | None = None
    gemini_bridge: GeminiLiveBridge | None = None
    gemini_receive_task: asyncio.Task | None = None

    async def send(event: dict) -> None:
        await ws.send_text(json.dumps(event, separators=(",", ":")))

    try:
        while True:
            event = json.loads(await ws.receive_text())
            event_type = event.get("type")

            if event_type == "session.start":
                user_token = event.get("token") or ""
                if not user_token:
                    await send({"type": "error", "code": "auth_required", "message": "Missing token"})
                    continue

                runtime = await _runtime_settings(force=True)
                if not _enabled(runtime):
                    await send({"type": "error", "code": "voice_ai_disabled", "message": "AI voice assistant is disabled"})
                    continue

                provider, fallback_used = _select_provider(runtime)
                if provider is None:
                    await send({"type": "error", "code": "provider_unavailable", "message": "No Voice AI provider is configured"})
                    continue

                session = await sessions.create(event.get("user_id") or "customer")
                session.provider = provider
                session.fallback_provider = str(runtime.get("voice_ai_fallback_provider") or "none")
                session.fallback_used = fallback_used
                session.language = _session_language(event, runtime)
                await sessions.save(session)

                await _record_usage_safe(user_token, str(uuid.uuid4()), {
                    "session_id": session.session_id,
                    "provider": session.provider,
                    "fallback_provider": session.fallback_provider,
                    "fallback_used": session.fallback_used,
                    "status": "started",
                })

                if provider == "gemini_live":
                    gemini_bridge = GeminiLiveBridge(runtime, laravel, user_token, session.session_id)
                    await gemini_bridge.connect()
                    gemini_receive_task = asyncio.create_task(gemini_bridge.receive_loop(send))

                await send({
                    "type": "session.started",
                    "session_id": session.session_id,
                    "provider": provider,
                    "fallback_used": fallback_used,
                    "audio": _audio_config(provider),
                    "session_limit_seconds": int(runtime.get("voice_ai_max_session_minutes") or 10) * 60,
                    "idle_timeout_seconds": int(runtime.get("voice_ai_idle_timeout_seconds") or 45),
                    "barge_in_enabled": bool(runtime.get("voice_ai_barge_in_enabled", True)),
                })
                continue

            if session is None:
                await send({"type": "error", "code": "session_required", "message": "Send session.start first"})
                continue

            if event_type == "audio.chunk":
                chunk = parse_b64_audio(event.get("audio", ""))
                audio_buffer.extend(chunk)
                if session.provider == "gemini_live" and gemini_bridge is not None:
                    await gemini_bridge.send_audio(chunk, settings.audio_sample_rate)
                    continue

                if speaking_task and not speaking_task.done() and vad.has_speech(chunk):
                    speaking_task.cancel()
                    session.active_tts = False
                    await sessions.save(session)
                    await send({"type": "assistant.interrupted"})
                if vad.has_speech(chunk):
                    await send({"type": "speech.start"})
                continue

            if event_type == "audio.end":
                request_id = str(uuid.uuid4())
                pcm = bytes(audio_buffer)
                audio_buffer.clear()
                if not pcm:
                    await send({"type": "error", "code": "no_audio", "message": "No audio received"})
                    continue

                input_seconds = _audio_seconds(pcm, settings.audio_sample_rate)
                if session.provider == "gemini_live" and gemini_bridge is not None:
                    await gemini_bridge.finish_turn()
                    await _record_usage_safe(user_token, request_id, {
                        "session_id": session.session_id,
                        "provider": session.provider,
                        "fallback_provider": session.fallback_provider,
                        "fallback_used": session.fallback_used,
                        "status": "active",
                        "seconds": input_seconds,
                        "input_audio_seconds": input_seconds,
                        "tool_calls_count": gemini_bridge.tool_calls_count,
                    })
                    gemini_bridge.tool_calls_count = 0
                    continue

                started = time.perf_counter()
                transcript = await stt.transcribe_pcm16(pcm, event.get("language") or session.language)
                await send({"type": "transcript.final", "text": transcript, "latency_ms": _ms(started)})
                answer, session = await agent.handle_text(transcript, session, user_token, request_id)
                session.recent_messages.extend([
                    {"role": "user", "content": transcript},
                    {"role": "assistant", "content": answer},
                ])
                await sessions.save(session)
                await send({"type": "assistant.text.delta", "text": answer})
                speaking_task = asyncio.create_task(_stream_tts(answer, session.language, send))
                await _record_usage_safe(user_token, request_id, {
                    "session_id": session.session_id,
                    "provider": session.provider,
                    "fallback_provider": session.fallback_provider,
                    "fallback_used": session.fallback_used,
                    "status": "active",
                    "seconds": input_seconds,
                    "input_audio_seconds": input_seconds,
                })
                continue

            if event_type == "session.end":
                await _record_usage_safe(user_token, str(uuid.uuid4()), {
                    "session_id": session.session_id,
                    "provider": session.provider,
                    "fallback_provider": session.fallback_provider,
                    "fallback_used": session.fallback_used,
                    "status": "ended",
                })
                await send({"type": "session.ended"})
                await ws.close()
                return

            await send({"type": "error", "code": "unknown_event", "message": f"Unknown event {event_type}"})
    except WebSocketDisconnect:
        pass
    finally:
        if speaking_task and not speaking_task.done():
            speaking_task.cancel()
        if gemini_receive_task and not gemini_receive_task.done():
            gemini_receive_task.cancel()
        if gemini_bridge is not None:
            with contextlib.suppress(Exception):
                await gemini_bridge.close()


async def _stream_tts(text: str, language: str | None, send) -> None:
    started = time.perf_counter()
    first = True
    async for chunk in tts.synthesize_stream(text, language):
        event = {"type": "assistant.audio.chunk", "audio": b64_pcm(chunk), "format": "pcm16", "sample_rate": settings.audio_sample_rate}
        if first:
            event["first_audio_latency_ms"] = _ms(started)
            first = False
        await send(event)
    await send({"type": "assistant.audio.end"})


def _audio_config(provider: str = "self_hosted") -> dict:
    sample_rate = 24000 if provider == "gemini_live" else settings.audio_sample_rate
    return {"format": "pcm16", "sample_rate": sample_rate, "input_sample_rate": settings.audio_sample_rate, "channels": settings.audio_channels, "sample_width_bytes": settings.audio_sample_width_bytes}


def _bearer(header: str) -> str:
    if not header.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Bearer token required")
    return header.removeprefix("Bearer ").strip()


async def _runtime_settings(force: bool = False) -> dict[str, Any]:
    now = time.time()
    if not force and _runtime_cache["data"] and float(_runtime_cache["expires_at"]) > now:
        return dict(_runtime_cache["data"])
    try:
        data = await laravel.voice_settings()
    except Exception:
        data = {
            "voice_ai_enabled": True,
            "voice_ai_primary_provider": "self_hosted",
            "voice_ai_fallback_provider": "none",
            "voice_ai_barge_in_enabled": True,
            "voice_ai_max_session_minutes": 10,
            "voice_ai_idle_timeout_seconds": 45,
        }
    _runtime_cache["data"] = data
    _runtime_cache["expires_at"] = now + settings.settings_cache_ttl_seconds
    return dict(data)


def _routing(runtime: dict[str, Any]) -> dict[str, Any]:
    selected, fallback_used = _select_provider(runtime)
    return {"primary_provider": runtime.get("voice_ai_primary_provider"), "fallback_provider": runtime.get("voice_ai_fallback_provider"), "selected_provider": selected, "fallback_used": fallback_used}


def _enabled(runtime: dict[str, Any]) -> bool:
    return bool(runtime.get("voice_ai_enabled", True))


def _select_provider(runtime: dict[str, Any]) -> tuple[str | None, bool]:
    primary = str(runtime.get("voice_ai_primary_provider") or "self_hosted")
    fallback = str(runtime.get("voice_ai_fallback_provider") or "none")
    if _provider_available(primary, runtime):
        return primary, False
    if fallback != "none" and fallback != primary and _provider_available(fallback, runtime):
        return fallback, True
    return None, False


def _provider_available(provider: str, runtime: dict[str, Any]) -> bool:
    if provider == "self_hosted":
        return True
    if provider == "gemini_live":
        return bool(runtime.get("voice_ai_gemini_api_key"))
    return False


def _provider_health(runtime: dict[str, Any]) -> dict[str, Any]:
    return {
        "self_hosted": {"status": "ok", "gpu": _gpu_available(), "llm": True, "stt": True, "tts": True},
        "gemini_live": {"status": "configured" if runtime.get("voice_ai_gemini_api_key") else "misconfigured", "model": runtime.get("voice_ai_gemini_model")},
    }


def _session_language(event: dict[str, Any], runtime: dict[str, Any]) -> str | None:
    language = event.get("language") or runtime.get("voice_ai_language")
    return None if language in (None, "", "auto") else str(language)


async def _record_usage_safe(voice_token: str, request_id: str, payload: dict[str, Any]) -> None:
    try:
        await laravel.record_usage(voice_token, request_id, payload)
    except Exception:
        return


def _gpu_available() -> bool:
    try:
        import torch
        return bool(torch.cuda.is_available())
    except Exception:
        return False


def _audio_seconds(pcm: bytes, sample_rate: int) -> int:
    bytes_per_second = sample_rate * settings.audio_channels * settings.audio_sample_width_bytes
    return max(1, math.ceil(len(pcm) / bytes_per_second)) if pcm else 0


def _ms(started: float) -> int:
    return int((time.perf_counter() - started) * 1000)
