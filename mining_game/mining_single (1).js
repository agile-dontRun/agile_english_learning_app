// single.js

/** --- 1. 联动数据获取 --- **/
// 从 sessionStorage 获取玩家在 map.html 选中的地图，如果没有默认进 map1
const currentMapId = sessionStorage.getItem('selectedMapId') || 'map1';
const currentMapName = sessionStorage.getItem('selectedMapName') || '新手森林';

const WORD_BANK = [
    { en: "abandon", cn:["放弃", "保持", "收获", "勇敢"], a: 0 },
    { en: "benevolent", cn:["残忍的", "仁慈的", "平庸的", "迅速的"], a: 1 },
    { en: "capacity", cn: ["高度", "容量/能力", "速度", "性格"], a: 1 },
    { en: "dazzling", cn:["昏暗的", "寂静的", "耀眼的", "坚硬的"], a: 2 },
    { en: "eloquent", cn: ["雄辩的", "迟钝的", "优雅的", "傲慢的"], a: 0 },
    { en: "fetch", cn: ["放弃", "拿来", "坚持", "快乐"], a: 1 },
    { en: "genuine", cn:["虚伪的", "天才的", "真实的", "慷慨的"], a: 2 },
    { en: "hypocrisy", cn: ["民主", "伪善", "诚实", "冷静"], a: 1 },
    { en: "resilience", cn: ["脆弱", "弹性/复原力", "沉默", "愤怒"], a: 1 }
];

/** --- 2. 资源加载 (动态加载对应地图背景) --- **/
const ASSETS = {
    oil: 'oil.png',
    diamond: 'diamond.png',
    ruby: 'ruby.png',
    emerald: 'emerald.png',
    amethyst: 'amethyst.png',
    gold: 'gold.png',
    silver: 'silver.png',
    coin: 'coin.png',
    miner: 'miner.png', 
    hook: 'hook.png',   
    bg: currentMapId + '_bg.png' // 🌟 核心：动态加载玩家选择的地图背景图片
};

const IMAGES = {};
let loadedCount = 0;
const totalImages = Object.keys(ASSETS).length;

function loadAssets(callback) {
    if (totalImages === 0) return callback();
    for (let key in ASSETS) {
        IMAGES[key] = new Image();
        IMAGES[key].src = ASSETS[key];
        IMAGES[key].onload = () => { IMAGES[key].isReady = true; checkDone(); };
        IMAGES[key].onerror = () => { 
            console.warn(`图片缺少: ${ASSETS[key]}，将使用替代效果`);
            IMAGES[key].isReady = false; 
            checkDone(); 
        };
    }
    function checkDone() {
        loadedCount++;
        if (loadedCount === totalImages) callback();
    }
}

// 矿物配置表
const OBJECTS_CONFIG = {
    oil:      { value: 5000, targetQuestions: 10, weight: 1.2, radius: 45, drawScale: 2.5 }, 
    diamond:  { value: 2000, targetQuestions: 8,  weight: 0.8, radius: 35, drawScale: 3.5 },
    ruby:     { value: 1500, targetQuestions: 6,  weight: 0.9, radius: 35, drawScale: 3.5 },
    emerald:  { value: 1200, targetQuestions: 5,  weight: 0.9, radius: 35, drawScale: 3.5 },
    amethyst: { value: 1000, targetQuestions: 4,  weight: 1.0, radius: 35, drawScale: 3.5 },
    gold:     { value: 600,  targetQuestions: 3,  weight: 1.5, radius: 40, drawScale: 3 },
    silver:   { value: 400,  targetQuestions: 2,  weight: 1.3, radius: 35, drawScale: 3 },
    coin:     { value: 200,  targetQuestions: 1,  weight: 0.5, radius: 25, drawScale: 3 },
};

const STATE = { SWINGING: 0, EXTENDING: 1, DIGGING_QUIZ: 2, RETRACTING_EMPTY: 3, RETRACTING_WITH_STONE: 4 };

/** --- 3. 环境初始化 --- **/
const p1Canvas = document.getElementById('p1Canvas');
const ctx1 = p1Canvas.getContext('2d');
const quizModal = document.getElementById('quiz-modal');
const gameContainer = document.getElementById('game-container');

let gameState = { isQuizActive: false, currentQuestion: null, currentLevel: 1, currentMistakes: 0, isPaused: false };
let p1;
/**
 * 记录矿石收藏
 */
function recordAchievement(oreId) {
    let unlockedList = JSON.parse(localStorage.getItem('myMineAchieve')) || [];
    if (!unlockedList.includes(oreId)) {
        unlockedList.push(oreId);
        localStorage.setItem('myMineAchieve', JSON.stringify(unlockedList));
        console.log(`✨ 解锁新收藏：${oreId}`);
    }
}

