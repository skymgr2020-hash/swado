import base64
import io
import re
import wave
from typing import AsyncIterator

import httpx
import numpy as np

from .config import Settings


class STTProvider:
    async def transcribe_pcm16(self, pcm: bytes, language: str | None = None) -> str:
        raise NotImplementedError


class FasterWhisperProvider(STTProvider):
    def __init__(self, settings: Settings) -> None:
        self.settings = settings
        self._model = None

    def _load(self):
        if self._model is None:
            from faster_whisper import WhisperModel

            self._model = WhisperModel(
                self.settings.whisper_model,
                device=self.settings.whisper_device,
                compute_type=self.settings.whisper_compute_type,
            )
        return self._model

    async def transcribe_pcm16(self, pcm: bytes, language: str | None = None) -> str:
        model = self._load()
        wav = _pcm16_to_wav_bytes(pcm, self.settings.audio_sample_rate)
        segments, _ = model.transcribe(io.BytesIO(wav), language=language, vad_filter=False)
        return " ".join(segment.text.strip() for segment in segments).strip()


class LLMProvider:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings
        self.client = httpx.AsyncClient(timeout=httpx.Timeout(60.0, connect=5.0))

    async def stream_chat(self, messages: list[dict], tools: list[dict] | None = None) -> AsyncIterator[str]:
        payload = {
            "model": self.settings.llm_model,
            "messages": messages,
            "temperature": 0.2,
            "stream": True,
        }
        if tools:
            payload["tools"] = tools
            payload["tool_choice"] = "auto"
        async with self.client.stream(
            "POST",
            f"{self.settings.llm_base_url.rstrip('/')}/chat/completions",
            headers={"Authorization": f"Bearer {self.settings.llm_api_key}"},
            json=payload,
        ) as response:
            response.raise_for_status()
            async for line in response.aiter_lines():
                if not line.startswith("data: "):
                    continue
                data = line.removeprefix("data: ").strip()
                if data == "[DONE]":
                    break
                yield data

    async def complete_chat(self, messages: list[dict], tools: list[dict] | None = None) -> str:
        payload = {
            "model": self.settings.llm_model,
            "messages": messages,
            "temperature": 0.2,
            "max_tokens": 160,
            "stream": False,
        }
        if tools:
            payload["tools"] = tools
            payload["tool_choice"] = "auto"
        response = await self.client.post(
            f"{self.settings.llm_base_url.rstrip('/')}/chat/completions",
            headers={"Authorization": f"Bearer {self.settings.llm_api_key}"},
            json=payload,
        )
        response.raise_for_status()
        content = response.json()["choices"][0]["message"].get("content") or ""
        return _strip_reasoning(content).strip()


class TTSProvider:
    async def synthesize_stream(self, text: str, language: str | None = None) -> AsyncIterator[bytes]:
        raise NotImplementedError


class KokoroProvider(TTSProvider):
    def __init__(self, settings: Settings) -> None:
        self.settings = settings

    async def synthesize_stream(self, text: str, language: str | None = None) -> AsyncIterator[bytes]:
        try:
            from kokoro import KPipeline

            pipeline = KPipeline(lang_code="a")
            generator = pipeline(text, voice=self.settings.tts_voice, speed=1.0)
            for _, _, audio in generator:
                yield _float_audio_to_pcm16(audio)
        except Exception:
            yield _silence_pcm16(milliseconds=180, sample_rate=self.settings.audio_sample_rate)


class SileroVad:
    def __init__(self, threshold: float = 0.012) -> None:
        self.threshold = threshold

    def has_speech(self, pcm: bytes) -> bool:
        if not pcm:
            return False
        samples = np.frombuffer(pcm, dtype=np.int16).astype(np.float32) / 32768.0
        if samples.size == 0:
            return False
        return float(np.sqrt(np.mean(samples * samples))) >= self.threshold


def _pcm16_to_wav_bytes(pcm: bytes, sample_rate: int) -> bytes:
    buffer = io.BytesIO()
    with wave.open(buffer, "wb") as wav:
        wav.setnchannels(1)
        wav.setsampwidth(2)
        wav.setframerate(sample_rate)
        wav.writeframes(pcm)
    return buffer.getvalue()


def _float_audio_to_pcm16(audio) -> bytes:
    arr = np.asarray(audio, dtype=np.float32)
    arr = np.clip(arr, -1.0, 1.0)
    return (arr * 32767.0).astype(np.int16).tobytes()


def _silence_pcm16(milliseconds: int, sample_rate: int) -> bytes:
    samples = int(sample_rate * milliseconds / 1000)
    return np.zeros(samples, dtype=np.int16).tobytes()


def b64_pcm(payload: bytes) -> str:
    return base64.b64encode(payload).decode("ascii")


def parse_b64_audio(value: str) -> bytes:
    return base64.b64decode(value.encode("ascii"), validate=True)


def _strip_reasoning(text: str) -> str:
    text = re.sub(r"<think>.*?</think>", "", text, flags=re.S | re.I)
    text = re.sub(r"^<think>.*", "", text, flags=re.S | re.I)
    return text
