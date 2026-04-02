import asyncio
import websockets
import uuid
import json
import gzip
import protocol
import config

async def handle_client(frontend_ws, path):
    
        # 5. Enable bidirectional data forwarding (concurrent)
        async def volc_to_frontend():
            """Receive response from Volcano Engine, decompress and send to frontend"""
            try:
                while True:
                    res = await volc_ws.recv()
                    parsed = protocol.parse_response(res)
                    if not parsed: continue
                    
                    if parsed.get('message_type') == 'SERVER_ERROR':
                        err_msg = json.dumps({"error": f"Volcano Engine error Code: {parsed.get('code')}"})
                        await frontend_ws.send(err_msg)
                        continue

                    if 'payload_msg' in parsed:
                        msg = parsed['payload_msg']
                        if isinstance(msg, bytes):
                            # Send pure PCM audio to frontend for playback
                            await frontend_ws.send(msg)
                        else:
                            # Send recognized text and AI response text to frontend for display
                            await frontend_ws.send(json.dumps(msg))
            except websockets.exceptions.ConnectionClosed:
                pass
            except Exception as e:
                print(f"[{session_id}] Error receiving Volcano Engine data: {e}")

      



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