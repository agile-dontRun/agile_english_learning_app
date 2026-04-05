import uuid


ws_connect_config = {
    "base_url": "wss://openspeech.bytedance.com/api/v3/realtime/dialogue",
    "headers": {
        "X-Api-App-ID": "6847154685",
        "X-Api-Access-Key": "X3EXjni2ZZlW3I9y9Ef9xpxCHVQn3Cxn",
        "X-Api-Resource-Id": "volc.speech.dialog",  
        "X-Api-App-Key": "PlgvMymc7f3tQnJ6",  
        "X-Api-Connect-Id": str(uuid.uuid4()),
    }
}

start_session_req = {
    "asr": {
        "extra": {
            "end_smooth_window_ms": 1500,
        },
    },
    "tts": {
        #"speaker": "zh_male_yunzhou_jupiter_bigtts",
        "speaker": "S2S_Model_storage_Dww-iuc5y3smiou6",  
        # "speaker": "ICL_zh_female_aojiaonvyou_tob" 
        "audio_config": {
            "channel": 1,
            "format": "pcm",
            "sample_rate": 24000
        },
    },
    "dialog": {
        "bot_name": "Emma",
        "system_role": "You are Emma, a professional and friendly English speaking coach. You must speak ONLY in English. Your primary goal is to help the user improve their oral English through natural conversation. Please correct the user's grammar or word choice errors politely when appropriate. Encourage the user to express their thoughts fully.",
        "speaking_style": "Clear, patient, and encouraging. Use a moderate speaking pace suitable for English learners. Your tone should be supportive and professional.",
        "location": {
          "city": "London",
        },
        "extra": {
            "strict_audit": False,
            "audit_response": "Safety audit triggered. Please rephrase.",
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
