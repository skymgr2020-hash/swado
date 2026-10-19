import json
import uuid
from typing import Any, Awaitable, Callable
from urllib.parse import quote

import websockets

from .agent import TOOL_SCHEMAS
from .laravel_tools import LaravelToolClient
from .providers import b64_pcm

SendEvent = Callable[[dict[str, Any]], Awaitable[None]]


class GeminiLiveBridge:
    def __init__(self, runtime: dict[str, Any], laravel: LaravelToolClient, voice_token: str, session_id: str, request_id: str | None = None) -> None:
        self.runtime = runtime
        self.laravel = laravel
        self.voice_token = voice_token
        self.session_id = session_id
        self.request_id = request_id or str(uuid.uuid4())
        self.socket = None
        self.tool_calls_count = 0

    @property
    def model(self) -> str:
        return str(self.runtime.get("voice_ai_gemini_model") or "gemini-3.1-flash-live-preview")

    @property
    def api_key(self) -> str:
        return str(self.runtime.get("voice_ai_gemini_api_key") or "")

    async def connect(self) -> None:
        if not self.api_key:
            raise RuntimeError("Gemini API key is not configured")
        url = "wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContent?key=" + quote(self.api_key)
        self.socket = await websockets.connect(url, max_size=None, ping_interval=20, ping_timeout=20)
        await self._send({"setup": self._setup_payload()})

    async def send_audio(self, pcm: bytes, sample_rate: int = 16000) -> None:
        await self._send({"realtimeInput": {"audio": {"data": b64_pcm(pcm), "mimeType": f"audio/pcm;rate={sample_rate}"}}})

    async def finish_turn(self) -> None:
        await self._send({"realtimeInput": {"activityEnd": {}}})

    async def close(self) -> None:
        if self.socket is not None:
            await self.socket.close()

    async def receive_loop(self, send: SendEvent) -> None:
        if self.socket is None:
            raise RuntimeError("Gemini bridge is not connected")
        async for raw in self.socket:
            payload = json.loads(raw)
            if "setupComplete" in payload:
                await send({"type": "provider.ready", "provider": "gemini_live"})
                continue
            if "toolCall" in payload:
                await self._handle_tool_call(payload["toolCall"])
                continue
            server_content = payload.get("serverContent") or {}
            input_tx = server_content.get("inputTranscription") or server_content.get("input_transcription") or {}
            if input_tx.get("text"):
                await send({"type": "transcript.final", "text": input_tx["text"]})
            output_tx = server_content.get("outputTranscription") or server_content.get("output_transcription") or {}
            if output_tx.get("text"):
                await send({"type": "assistant.text.delta", "text": output_tx["text"]})
            for audio in self._audio_chunks(payload):
                await send({"type": "assistant.audio.chunk", "audio": audio, "format": "pcm16", "sample_rate": 24000})
            if server_content.get("turnComplete") or server_content.get("turn_complete") or server_content.get("generationComplete"):
                await send({"type": "assistant.audio.end", "provider": "gemini_live"})

    async def _handle_tool_call(self, tool_call: dict[str, Any]) -> None:
        calls = tool_call.get("functionCalls") or tool_call.get("function_calls") or []
        responses = []
        for call in calls:
            name = call.get("name")
            args = call.get("args") or call.get("arguments") or {}
            call_id = call.get("id")
            if not name:
                continue
            try:
                result = await self.laravel.call(name, args if isinstance(args, dict) else {}, self.voice_token, self.request_id)
            except Exception as exc:
                result = {"error": str(exc)}
            self.tool_calls_count += 1
            response = {"name": name, "response": result}
            if call_id:
                response["id"] = call_id
            responses.append(response)
        if responses:
            await self._send({"toolResponse": {"functionResponses": responses}})

    async def _send(self, payload: dict[str, Any]) -> None:
        if self.socket is None:
            raise RuntimeError("Gemini bridge is not connected")
        await self.socket.send(json.dumps(payload, separators=(",", ":")))

    def _setup_payload(self) -> dict[str, Any]:
        setup = {
            "model": f"models/{self.model}",
            "responseModalities": ["AUDIO"],
            "inputAudioTranscription": {},
            "outputAudioTranscription": {},
            "systemInstruction": {"parts": [{"text": str(self.runtime.get("voice_ai_gemini_system_prompt") or "You are Swado's voice ordering assistant.")}]},
            "generationConfig": {"temperature": float(self.runtime.get("voice_ai_gemini_temperature") or 0.2)},
        }
        if bool(self.runtime.get("voice_ai_gemini_tool_calling_enabled", True)):
            setup["tools"] = [{"functionDeclarations": _gemini_tool_declarations()}]
        return setup

    @staticmethod
    def _audio_chunks(payload: dict[str, Any]) -> list[str]:
        chunks: list[str] = []
        data = payload.get("data")
        if isinstance(data, str):
            chunks.append(data)
        server_content = payload.get("serverContent") or {}
        model_turn = server_content.get("modelTurn") or server_content.get("model_turn") or {}
        for part in model_turn.get("parts") or []:
            inline_data = part.get("inlineData") or part.get("inline_data") or {}
            encoded = inline_data.get("data")
            if isinstance(encoded, str):
                chunks.append(encoded)
        return chunks


def _gemini_tool_declarations() -> list[dict[str, Any]]:
    declarations: list[dict[str, Any]] = []
    for schema in TOOL_SCHEMAS:
        function = schema.get("function") or {}
        declaration = {"name": function.get("name"), "description": function.get("description", "")}
        parameters = function.get("parameters")
        if parameters:
            declaration["parameters"] = parameters
        declarations.append(declaration)
    return [item for item in declarations if item.get("name")]
