const SERVER_URL = "http://8.162.9.154:3000";

const ACTIVE_OUTFIT_ENDPOINT = "/galgame/dress_up_game/api/get_active_outfit.php";
const dressUpLayerOrder = [
  "background",
  "body",
  "shoes",
  "top",
  "pants",
  "dress",
  "suit",
  "eye",
  "eyebrows",
  "nose",
  "mouse",
  "hair",
  "character",
  "glass",
  "head",
];

function buildLookCandidates(layer) {
  const filePath = layer?.file_path || "";
  const normalized = filePath.startsWith("/") ? filePath : `/${filePath}`;
  return [`/galgame/dress_up_game${normalized}`, layer?.url || ""].filter(Boolean);
}

function setLookImage(img, candidates, index = 0) {
  if (!img || index >= candidates.length) {
    if (img) img.remove();
    return;
  }

  const candidate = candidates[index];
  img.onerror = () => setLookImage(img, candidates, index + 1);
  img.src = `${candidate}${candidate.includes("?") ? "&" : "?"}t=${Date.now()}`;
}

function renderLookToStage(stageId, emptyId, data) {
  const stage = document.getElementById(stageId);
  const empty = document.getElementById(emptyId);
  if (!stage || !empty) return;

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

async function loadMyMinerLook() {
  try {
    const response = await fetch(`${ACTIVE_OUTFIT_ENDPOINT}?_=${Date.now()}`, {
      cache: "no-store",
      credentials: "include",
    });
    const data = await response.json();
    renderLookToStage("myMinerLookStage", "myMinerLookEmpty", data);
  } catch (error) {
    renderLookToStage("myMinerLookStage", "myMinerLookEmpty", null);
  }
}

function applyLookSide(isLeft) {
  const myCard = document.getElementById("myMinerLookCard");
  if (!myCard) return;

  myCard.classList.remove("is-left", "is-right");
  myCard.classList.add(isLeft ? "is-left" : "is-right");
}
const socket = io(SERVER_URL);
const roomId = sessionStorage.getItem("matchRoomId");
const sharedGemsData = JSON.parse(sessionStorage.getItem("sharedGems"));

if (!roomId || !sharedGemsData) {
  alert("Match data is missing. Please enter matchmaking again.");
  window.location.href = "mining_match.php";
}

document.getElementById("room-id-display").innerText = roomId;

let oppoPivotX = 0;
let oppoHook = { angle: Math.PI / 2, length: 80, grabbedGemId: null };

let WORD_BANK = [];

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
  bg: "bg.png",
};

const OBJECTS_CONFIG = {
  oil: { value: 5000, targetQuestions: 10, weight: 1.2, radius: 45, drawScale: 2.5 },
  diamond: { value: 2000, targetQuestions: 8, weight: 0.8, radius: 35, drawScale: 3.5 },
  ruby: { value: 1500, targetQuestions: 6, weight: 0.9, radius: 35, drawScale: 3.5 },
  emerald: { value: 1200, targetQuestions: 5, weight: 0.9, radius: 35, drawScale: 3.5 },
  amethyst: { value: 1000, targetQuestions: 4, weight: 1.0, radius: 35, drawScale: 3.5 },
  gold: { value: 600, targetQuestions: 3, weight: 1.5, radius: 40, drawScale: 3 },
  silver: { value: 400, targetQuestions: 2, weight: 1.3, radius: 35, drawScale: 3 },
  coin: { value: 200, targetQuestions: 1, weight: 0.5, radius: 25, drawScale: 3 },
};

const IMAGES = {};
let loadedCount = 0;
const totalImages = Object.keys(ASSETS).length;

function loadAssets(callback) {
  if (totalImages === 0) return callback();
  for (const key in ASSETS) {
    IMAGES[key] = new Image();
    IMAGES[key].src = ASSETS[key];
    IMAGES[key].onload = () => {
      IMAGES[key].isReady = true;
      checkDone();
    };
    IMAGES[key].onerror = () => {
      IMAGES[key].isReady = false;
      checkDone();
    };
  }

  function checkDone() {
    loadedCount += 1;
    if (loadedCount === totalImages) {
      callback();
    }
  }
}

const STATE = {
  SWINGING: 0,
  EXTENDING: 1,
  DIGGING_QUIZ: 2,
  RETRACTING_EMPTY: 3,
  RETRACTING_WITH_STONE: 4,
};

const canvas = document.getElementById("doubleCanvas");
const ctx = canvas.getContext("2d");
const quizModal = document.getElementById("quiz-modal");

let gameState = {
  isQuizActive: false,
  currentQuestion: null,
  currentMistakes: 0,
};

let p1;

class Player {
  constructor(isLeft) {
    this.TOP_PANEL_HEIGHT = 160;
    this.minerPivotX = canvas.width * (isLeft ? 0.3 : 0.7);
    this.minerPivotY = this.TOP_PANEL_HEIGHT;
    oppoPivotX = canvas.width * (isLeft ? 0.7 : 0.3);

    this.BASE_EXTENSION_SPEED = 14;
    this.BASE_RETRACT_SPEED = 20;
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
    this.quizProgress = 0;
    this.objects = [];
    this.updateHookCoords();
  }