/** --- 4. 玩家类 --- **/
class Player {
    constructor(id, canvas, ctx) {
        this.id = id; this.canvas = canvas; this.ctx = ctx; this.objects =[];
        
        // 🌟 核心：从 localStorage 读取全局金币作为初始金币！
        this.score = parseInt(localStorage.getItem('playerCoins')) || 0;
        
        this.TOP_PANEL_HEIGHT = 160; 
        this.minerPivotX = this.canvas.width / 2;
        this.minerPivotY = this.TOP_PANEL_HEIGHT;

        this.BASE_EXTENSION_SPEED = 12; this.BASE_RETRACT_SPEED = 18;
        this.SWING_SPEED = 0.008; this.HOOK_BASE_LEN = 80; 

        this.hook = { angle: Math.PI / 2, dir: 1, length: this.HOOK_BASE_LEN, x: 0, y: 0 };
        this.status = STATE.SWINGING;
        this.grabbedObject = null; this.grabbedObjectIdx = -1; this.quizProgress = 0;
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
                if (dist < obj.radius + 15) { 
                    this.status = STATE.DIGGING_QUIZ; this.grabbedObject = obj; this.grabbedObjectIdx = i;
                    triggerQuiz(); return;
                }
            }
            if (this.hook.y > this.canvas.height || this.hook.x < 0 || this.hook.x > this.canvas.width) {
                this.status = STATE.RETRACTING_EMPTY;
            }
        } 
        else if (this.status === STATE.RETRACTING_EMPTY) {
            this.hook.length -= this.BASE_RETRACT_SPEED; this.updateHookCoords();
            if (this.hook.length <= this.HOOK_BASE_LEN) this.reset();
        }
        else if (this.status === STATE.RETRACTING_WITH_STONE) {
            let pullSpeed = this.BASE_RETRACT_SPEED / this.grabbedObject.weight;
            this.hook.length -= pullSpeed; this.updateHookCoords();
            if (this.hook.length <= this.HOOK_BASE_LEN) this.collect();
        }
    }

    updateHookCoords() {
        this.hook.x = this.minerPivotX + Math.cos(this.hook.angle) * this.hook.length;
        this.hook.y = this.minerPivotY + Math.sin(this.hook.angle) * this.hook.length;
    }

    reset() {
        this.hook.length = this.HOOK_BASE_LEN; this.updateHookCoords();
        this.status = STATE.SWINGING; this.grabbedObject = null;
        this.grabbedObjectIdx = -1; this.quizProgress = 0;
    }

    collect() {
        // 获取当前矿石类型（根据你的数据结构调整）
    const oreType = this.grabbedObject.type;  // 例如 'oil', 'diamond' 等

    // 记录成就（如果尚未解锁）
    recordAchievement(oreType);

    // 原有逻辑：增加金币，移除物体等
    this.score += this.grabbedObject.value;
    this.objects.splice(this.grabbedObjectIdx, 1); 
    
    //金币成就
    let stats = JSON.parse(localStorage.getItem('gameStats')) || {};
    stats.stat_total_coins = (stats.stat_total_coins || 0) + this.grabbedObject.value;
    localStorage.setItem('gameStats', JSON.stringify(stats));

    // 保存金币到 localStorage（用于跨页面显示）
    localStorage.setItem('playerCoins', this.score);

    updateScoreDisplay();
    this.reset();
    checkLevelComplete();
        
    }
}

/** --- 5. 游戏控制与生成逻辑 --- **/
function initGame() {
    p1Canvas.width = window.innerWidth;
    p1Canvas.height = window.innerHeight;

    p1 = new Player(1, p1Canvas, ctx1);
    
    // 初始化 UI 显示
    document.getElementById('map-name-display').innerText = `[${currentMapName}]`;
    updateScoreDisplay();

    generateObjects();
    gameLoop();
}

