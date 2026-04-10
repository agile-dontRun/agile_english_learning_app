// ai-agent.js - Oxford Academic Edition
(function () {
    // --- 1. 样式与 HTML 注入 (牛津蓝与学术金设计) ---
    const style = document.createElement('style');
    style.innerHTML = `
        :root {
            --oxford-blue: #002147;
            --oxford-blue-light: #003066;
            --oxford-gold: #c4a661;
            --oxford-gold-light: #d4b671;
            --academic-bg: #f4f7f6;
            --white: #ffffff;
        }

        #ai-agent-wrapper { 
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
            display: none; z-index: 10000; 
            background: rgba(0, 33, 71, 0.5); /* 牛津蓝透明层 */
            backdrop-filter: blur(8px); align-items: center; justify-content: center; 
            font-family: 'Playfair Display', 'Lora', 'Segoe UI', serif; 
        }

        #ai-main-container { 
            display: flex; width: 90%; max-width: 1150px; height: 85vh; 
            background: var(--white); border-radius: 12px; overflow: hidden; 
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            border-top: 6px solid var(--oxford-gold); /* 学术金顶边 */
        }

        #ai-avatar-side { 
            flex: 0 0 42%; 
            background: linear-gradient(135deg, var(--oxford-blue) 0%, var(--oxford-blue-light) 100%); 
            display: flex; flex-direction: column; align-items: center; 
            justify-content: flex-end; position: relative; 
            border-right: 1px solid rgba(255,255,255,0.1);
        }

        #ai-visual-tutor { 
            font-size: 300px; line-height: 1; margin-bottom: -10px; 
            filter: drop-shadow(0 15px 30px rgba(0,0,0,0.4)); 
            transition: transform 0.4s ease; user-select: none; 
        }

        #ai-look-stage {
            position: relative;
            width: min(300px, 80%);
            aspect-ratio: 11 / 13;
            margin-bottom: 18px;
            overflow: hidden;
            filter: drop-shadow(0 15px 30px rgba(0,0,0,0.4));
            display: none;
        }

        .ai-look-layer {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: bottom center;
            pointer-events: none;
        }

        .tutor-speaking { transform: scale(1.02) translateY(-3px); }

        #ai-chat-side { flex: 1; display: flex; flex-direction: column; background: var(--academic-bg); }

        #ai-body { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }

        .msg-row { display: flex; width: 100%; align-items: flex-start; gap: 12px; }
        .user-row { justify-content: flex-end; }

        .msg { 
            padding: 14px 20px; border-radius: 4px; font-size: 15px; line-height: 1.6; 
            max-width: 80%; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .bot { 
            background: var(--white); color: var(--oxford-blue); 
            border: 1px solid #e0e0e0; border-left: 4px solid var(--oxford-gold); 
        }

        .user { 
            background: var(--oxford-blue); color: var(--white); 
            border-right: 4px solid var(--oxford-gold); 
        }

        .typing::after { content: '▋'; animation: blink 0.8s infinite; color: var(--oxford-gold); }
        @keyframes blink { 50% { opacity: 0; } }

        #ai-footer { 
            padding: 25px 30px; background: var(--white); 
            border-top: 1px solid #e0e0e0; display: flex; gap: 15px; 
        }

        #ai-input { 
            flex: 1; border: 1px solid #dcdcdc; padding: 12px 18px; 
            border-radius: 4px; outline: none; font-size: 14px;
        }
        #ai-input:focus { border-color: var(--oxford-gold); }

        #ai-send { 
            background: var(--oxford-blue); color: var(--white); 
            border: none; padding: 0 25px; border-radius: 4px; 
            font-weight: bold; cursor: pointer; transition: 0.3s;
            font-family: 'Playfair Display', serif;
            letter-spacing: 1px;
        }
        #ai-send:hover { background: var(--oxford-gold); color: var(--oxford-blue); }

        #ai-trigger-bubble { 
            position: fixed; bottom: 30px; right: 30px; 
            width: 70px; height: 70px; background: var(--oxford-blue); 
            border: 2px solid var(--oxford-gold);
            border-radius: 50%; display: flex; align-items: center; 
            justify-content: center; color: var(--oxford-gold); 
            font-size: 32px; cursor: pointer; 
            box-shadow: 0 10px 25px rgba(0, 33, 71, 0.3); z-index: 10001; 
        }
    `;
    document.head.appendChild(style);

    const wrapper = document.createElement('div');
    wrapper.id = 'ai-agent-wrapper';
    wrapper.innerHTML = `
        <div id="ai-main-container">
            <div id="ai-avatar-side">
                <div style="position:absolute; top:40px; left:40px; color:white;">
                    <h2 style="margin:0; font-family:'Playfair Display', serif;">Teacher Luna</h2>
                    <p style="margin:5px 0 0 0; opacity:0.8; font-size:14px; letter-spacing:1px;">Oxford Academic Mentor</p>
                </div>
                <div id="ai-look-stage" aria-hidden="true"></div>
                <div id="ai-visual-tutor">👩‍🏫</div>
            </div>
            <div id="ai-chat-side">
                <div style="padding:20px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; background:#fff;">
                    <span style="font-weight:bold; color:var(--oxford-blue); letter-spacing:1px;">ACADEMIC SESSION</span>
                    <button id="ai-close" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--oxford-blue);">&times;</button>
                </div>
                <div id="ai-body"></div>
                <div id="ai-footer">
                    <input type="text" id="ai-input" placeholder="Inquire about English academic rigor...">
                    <button id="ai-send">CONSULT</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(wrapper);

    const bubble = document.createElement('div');
    bubble.id = 'ai-trigger-bubble';
    bubble.innerHTML = '🎓'; // 换成学位帽图标更符合牛津风格
    document.body.appendChild(bubble);

    const bodyEl = document.getElementById('ai-body');
    const inputEl = document.getElementById('ai-input');
    const sendBtn = document.getElementById('ai-send');
    const visualTutor = document.getElementById('ai-visual-tutor');
    const lookStage = document.getElementById('ai-look-stage');
    const ACTIVE_OUTFIT_ENDPOINT = '/galgame/dress_up_game/api/get_active_outfit.php';
    const IMAGE_BASE_PATH = '/galgame/dress_up_game';
    const dressUpLayerOrder = [
        'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
        'eye', 'eyebrows', 'nose', 'mouse', 'hair', 'character', 'glass', 'head'
    ];

    let isThinking = false;
    let history = [];

    function buildImageCandidates(layer) {
        const filePath = layer && layer.file_path ? layer.file_path : '';
        const normalizedFilePath = filePath.startsWith('/') ? filePath : `/${filePath}`;
        return [
            `${IMAGE_BASE_PATH}${normalizedFilePath}`,
            layer && layer.url ? layer.url : ''
        ].filter(Boolean);
    }

    function setImageWithFallback(img, candidates, index = 0) {
        if (!img || index >= candidates.length) {
            if (img) {
                img.remove();
            }
            return;
        }

        const candidate = candidates[index];
        img.onerror = () => setImageWithFallback(img, candidates, index + 1);
        img.src = `${candidate}${candidate.includes('?') ? '&' : '?'}t=${Date.now()}`;
    }

    function clearLookStage() {
        if (!lookStage) return;
        Array.from(lookStage.querySelectorAll('.ai-look-layer')).forEach((node) => node.remove());
    }

    function renderActiveLook(data) {
        if (!lookStage || !visualTutor) return;

        clearLookStage();

        if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
            lookStage.style.display = 'none';
            visualTutor.style.display = '';
            return;
        }

        const layerMap = new Map();
        data.layers.forEach((layer) => {
            if (layer && layer.layer) {
                layerMap.set(layer.layer, layer);
            }
        });

        dressUpLayerOrder.forEach((layerName) => {
            if (layerName === 'background') {
                return;
            }

            const layer = layerMap.get(layerName);
            if (!layer) {
                return;
            }

            const img = document.createElement('img');
            img.className = 'ai-look-layer';
            img.alt = layer.name || layerName;
            lookStage.appendChild(img);
            setImageWithFallback(img, buildImageCandidates(layer));
        });

        lookStage.style.display = 'block';
        visualTutor.style.display = 'none';
    }

    async function loadActiveLook() {
        try {
            const response = await fetch(ACTIVE_OUTFIT_ENDPOINT, { cache: 'no-store' });
            const rawText = await response.text();

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = JSON.parse(rawText);
            renderActiveLook(data);
        } catch (error) {
            renderActiveLook(null);
        }
    }

    function appendMsg(role, text, isNew = false) {
        const row = document.createElement('div');
        row.className = `msg-row ${role === 'user' ? 'user-row' : 'bot-row'}`;
        row.innerHTML = `<div class="msg ${role}"></div>`;
        bodyEl.appendChild(row);
        const msgDiv = row.querySelector('.msg');
        if (role === 'bot' && isNew) {
            typeWriter(text, msgDiv);
            visualTutor.classList.add('tutor-speaking');
        } else {
            msgDiv.innerText = text;
        }
        bodyEl.scrollTop = bodyEl.scrollHeight;
    }

    function typeWriter(text, element) {
        let i = 0; element.classList.add('typing');
        function type() {
            if (i < text.length) {
                element.innerText += text.charAt(i); i++;
                bodyEl.scrollTop = bodyEl.scrollHeight;
                setTimeout(type, 20);
            } else {
                element.classList.remove('typing');
                visualTutor.classList.remove('tutor-speaking');
                isThinking = false; inputEl.disabled = false; sendBtn.disabled = false; inputEl.focus();
            }
        }
        type();
    }

    async function sendMessage() {
        if (isThinking) return;
        const text = inputEl.value.trim();
        if (!text) return;

        isThinking = true;
        inputEl.disabled = true;
        sendBtn.disabled = true;
        appendMsg('user', text);
        inputEl.value = '';

        const currentWords = Array.from(document.querySelectorAll('#word-table strong')).map(el => el.innerText).join(', ');

        try {
            const res = await fetch('ai_proxy.php', {
                method: 'POST',
                body: JSON.stringify({ message: text, history: history, context: { words: currentWords } })
            });
            const data = await res.json();

            // --- 处理跳转逻辑 ---
            if (data.action && data.action.type === 'redirect') {
                appendMsg('bot', data.reply, true);
                setTimeout(() => {
                    window.location.href = data.action.url;
                }, 1500);
                return;
            }

            // 工具调用：保存单词
            if (data.action && data.action.function_name === 'add_by_name') {
                const formData = new URLSearchParams();
                formData.append('action', 'add_to_notebook');
                formData.append('word_text', data.action.word);
                fetch('vocabulary.php', { method: 'POST', body: formData });
            }

            appendMsg('bot', data.reply, true);
            history.push({ role: 'user', content: text }, { role: 'assistant', content: data.reply });

        } catch (e) {
            appendMsg('bot', "Connection error.");
            isThinking = false;
            inputEl.disabled = false;
            sendBtn.disabled = false;
        }
    }

    bubble.onclick = () => wrapper.style.display = 'flex';
    document.getElementById('ai-close').onclick = () => wrapper.style.display = 'none';
    sendBtn.onclick = sendMessage;
    inputEl.onkeydown = (e) => { if (e.key === 'Enter') sendMessage(); };

    // --- 拖拽逻辑 ---
    let isDragging = false;
    let startX, startY;
    let dragThreshold = 5;

    bubble.addEventListener('mousedown', (e) => {
        isDragging = false;
        startX = e.clientX;
        startY = e.clientY;

        const rect = bubble.getBoundingClientRect();
        const offsetX = e.clientX - rect.left;
        const offsetY = e.clientY - rect.top;

        const onMouseMove = (moveEvent) => {
            if (Math.abs(moveEvent.clientX - startX) > dragThreshold ||
                Math.abs(moveEvent.clientY - startY) > dragThreshold) {
                isDragging = true;
            }

            if (isDragging) {
                bubble.style.cursor = 'grabbing';
                let x = moveEvent.clientX - offsetX;
                let y = moveEvent.clientY - offsetY;
                x = Math.max(0, Math.min(window.innerWidth - rect.width, x));
                y = Math.max(0, Math.min(window.innerHeight - rect.height, y));
                bubble.style.left = x + 'px';
                bubble.style.top = y + 'px';
                bubble.style.bottom = 'auto';
                bubble.style.right = 'auto';
            }
        };

        const onMouseUp = () => {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
            bubble.style.cursor = 'pointer';
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });

    bubble.onclick = () => { if (!isDragging) wrapper.style.display = 'flex'; };

    // 初始问候 (牛津风)
    appendMsg('bot', "Greetings. I am Luna, your academic mentor. How may I assist your pursuit of linguistic excellence today?");
    loadActiveLook();
})();
