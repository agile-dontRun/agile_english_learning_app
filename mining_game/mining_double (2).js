// ==========================================
// ⚠️ 填入你的服务器公网 IP
const SERVER_URL = 'http://8.162.9.154:3000'; 
// ==========================================

const socket = io(SERVER_URL);
const roomId = sessionStorage.getItem('matchRoomId');
const sharedGemsData = JSON.parse(sessionStorage.getItem('sharedGems'));

if (!roomId || !sharedGemsData) {
    alert("数据丢失，请重新匹配！");
    window.location.href = 'mining_match.php';
}
document.getElementById('room-id-display').innerText = roomId;

// 同步对手状态的数据容器
let oppoPivotX = 0; 
let oppoHook = { angle: Math.PI/2, length: 80, grabbedGemId: null };

// --- 此处省略 WORD_BANK, ASSETS, IMAGES 加载配置，请保留你之前原有的词库和贴图配置 ---
const WORD_BANK =[ { en: "abandon", cn: ["放弃", "保持", "收获", "勇敢"], a: 0 }, { en: "benevolent", cn: ["残忍的", "仁慈的", "平庸的", "迅速的"], a: 1 }, { en: "capacity", cn:["高度", "容量/能力", "速度", "性格"], a: 1 }, { en: "dazzling", cn:["昏暗的", "寂静的", "耀眼的", "坚硬的"], a: 2 }, { en: "eloquent", cn:["雄辩的", "迟钝的", "优雅的", "傲慢的"], a: 0 }, { en: "fetch", cn:["放弃", "拿来", "坚持", "快乐"], a: 1 }, { en: "genuine", cn:["虚伪的", "天才的", "真实的", "慷慨的"], a: 2 }, { en: "hypocrisy", cn: ["民主", "伪善", "诚实", "冷静"], a: 1 }, { en: "resilience", cn:["脆弱", "弹性/复原力", "沉默", "愤怒"], a: 1 } ];
const ASSETS = { oil: 'oil.png', diamond: 'diamond.png', ruby: 'ruby.png', emerald: 'emerald.png', amethyst: 'amethyst.png', gold: 'gold.png', silver: 'silver.png', coin: 'coin.png', miner: 'miner.png', hook: 'hook.png', bg: 'bg.png' };
const OBJECTS_CONFIG = { oil: { value: 5000, targetQuestions: 10, weight: 1.2, radius: 45, drawScale: 2.5 }, diamond: { value: 2000, targetQuestions: 8, weight: 0.8, radius: 35, drawScale: 3.5 }, ruby: { value: 1500, targetQuestions: 6, weight: 0.9, radius: 35, drawScale: 3.5 }, emerald: { value: 1200, targetQuestions: 5, weight: 0.9, radius: 35, drawScale: 3.5 }, amethyst: { value: 1000, targetQuestions: 4, weight: 1.0, radius: 35, drawScale: 3.5 }, gold: { value: 600, targetQuestions: 3, weight: 1.5, radius: 40, drawScale: 3 }, silver: { value: 400, targetQuestions: 2, weight: 1.3, radius: 35, drawScale: 3 }, coin: { value: 200, targetQuestions: 1, weight: 0.5, radius: 25, drawScale: 3 } };
const IMAGES = {}; let loadedCount = 0; const totalImages = Object.keys(ASSETS).length;
function loadAssets(callback) { if (totalImages === 0) return callback(); for (let key in ASSETS) { IMAGES[key] = new Image(); IMAGES[key].src = ASSETS[key]; IMAGES[key].onload = () => { IMAGES[key].isReady = true; checkDone(); }; IMAGES[key].onerror = () => { IMAGES[key].isReady = false; checkDone(); }; } function checkDone() { loadedCount++; if (loadedCount === totalImages) callback(); } }
// ----------------------------------------------------------------------------------

const STATE = { SWINGING: 0, EXTENDING: 1, DIGGING_QUIZ: 2, RETRACTING_EMPTY: 3, RETRACTING_WITH_STONE: 4 };
const canvas = document.getElementById('doubleCanvas');
const ctx = canvas.getContext('2d');
const quizModal = document.getElementById('quiz-modal');

