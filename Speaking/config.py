import uuid

# Configuration information
ws_connect_config = {
    "base_url": "wss://openspeech.bytedance.com/api/v3/realtime/dialogue",
    "headers": {
        "X-Api-App-ID": "6847154685",
        "X-Api-Access-Key": "X3EXjni2ZZlW3I9y9Ef9xpxCHVQn3Cxn",
        "X-Api-Resource-Id": "volc.speech.dialog",  # Fixed value
        "X-Api-App-Key": "PlgvMymc7f3tQnJ6",  # Fixed value
        "X-Api-Connect-Id": str(uuid.uuid4()),
    }
}

# Modified section in config.py
start_session_req = {
    "asr": {
        "extra": { "end_smooth_window_ms": 1500 },
    },
    "tts": {
        # Recommend using pure English voice, e.g.: en_female_emotional_pro
        "speaker": "zh_male_yunzhou_jupiter_bigtts", 
        "audio_config": {
            "channel": 1,
            "format": "pcm_s16le",
            "sample_rate": 24000
        },
    },
    # Modified dialog configuration in config.py
    "dialog": {
        "bot_name": "Tutor Emma",
        # Core instructions: enforce language, role setting, scoring criteria
        "system_role": (
            "1. You are a professional native English teacher. "
            "2. LANGUAGE: Speak ONLY English. NEVER use Chinese or any other language, even if the user asks. "
            "3. SCORING: After each response, evaluate the user's last sentence based on grammar, vocabulary, and fluency. "
            "4. FORMAT: Start your response with a score like '[Score: 8/10]' and a very brief correction if needed, then continue the conversation in English. "
            "5. TONE: Encouraging, patient, and professional."
        ),
        "speaking_style": "Speak clearly, at a moderate pace, like a teacher helping a student.",
        "location": { "city": "London" },
        "extra": {
            "strict_audit": False,
            "recv_timeout": 10,
            "input_mod": "audio"
        }
    }
}

input_audio_config = {
    "chunk": 3200,
    "format": "pcm",
    "channels": 1,
    "sample_rate": 16000,
    "bit_size": 8
}

output_audio_config = {
    "chunk": 3200,
    "format": "pcm",
    "channels": 1,
    "sample_rate": 24000,
    "bit_size": 1
}
