<?php
// speaking.php
session_start();
// 建议生产环境中 AppID 和 Access Token 不要明文写死
$appId = '6847154685';
$accessKey = 'X3EXjni2ZZlW3I9y9Ef9xpxCHVQn3Cxn';
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>English Speaking Practice</title>
  <style> 
    body { font-family: sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
    #chat { height: 400px; border: 1px solid #ccc; padding: 10px; overflow-y: auto; margin-bottom: 20px; background: #f9f9f9; border-radius: 5px; }
    .msg { margin: 10px 0; padding: 8px; border-radius: 5px; }
    .user { background: #d1ecf1; text-align: right; }
    .ai { background: #d4edda; }
    button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
    #status { margin-top: 10px; color: #666; }
  </style>
</head>
<body>
  <h2>与 Emma 练口语</h2>
  <div id="chat"></div>
  <button id="startBtn">🎤 开始对话</button>
  <button id="endBtn" style="display:none">🛑 结束对话并获取反馈</button>
  <div id="status">准备就绪...</div>

  <script>
    const APP_ID = '<?php echo $appId; ?>';
    const ACCESS_KEY = '<?php echo $accessKey; ?>';
    const PROXY_URL = 'ws://8.162.9.154:8081/?appid=' + APP_ID + '&accesskey=' + ACCESS_KEY;

    let ws = null;
    let audioContext, playbackContext, mediaStream, processor;
    let conversationLog = [];

    // 1. 连接 WebSocket
    function connectWS() {
      ws = new WebSocket(PROXY_URL);
      ws.binaryType = 'arraybuffer';

      ws.onopen = () => {
        console.log('✅ 已连接代理');
        sendStartSession();
      };

      ws.onmessage = (event) => {
        if (event.data instanceof ArrayBuffer) {
          playAIResponse(event.data);
        } else {
          const msg = JSON.parse(event.data);
          handleServerEvent(msg);
        }
      };

      ws.onerror = (e) => console.error('WS 错误', e);
      ws.onclose = () => document.getElementById('status').innerHTML = '❌ 连接已断开';
    }

            // 2. 发送配置
            function sendStartSession() {
          const startSession = {
            asr: { 
              audio_info: { format: "pcm", sample_rate: 16000, channel: 1 } 
            },
            tts: { 
              audio_config: { 
                channel: 1, 
                format: "pcm", // 必须是 pcm
                sample_rate: 24000 
              }, 
              // 使用最基础的音色，防止权限问题
              speaker: "zh_female_shuangmawei_conversation_sig" 
            },
            dialog: {
              bot_name: "Emma",
              system_role: "You are Emma, a helpful English teacher.",
              extra: { 
                input_mod: "keep_alive",
                // 注意：这里的 model 如果填错会直接断开
                // 如果你不确定，可以尝试删除 model 字段让它使用默认值
                model: "doubao-pro-4k" 
              }
            }
          };
        
          console.log('🚀 发送初始化配置...');
          ws.send(JSON.stringify(startSession));
        }

    // 3. 麦克风录制 (16000Hz)
    async function startRecording() {
      try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        // 专用于录音的 Context
        audioContext = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 16000 });
        const source = audioContext.createMediaStreamSource(mediaStream);
        
        processor = audioContext.createScriptProcessor(2048, 1, 1);
        source.connect(processor);
        processor.connect(audioContext.destination);

        processor.onaudioprocess = (e) => {
          if (ws && ws.readyState === WebSocket.OPEN) {
            const inputData = e.inputBuffer.getChannelData(0);
            const pcmBuffer = new Int16Array(inputData.length);
            for (let i = 0; i < inputData.length; i++) {
              pcmBuffer[i] = Math.max(-32768, Math.min(32767, inputData[i] * 32768));
            }
            ws.send(pcmBuffer.buffer);
          }
        };
      } catch (err) {
        alert('无法获取麦克风权限！');
      }
    }
    
  

    // 4. 处理文字事件
    function handleServerEvent(msg) {
      if (msg.results && msg.results[0] && !msg.results[0].is_interim) {
        const text = msg.results[0].text;
        conversationLog.push({ role: 'user', text });
        appendChat('You: ' + text, 'user');
      }
      if (msg.content) {
        const text = msg.content;
        conversationLog.push({ role: 'ai', text });
        appendChat('Emma: ' + text, 'ai');
      }
    }
    
 

    // 5. 播放 AI 声音 (24000Hz，解决崩溃与爆音)
    function playAIResponse(binaryData) {
      // 使用独立的 playbackContext！
      if (!playbackContext) {
        playbackContext = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 24000 });
      }

      // 剥离火山引擎的 28 字节协议头
      if (binaryData.byteLength <= 28) return;
      const payload = binaryData.slice(28);

      // 安全校验：确保长度是偶数，防止 Int16Array 报错崩溃
      const validLength = payload.byteLength - (payload.byteLength % 2);
      if (validLength <= 0) return;

      const int16 = new Int16Array(payload, 0, validLength / 2);
      const float32 = new Float32Array(int16.length);

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

    // 6. UI 交互
    // 修改后的启动逻辑
   // 修改后的启动按钮逻辑
    document.getElementById('startBtn').onclick = () => {
      // 1. 先建立连接
      connectWS();
      
      // 2. 只有当收到代理反馈“连接成功”后，才启动录音
      // 我们给 ws 增加一个自定义监听
      const timer = setInterval(() => {
        if (ws && ws.readyState === WebSocket.OPEN) {
          clearInterval(timer);
          console.log("🔗 通道已建立，准备录音...");
          
          // 延迟 500ms 启动录音，确保 StartSession JSON 先被火山处理
          setTimeout(() => {
            startRecording();
            document.getElementById('startBtn').style.display = 'none';
            document.getElementById('endBtn').style.display = 'inline-block';
            document.getElementById('status').innerHTML = '🟢 正在通话...';
          }, 500);
        }
      }, 100);
    };

    document.getElementById('endBtn').onclick = () => {
      if (ws) ws.close();
      if (processor) processor.disconnect();
      if (mediaStream) mediaStream.getTracks().forEach(t => t.stop());
      document.getElementById('status').innerHTML = '✅ 对话已结束';
    };

    function appendChat(text, role) {
      const div = document.createElement('div');
      div.className = 'msg ' + role;
      div.innerText = text;
      const chatBox = document.getElementById('chat');
      chatBox.appendChild(div);
      chatBox.scrollTop = chatBox.scrollHeight;
    }
  </script>
</body>
</html>