let gameState = { isQuizActive: false, currentQuestion: null, currentMistakes: 0 };
let p1;

class Player {
    constructor(isLeft) {
        this.TOP_PANEL_HEIGHT = 160; 
        // 核心修改：如果是左侧玩家站在 30% 处，右侧站在 70% 处
        this.minerPivotX = canvas.width * (isLeft ? 0.3 : 0.7);
        this.minerPivotY = this.TOP_PANEL_HEIGHT;

        // 设置对手位置
        oppoPivotX = canvas.width * (isLeft ? 0.7 : 0.3);

        this.BASE_EXTENSION_SPEED = 14; 
        this.BASE_RETRACT_SPEED = 20;
        this.SWING_SPEED = 0.008; 
        this.HOOK_BASE_LEN = 80; 

        this.hook = { angle: Math.PI / 2, dir: 1, length: this.HOOK_BASE_LEN, x: 0, y: 0 };
        this.status = STATE.SWINGING;
        this.grabbedObject = null;
        this.quizProgress = 0;
        this.objects =[];
        this.updateHookCoords();
    }

    update() {
        if (this.status === STATE.SWINGING) {
            this.hook.angle += this.SWING_SPEED * this.hook.dir;
            if (this.hook.angle > Math.PI - 0.2 || this.hook.angle < 0.2) this.hook.dir *= -1;
            this.updateHookCoords();
        } 
        else if (this.status === STATE.EXTENDING) {
            this.hook.length += this.BASE_EXTENSION_SPEED;
            this.updateHookCoords();

            for (let i = 0; i < this.objects.length; i++) {
                let obj = this.objects[i];
                let dist = Math.hypot(this.hook.x - obj.x, this.hook.y - obj.y);
                
                // 🌟 核心防抢逻辑：对手没在抓，且矿石处于闲置状态，我才能抓！
                if (dist < obj.radius + 15 && obj.status === 'idle' && oppoHook.grabbedGemId !== obj.serverId) { 
                    this.status = STATE.DIGGING_QUIZ; 
                    this.grabbedObject = obj;
                    
                    obj.status = 'locked';
                    socket.emit('lockGem', { roomId, gemId: obj.serverId });
                    triggerQuiz();
                    return;
                }
            }
            if (this.hook.y > canvas.height || this.hook.x < 0 || this.hook.x > canvas.width) this.status = STATE.RETRACTING_EMPTY;
        } 
        else if (this.status === STATE.RETRACTING_EMPTY) {
            this.hook.length -= this.BASE_RETRACT_SPEED;
            this.updateHookCoords();
            if (this.hook.length <= this.HOOK_BASE_LEN) this.reset();
        }
        else if (this.status === STATE.RETRACTING_WITH_STONE) {
            let pullSpeed = this.BASE_RETRACT_SPEED / this.grabbedObject.weight;
            this.hook.length -= pullSpeed;
            this.updateHookCoords();
            if (this.hook.length <= this.HOOK_BASE_LEN) this.collect();
        }

        // 🌟 实时广播我的爪子状态给对手！
        socket.emit('syncHook', { 
            roomId, 
            hookData: { 
                angle: this.hook.angle, 
                length: this.hook.length, 
                grabbedGemId: this.grabbedObject ? this.grabbedObject.serverId : null 
            }
        });
    }

    updateHookCoords() {
        this.hook.x = this.minerPivotX + Math.cos(this.hook.angle) * this.hook.length;
        this.hook.y = this.minerPivotY + Math.sin(this.hook.angle) * this.hook.length;
    }

    reset() {
        this.hook.length = this.HOOK_BASE_LEN; this.updateHookCoords();
        this.status = STATE.SWINGING; this.grabbedObject = null; this.quizProgress = 0;
    }

    collect() {
        socket.emit('collectGem', { roomId, gemId: this.grabbedObject.serverId, value: this.grabbedObject.value });
        this.reset();
    }
}

function gameLoop() {
    p1.update();
    drawPlayerView();
    requestAnimationFrame(gameLoop);
}