  update() {
    if (this.status === STATE.SWINGING) {
      this.hook.angle += this.SWING_SPEED * this.hook.dir;
      if (this.hook.angle > Math.PI - 0.2 || this.hook.angle < 0.2) {
        this.hook.dir *= -1;
      }
      this.updateHookCoords();
    } else if (this.status === STATE.EXTENDING) {
      this.hook.length += this.BASE_EXTENSION_SPEED;
      this.updateHookCoords();

      for (const obj of this.objects) {
        const dist = Math.hypot(this.hook.x - obj.x, this.hook.y - obj.y);
        if (
          dist < obj.radius + 15 &&
          obj.status === "idle" &&
          oppoHook.grabbedGemId !== obj.serverId
        ) {
          this.status = STATE.DIGGING_QUIZ;
          this.grabbedObject = obj;
          obj.status = "locked";
          socket.emit("lockGem", { roomId, gemId: obj.serverId });
          triggerQuiz();
          return;
        }
      }

      if (this.hook.y > canvas.height || this.hook.x < 0 || this.hook.x > canvas.width) {
        this.status = STATE.RETRACTING_EMPTY;
      }
    } else if (this.status === STATE.RETRACTING_EMPTY) {
      this.hook.length -= this.BASE_RETRACT_SPEED;
      this.updateHookCoords();
      if (this.hook.length <= this.HOOK_BASE_LEN) {
        this.reset();
      }
    } else if (this.status === STATE.RETRACTING_WITH_STONE) {
      const pullSpeed = this.BASE_RETRACT_SPEED / this.grabbedObject.weight;
      this.hook.length -= pullSpeed;
      this.updateHookCoords();
      if (this.hook.length <= this.HOOK_BASE_LEN) {
        this.collect();
      }
    }

    socket.emit("syncHook", {
      roomId,
      hookData: {
        angle: this.hook.angle,
        length: this.hook.length,
        grabbedGemId: this.grabbedObject ? this.grabbedObject.serverId : null,
      },
    });
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
    this.quizProgress = 0;
  }

  collect() {
    socket.emit("collectGem", {
      roomId,
      gemId: this.grabbedObject.serverId,
      value: this.grabbedObject.value,
    });
    this.reset();
  }
}

function gameLoop() {
  p1.update();
  drawPlayerView();
  requestAnimationFrame(gameLoop);
}

function drawPlayerView() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  if (IMAGES.bg && IMAGES.bg.isReady) {
    ctx.drawImage(IMAGES.bg, 0, 0, canvas.width, canvas.height);
  } else {
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue("--mine-dirt");
    ctx.fillRect(0, 0, canvas.width, canvas.height);
  }

  p1.objects.forEach((obj) => {
    if (p1.grabbedObject && obj.serverId === p1.grabbedObject.serverId) return;
    if (oppoHook.grabbedGemId === obj.serverId) return;

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
  });

  function drawMiner(pivotX, pivotY, hookAngle, hookLength, grabbedGemId) {
    if (!IMAGES.miner || !IMAGES.miner.isReady) return;

    ctx.save();
    ctx.translate(pivotX, pivotY);
    ctx.rotate(hookAngle - Math.PI / 2);

    if (pivotX === oppoPivotX) {
      ctx.filter = "brightness(0.7)";
    }
    ctx.drawImage(IMAGES.miner, -60, -60, 120, 120);
    ctx.filter = "none";

    const ropeLength = hookLength - pivotY - 50;
    if (ropeLength > 0) {
      ctx.strokeStyle = "#5a3c26";
      ctx.lineWidth = 4;
      ctx.beginPath();
      ctx.moveTo(0, 50);
      ctx.lineTo(0, 50 + ropeLength);
      ctx.stroke();

      ctx.save();
      ctx.translate(0, 50 + ropeLength);

      if (grabbedGemId !== null) {
        const obj = p1.objects.find((gem) => gem.serverId === grabbedGemId);
        if (obj && IMAGES[obj.type]) {
          const r = obj.radius * (obj.drawScale || 1);
          ctx.drawImage(IMAGES[obj.type], -r, 35 - r, r * 2, r * 2);
        }
      }

      if (IMAGES.hook && IMAGES.hook.isReady) {
        ctx.rotate(-Math.PI / 2);
        ctx.drawImage(IMAGES.hook, -25, -5, 50, 60);
      }

      ctx.restore();
    }

    ctx.restore();
  }

  drawMiner(
    p1.minerPivotX,
    p1.minerPivotY,
    p1.hook.angle,
    p1.hook.length,
    p1.grabbedObject ? p1.grabbedObject.serverId : null,
  );

  drawMiner(
    oppoPivotX,
    p1.minerPivotY,
    oppoHook.angle,
    oppoHook.length,
    oppoHook.grabbedGemId,
  );
}

