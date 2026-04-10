import asyncio
import os
from fastapi import FastAPI, WebSocket, WebSocketDisconnect
from fastapi.middleware.cors import CORSMiddleware
from audio_manager import DialogSession
import config

# 1. Initialize FastAPI application
app = FastAPI()

# 2. Configure CORS middleware
# Since your webpage is accessed on port 80 while WebSocket is on 8082,
# cross-origin requests must be allowed, otherwise the browser may block the connection
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allow access from all origins
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.websocket("/ws/english_tutor")
async def websocket_endpoint(websocket: WebSocket):
    """
    WebSocket endpoint: handles real-time relay between frontend microphone audio and Volcengine large model
    """
    await websocket.accept()
    
    # Initialize dialog session
    # The config here will read the English teacher persona you set
    session = DialogSession(
        ws_config=config.ws_connect_config,
        output_audio_format="pcm",  # Return PCM audio with 24k sample rate
        mod="audio"
    )
    
    await session.start()

   
            
    async def server_to_browser():
        try:
            while session.is_running:
                try:
                    # Check audio queue
                    # Add non-blocking check to prevent get() from hanging
                    if not session.outbound_audio_queue.empty():
                        audio_chunk = await session.outbound_audio_queue.get()
                        await websocket.send_bytes(audio_chunk)
                    
                    # Check text queue
                    if not session.outbound_text_queue.empty():
                        text_msg = await session.outbound_text_queue.get()
                        await websocket.send_json({"type": "text", "content": text_msg})
                except Exception:
                    pass  # Ignore single transmission errors to prevent loop interruption
                
                await asyncio.sleep(0.01)
        except Exception as e:
            print(f"Broadcast error: {e}")

    # 3. Core compatibility fix: Python 3.6 must use ensure_future
    # Your server environment is Python 3.6, which does not support create_task

    asyncio.ensure_future(server_to_browser())

    # 4. Main loop: receive microphone binary audio stream from frontend
    try:
        while True:
            # Receive PCM audio frames from browser
            data = await websocket.receive_bytes()
            # Forward to Volcengine real-time speech interface
            await session.send_audio_frame(data)
    except WebSocketDisconnect:
        print("[Info] English conversation practice user has disconnected")
    except Exception as e:
        print(f"[Error] WebSocket exception: {e}")
    finally:
        # Ensure session resources are cleaned up
        await session.close()

if __name__ == "__main__":
    import uvicorn
    # Start the server, listen on port 8082
    # Please ensure the Alibaba Cloud security group and Baota firewall have allowed TCP port 8082
    uvicorn.run(app, host="0.0.0.0", port=8082)