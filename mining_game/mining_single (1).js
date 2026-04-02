// single.js

/** --- 1. Linked data retrieval --- **/
// Read the map selected on the map page from sessionStorage; fall back to map1.
const currentMapId = sessionStorage.getItem('selectedMapId') || 'map1';
const currentMapName = sessionStorage.getItem('selectedMapName') || 'Beginner Forest';

const WORD_BANK = [
    { en: "abandon", cn: ["give up", "keep", "harvest", "brave"], a: 0 },
    { en: "benevolent", cn: ["cruel", "kind", "peaceful", "fast"], a: 1 },
    { en: "capacity", cn: ["height", "capacity/ability", "speed", "personality"], a: 1 },
    { en: "dazzling", cn: ["dim", "quiet", "dazzling", "hard"], a: 2 },
    { en: "eloquent", cn: ["eloquent", "slow", "elegant", "arrogant"], a: 0 },
    { en: "fetch", cn: ["give up", "bring", "persist", "happy"], a: 1 },
    { en: "genuine", cn: ["fake", "talented", "real", "careful"], a: 2 },
    { en: "hypocrisy", cn: ["democracy", "hypocrisy", "honesty", "calm"], a: 1 },
    { en: "resilience", cn: ["fragility", "resilience/recovery", "silence", "melancholy"], a: 1 }
];

/** --- 2. Asset loading (dynamically load the selected map background) --- **/
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
    bg: currentMapId + '_bg.png' // Core: load the background for the selected map dynamically.
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
            console.warn(`Missing image: ${ASSETS[key]}. A fallback effect will be used.`);
            IMAGES[key].isReady = false;
            checkDone();
        };
    }

    function checkDone() {
        loadedCount++;
        if (loadedCount === totalImages) callback();
    }
}

// Ore configuration table
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

/** --- 3. Environment initialization --- **/
const p1Canvas = document.getElementById('p1Canvas');
const ctx1 = p1Canvas.getContext('2d');
const quizModal = document.getElementById('quiz-modal');
const gameContainer = document.getElementById('game-container');

let gameState = { isQuizActive: false, currentQuestion: null, currentLevel: 1, currentMistakes: 0, isPaused: false };
let p1;

/**
 * Record collected ore achievements.
 */
function recordAchievement(oreId) {
    let unlockedList = JSON.parse(localStorage.getItem('myMineAchieve')) || [];
    if (!unlockedList.includes(oreId)) {
        unlockedList.push(oreId);
        localStorage.setItem('myMineAchieve', JSON.stringify(unlockedList));
        console.log(`Unlocked new collectible: ${oreId}`);
    }
}

/** --- 4. Player class --- **/
class Player {
    constructor(id, canvas, ctx) {
        this.id = id;
        this.canvas = canvas;
        this.ctx = ctx;
        this.objects = [];

        // Use the global coin total from localStorage as the starting score.
        this.score = parseInt(localStorage.getItem('playerCoins')) || 0;

        this.TOP_PANEL_HEIGHT = 160;
        this.minerPivotX = this.canvas.width / 2;
        this.minerPivotY = this.TOP_PANEL_HEIGHT;

        this.BASE_EXTENSION_SPEED = 12;
        this.BASE_RETRACT_SPEED = 18;
        this.SWING_SPEED = 0.008;
        this.HOOK_BASE_LEN = 80;

        this.hook = { angle: Math.PI / 2, dir: 1, length: this.HOOK_BASE_LEN, x: 0, y: 0 };
        this.status = STATE.SWINGING;
        this.grabbedObject = null;
        this.grabbedObjectIdx = -1;
        this.quizProgress = 0;
        this.updateHookCoords();
    }