function triggerQuiz() {
  if (gameState.isQuizActive) return;
  gameState.isQuizActive = true;
  gameState.currentMistakes = 0;
  quizModal.classList.add("active");
  document.getElementById("player-indicator").innerText =
    `Mining [${p1.grabbedObject.type.toUpperCase()}]...`;
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
  btns.forEach((btn) => {
    btn.disabled = true;
  });

  if (selectedIndex === correctIndex) {
    btns[selectedIndex].classList.add("correct-flash");
    p1.quizProgress += 1;
    p1.hook.length -= 10;
    p1.updateHookCoords();

    if (p1.quizProgress >= p1.grabbedObject.targetQuestions) {
      setTimeout(() => {
        quizModal.classList.remove("active");
        gameState.isQuizActive = false;
        p1.status = STATE.RETRACTING_WITH_STONE;
      }, 300);
    } else {
      setTimeout(() => loadNewWord(), 300);
    }
  } else {
    btns[selectedIndex].classList.add("wrong-flash");
    btns[correctIndex].classList.add("correct-flash");
    p1.hook.length += 20;
    p1.updateHookCoords();
    p1.quizProgress = Math.max(0, p1.quizProgress - 1);
    gameState.currentMistakes += 1;

    setTimeout(() => {
      if (gameState.currentMistakes >= 3) {
        quizModal.classList.remove("active");
        gameState.isQuizActive = false;
        p1.status = STATE.RETRACTING_EMPTY;
        socket.emit("freeGem", { roomId, gemId: p1.grabbedObject.serverId });
        p1.grabbedObject = null;
        p1.quizProgress = 0;
      } else {
        loadNewWord();
      }
    }, 600);
  }
}

socket.on("playerAssigned", async (index) => {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  const isLeft = index === 0;
  p1 = new Player(isLeft);

  applyLookSide(isLeft);
  loadMyMinerLook();

  p1.objects = sharedGemsData.map((g) => ({
    serverId: g.id,
    x: g.rx * canvas.width,
    y: g.ry * canvas.height,
    type: g.type,
    status: g.status,
    ...OBJECTS_CONFIG[g.type],
  }));

  try {
    console.log("正在从数据库加载双人对战词库...");
    const response = await fetch("api_get_words.php");
    const data = await response.json();

    if (data.error) {
      alert("词库加载错误: " + data.error);
    } else {
      WORD_BANK = data;
      console.log("成功加载对战词库，单词数量:", WORD_BANK.length);
    }
  } catch (error) {
    console.error("请求词库失败:", error);
    alert("网络请求失败，请检查 api_get_words.php 是否正常工作！");
  }

  loadAssets(() => {
    gameLoop();
  });
});

socket.on("oppoHook", (data) => {
  oppoHook = data;
});

socket.on("gemLocked", (gemId) => {
  const gem = p1.objects.find((g) => g.serverId === gemId);
  if (gem) {
    gem.status = "locked";
  }
});

socket.on("gemFreed", (gemId) => {
  const gem = p1.objects.find((g) => g.serverId === gemId);
  if (gem) {
    gem.status = "idle";
  }
});

socket.on("updateGame", (data) => {
  p1.objects = p1.objects.filter((g) => g.serverId !== data.gemId);
  const oppoId = Object.keys(data.scores).find((id) => id !== socket.id);
  document.getElementById("my-score").innerText = data.scores[socket.id]?.score || 0;
  if (oppoId) {
    document.getElementById("oppo-score").innerText = data.scores[oppoId]?.score || 0;
  }
});

socket.on("timeUpdate", (time) => {
  document.getElementById("game-timer").innerText = time;
});

socket.on("gameOver", async (data) => {
  let msg = "";

  if (data.winnerId === socket.id) {
    try {
      const formData = new FormData();
      formData.append("action", "award_pvp_win");
      formData.append("room_id", roomId);

      const response = await fetch("mining_coin_api.php", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();

      if (response.ok && result.success) {
        if (Number(result.reward_amount || 0) > 0) {
          const stats = JSON.parse(localStorage.getItem("gameStats")) || {};
          stats.stat_double_wins = (stats.stat_double_wins || 0) + 1;
          localStorage.setItem("gameStats", JSON.stringify(stats));
          msg = `You win and receive ${result.reward_amount} coins!`;
        } else {
          msg = "You win! This room reward has already been claimed.";
        }
      } else {
        msg = "You win, but reward settlement failed.";
      }
    } catch (error) {
      console.error("Failed to settle PvP reward:", error);
      msg = "You win, but reward settlement failed.";
    }
  } else if (data.winnerId === null) {
    msg = "Draw. No coin reward.";
  } else {
    msg = "You lost. No coin reward.";
  }

  setTimeout(() => {
    alert(`Battle finished.\n${msg}\nScore: ${data.p1Score} : ${data.p2Score}`);
    window.location.href = "mining_index.php";
  }, 1000);
});

window.addEventListener("keydown", (e) => {
  if (gameState.isQuizActive || !p1) return;
  if (
    (e.code === "ArrowDown" || e.code === "Space" || e.code === "KeyS") &&
    p1.status === STATE.SWINGING
  ) {
    p1.status = STATE.EXTENDING;
  }
});

socket.on("connect", () => {
  socket.emit("rejoinGame", roomId);
});
