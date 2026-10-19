import asyncio
import base64
import json
import sys

import websockets


async def main() -> None:
    if len(sys.argv) < 3:
        raise SystemExit("usage: python tests/ws_smoke_client.py ws://host/v1/voice VOICE_TOKEN")

    url = sys.argv[1]
    token = sys.argv[2]
    async with websockets.connect(url, ping_interval=20) as ws:
        await ws.send(json.dumps({"type": "session.start", "token": token}))
        print(await ws.recv())
        await ws.send(json.dumps({"type": "audio.chunk", "audio": base64.b64encode(b"\x00" * 3200).decode()}))
        await ws.send(json.dumps({"type": "audio.end"}))
        for _ in range(4):
            print(await ws.recv())


if __name__ == "__main__":
    asyncio.run(main())