    update() {
        if (this.status === STATE.SWINGING) {
            this.hook.angle += this.SWING_SPEED * this.hook.dir;
            if (this.hook.angle > Math.PI - 0.2 || this.hook.angle < 0.2) this.hook.dir *= -1;
            this.updateHookCoords();
        } else if (this.status === STATE.EXTENDING) {
            this.hook.length += this.BASE_EXTENSION_SPEED;
            this.updateHookCoords();
            for (let i = 0; i < this.objects.length; i++) {
                let obj = this.objects[i];
                let dist = Math.hypot(this.hook.x - obj.x, this.hook.y - obj.y);
                if (dist < obj.radius + 15) {
                    this.status = STATE.DIGGING_QUIZ;
                    this.grabbedObject = obj;
                    this.grabbedObjectIdx = i;
                    triggerQuiz();
                    return;
                }
            }
            if (this.hook.y > this.canvas.height || this.hook.x < 0 || this.hook.x > this.canvas.width) {
                this.status = STATE.RETRACTING_EMPTY;
            }
        } else if (this.status === STATE.RETRACTING_EMPTY) {
            this.hook.length -= this.BASE_RETRACT_SPEED;
            this.updateHookCoords();
            if (this.hook.length <= this.HOOK_BASE_LEN) this.reset();
        } else if (this.status === STATE.RETRACTING_WITH_STONE) {
            let pullSpeed = this.BASE_RETRACT_SPEED / this.grabbedObject.weight;
            this.hook.length -= pullSpeed;
            this.updateHookCoords();
            if (this.hook.length <= this.HOOK_BASE_LEN) this.collect();
        }
    }

    updateHookCoords() {
        this.hook.x = this.minerPivotX + Math.cos(this.hook.angle) * this.hook.length;
        this.hook.y = this.minerPivotY + Math.sin(this.hook.angle) * this.hook.length;
    }

    reset() {
        this.hook.length = this.HOOK_BASE_LEN;
        this.updateHookCoords();
        this.status = STATE.SWINGING;
        this.grabbedObject = null;
        this.grabbedObjectIdx = -1;
        this.quizProgress = 0;
    }

    collect() {
        // Get the current ore type.
        const oreType = this.grabbedObject.type;

        // Record the achievement if it has not been unlocked yet.
        recordAchievement(oreType);

        // Award coins and remove the collected object.
        this.score += this.grabbedObject.value;
        this.objects.splice(this.grabbedObjectIdx, 1);

        // Update coin-related stats.
        let stats = JSON.parse(localStorage.getItem('gameStats')) || {};
        stats.stat_total_coins = (stats.stat_total_coins || 0) + this.grabbedObject.value;
        localStorage.setItem('gameStats', JSON.stringify(stats));

        // Save the current coin total for cross-page display.
        localStorage.setItem('playerCoins', this.score);

        updateScoreDisplay();
        this.reset();
        checkLevelComplete();
    }
}

/** --- 5. Game control and generation logic --- **/
function initGame() {
    p1Canvas.width = window.innerWidth;
    p1Canvas.height = window.innerHeight;

    p1 = new Player(1, p1Canvas, ctx1);

    // Initialize UI display.
    document.getElementById('map-name-display').innerText = `[${currentMapName}]`;
    updateScoreDisplay();

    generateObjects();
    gameLoop();
}

