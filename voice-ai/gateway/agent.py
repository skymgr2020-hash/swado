import re
import uuid
from typing import Any

from .laravel_tools import LaravelToolClient
from .providers import LLMProvider
from .sessions import VoiceSession


SYSTEM_PROMPT = """You are Swado's realtime food ordering voice assistant.
Speak naturally and briefly in the customer's language: English, Hindi, or Hinglish.
Never invent restaurant names, prices, stock, fees, discounts, addresses, payment methods, ETAs, or order status.
Use tools for dynamic business data. Ask a clear confirmation before placing orders, selecting payment, changing ambiguous addresses, or cancelling orders.
Do not read technical IDs aloud unless needed to disambiguate.
Do not expose chain-of-thought or reasoning tags; answer with the customer-facing sentence only.
"""


TOOL_SCHEMAS: list[dict[str, Any]] = [
    {"type": "function", "function": {"name": "search_products", "description": "Search available menu products", "parameters": {"type": "object", "properties": {"restaurant_id": {"type": "integer"}, "query": {"type": "string"}}, "required": ["query"]}}},
    {"type": "function", "function": {"name": "search_restaurants", "description": "Search restaurants", "parameters": {"type": "object", "properties": {"query": {"type": "string"}, "lat": {"type": "number"}, "lng": {"type": "number"}}, "required": ["query"]}}},
    {"type": "function", "function": {"name": "get_saved_addresses", "description": "List the customer's saved addresses", "parameters": {"type": "object", "properties": {"restaurant_id": {"type": "integer"}}}}},
    {"type": "function", "function": {"name": "calculate_order_totals", "description": "Calculate authoritative order totals", "parameters": {"type": "object", "properties": {"restaurant_id": {"type": "integer"}, "items": {"type": "array"}, "delivery_address_id": {"type": "integer"}, "payment_method": {"type": "string"}, "coupon_code": {"type": "string"}}}}},
    {"type": "function", "function": {"name": "create_order", "description": "Place a confirmed order", "parameters": {"type": "object", "properties": {"restaurant_id": {"type": "integer"}, "items": {"type": "array"}, "delivery_address_id": {"type": "integer"}, "payment_method": {"type": "string"}, "coupon_code": {"type": "string"}}}}},
    {"type": "function", "function": {"name": "get_order_status", "description": "Get order status", "parameters": {"type": "object", "properties": {"order_id": {"type": "integer"}}, "required": ["order_id"]}}},
    {"type": "function", "function": {"name": "cancel_order", "description": "Cancel a confirmed order after explicit confirmation", "parameters": {"type": "object", "properties": {"order_id": {"type": "integer"}, "reason": {"type": "string"}}, "required": ["order_id", "reason"]}}},
]