function generateObjects() {
    // 🌟 核心：根据不同地图生成专属的矿石概率池！
    let pool = [];
    if (currentMapId === 'map1') {
        pool =['coin', 'coin', 'coin', 'silver', 'silver', 'gold']; // 煤矿：钱币和银为主
    } else if (currentMapId === 'map2') {
        pool =['oil',  'silver', 'silver','silver', 'silver','coin', 'coin','gold']; // 白银：石油和红宝石多
    } else if (currentMapId === 'map3') {
        pool =['gold', 'gold', 'gold', 'gold', 'coin', 'oil', 'ruby']; // 黄金
    } else if (currentMapId === 'map4') {
        pool =['emerald', 'emerald', 'silver', 'amethyst', 'diamond','ruby', 'ruby','emerald']; // 钻石
    } else if (currentMapId === 'map5') {
        pool =['diamond', 'diamond', 'amethyst', 'amethyst', 'silver']; // FINAL：顶级宝石
    } else {
        pool =['coin', 'silver', 'gold']; // 容错兜底
    }
    
    p1.objects =[];
    const count = 18; 
    for(let i=0; i < count; i++) {
        const type = pool[Math.floor(Math.random() * pool.length)];
        const config = OBJECTS_CONFIG[type];
        const safeYStart = 250; 
        p1.objects.push({
            x: Math.random() * (p1Canvas.width - 100) + 50,
            y: Math.random() * (p1Canvas.height - safeYStart - 100) + safeYStart,
            type: type, ...config
        });
    }
}

function checkLevelComplete() {
    if (p1.objects.length === 0) {
        gameState.currentLevel++;
        
        document.getElementById('level-num').innerText = gameState.currentLevel;
        generateObjects();
    }
}

function gameLoop() {
    if (!gameState.isPaused) p1.update();
    drawPlayerView(p1, p1.objects);
    requestAnimationFrame(gameLoop);
}

// --- 绘制视图 ---
function drawPlayerView(p, objects) {
    const ctx = p.ctx;
    const pivotX = p.minerPivotX;
    const pivotY = p.minerPivotY;
    
    ctx.clearRect(0, 0, p.canvas.width, p.canvas.height);
    
    // 绘制地图背景 (现在会自动使用 map1.png, map2.png...)
    if (IMAGES.bg && IMAGES.bg.isReady) {
        ctx.drawImage(IMAGES.bg, 0, 0, p.canvas.width, p.canvas.height);
    } else {
        // Fallback: 如果图片没加载出来，全屏填充备用底色
        ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--mine-dirt');
        ctx.fillRect(0, 0, p.canvas.width, p.canvas.height);
    }

    objects.forEach((obj, idx) => {
        if ((p.status === STATE.DIGGING_QUIZ || p.status === STATE.RETRACTING_WITH_STONE) && idx === p.grabbedObjectIdx) return; 
        drawGameObject(ctx, obj);
    });

    if (IMAGES.miner && IMAGES.miner.isReady) {
        ctx.save(); ctx.translate(pivotX, pivotY); ctx.rotate(p.hook.angle - Math.PI / 2);
        const machineSize = 120; 
        ctx.drawImage(IMAGES.miner, -machineSize/2, -machineSize/2, machineSize, machineSize); 
        
        const ropeStartX = 0; const ropeStartY = machineSize/2 - 10; 
        const ropeLength = p.hook.length - pivotY - ropeStartY;
        
        if (ropeLength > 0) {
            ctx.strokeStyle = '#5a3c26'; ctx.lineWidth = 4;
            ctx.beginPath(); ctx.moveTo(ropeStartX, ropeStartY); ctx.lineTo(0, ropeStartY + ropeLength); ctx.stroke();
            
            ctx.save(); ctx.translate(0, ropeStartY + ropeLength);
            if ((p.status === STATE.DIGGING_QUIZ || p.status === STATE.RETRACTING_WITH_STONE) && p.grabbedObject) {
                drawGameObject(ctx, { ...p.grabbedObject, x: 0, y: 35 });
            }
            if (IMAGES.hook && IMAGES.hook.isReady) {
                ctx.rotate(-Math.PI / 2); ctx.drawImage(IMAGES.hook, -25, -5, 50, 60);
            } else {
                ctx.fillStyle = '#7f8c8d'; ctx.strokeStyle = '#5a3c26'; ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.moveTo(-10, -5); ctx.lineTo(-20, 20); ctx.lineTo(-12, 35); ctx.lineTo(-18, 55); ctx.lineTo(-10, 50); ctx.lineTo(-5, 40); ctx.lineTo(-10, 30); ctx.lineTo(-5, 20); ctx.lineTo(0, -5);
                ctx.moveTo(10, -5); ctx.lineTo(20, 20); ctx.lineTo(12, 35); ctx.lineTo(18, 55); ctx.lineTo(10, 50); ctx.lineTo(5, 40); ctx.lineTo(10, 30); ctx.lineTo(5, 20); ctx.lineTo(0, -5);
                ctx.fill(); ctx.stroke();
            }
            ctx.restore();
        }
        ctx.restore();
    }
}

