import asyncio
import websockets
import uuid
import json
import gzip
import protocol
import config
import mysql.connector


def save_speaking_record(user_id, session_id, user_text, ai_text, eval_payload):
    db = mysql.connector.connect(host="localhost", user="", password="", database="english_learning_app")
    cursor = db.cursor()
    
    # Retrieve scores from evaluation payload, defaulting to 0 if not present
    overall = eval_payload.get('score', 0)
    pron = eval_payload.get('pronunciation', 0)
    fluency = eval_payload.get('fluency', 0)
    
    sql = """INSERT INTO user_speaking_attempts 
             (user_id, session_id, user_text, ai_response, overall_score, pronunciation_score, fluency_score, evaluation_json) 
             VALUES (%s, %s, %s, %s, %s, %s, %s, %s)"""
    
    values = (user_id, session_id, user_text, ai_text, overall, pron, fluency, json.dumps(eval_payload))
    cursor.execute(sql, values)
    db.commit()
    db.close()


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