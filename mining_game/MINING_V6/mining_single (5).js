const currentMapId = sessionStorage.getItem("selectedMapId") || "map1";
const MINING_BOOTSTRAP = window.MINING_BOOTSTRAP || { balance: 0 };
const ACTIVE_OUTFIT_ENDPOINT = "/galgame/dress_up_game/api/get_active_outfit.php";
const dressUpLayerOrder = ["background","body","shoes","top","pants","dress","suit","eye","eyebrows","nose","mouse","hair","character","glass","head"];
const singleRunId = String(Date.now());
let singleRewardSequence = 0;
let mapClearSubmitted = false;
const currentMapName = sessionStorage.getItem("selectedMapName") || "新手森林";

let WORD_BANK = [];
let runCoinsEarned = 0;

function buildLookCandidates(layer) {
  const filePath = layer?.file_path || "";
  const normalized = filePath.startsWith("/") ? filePath : `/${filePath}`;
  return [`/galgame/dress_up_game${normalized}`, layer?.url || ""].filter(Boolean);
}

function setLookImage(img, candidates, index = 0) {
  if (!img || index >= candidates.length) {
    if (img) {
      img.remove();
    }
    return;
  }

  const candidate = candidates[index];
  img.onerror = () => setLookImage(img, candidates, index + 1);
  img.src = `${candidate}${candidate.includes("?") ? "&" : "?"}t=${Date.now()}`;
}

function renderMinerLook(data) {
  const stage = document.getElementById("minerLookStage");
  const empty = document.getElementById("minerLookEmpty");
  if (!stage || !empty) {
    return;
  }

  Array.from(stage.querySelectorAll(".miner-look-layer")).forEach((node) => node.remove());

  if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
    empty.style.display = "flex";
    empty.innerText = "No active look";
    return;
  }

  empty.style.display = "none";
  const layerMap = new Map();
  data.layers.forEach((layer) => {
    if (layer && layer.layer) {
      layerMap.set(layer.layer, layer);
    }
  });

  dressUpLayerOrder.forEach((layerName) => {
    if (layerName === "background") return;
    const layer = layerMap.get(layerName);
    if (!layer) return;
    const img = document.createElement("img");
    img.className = "miner-look-layer";
    img.alt = layer.name || layerName;
    stage.appendChild(img);
    setLookImage(img, buildLookCandidates(layer));
  });
}

async function loadMinerLook() {
  try {
    const response = await fetch(`${ACTIVE_OUTFIT_ENDPOINT}?_=${Date.now()}`, {
      cache: "no-store",
      credentials: "include",
    });
    const data = await response.json();
    renderMinerLook(data);
  } catch (error) {
    renderMinerLook(null);
  }
}

const ASSETS = {
  oil: "oil.png",
  diamond: "diamond.png",
  ruby: "ruby.png",
  emerald: "emerald.png",
  amethyst: "amethyst.png",
  gold: "gold.png",
  silver: "silver.png",
  coin: "coin.png",
  miner: "miner.png",
  hook: "hook.png",
  bg: currentMapId + "_bg.png", //
};

const IMAGES = {};
let loadedCount = 0;
const totalImages = Object.keys(ASSETS).length;

