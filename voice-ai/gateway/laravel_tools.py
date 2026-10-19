from typing import Any

import httpx


class LaravelToolError(RuntimeError):
    pass


class LaravelToolClient:
    def __init__(self, base_url: str, service_secret: str) -> None:
        self.base_url = base_url.rstrip("/")
        self.service_secret = service_secret
        self.client = httpx.AsyncClient(timeout=httpx.Timeout(20.0, connect=5.0))

    async def call(
        self,
        tool: str,
        arguments: dict[str, Any],
        voice_token: str,
        request_id: str,
    ) -> dict[str, Any]:
        response = await self.client.post(
            f"{self.base_url}/ai/tools/{tool}",
            json={"arguments": arguments},
            headers=self._headers(voice_token, request_id),
        )
        payload = self._json(response)
        if response.status_code >= 400 or not payload.get("success", False):
            raise LaravelToolError(str(payload.get("message") or "Laravel tool call failed"))
        return payload.get("data") or {}

    async def voice_settings(self) -> dict[str, Any]:
        response = await self.client.get(
            f"{self.base_url}/ai/settings",
            headers={
                "X-AI-Service-Secret": self.service_secret,
                "Accept": "application/json",
            },
        )
        payload = self._json(response)
        if response.status_code >= 400 or not payload.get("success", False):
            raise LaravelToolError(str(payload.get("message") or "Unable to load Voice AI settings"))
        return payload.get("data") or {}

    async def record_usage(
        self,
        voice_token: str,
        request_id: str,
        payload: dict[str, Any],
    ) -> dict[str, Any]:
        response = await self.client.post(
            f"{self.base_url}/ai/usage",
            json=payload,
            headers=self._headers(voice_token, request_id),
        )
        data = self._json(response)
        if response.status_code >= 400 or not data.get("success", False):
            raise LaravelToolError(str(data.get("message") or "Unable to record Voice AI usage"))
        return data.get("data") or {}

    def _headers(self, voice_token: str, request_id: str) -> dict[str, str]:
        return {
            "X-AI-Service-Secret": self.service_secret,
            "X-AI-Voice-Token": voice_token,
            "X-Request-ID": request_id,
            "Accept": "application/json",
        }

    @staticmethod
    def _json(response: httpx.Response) -> dict[str, Any]:
        try:
            return response.json()
        except ValueError as exc:
            raise LaravelToolError(f"Laravel returned non-JSON response ({response.status_code})") from exc