class VoiceAgent:
    def __init__(self, laravel: LaravelToolClient, llm: LLMProvider | None = None) -> None:
        self.laravel = laravel
        self.llm = llm

    async def handle_text(
        self,
        text: str,
        session: VoiceSession,
        user_token: str,
        request_id: str | None = None,
    ) -> tuple[str, VoiceSession]:
        request_id = request_id or str(uuid.uuid4())
        lowered = text.lower()

        if "cod" in lowered or "cash" in lowered:
            session.payment_method = "cod"

        if "home" in lowered or "ghar" in lowered:
            addresses = await self.laravel.call(
                "get_saved_addresses",
                {"restaurant_id": session.restaurant_id},
                user_token,
                request_id,
            )
            selected = self._select_address(addresses.get("addresses") or [], "home")
            if selected:
                session.address_id = int(selected["id"])
                if session.cart and session.payment_method:
                    totals = await self._totals(session, user_token, request_id)
                    return f"Home address aur COD select ho gaya. Final total {self._total_label(totals)}. Order place kar doon?", session
                return "Home address select ho gaya.", session

        if any(word in lowered for word in ["remove", "hata", "nikal"]):
            removed = self._remove_from_cart(lowered, session)
            if removed:
                totals = await self._totals(session, user_token, request_id)
                return f"{removed} hata diya. Updated total {self._total_label(totals)}.", session

        if any(word in lowered for word in ["total", "kitna", "amount"]):
            totals = await self._totals(session, user_token, request_id)
            return f"Your current total is {self._total_label(totals)}.", session

        if any(word in lowered for word in ["place order", "order place", "haan", "yes"]) and session.cart:
            if not session.address_id or not session.payment_method:
                return "Address aur payment method confirm karna baaki hai.", session
            order = await self.laravel.call(
                "create_order",
                {
                    "restaurant_id": session.restaurant_id,
                    "items": session.cart,
                    "delivery_address_id": session.address_id,
                    "payment_method": session.payment_method,
                },
                user_token,
                request_id,
            )
            session.cart = []
            return f"Order place ho gaya. Order number {order.get('order_number')}.", session

        product_query = self._food_query(text)
        if product_query:
            added = []
            missing = []
            ambiguous = []
            for query in self._split_food_queries(product_query):
                result = await self.laravel.call(
                    "search_products",
                    {"restaurant_id": session.restaurant_id, "query": query},
                    user_token,
                    request_id,
                )
                items = result.get("items") or []
                if not items:
                    missing.append(query)
                    continue
                if len(items) > 1 and not self._exactish(query, items[0].get("name", "")):
                    ambiguous.extend(items[:3])
                    continue
                item = items[0]
                session.restaurant_id = int(item["restaurant_id"])
                quantity = self._quantity(lowered)
                self._upsert_cart(session, item, quantity)
                added.append(item)
            if ambiguous:
                options = ", ".join(f"{item['name']} Rs {item['price']}" for item in ambiguous[:3])
                return f"Mujhe ye options mile: {options}. Kaunsa add karun?", session
            if missing and not added:
                return "Ye item available nahi mila. Kuch aur try karen?", session
            if added:
                labels = ", ".join(f"{item['name']} Rs {item['price']}" for item in added)
                suffix = f" {', '.join(missing)} nahi mila." if missing else ""
                return f"{labels} cart mein add kar diya.{suffix}", session

        return await self._fallback(text, session), session

    async def _fallback(self, text: str, session: VoiceSession) -> str:
        if self.llm is None:
            return "Main help kar sakta hoon. Aap item, restaurant, total, address, ya order status bol sakte hain."
        messages = [{"role": "system", "content": SYSTEM_PROMPT}]
        messages.extend(session.recent_messages[-8:])
        cart_hint = ", ".join(str(item.get("name") or item.get("id")) for item in session.cart) or "empty"
        messages.append({"role": "system", "content": f"Session cart: {cart_hint}. Payment: {session.payment_method or 'not selected'}. Address: {session.address_id or 'not selected'}."})
        messages.append({"role": "user", "content": text})
        try:
            answer = await self.llm.complete_chat(messages)
        except Exception:
            return "Main help kar sakta hoon. Aap item, restaurant, total, address, ya order status bol sakte hain."
        return answer or "Main help kar sakta hoon. Aap item, restaurant, total, address, ya order status bol sakte hain."

    async def _totals(self, session: VoiceSession, user_token: str, request_id: str) -> dict[str, Any]:
        if not session.restaurant_id or not session.cart:
            return {"total": 0, "currency": "INR"}
        return await self.laravel.call(
            "calculate_order_totals",
            {
                "restaurant_id": session.restaurant_id,
                "items": session.cart,
                "delivery_address_id": session.address_id,
                "payment_method": session.payment_method or "cod",
            },
            user_token,
            request_id,
        )

    @staticmethod
    def _food_query(text: str) -> str | None:
        cleaned = re.sub(r"\b(add|karo|kar do|dikhao|show|want|mujhe|ek|do|two|one)\b", " ", text, flags=re.I)
        cleaned = re.sub(r"\s+", " ", cleaned).strip(" .")
        return cleaned or None

    @staticmethod
    def _split_food_queries(text: str) -> list[str]:
        raw_parts = re.split(r"\s+(?:aur|and|with|plus|bhi)\s+", text, flags=re.I)
        parts = [re.sub(r"\s+", " ", part).strip(" .") for part in raw_parts]
        if len(parts) == 1:
            words = parts[0].split()
            if len(words) >= 3 and "coke" in [word.lower() for word in words]:
                before_coke = " ".join(word for word in words if word.lower() != "coke")
                return [before_coke, "coke"]
        return [part for part in parts if part]

    @staticmethod
    def _quantity(text: str) -> int:
        if re.search(r"\b(do|two|2)\b", text):
            return 2
        return 1

    @staticmethod
    def _exactish(query: str, name: str) -> bool:
        q = set(query.lower().split())
        n = set(name.lower().split())
        return bool(q) and len(q & n) >= min(2, len(q))

    @staticmethod
    def _upsert_cart(session: VoiceSession, item: dict[str, Any], quantity: int) -> None:
        for cart_item in session.cart:
            if int(cart_item["id"]) == int(item["id"]):
                cart_item["quantity"] = int(cart_item.get("quantity", 1)) + quantity
                cart_item["name"] = item.get("name")
                return
        session.cart.append({"id": int(item["id"]), "quantity": quantity, "name": item.get("name")})

    @staticmethod
    def _remove_from_cart(text: str, session: VoiceSession) -> str | None:
        for index, item in enumerate(session.cart):
            name = str(item.get("name") or item.get("label") or item.get("id"))
            if name.lower() in text or any(part in text for part in name.lower().split()):
                session.cart.pop(index)
                return name
        if session.cart:
            removed = session.cart.pop()
            return str(removed.get("name") or removed.get("id"))
        return None

    @staticmethod
    def _select_address(addresses: list[dict[str, Any]], label: str) -> dict[str, Any] | None:
        lowered = label.lower()
        for address in addresses:
            if lowered in str(address.get("name", "")).lower():
                return address
        for address in addresses:
            if address.get("is_default"):
                return address
        return addresses[0] if addresses else None

    @staticmethod
    def _total_label(totals: dict[str, Any]) -> str:
        total = totals.get("total") or totals.get("pricing", {}).get("total") or 0
        return f"Rs {total}"