function loadAssets(callback) {
  if (totalImages === 0) return callback();
  for (let key in ASSETS) {
    IMAGES[key] = new Image();
    IMAGES[key].src = ASSETS[key];
    IMAGES[key].onload = () => {
      IMAGES[key].isReady = true;
      checkDone();
    };
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

const OBJECTS_CONFIG = {
  oil: {
    value: 5000,
    targetQuestions: 10,
    weight: 1.2,
    radius: 45,
    drawScale: 2.5,
  },
  diamond: {
    value: 2000,
    targetQuestions: 8,
    weight: 0.8,
    radius: 35,
    drawScale: 3.5,
  },
  ruby: {
    value: 1500,
    targetQuestions: 6,
    weight: 0.9,
    radius: 35,
    drawScale: 3.5,
  },
  emerald: {
    value: 1200,
    targetQuestions: 5,
    weight: 0.9,
    radius: 35,
    drawScale: 3.5,
  },
  amethyst: {
    value: 1000,
    targetQuestions: 4,
    weight: 1.0,
    radius: 35,
    drawScale: 3.5,
  },
  gold: {
    value: 600,
    targetQuestions: 3,
    weight: 1.5,
    radius: 40,
    drawScale: 3,
  },
  silver: {
    value: 400,
    targetQuestions: 2,
    weight: 1.3,
    radius: 35,
    drawScale: 3,
  },
  coin: {
    value: 200,
    targetQuestions: 1,
    weight: 0.5,
    radius: 25,
    drawScale: 3,
  },
};

const STATE = {
  SWINGING: 0,
  EXTENDING: 1,
  DIGGING_QUIZ: 2,
  RETRACTING_EMPTY: 3,
  RETRACTING_WITH_STONE: 4,
};

const p1Canvas = document.getElementById("p1Canvas");
const ctx1 = p1Canvas.getContext("2d");
const quizModal = document.getElementById("quiz-modal");
const gameContainer = document.getElementById("game-container");

let gameState = {
  isQuizActive: false,
  currentQuestion: null,
  currentLevel: 1,
  currentMistakes: 0,
  isPaused: false,
};
let p1;

async function postMiningAction(action, payload = {}) {
  const formData = new FormData();
  formData.append("action", action);
  Object.entries(payload).forEach(([key, value]) => {
    formData.append(key, String(value));
  });

  const response = await fetch("mining_coin_api.php", {
    method: "POST",
    body: formData,
  });
  const data = await response.json();
  if (!response.ok || !data.success) {
    throw new Error(data.message || data.error || "Mining request failed.");
  }
  return data;
}

async function syncSingleOreReward(oreType, amount) {
  singleRewardSequence += 1;
  const result = await postMiningAction("award_single_ore", {
    run_id: singleRunId,
    sequence: singleRewardSequence,
    map_key: currentMapId,
    ore_type: oreType,
    amount,
  });
  return Number(result.balance || 0);
}

async function submitMapClear() {
  if (mapClearSubmitted) {
    return;
  }
  mapClearSubmitted = true;

  try {
    await postMiningAction("clear_map", { map_key: currentMapId });
    alert(`Congratulations! ${currentMapName} cleared. You earned ${runCoinsEarned} coins in this run.`);
  } catch (error) {
    console.error("Failed to save map clear:", error);
    alert("Failed to save map clear progress.");
  } finally {
    window.location.href = "mining_map.php";
  }
}

function recordAchievement(oreId) {
  let unlockedList = JSON.parse(localStorage.getItem("myMineAchieve")) || [];
  if (!unlockedList.includes(oreId)) {
    unlockedList.push(oreId);
    localStorage.setItem("myMineAchieve", JSON.stringify(unlockedList));
    console.log(`✨unlock new  achievement!${oreId}`);
  }
}

class Player {
  constructor(id, canvas, ctx) {
    this.id = id;
    this.canvas = canvas;
    this.ctx = ctx;
    this.objects = [];

    this.score = Number(MINING_BOOTSTRAP.balance || 0);

    this.TOP_PANEL_HEIGHT = 160;
    this.minerPivotX = this.canvas.width / 2;
    this.minerPivotY = this.TOP_PANEL_HEIGHT;

    this.BASE_EXTENSION_SPEED = 12;
    this.BASE_RETRACT_SPEED = 18;
    this.SWING_SPEED = 0.008;
    this.HOOK_BASE_LEN = 80;

    this.hook = {
      angle: Math.PI / 2,
      dir: 1,
      length: this.HOOK_BASE_LEN,
      x: 0,
      y: 0,
    };
    this.status = STATE.SWINGING;
    this.grabbedObject = null;
    this.grabbedObjectIdx = -1;
    this.quizProgress = 0;
    this.isCollecting = false;
    this.updateHookCoords();
  }

  update() {
    if (this.status === STATE.SWINGING) {
      this.hook.angle += this.SWING_SPEED * this.hook.dir;
      if (this.hook.angle > Math.PI - 0.2 || this.hook.angle < 0.2)
        this.hook.dir *= -1;
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
      if (
        this.hook.y > this.canvas.height ||
        this.hook.x < 0 ||
        this.hook.x > this.canvas.width
      ) {
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
      if (this.hook.length <= this.HOOK_BASE_LEN && !this.isCollecting) {
        this.collect();
      }
    }
  }

  updateHookCoords() {
    this.hook.x =
      this.minerPivotX + Math.cos(this.hook.angle) * this.hook.length;
    this.hook.y =
      this.minerPivotY + Math.sin(this.hook.angle) * this.hook.length;
  }

  reset() {
    this.hook.length = this.HOOK_BASE_LEN;
    this.updateHookCoords();
    this.status = STATE.SWINGING;
    this.grabbedObject = null;
    this.grabbedObjectIdx = -1;
    this.quizProgress = 0;
    this.isCollecting = false;
  }

  async collect() {
    this.isCollecting = true;
    const oreType = this.grabbedObject.type;
    const oreValue = this.grabbedObject.value;

    recordAchievement(oreType);

    this.score += oreValue;
    this.objects.splice(this.grabbedObjectIdx, 1);

    let stats = JSON.parse(localStorage.getItem("gameStats")) || {};
    stats.stat_total_coins = (stats.stat_total_coins || 0) + oreValue;
    localStorage.setItem("gameStats", JSON.stringify(stats));
    runCoinsEarned += oreValue;

    updateScoreDisplay();

    try {
      const balance = await syncSingleOreReward(oreType, oreValue);
      this.score = balance;
      updateScoreDisplay();
    } catch (error) {
      console.error("Failed to sync ore reward:", error);
    }

    this.reset();
    checkLevelComplete();
  }
}

function initGame() {
  p1Canvas.width = window.innerWidth;
  p1Canvas.height = window.innerHeight;

  p1 = new Player(1, p1Canvas, ctx1);

  document.getElementById("map-name-display").innerText = `[${currentMapName}]`;
  updateScoreDisplay();

  generateObjects();
  gameLoop();
}

function generateObjects() {
  let pool = [];
  if (currentMapId === "map1") {
    pool = ["coin", "coin", "coin", "silver", "silver", "gold"];
  } else if (currentMapId === "map2") {
    pool = [
      "oil",
      "silver",
      "silver",
      "silver",
      "silver",
      "coin",
      "coin",
      "gold",
    ];
  } else if (currentMapId === "map3") {
    pool = ["gold", "gold", "gold", "gold", "coin", "oil", "ruby"];
  } else if (currentMapId === "map4") {
    pool = [
      "emerald",
      "emerald",
      "silver",
      "amethyst",
      "diamond",
      "ruby",
      "ruby",
      "emerald",
    ];
  } else if (currentMapId === "map5") {
    pool = ["diamond", "diamond", "amethyst", "amethyst", "silver"];
  } else {
    pool = ["coin", "silver", "gold"];
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
      ...config,
    });
  }
}

function checkLevelComplete() {
  if (p1.objects.length === 0) {
    gameState.isPaused = true;
    submitMapClear();
  }
}

function gameLoop() {
  if (!gameState.isPaused) p1.update();
  drawPlayerView(p1, p1.objects);
  requestAnimationFrame(gameLoop);
}

function drawPlayerView(p, objects) {
  const ctx = p.ctx;
  const pivotX = p.minerPivotX;
  const pivotY = p.minerPivotY;

  ctx.clearRect(0, 0, p.canvas.width, p.canvas.height);

  if (IMAGES.bg && IMAGES.bg.isReady) {
    ctx.drawImage(IMAGES.bg, 0, 0, p.canvas.width, p.canvas.height);
  } else {
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue(
      "--mine-dirt",
    );
    ctx.fillRect(0, 0, p.canvas.width, p.canvas.height);
  }

  objects.forEach((obj, idx) => {
    if (
      (p.status === STATE.DIGGING_QUIZ ||
        p.status === STATE.RETRACTING_WITH_STONE) &&
      idx === p.grabbedObjectIdx
    )
      return;
    drawGameObject(ctx, obj);
  });

  if (IMAGES.miner && IMAGES.miner.isReady) {
    ctx.save();
    ctx.translate(pivotX, pivotY);
    ctx.rotate(p.hook.angle - Math.PI / 2);
    const machineSize = 120;
    ctx.drawImage(
      IMAGES.miner,
      -machineSize / 2,
      -machineSize / 2,
      machineSize,
      machineSize,
    );

    const ropeStartX = 0;
    const ropeStartY = machineSize / 2 - 10;
    const ropeLength = p.hook.length - pivotY - ropeStartY;

    if (ropeLength > 0) {
      ctx.strokeStyle = "#5a3c26";
      ctx.lineWidth = 4;
      ctx.beginPath();
      ctx.moveTo(ropeStartX, ropeStartY);
      ctx.lineTo(0, ropeStartY + ropeLength);
      ctx.stroke();

      ctx.save();
      ctx.translate(0, ropeStartY + ropeLength);
      if (
        (p.status === STATE.DIGGING_QUIZ ||
          p.status === STATE.RETRACTING_WITH_STONE) &&
        p.grabbedObject
      ) {
        drawGameObject(ctx, { ...p.grabbedObject, x: 0, y: 35 });
      }
      if (IMAGES.hook && IMAGES.hook.isReady) {
        ctx.rotate(-Math.PI / 2);
        ctx.drawImage(IMAGES.hook, -25, -5, 50, 60);
      } else {
        ctx.fillStyle = "#7f8c8d";
        ctx.strokeStyle = "#5a3c26";
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
    ctx.drawImage(
      img,
      -drawRadius,
      -drawRadius,
      drawRadius * 2,
      drawRadius * 2,
    );
  } else {
    ctx.fillStyle = obj.color || "#ccc";
    ctx.beginPath();
    ctx.arc(0, 0, obj.radius, 0, Math.PI * 2);
    ctx.fill();
  }
  ctx.restore();
}

function triggerQuiz() {
  if (gameState.isQuizActive) return;
  gameState.isQuizActive = true;
  gameState.currentMistakes = 0;

  quizModal.classList.add("active");
  const indicator = document.getElementById("player-indicator");
  indicator.innerText = `mining [${p1.grabbedObject.type.toUpperCase()}]... (chance: 3次)`;
  indicator.style.color = "var(--p1-main)";
  loadNewWord();
}

function loadNewWord() {
  const word = WORD_BANK[Math.floor(Math.random() * WORD_BANK.length)];
  gameState.currentQuestion = word;
  document.getElementById("word-display").innerText = word.en;

  const btns = document.querySelectorAll(".opt-btn");
  word.cn.forEach((choice, i) => {
    btns[i].innerText = choice;
    btns[i].className = "opt-btn";
    btns[i].disabled = false;
  });

  const percent = (p1.quizProgress / p1.grabbedObject.targetQuestions) * 100;
  document.getElementById("progress-bar").style.width = percent + "%";
  document.getElementById("target-num").innerText =
    `${p1.quizProgress}/${p1.grabbedObject.targetQuestions}`;
}

function handleAnswer(selectedIndex) {
  if (!gameState.isQuizActive) return;
  const correctIndex = gameState.currentQuestion.a;
  const btns = document.querySelectorAll(".opt-btn");
  btns.forEach((b) => (b.disabled = true));

  if (selectedIndex === correctIndex) {
    btns[selectedIndex].classList.add("correct-flash");
    p1.quizProgress++;
    p1.hook.length -= 10;
    p1.updateHookCoords();

    if (p1.quizProgress >= p1.grabbedObject.targetQuestions) {
      setTimeout(() => {
        quizModal.classList.remove("active");
        gameState.isQuizActive = false;
        p1.status = STATE.RETRACTING_WITH_STONE;
      }, 400);
    } else {
      setTimeout(() => loadNewWord(), 300);
    }
  } else {
    btns[selectedIndex].classList.add("wrong-flash");
    btns[correctIndex].classList.add("correct-flash");
    gameContainer.classList.add("shake-animation");

    p1.hook.length += 20;
    p1.updateHookCoords();
    p1.quizProgress = Math.max(0, p1.quizProgress - 1);
    gameState.currentMistakes++;

    setTimeout(() => {
      gameContainer.classList.remove("shake-animation");
      if (gameState.currentMistakes >= 3) {
        if (p1.grabbedObject && p1.grabbedObject.type === "diamond") {
          let stats = JSON.parse(localStorage.getItem("gameStats")) || {};
          stats.stat_diamond_fail = (stats.stat_diamond_fail || 0) + 1;
          localStorage.setItem("gameStats", JSON.stringify(stats));
        }

        quizModal.classList.remove("active");
        gameState.isQuizActive = false;
        p1.status = STATE.RETRACTING_EMPTY;
        p1.grabbedObject = null;
        p1.grabbedObjectIdx = -1;
        p1.quizProgress = 0;
      } else {
        loadNewWord();
        const leftChances = 3 - gameState.currentMistakes;
        const indicator = document.getElementById("player-indicator");
        indicator.innerText = `mining [${p1.grabbedObject.type.toUpperCase()}]... (chance: ${leftChances}times)`;
        indicator.style.color = "#e74c3c";
      }
    }, 700);
  }
}

function updateScoreDisplay() {
  const scoreElement = document.getElementById("p1-score");
  if (scoreElement && p1) {
    scoreElement.innerText = p1.score;
  }
}

window.addEventListener("keydown", (e) => {
  if (e.code === "KeyP" || e.code === "Escape") {
    togglePause();
    return;
  }
  if (gameState.isPaused || gameState.isQuizActive) return;
  if (
    (e.code === "ArrowDown" || e.code === "Space" || e.code === "KeyS") &&
    p1.status === STATE.SWINGING
  ) {
    p1.status = STATE.EXTENDING;
  }
});

window.addEventListener("resize", () => {
  p1Canvas.width = window.innerWidth;
  p1Canvas.height = window.innerHeight;
  p1.minerPivotX = p1Canvas.width / 2;
  p1.reset();
});

const pauseOverlay = document.getElementById("pause-overlay");
const pauseBtn = document.getElementById("pause-btn");
const resumeBtn = document.getElementById("resume-btn");

function togglePause() {
  gameState.isPaused = !gameState.isPaused;
  if (gameState.isPaused) pauseOverlay.classList.add("active");
  else pauseOverlay.classList.remove("active");
}

pauseBtn.addEventListener("click", togglePause);
resumeBtn.addEventListener("click", togglePause);

async function fetchWordsAndStart() {
  try {
    console.log("loading words...");
    const response = await fetch("api_get_words.php");
    const data = await response.json();

    if (data.error) {
      alert("loading error: " + data.error);
      return;
    }

    WORD_BANK = data;
    console.log("Success，words number:", WORD_BANK.length);

    loadAssets(() => {
      initGame();
    });
  } catch (error) {
    console.error("Fail loading:", error);
    alert("Network is broken");
  }
}

fetchWordsAndStart();
loadMinerLook();