function drawGameObject(ctx, obj) {
    ctx.save(); ctx.translate(obj.x, obj.y);
    const img = IMAGES[obj.type];
    if (img && img.isReady) {
        const scale = obj.drawScale || 1;
        const drawRadius = obj.radius * scale;
        ctx.drawImage(img, -drawRadius, -drawRadius, drawRadius * 2, drawRadius * 2);
    } else {
        ctx.fillStyle = obj.color || "#ccc";
        ctx.beginPath(); ctx.arc(0, 0, obj.radius, 0, Math.PI * 2); ctx.fill();
    }
    ctx.restore();
}

/** --- 6. 答题 UI 交互 --- **/
function triggerQuiz() {
    if (gameState.isQuizActive) return; 
    gameState.isQuizActive = true; gameState.currentMistakes = 0; 
    
    quizModal.classList.add('active');
    const indicator = document.getElementById('player-indicator');
    indicator.innerText = `正在挖掘 [${p1.grabbedObject.type.toUpperCase()}]... (容错: 3次)`;
    indicator.style.color = 'var(--p1-main)';
    loadNewWord();
}

function loadNewWord() {
    const word = WORD_BANK[Math.floor(Math.random() * WORD_BANK.length)];
    gameState.currentQuestion = word;
    document.getElementById('word-display').innerText = word.en;
    
    const btns = document.querySelectorAll('.opt-btn');
    word.cn.forEach((choice, i) => {
        btns[i].innerText = choice; btns[i].className = 'opt-btn'; btns[i].disabled = false; 
    });

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
        p1.quizProgress++;
        p1.hook.length -= 10; p1.updateHookCoords();

        if (p1.quizProgress >= p1.grabbedObject.targetQuestions) {
            setTimeout(() => {
                quizModal.classList.remove('active');
                gameState.isQuizActive = false;
                p1.status = STATE.RETRACTING_WITH_STONE; 
            }, 400);
        } else {
            setTimeout(() => loadNewWord(), 300); 
        }
    } else {
        btns[selectedIndex].classList.add('wrong-flash');
        btns[correctIndex].classList.add('correct-flash'); 
        gameContainer.classList.add('shake-animation');
        
        p1.hook.length += 20; p1.updateHookCoords();
        p1.quizProgress = Math.max(0, p1.quizProgress - 1); 
        gameState.currentMistakes++;

        setTimeout(() => {
            gameContainer.classList.remove('shake-animation');
            if (gameState.currentMistakes >= 3) {
                
                // 🌟【新增】记录：如果掉落的是钻石，记录失之交臂成就
                if (p1.grabbedObject && p1.grabbedObject.type === 'diamond') {
                    let stats = JSON.parse(localStorage.getItem('gameStats')) || {};
                    stats.stat_diamond_fail = (stats.stat_diamond_fail || 0) + 1;
                    localStorage.setItem('gameStats', JSON.stringify(stats));
                }

                quizModal.classList.remove('active');
                gameState.isQuizActive = false;
                p1.status = STATE.RETRACTING_EMPTY;
                p1.grabbedObject = null; p1.grabbedObjectIdx = -1; p1.quizProgress = 0;
            } else {
                loadNewWord();
                const leftChances = 3 - gameState.currentMistakes;
                const indicator = document.getElementById('player-indicator');
                indicator.innerText = `正在挖掘 [${p1.grabbedObject.type.toUpperCase()}]... (剩余容错: ${leftChances}次)`;
                indicator.style.color = '#e74c3c'; 
            }
        }, 700);
    }
}

function updateScoreDisplay() {
    const scoreElement = document.getElementById('p1-score');
    if (scoreElement && p1) { scoreElement.innerText = p1.score; }
}

/** --- 7. 输入与暂停控制 --- **/
window.addEventListener('keydown', (e) => {
    if (e.code === 'KeyP' || e.code === 'Escape') { togglePause(); return; }
    if (gameState.isPaused || gameState.isQuizActive) return; 
    if ((e.code === 'ArrowDown' || e.code === 'Space' || e.code === 'KeyS') && p1.status === STATE.SWINGING) {
        p1.status = STATE.EXTENDING;
    }
});

window.addEventListener('resize', () => {
    p1Canvas.width = window.innerWidth; p1Canvas.height = window.innerHeight;
    p1.minerPivotX = p1Canvas.width / 2; p1.reset();
});

const pauseOverlay = document.getElementById('pause-overlay');
const pauseBtn = document.getElementById('pause-btn');
const resumeBtn = document.getElementById('resume-btn');

function togglePause() {
    gameState.isPaused = !gameState.isPaused;
    if (gameState.isPaused) pauseOverlay.classList.add('active');
    else pauseOverlay.classList.remove('active');
}

pauseBtn.addEventListener('click', togglePause);
resumeBtn.addEventListener('click', togglePause);

// 启动游戏
loadAssets(() => { initGame(); });