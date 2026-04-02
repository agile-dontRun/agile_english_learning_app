// ai-agent.js
(function() {
    // --- 1. 注入样式 ---
    const style = document.createElement('style');
    style.innerHTML = `
        #ai-agent-box { position: fixed; bottom: 20px; right: 20px; z-index: 10000; font-family: 'Segoe UI', sans-serif; }
        #ai-bubble { width: 60px; height: 60px; background: #4CAF50; border-radius: 50%; cursor: move; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); user-select: none; transition: transform 0.2s; }
        #ai-bubble:active { transform: scale(0.9); }
        #ai-window { position: absolute; bottom: 75px; right: 0; width: 340px; height: 480px; background: white; border-radius: 12px; display: none; flex-direction: column; box-shadow: 0 8px 24px rgba(0,0,0,0.2); overflow: hidden; border: 1px solid #eee; }
        #ai-header { background: #4CAF50; color: white; padding: 12px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        #ai-body { flex: 1; padding: 15px; overflow-y: auto; background: #f9f9f9; display: flex; flex-direction: column; gap: 10px; }
        .msg { padding: 8px 12px; border-radius: 8px; font-size: 14px; max-width: 85%; line-height: 1.5; word-wrap: break-word; position: relative; }
        .user { background: #DCF8C6; align-self: flex-end; }
        .bot { background: white; align-self: flex-start; border: 1px solid #eee; min-height: 20px; }
        .typing::after { content: '|'; animation: blink 0.7s infinite; }
        @keyframes blink { 50% { opacity: 0; } }
        #ai-footer { padding: 10px; border-top: 1px solid #eee; display: flex; gap: 8px; background: white; }
        #ai-input { flex: 1; border: 1px solid #ddd; padding: 8px; border-radius: 4px; outline: none; }
        #ai-input:disabled { background: #f5f5f5; cursor: not-allowed; }
        #ai-send { background: #4CAF50; color: white; border: none; padding: 0 15px; border-radius: 4px; cursor: pointer; }
        #ai-send:disabled { background: #ccc; }
    `;
    document.head.appendChild(style);

    // --- 2. 注入 HTML ---
    const container = document.createElement('div');
    container.id = 'ai-agent-box';
    container.innerHTML = `
        <div id="ai-window">
            <div id="ai-header"><span>DeepSeek Agent</span><span id="ai-close" style="cursor:pointer;font-size:20px;">×</span></div>
            <div id="ai-body"></div>
            <div id="ai-footer">
                <input type="text" id="ai-input" placeholder="问问我这个单词...">
                <button id="ai-send">发送</button>
            </div>
        </div>
        <div id="ai-bubble">🤖</div>
    `;
    document.body.appendChild(container);

    const bubble = document.getElementById('ai-bubble');
    const windowEl = document.getElementById('ai-window');
    const bodyEl = document.getElementById('ai-body');
    const inputEl = document.getElementById('ai-input');
    const sendBtn = document.getElementById('ai-send');

    let isThinking = false; // 状态锁：回答时禁止输入
    let chatHistory = JSON.parse(localStorage.getItem('ai_agent_history')) || [];

    // --- 3. 打字机效果函数 ---
    function typeWriter(text, element, callback) {
        let i = 0;
        element.classList.add('typing');
        function type() {
            if (i < text.length) {
                element.innerText += text.charAt(i);
                i++;
                bodyEl.scrollTop = bodyEl.scrollHeight;
                setTimeout(type, 30); // 打字速度，越小越快
            } else {
                element.classList.remove('typing');
                if (callback) callback();
            }
        }
        type();
    }

    // --- 4. 渲染消息逻辑 ---
    function appendMsg(role, text, isNew = false) {
        const div = document.createElement('div');
        div.className = `msg ${role}`;
        bodyEl.appendChild(div);
        
        if (role === 'bot' && isNew) {
            typeWriter(text, div, () => {
                isThinking = false; // 打字结束，解除锁定
                inputEl.disabled = false;
                sendBtn.disabled = false;
                inputEl.focus();
            });
        } else {
            div.innerText = text;
        }
        bodyEl.scrollTop = bodyEl.scrollHeight;
        return div;
    }

    // 初始化加载历史
    const init = () => {
        if (chatHistory.length === 0) {
            appendMsg('bot', '你好！我是你的智能学习助手。');
        } else {
            chatHistory.forEach(m => appendMsg(m.role === 'user' ? 'user' : 'bot', m.content));
        }
    };
    init();

    // --- 5. 核心：发送与 Tool Calling 执行 ---
    async function sendMessage() {
        if (isThinking) return;
        const text = inputEl.value.trim();
        if (!text) return;

        // 锁定输入
        isThinking = true;
        inputEl.disabled = true;
        sendBtn.disabled = true;

        appendMsg('user', text);
        inputEl.value = '';

        // 收集页面上下文（单词ID信息）
        const wordsOnPage = Array.from(document.querySelectorAll('#word-table tr'))
            .map(row => {
                const id = row.querySelector('.btn-add')?.getAttribute('onclick')?.match(/\d+/)?.[0];
                const word = row.querySelector('strong')?.innerText;
                return id ? `${word}(ID:${id})` : null;
            }).filter(x => x).join(', ');

        try {
            const res = await fetch('ai_proxy.php', {
                method: 'POST',
                body: JSON.stringify({ 
                    message: text, 
                    history: chatHistory, 
                    context: { words_info: wordsOnPage },
                    url: window.location.href 
                })
            });
            const data = await res.json();

            // 1. 处理 Tool Calling (自动执行加词)
            if (data.action && data.action.function_name === 'add_to_notebook') {
                const wordId = data.action.word_id;
                // 在当前页面寻找对应的按钮并点击
                const targetBtn = document.querySelector(`button[onclick*="${wordId}"]`);
                if (targetBtn && !targetBtn.disabled) {
                    targetBtn.click(); // 触发页面原有的 handleAdd 逻辑
                }
            }

            // 2. 触发流式打字回复
            appendMsg('bot', data.reply, true);

            // 3. 存入历史记录
            chatHistory.push({role: 'user', content: text}, {role: 'assistant', content: data.reply});
            if (chatHistory.length > 20) chatHistory.shift(); 
            localStorage.setItem('ai_agent_history', JSON.stringify(chatHistory));

        } catch (e) {
            appendMsg('bot', '抱歉，我现在连接不到大脑，请检查网络。');
            isThinking = false;
            inputEl.disabled = false;
            sendBtn.disabled = false;
        }
    }

    sendBtn.onclick = sendMessage;
    inputEl.onkeydown = (e) => { if(e.key === 'Enter') sendMessage(); };

    // --- 6. 拖动与显示逻辑 ---
    let isDragging = false;
    bubble.onmousedown = function(e) {
        isDragging = false;
        let startX = e.clientX - container.offsetLeft;
        let startY = e.clientY - container.offsetTop;
        function move(me) {
            isDragging = true;
            container.style.left = (me.clientX - startX) + 'px';
            container.style.top = (me.clientY - startY) + 'px';
            container.style.right = 'auto'; container.style.bottom = 'auto';
        }
        document.onmousemove = move;
        document.onmouseup = function() {
            document.onmousemove = null;
            document.onmouseup = null;
        };
    };
    bubble.onclick = () => { if(!isDragging) windowEl.style.display = windowEl.style.display === 'flex' ? 'none' : 'flex'; };
    document.getElementById('ai-close').onclick = () => windowEl.style.display = 'none';

})();