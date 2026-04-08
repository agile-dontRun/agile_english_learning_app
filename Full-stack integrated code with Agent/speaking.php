<?php
/**
 * Spires Academy - Emma Oral Practice
 * Real-time AI English conversation using Volcengine WebSocket Protocol.
 * Designed for low-latency feedback and immersive learning.
 */
session_start();

// Production Note: These credentials should ideally be fetched from a secure environment variable
$appId = '6847154685';
$accessKey = 'X3EXjni2ZZlW3I9y9Ef9xpxCHVQn3Cxn';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emma Speaking Practice - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --oxford-blue: #002147;
            --oxford-gold: #c4a661;
            --white: #ffffff;
            --ai-bubble: #f0f4f8;
            --user-bubble: #e3f2fd;
        }

        body { 
            font-family: 'Open Sans', sans-serif; 
            background-color: #f4f7f6; 
            margin: 0; padding: 20px;
            display: flex; flex-direction: column; align-items: center;
        }

        h2 { font-family: 'Playfair Display', serif; color: var(--oxford-blue); margin-bottom: 25px; }

        /* Modern Chat Container */
        #chat-container { 
            width: 100%; max-width: 700px; 
            height: 500px; background: white; 
            border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            display: flex; flex-direction: column; overflow: hidden;
            border-top: 5px solid var(--oxford-gold);
        }

        #chat-window { 
            flex: 1; padding: 25px; overflow-y: auto; 
            display: flex; flex-direction: column; gap: 15px;
        }

        /* Conversation Bubbles */
        .msg { max-width: 80%; padding: 12px 18px; border-radius: 15px; font-size: 15px; line-height: 1.5; position: relative; }
        .user { align-self: flex-end; background: var(--oxford-blue); color: white; border-bottom-right-radius: 2px; }
        .ai { align-self: flex-start; background: var(--ai-bubble); color: var(--oxford-blue); border-bottom-left-radius: 2px; border: 1px solid #dee2e6; }

        /* Control Panel */
        .controls { padding: 20px; background: #fff; border-top: 1px solid #eee; text-align: center; }
        
        button { 
            padding: 12px 30px; font-size: 14px; font-weight: bold; border: none; 
            border-radius: 30px; cursor: pointer; transition: 0.3s; text-transform: uppercase;
        }

        #startBtn { background: var(--oxford-blue); color: white; }
        #startBtn:hover { background: var(--oxford-blue-light); transform: scale(1.05); }

        #endBtn { background: #dc3545; color: white; display: none; }

        #status { margin-top: 15px; font-size: 13px; color: #666; font-style: italic; }

        /* Recording Animation */
        .recording-pulse {
            width: 10px; height: 10px; background: #dc3545; border-radius: 50%;
            display: inline-block; margin-right: 8px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(2.5); opacity: 0; }
        }
    </style>
</head>
<body>

    <h2>Practice English with Emma</h2>

    <div id="chat-container">
        <div id="chat-window">
            <div class="msg ai">Hello! I'm Emma, your English mentor. Click the button below to start our conversation. How can I help you today?</div>
        </div>

        <div class="controls">
            <button id="startBtn">🎤 Start Conversation</button>
            <button id="endBtn">🛑 Stop & Get Feedback</button>
            <div id="status">System Ready</div>
        </div>
    </div>

    <script src="ai-agent.js"></script>

    <script>
        /**
         * Voice Socket Configuration
         * Note: Proxy handles secure handshake with Volcengine.
         */
        const APP_ID = '<?php echo $appId; ?>';
        const ACCESS_KEY = '<?php echo $accessKey; ?>';
        const PROXY_URL = 'ws://8.162.9.154:8081/?appid=' + APP_ID + '&accesskey=' + ACCESS_KEY;

        let ws = null;
        let audioContext, playbackContext, mediaStream, processor;
        let conversationLog = [];

        function connectWS() {
            ws = new WebSocket(PROXY_URL);
            ws.binaryType = 'arraybuffer';

            ws.onopen = () => {
                console.log('✅ Bridge Connected');
                sendStartSession();
            };

            ws.onmessage = (event) => {
                if (event.data instanceof ArrayBuffer) {
                    // Audio payload received
                    playAIResponse(event.data);
                } else {
                    // Metadata or Text events received
                    const msg = JSON.parse(event.data);
                    handleServerEvent(msg);
                }
            };

            ws.onclose = () => {
                document.getElementById('status').innerHTML = '❌ Connection Terminated';
            };
        }

        function sendStartSession() {
            const config = {
                asr: { audio_info: { format: "pcm", sample_rate: 16000, channel: 1 } },
                tts: { audio_config: { channel: 1, format: "pcm", sample_rate: 24000 }, speaker: "zh_female_shuangmawei_conversation_sig" },
                dialog: {
                    bot_name: "Emma",
                    system_role: "You are Emma, a helpful English teacher at Spires Academy. Keep responses concise and encourage the student to speak more.",
                    extra: { input_mod: "keep_alive", model: "doubao-pro-4k" }
                }
            };
            ws.send(JSON.stringify(config));
        }

        async function startRecording() {
            try {
                mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                // Separate context for recording to avoid sample rate mismatch
                audioContext = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 16000 });
                const source = audioContext.createMediaStreamSource(mediaStream);
                
                // 2048 buffer size for balanced latency
                processor = audioContext.createScriptProcessor(2048, 1, 1);
                source.connect(processor);
                processor.connect(audioContext.destination);

                processor.onaudioprocess = (e) => {
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        const inputData = e.inputBuffer.getChannelData(0);
                        const pcmBuffer = new Int16Array(inputData.length);
                        // Normalize and convert Float32 to Int16 PCM
                        for (let i = 0; i < inputData.length; i++) {
                            pcmBuffer[i] = Math.max(-32768, Math.min(32767, inputData[i] * 32768));
                        }
                        ws.send(pcmBuffer.buffer);
                    }
                };
            } catch (err) {
                alert('Microphone access denied or not found.');
            }
        }

        function handleServerEvent(msg) {
            // ASR Result (User's speech to text)
            if (msg.results && msg.results[0] && !msg.results[0].is_interim) {
                const text = msg.results[0].text;
                appendChat(text, 'user');
            }
            // Dialog Result (Emma's text response)
            if (msg.content) {
                appendChat(msg.content, 'ai');
            }
        }

        function playAIResponse(binaryData) {
            if (!playbackContext) {
                playbackContext = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 24000 });
            }

            // Volcengine Protocol: 28-byte header must be stripped
            if (binaryData.byteLength <= 28) return;
            const payload = binaryData.slice(28);

            // Byte alignment check to prevent Int16Array crash
            const validLength = payload.byteLength - (payload.byteLength % 2);
            if (validLength <= 0) return;

            const int16 = new Int16Array(payload, 0, validLength / 2);
            const float32 = new Float32Array(int16.length);

            // Convert PCM back to Float32 for AudioBuffer
            for (let i = 0; i < int16.length; i++) {
                float32[i] = int16[i] / 32768.0;
            }

            const buffer = playbackContext.createBuffer(1, float32.length, 24000);
            buffer.copyToChannel(float32, 0);

            const source = playbackContext.createBufferSource();
            source.buffer = buffer;
            source.connect(playbackContext.destination);
            source.start(0);
        }

        document.getElementById('startBtn').onclick = () => {
            connectWS();
            document.getElementById('status').innerHTML = 'Connecting to Emma...';
            
            const timer = setInterval(() => {
                if (ws && ws.readyState === WebSocket.OPEN) {
                    clearInterval(timer);
                    setTimeout(() => {
                        startRecording();
                        document.getElementById('startBtn').style.display = 'none';
                        document.getElementById('endBtn').style.display = 'inline-block';
                        document.getElementById('status').innerHTML = '<span class="recording-pulse"></span>Emma is listening...';
                    }, 500);
                }
            }, 100);
        };

        document.getElementById('endBtn').onclick = () => {
            if (ws) ws.close();
            if (processor) processor.disconnect();
            if (mediaStream) mediaStream.getTracks().forEach(t => t.stop());
            document.getElementById('status').innerHTML = 'Conversation Finished';
            document.getElementById('endBtn').style.display = 'none';
            document.getElementById('startBtn').style.display = 'inline-block';
        };

        function appendChat(text, role) {
            const chatWindow = document.getElementById('chat-window');
            const div = document.createElement('div');
            div.className = 'msg ' + role;
            div.innerText = text;
            chatWindow.appendChild(div);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }
    </script>
</body>
</html>