// 渲染画面
function drawPlayerView() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (IMAGES.bg && IMAGES.bg.isReady) ctx.drawImage(IMAGES.bg, 0, 0, canvas.width, canvas.height);
    else { ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--mine-dirt'); ctx.fillRect(0, 0, canvas.width, canvas.height); }

    // 1. 绘制地上的矿石
    p1.objects.forEach((obj) => {
        // 别人抓走的或者我抓走的，不在地上画
        if (p1.grabbedObject && obj.serverId === p1.grabbedObject.serverId) return; 
        if (oppoHook.grabbedGemId === obj.serverId) return;

        ctx.save(); ctx.translate(obj.x, obj.y);
        const img = IMAGES[obj.type];
        if (img && img.isReady) {
            const scale = obj.drawScale || 1; const drawRadius = obj.radius * scale;
            ctx.drawImage(img, -drawRadius, -drawRadius, drawRadius * 2, drawRadius * 2);
        } else { ctx.fillStyle = obj.color || "#ccc"; ctx.beginPath(); ctx.arc(0, 0, obj.radius, 0, Math.PI * 2); ctx.fill(); }
        ctx.restore();
    });

    // 2. 封装绘制人物和爪子的函数
    function drawMiner(pivotX, pivotY, hookAngle, hookLength, grabbedGemId) {
        if (!IMAGES.miner || !IMAGES.miner.isReady) return;
        ctx.save(); ctx.translate(pivotX, pivotY); ctx.rotate(hookAngle - Math.PI / 2);
        
        // 为了区分敌我，可以给对手的图加上滤镜（变暗一点）
        if(pivotX === oppoPivotX) ctx.filter = 'brightness(0.7)';
        ctx.drawImage(IMAGES.miner, -60, -60, 120, 120); 
        ctx.filter = 'none';

        const ropeLength = hookLength - pivotY - 50;
        if (ropeLength > 0) {
            ctx.strokeStyle = '#5a3c26'; ctx.lineWidth = 4;
            ctx.beginPath(); ctx.moveTo(0, 50); ctx.lineTo(0, 50 + ropeLength); ctx.stroke();
            
            ctx.save(); ctx.translate(0, 50 + ropeLength);
            // 画挂在爪子上的矿石
            if (grabbedGemId !== null) {
                let obj = p1.objects.find(g => g.serverId === grabbedGemId);
                if (obj && IMAGES[obj.type]) {
                    const r = obj.radius * (obj.drawScale||1);
                    ctx.drawImage(IMAGES[obj.type], -r, 35-r, r*2, r*2);
                }
            }
            if (IMAGES.hook && IMAGES.hook.isReady) { ctx.rotate(-Math.PI / 2); ctx.drawImage(IMAGES.hook, -25, -5, 50, 60); }
            ctx.restore();
        }
        ctx.restore();
    }

    // 绘制自己
    drawMiner(p1.minerPivotX, p1.minerPivotY, p1.hook.angle, p1.hook.length, p1.grabbedObject ? p1.grabbedObject.serverId : null);
    // 绘制对手
    drawMiner(oppoPivotX, p1.minerPivotY, oppoHook.angle, oppoHook.length, oppoHook.grabbedGemId);
}

// 答题与事件保持原有逻辑
function triggerQuiz() {
    if (gameState.isQuizActive) return; 
    gameState.isQuizActive = true; gameState.currentMistakes = 0; 
    quizModal.classList.add('active');
    document.getElementById('player-indicator').innerText = `正在挖掘 [${p1.grabbedObject.type.toUpperCase()}]...`;
    loadNewWord();
}

function loadNewWord() {
    const word = WORD_BANK[Math.floor(Math.random() * WORD_BANK.length)];
    gameState.currentQuestion = word;
    document.getElementById('word-display').innerText = word.en;
    const btns = document.querySelectorAll('.opt-btn');
    word.cn.forEach((choice, i) => { btns[i].innerText = choice; btns[i].className = 'opt-btn'; btns[i].disabled = false; });
    const percent = (p1.quizProgress / p1.grabbedObject.targetQuestions) * 100;
    document.getElementById('progress-bar').style.width = percent + '%';
    document.getElementById('target-num').innerText = `${p1.quizProgress}/${p1.grabbedObject.targetQuestions}`;
}

