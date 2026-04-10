// ai-agent.js - 刷新即失忆版
(function () {
    // --- 1. 样式与 HTML 注入 (保持蓝色沉浸式设计) ---
    const style = document.createElement('style');
    style.innerHTML = `
        #ai-agent-wrapper { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; display: none; z-index: 10000; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); align-items: center; justify-content: center; font-family: 'Segoe UI', system-ui, sans-serif; }
        #ai-main-container { display: flex; width: 90%; max-width: 1150px; height: 85vh; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.4); }
        #ai-avatar-side { flex: 0 0 45%; background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%); display: flex; flex-direction: column; align-items: center; justify-content: flex-end; position: relative; }
        #ai-visual-tutor { font-size: 320px; line-height: 1; margin-bottom: -15px; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.3)); transition: transform 0.4s ease; user-select: none; }
        .tutor-speaking { transform: scale(1.03) translateY(-5px); }
        #ai-chat-side { flex: 1; display: flex; flex-direction: column; background: #F8FAFC; }
        #ai-body { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }
        .msg-row { display: flex; width: 100%; align-items: flex-start; gap: 12px; }
        .user-row { justify-content: flex-end; }
        .msg { padding: 14px 20px; border-radius: 18px; font-size: 15px; line-height: 1.6; max-width: 80%; }
        .bot { background: white; color: #1E293B; border: 1px solid #E2E8F0; border-top-left-radius: 4px; }
        .user { background: #2563EB; color: white; border-top-right-radius: 4px; }
        .typing::after { content: '▋'; animation: blink 0.8s infinite; color: #3B82F6; }
        @keyframes blink { 50% { opacity: 0; } }
        #ai-footer { padding: 25px 30px; background: #fff; border-top: 1px solid #E2E8F0; display: flex; gap: 15px; }
        #ai-input { flex: 1; border: 2px solid #F1F5F9; padding: 12px 20px; border-radius: 12px; outline: none; }
        #ai-send { background: #2563EB; color: #fff; border: none; padding: 0 25px; border-radius: 12px; font-weight: bold; cursor: pointer; }
        #ai-trigger-bubble { position: fixed; bottom: 30px; right: 30px; width: 70px; height: 70px; background: #2563EB; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 32px; cursor: pointer; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4); z-index: 10001; }
    `;
    document.head.appendChild(style);

    const wrapper = document.createElement('div');
    wrapper.id = 'ai-agent-wrapper';
    wrapper.innerHTML = `
        <div id="ai-main-container">
            <div id="ai-avatar-side">
                <div style="position:absolute; top:40px; left:40px; color:white;"><h2>Teacher Luna</h2><p>Oxford English Tutor</p></div>
                <div id="ai-visual-tutor">👩‍🏫</div>
            </div>
            <div id="ai-chat-side">
                <div style="padding:20px 30px; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; background:#fff;">
                    <span style="font-weight:bold; color:#64748B;">LUNA SESSION</span>
                    <button id="ai-close" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94A3B8;">&times;</button>
                </div>
                <div id="ai-body"></div>
                <div id="ai-footer">
                    <input type="text" id="ai-input" placeholder="Ask Luna about English...">
                    <button id="ai-send">SEND</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(wrapper);

    const bubble = document.createElement('div');
    bubble.id = 'ai-trigger-bubble';
    bubble.innerHTML = '🤖';
    document.body.appendChild(bubble);

    const bodyEl = document.getElementById('ai-body');
    const inputEl = document.getElementById('ai-input');
    const sendBtn = document.getElementById('ai-send');
    const visualTutor = document.getElementById('ai-visual-tutor');

    // --- 核心变化：不再从 localStorage 读取，每次刷新都是空的数组 ---
    let isThinking = false;
    let history = [];

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
        isThinking = true; inputEl.disabled = true; sendBtn.disabled = true;
        appendMsg('user', text);
        inputEl.value = '';

        const currentWords = Array.from(document.querySelectorAll('#word-table strong')).map(el => el.innerText).join(', ');

        try {
            const res = await fetch('ai_proxy.php', {
                method: 'POST',
                body: JSON.stringify({ message: text, history: history, context: { words: currentWords } })
            });
            const data = await res.json();

            // 工具调用：静默写入数据库
            if (data.action && data.action.function_name === 'add_by_name') {
                const formData = new URLSearchParams();
                formData.append('action', 'add_to_notebook');
                formData.append('word_text', data.action.word);
                fetch('vocabulary.php', { method: 'POST', body: formData });
            }

            appendMsg('bot', data.reply, true);
            // 存入当前会话历史
            history.push({ role: 'user', content: text }, { role: 'assistant', content: data.reply });
        } catch (e) {
            appendMsg('bot', "Connection error.");
            isThinking = false; inputEl.disabled = false; sendBtn.disabled = false;
        }
    }

    bubble.onclick = () => wrapper.style.display = 'flex';
    document.getElementById('ai-close').onclick = () => wrapper.style.display = 'none';
    sendBtn.onclick = sendMessage;
    inputEl.onkeydown = (e) => { if (e.key === 'Enter') sendMessage(); };

    // 初始问候
    appendMsg('bot', "Hello! I'm Luna, your private tutor. How can I help you explore the beauty of English today?");
})();