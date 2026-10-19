import json
import time
import uuid
from dataclasses import dataclass, field
from typing import Any

from redis.asyncio import Redis


@dataclass
class VoiceSession:
    user_id: str
    session_id: str = field(default_factory=lambda: str(uuid.uuid4()))
    restaurant_id: int | None = None
    address_id: int | None = None
    payment_method: str | None = None
    language: str | None = None
    provider: str | None = None
    fallback_provider: str | None = None
    fallback_used: bool = False
    started_at: float = field(default_factory=time.time)
    cart: list[dict[str, Any]] = field(default_factory=list)
    recent_messages: list[dict[str, str]] = field(default_factory=list)
    active_tts: bool = False
    last_activity: float = field(default_factory=time.time)

    def to_dict(self) -> dict[str, Any]:
        return {
            "user_id": self.user_id,
            "session_id": self.session_id,
            "restaurant_id": self.restaurant_id,
            "address_id": self.address_id,
            "payment_method": self.payment_method,
            "language": self.language,
            "provider": self.provider,
            "fallback_provider": self.fallback_provider,
            "fallback_used": self.fallback_used,
            "started_at": self.started_at,
            "cart": self.cart,
            "recent_messages": self.recent_messages[-12:],
            "active_tts": self.active_tts,
            "last_activity": self.last_activity,
        }

    @classmethod
    def from_dict(cls, payload: dict[str, Any]) -> "VoiceSession":
        session = cls(user_id=str(payload["user_id"]), session_id=str(payload["session_id"]))
        session.restaurant_id = payload.get("restaurant_id")
        session.address_id = payload.get("address_id")
        session.payment_method = payload.get("payment_method")
        session.language = payload.get("language")
        session.provider = payload.get("provider")
        session.fallback_provider = payload.get("fallback_provider")
        session.fallback_used = bool(payload.get("fallback_used", False))
        session.started_at = float(payload.get("started_at", time.time()))
        session.cart = list(payload.get("cart") or [])
        session.recent_messages = list(payload.get("recent_messages") or [])
        session.active_tts = bool(payload.get("active_tts", False))
        session.last_activity = float(payload.get("last_activity", time.time()))
        return session


class SessionStore:
    def __init__(self, redis_url: str, ttl_seconds: int) -> None:
        self.redis = Redis.from_url(redis_url, decode_responses=True)
        self.ttl_seconds = ttl_seconds

    async def create(self, user_id: str) -> VoiceSession:
        session = VoiceSession(user_id=user_id)
        await self.save(session)
        return session

    async def get(self, session_id: str) -> VoiceSession | None:
        raw = await self.redis.get(self._key(session_id))
        if raw is None:
            return None
        return VoiceSession.from_dict(json.loads(raw))

    async def save(self, session: VoiceSession) -> None:
        session.last_activity = time.time()
        await self.redis.setex(
            self._key(session.session_id),
            self.ttl_seconds,
            json.dumps(session.to_dict(), separators=(",", ":")),
        )

    async def ping(self) -> bool:
        return bool(await self.redis.ping())

    @staticmethod
    def _key(session_id: str) -> str:
        return f"voice:session:{session_id}"
