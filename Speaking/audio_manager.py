import asyncio
import signal
import uuid
import queue
from typing import Optional, Dict, Any

import config
from realtime_dialog_client import RealtimeDialogClient

class DialogSession:
    """Dialog session management class - Server relay version (compatible with Python 3.6 & supports continuous text display)"""
    def __init__(self, ws_config: Dict[str, Any], output_audio_format: str = "pcm", mod: str = "audio", recv_timeout: int = 10):
        self.recv_timeout = recv_timeout
        self.mod = mod
        self.session_id = str(uuid.uuid4())
        
        # Initialize Volcano client
        self.client = RealtimeDialogClient(
            config=ws_config, 
            session_id=self.session_id,
            output_audio_format=output_audio_format, 
            mod=mod, 
            recv_timeout=recv_timeout
        )

        self.is_running = True
        self.is_session_finished = False
        
        # Async queue: for FastAPI server to read and send to browser
        self.outbound_audio_queue = asyncio.Queue()
        self.outbound_text_queue = asyncio.Queue()

        # Internal audio queue: for recording current audio chunks being sent, convenient for interruption
        self.audio_chunk_counter = 0

        # Register system signals
        try:
            signal.signal(signal.SIGINT, self._keyboard_signal)
        except ValueError:
            pass

    def handle_server_response(self, response: Dict[str, Any]) -> None:
        """Parse and dispatch all messages returned by Volcano server"""
        if not response:
            return

        payload_msg = response.get('payload_msg', {})
        event = response.get('event')

        # 1. Handle audio stream (SERVER_ACK)
        if response['message_type'] == 'SERVER_ACK' and isinstance(payload_msg, bytes):
            # Put binary audio chunks into queue for WebSocket thread to send
            asyncio.ensure_future(self.outbound_audio_queue.put(payload_msg))

        # 2. Handle text and events (SERVER_FULL_RESPONSE)
        elif response['message_type'] == 'SERVER_FULL_RESPONSE':
            # A. Handle AI generated text fragments
            if 'content' in payload_msg:
                # Put fragments directly into text queue, frontend index.php is responsible for merging
                asyncio.ensure_future(self.outbound_text_queue.put(payload_msg['content']))

            # B. Handle user's own speech recognition results (ASR)
            # This way the frontend can display what the user just said
            if 'results' in payload_msg and payload_msg['results']:
                asr_result = payload_msg['results'][0]
                if not asr_result.get('is_interim'): # Only send final confirmed sentences
                    user_text = asr_result.get('text', '')
                    if user_text:
                        asyncio.ensure_future(self.outbound_text_queue.put(f"USER_MSG:{user_text}"))

            # C. Handle interruption event (Event 450: user started speaking)
            if event == 450:
                # Notify frontend to reset AI bubble and stop playback
                asyncio.ensure_future(self.outbound_text_queue.put("__INTERRUPT__"))
                # Clear unsent audio queue
                while not self.outbound_audio_queue.empty():
                    try:
                        self.outbound_audio_queue.get_nowait()
                    except:
                        break

        elif response['message_type'] == 'SERVER_ERROR':
            print(f"Volcano API error: {payload_msg}")

    def _keyboard_signal(self, sig, frame):
        self.stop()

    def stop(self):
        self.is_running = False

    async def receive_loop(self):
        """Continuously monitor message loop from Volcano server"""
        try:
            while self.is_running:
                response = await self.client.receive_server_response()
                self.handle_server_response(response)
                
                # Check if session has ended
                if 'event' in response and response['event'] in [152, 153]:
                    break
        except Exception as e:
            print(f"Receive loop exception: {e}")
        finally:
            self.is_session_finished = True

    async def start(self) -> None:
        """Establish connection and start tasks"""
        try:
            await self.client.connect()
            # Adapt Python 3.6: use ensure_future instead of create_task
            asyncio.ensure_future(self.receive_loop())
            
            # Send initial text instruction to force Emma teacher to greet in English
            await self.client.chat_text_query("Hello Emma, I am ready. Please greet me in English and prepare to tutor me.")
        except Exception as e:
            print(f"Session startup failed: {e}")

    async def send_audio_frame(self, frame: bytes):
        """Receive PCM data from browser and forward to Volcano"""
        if self.is_running:
            await self.client.task_request(frame)

    async def close(self):
        """Resource cleanup"""
        self.stop()
        await self.client.finish_session()
        await self.client.close()