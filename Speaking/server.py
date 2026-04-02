import asyncio
import websockets
import uuid
import json
import gzip
import protocol
import config



async def main():
    print("🚀 Python AI voice backend started | Listening on port: 8081")
    # Allow access from all IPs
    async with websockets.serve(handle_client, "0.0.0.0", 8082):
        await asyncio.Future()  # Run indefinitely

if __name__ == "__main__":
    # Compatible startup method for Python 3.6 and below
    loop = asyncio.get_event_loop()
    try:
        loop.run_until_complete(main())
    except KeyboardInterrupt:
        pass
    finally:
        loop.close()