function handleAnswer(selectedIndex) {
    if (!gameState.isQuizActive) return;
    const correctIndex = gameState.currentQuestion.a;
    const btns = document.querySelectorAll('.opt-btn');
    btns.forEach(b => b.disabled = true);

    if (selectedIndex === correctIndex) {
        btns[selectedIndex].classList.add('correct-flash');
        p1.quizProgress++; p1.hook.length -= 10; p1.updateHookCoords();
        if (p1.quizProgress >= p1.grabbedObject.targetQuestions) {
            setTimeout(() => { quizModal.classList.remove('active'); gameState.isQuizActive = false; p1.status = STATE.RETRACTING_WITH_STONE; }, 300);
        } else { setTimeout(() => loadNewWord(), 300); }
    } else {
        btns[selectedIndex].classList.add('wrong-flash'); btns[correctIndex].classList.add('correct-flash'); 
        p1.hook.length += 20; p1.updateHookCoords(); p1.quizProgress = Math.max(0, p1.quizProgress - 1); 
        gameState.currentMistakes++;
        setTimeout(() => {
            if (gameState.currentMistakes >= 3) {
                quizModal.classList.remove('active'); gameState.isQuizActive = false; p1.status = STATE.RETRACTING_EMPTY;
                socket.emit('freeGem', { roomId, gemId: p1.grabbedObject.serverId });
                p1.grabbedObject = null; p1.quizProgress = 0;
            } else { loadNewWord(); }
        }, 600);
    }
}

// 接收服务器数据
socket.on('playerAssigned', (index) => {
    canvas.width = window.innerWidth; canvas.height = window.innerHeight;
    p1 = new Player(index === 0); // 0是左边，1是右边
    p1.objects = sharedGemsData.map(g => ({ serverId: g.id, x: g.rx * canvas.width, y: g.ry * canvas.height, type: g.type, status: g.status, ...OBJECTS_CONFIG[g.type] }));
    loadAssets(() => { gameLoop(); });
});

socket.on('oppoHook', (data) => { oppoHook = data; });
socket.on('gemLocked', (gemId) => { let gem = p1.objects.find(g => g.serverId === gemId); if (gem) gem.status = 'locked'; });
socket.on('gemFreed', (gemId) => { let gem = p1.objects.find(g => g.serverId === gemId); if (gem) gem.status = 'idle'; });

socket.on('updateGame', (data) => {
    p1.objects = p1.objects.filter(g => g.serverId !== data.gemId);
    let oppoId = Object.keys(data.scores).find(id => id !== socket.id);
    document.getElementById('my-score').innerText = data.scores[socket.id]?.score || 0;
    if(oppoId) document.getElementById('oppo-score').innerText = data.scores[oppoId]?.score || 0;
});

socket.on('timeUpdate', (time) => { document.getElementById('game-timer').innerText = time; });

socket.on('gameOver', (data) => {
    let msg = "";
    if (data.winnerId === socket.id) {
        msg = `🏆 恭喜你赢了！获得了 300 金币！`;
        let currentCoins = parseInt(localStorage.getItem('playerCoins')) || 0;
        localStorage.setItem('playerCoins', currentCoins + 300);
    } else if (data.winnerId === null) { msg = `🤝 平局！`; } 
    else { msg = `💔 遗憾败北！`; }
    setTimeout(() => { alert("对战结束！\n" + msg + `\n比分: ${data.p1Score} : ${data.p2Score}`); window.location.href = 'mining_index.php'; }, 1000);
});

window.addEventListener('keydown', (e) => {
    if (gameState.isQuizActive || !p1) return; 
    if ((e.code === 'ArrowDown' || e.code === 'Space' || e.code === 'KeyS') && p1.status === STATE.SWINGING) p1.status = STATE.EXTENDING;
});

// 重连入游戏
socket.on('connect', () => { socket.emit('rejoinGame', roomId); });