function generateObjects() {
    // Generate ore pools based on the current map.
    let pool = [];
    if (currentMapId === 'map1') {
        pool = ['coin', 'coin', 'coin', 'silver', 'silver', 'gold']; // Coal Mine
    } else if (currentMapId === 'map2') {
        pool = ['oil', 'silver', 'silver', 'silver', 'silver', 'coin', 'coin', 'gold']; // Silver Mine
    } else if (currentMapId === 'map3') {
        pool = ['gold', 'gold', 'gold', 'gold', 'coin', 'oil', 'ruby']; // Gold Mine
    } else if (currentMapId === 'map4') {
        pool = ['emerald', 'emerald', 'silver', 'amethyst', 'diamond', 'ruby', 'ruby', 'emerald']; // Diamond Mine
    } else if (currentMapId === 'map5') {
        pool = ['diamond', 'diamond', 'amethyst', 'amethyst', 'silver']; // FINAL
    } else {
        pool = ['coin', 'silver', 'gold']; // Safe fallback
    }

    p1.objects = [];
    const count = 18;
    for (let i = 0; i < count; i++) {
        const type = pool[Math.floor(Math.random() * pool.length)];
        const config = OBJECTS_CONFIG[type];
        const safeYStart = 250;
        p1.objects.push({
            x: Math.random() * (p1Canvas.width - 100) + 50,
            y: Math.random() * (p1Canvas.height - safeYStart - 100) + safeYStart,
            type: type,
            ...config
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

// --- Draw the scene ---
function drawPlayerView(p, objects) {
    const ctx = p.ctx;
    const pivotX = p.minerPivotX;
    const pivotY = p.minerPivotY;

    ctx.clearRect(0, 0, p.canvas.width, p.canvas.height);

    // Draw the map background. It uses map1.png, map2.png, and so on.
    if (IMAGES.bg && IMAGES.bg.isReady) {
        ctx.drawImage(IMAGES.bg, 0, 0, p.canvas.width, p.canvas.height);
    } else {
        // Fallback: fill the screen with a backup background color.
        ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--mine-dirt');
        ctx.fillRect(0, 0, p.canvas.width, p.canvas.height);
    }

    objects.forEach((obj, idx) => {
        if ((p.status === STATE.DIGGING_QUIZ || p.status === STATE.RETRACTING_WITH_STONE) && idx === p.grabbedObjectIdx) return;
        drawGameObject(ctx, obj);
    });

    if (IMAGES.miner && IMAGES.miner.isReady) {
        ctx.save();
        ctx.translate(pivotX, pivotY);
        ctx.rotate(p.hook.angle - Math.PI / 2);
        const machineSize = 120;
        ctx.drawImage(IMAGES.miner, -machineSize / 2, -machineSize / 2, machineSize, machineSize);

        const ropeStartX = 0;
        const ropeStartY = machineSize / 2 - 10;
        const ropeLength = p.hook.length - pivotY - ropeStartY;

        if (ropeLength > 0) {
            ctx.strokeStyle = '#5a3c26';
            ctx.lineWidth = 4;
            ctx.beginPath();
            ctx.moveTo(ropeStartX, ropeStartY);
            ctx.lineTo(0, ropeStartY + ropeLength);
            ctx.stroke();

            ctx.save();
            ctx.translate(0, ropeStartY + ropeLength);
            if ((p.status === STATE.DIGGING_QUIZ || p.status === STATE.RETRACTING_WITH_STONE) && p.grabbedObject) {
                drawGameObject(ctx, { ...p.grabbedObject, x: 0, y: 35 });
            }
            if (IMAGES.hook && IMAGES.hook.isReady) {
                ctx.rotate(-Math.PI / 2);
                ctx.drawImage(IMAGES.hook, -25, -5, 50, 60);
            } else {
                ctx.fillStyle = '#7f8c8d';
                ctx.strokeStyle = '#5a3c26';
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.moveTo(-10, -5);
                ctx.lineTo(-20, 20);
                ctx.lineTo(-12, 35);
                ctx.lineTo(-18, 55);
                ctx.lineTo(-10, 50);
                ctx.lineTo(-5, 40);
                ctx.lineTo(-10, 30);
                ctx.lineTo(-5, 20);
                ctx.lineTo(0, -5);
                ctx.moveTo(10, -5);
                ctx.lineTo(20, 20);
                ctx.lineTo(12, 35);
                ctx.lineTo(18, 55);
                ctx.lineTo(10, 50);
                ctx.lineTo(5, 40);
                ctx.lineTo(10, 30);
                ctx.lineTo(5, 20);
                ctx.lineTo(0, -5);
                ctx.fill();
                ctx.stroke();
            }
            ctx.restore();
        }
        ctx.restore();
    }
}

function drawGameObject(ctx, obj) {
    ctx.save();
    ctx.translate(obj.x, obj.y);
    const img = IMAGES[obj.type];
    if (img && img.isReady) {
        const scale = obj.drawScale || 1;
        const drawRadius = obj.radius * scale;
        ctx.drawImage(img, -drawRadius, -drawRadius, drawRadius * 2, drawRadius * 2);
    } else {
        ctx.fillStyle = obj.color || "#ccc";
        ctx.beginPath();
        ctx.arc(0, 0, obj.radius, 0, Math.PI * 2);
        ctx.fill();
    }
    ctx.restore();
}

/** --- 6. Quiz UI interactions --- **/
function triggerQuiz() {
    if (gameState.isQuizActive) return;
    gameState.isQuizActive = true;
    gameState.currentMistakes = 0;

    quizModal.classList.add('active');
    const indicator = document.getElementById('player-indicator');
    indicator.innerText = `Mining [${p1.grabbedObject.type.toUpperCase()}]... (Mistakes allowed: 3)`;
    indicator.style.color = 'var(--p1-main)';
    loadNewWord();
}

function loadNewWord() {
    const word = WORD_BANK[Math.floor(Math.random() * WORD_BANK.length)];
    gameState.currentQuestion = word;
    document.getElementById('word-display').innerText = word.en;

    const btns = document.querySelectorAll('.opt-btn');
    word.cn.forEach((choice, i) => {
        btns[i].innerText = choice;
        btns[i].className = 'opt-btn';
        btns[i].disabled = false;
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
        p1.hook.length -= 10;
        p1.updateHookCoords();

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

        p1.hook.length += 20;
        p1.updateHookCoords();
        p1.quizProgress = Math.max(0, p1.quizProgress - 1);
        gameState.currentMistakes++;

        setTimeout(() => {
            gameContainer.classList.remove('shake-animation');
            if (gameState.currentMistakes >= 3) {
                // Record the failed diamond attempt if the dropped object was a diamond.
                if (p1.grabbedObject && p1.grabbedObject.type === 'diamond') {
                    let stats = JSON.parse(localStorage.getItem('gameStats')) || {};
                    stats.stat_diamond_fail = (stats.stat_diamond_fail || 0) + 1;
                    localStorage.setItem('gameStats', JSON.stringify(stats));
                }

                quizModal.classList.remove('active');
                gameState.isQuizActive = false;
                p1.status = STATE.RETRACTING_EMPTY;
                p1.grabbedObject = null;
                p1.grabbedObjectIdx = -1;
                p1.quizProgress = 0;
            } else {
                loadNewWord();
                const leftChances = 3 - gameState.currentMistakes;
                const indicator = document.getElementById('player-indicator');
                indicator.innerText = `Mining [${p1.grabbedObject.type.toUpperCase()}]... (Mistakes left: ${leftChances})`;
                indicator.style.color = '#e74c3c';
            }
        }, 700);
    }
}

function updateScoreDisplay() {
    const scoreElement = document.getElementById('p1-score');
    if (scoreElement && p1) {
        scoreElement.innerText = p1.score;
    }
}

/** --- 7. Input and pause control --- **/
window.addEventListener('keydown', (e) => {
    if (e.code === 'KeyP' || e.code === 'Escape') {
        togglePause();
        return;
    }
    if (gameState.isPaused || gameState.isQuizActive) return;
    if ((e.code === 'ArrowDown' || e.code === 'Space' || e.code === 'KeyS') && p1.status === STATE.SWINGING) {
        p1.status = STATE.EXTENDING;
    }
});

window.addEventListener('resize', () => {
    p1Canvas.width = window.innerWidth;
    p1Canvas.height = window.innerHeight;
    p1.minerPivotX = p1Canvas.width / 2;
    p1.reset();
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

// Start the game
loadAssets(() => { initGame(); });
