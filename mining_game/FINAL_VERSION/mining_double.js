const SERVER_URL = "https://dundeestudy.xyz";
//
// API endpoint used to fetch the player's currently active outfit
const ACTIVE_OUTFIT_ENDPOINT = "/galgame/dress_up_game/api/get_active_outfit.php";

// LocalStorage key for saving BGM on/off state in double-player mode
const BGM_STORAGE_KEY = "mining_double_bgm_enabled";

// Current loading progress value shown on the loading screen
let loadingProgress = 0;

// The drawing order of outfit layers, from bottom to top
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

// Stores which side this player is on and outfit data for both players
let mySide = null;
let myOutfitData = null;
let roomOutfitsCache = { left: null, right: null };

// Build possible image paths for one outfit layer
function buildLookCandidates(layer) {
  const filePath = layer?.file_path || "";
  const normalized = filePath.startsWith("/") ? filePath : `/${filePath}`;
  return [`/galgame/dress_up_game${normalized}`, layer?.url || ""].filter(Boolean);
}

// Try loading an outfit image; if one path fails, try the next one
function setLookImage(img, candidates, index = 0) {
  if (!img || index >= candidates.length) {
    if (img) img.remove();
    return;
  }
  const candidate = candidates[index];
  img.onerror = () => setLookImage(img, candidates, index + 1);
  img.src = `${candidate}${candidate.includes("?") ? "&" : "?"}t=${Date.now()}`;
}

// Render one player's full outfit onto the preview stage
function renderLookToStage(stageId, emptyId, data) {
  const stage = document.getElementById(stageId);
  const empty = document.getElementById(emptyId);
  if (!stage || !empty) return;

  // Remove previously rendered outfit layers first
  Array.from(stage.querySelectorAll(".miner-look-layer")).forEach((node) => node.remove());

  // If no valid outfit data exists, show fallback text
  if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
    empty.style.display = "flex";
    empty.innerText = "No active look";
    return;
  }

  empty.style.display = "none";

  // Convert layer array into a map for faster lookup
  const layerMap = new Map();
  data.layers.forEach((layer) => {
    if (layer && layer.layer) layerMap.set(layer.layer, layer);
  });

  // Render outfit layers in the correct visual order
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

// Render both players' outfit previews in the room
function renderRoomOutfits(roomOutfits) {
  renderLookToStage("leftMinerLookStage", "leftMinerLookEmpty", roomOutfits?.left || null);
  renderLookToStage("rightMinerLookStage", "rightMinerLookEmpty", roomOutfits?.right || null);
}

// Load this player's currently active outfit from the dress-up system
async function loadMyOutfit() {
  try {
    const response = await fetch(`${ACTIVE_OUTFIT_ENDPOINT}?_=${Date.now()}`, {
      cache: "no-store",
      credentials: "include",
    });
    const data = await response.json();
    myOutfitData = data;
    return data;
  } catch (error) {
    myOutfitData = null;
    return null;
  }
}

// Apply this player's outfit to the local preview cache
function applyMyOutfitLocally() {
  if (!mySide) return;

  if (mySide === "left") {
    roomOutfitsCache.left = myOutfitData;
  } else {
    roomOutfitsCache.right = myOutfitData;
  }

  renderRoomOutfits(roomOutfitsCache);
}

// Send this player's outfit data to the room through socket
function sendMyOutfitToRoom() {
  if (!roomId || !mySide || !myOutfitData) return;

  socket.emit("submitOutfit", {
    roomId,
    side: mySide,
    outfit: myOutfitData,
  });
}
///
// Connect to the game server through socket.io
const socket = io(SERVER_URL);

// Read room data and shared gem layout from sessionStorage
const roomId = sessionStorage.getItem("matchRoomId");
const sharedGemsData = JSON.parse(sessionStorage.getItem("sharedGems"));

// If required match data is missing, return the player to matchmaking
if (!roomId || !sharedGemsData) {
  alert("Match data is missing. Please enter matchmaking again.");
  window.location.href = "mining_match.php";
}

// Show room ID on the page
document.getElementById("room-id-display").innerText = roomId;

// Opponent hook position/state cache
let oppoPivotX = 0;
let oppoHook = { angle: Math.PI / 2, length: 80, grabbedGemId: null };

// Update loading progress bar and percentage text
function setLoadingProgress(value) {
  const fill = document.getElementById("loading-bar-fill");
  const percent = document.getElementById("loading-percent");
  loadingProgress = Math.max(0, Math.min(100, value));

  if (fill) {
    fill.style.width = `${loadingProgress}%`;
  }
  if (percent) {
    percent.innerText = `${Math.floor(loadingProgress)}%`;
  }
}

// Update subtitle text on the loading screen
function setLoadingSubtitle(text) {
  const subtitle = document.getElementById("loading-subtitle");
  if (subtitle) {
    subtitle.innerText = text;
  }
}

// Hide loading screen after everything is ready
function hideLoadingScreen() {
  const loadingScreen = document.getElementById("loading-screen");
  if (loadingScreen) {
    loadingScreen.classList.add("hidden");
  }
}

// Update the BGM toggle button text
function updateBgmButton(enabled) {
  const btn = document.getElementById("bgm-toggle");
  if (btn) {
    btn.innerText = enabled ? "BGM ON" : "BGM OFF";
  }
}

// Play or stop BGM based on current state
async function applyBgmState(enabled) {
  const player = document.getElementById("bgm-player");
  if (!player) return;

  updateBgmButton(enabled);

  if (enabled) {
    try {
      await player.play();
    } catch (error) {
      console.warn("Autoplay blocked by browser:", error);
    }
  } else {
    player.pause();
    player.currentTime = 0;
  }
}

// Initialize BGM button and restore saved BGM state
function initBgmControl() {
  const btn = document.getElementById("bgm-toggle");
  const saved = localStorage.getItem(BGM_STORAGE_KEY);
  const enabled = saved !== "0";

  updateBgmButton(enabled);
  applyBgmState(enabled);

  if (btn) {
    btn.addEventListener("click", async () => {
      const current = localStorage.getItem(BGM_STORAGE_KEY) !== "0";
      const next = !current;
      localStorage.setItem(BGM_STORAGE_KEY, next ? "1" : "0");
      await applyBgmState(next);
      btn.blur();
    });

    // Remove default focus from the button after page load
    setTimeout(() => btn.blur(), 0);
  }
}

// Word list loaded from backend for quiz questions
let WORD_BANK = [];

// Static asset file names used in this page
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

// Configuration data for each collectible object type
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

// Image cache and loading state
const IMAGES = {};
let loadedCount = 0;
const totalImages = Object.keys(ASSETS).length;

// Preload all image assets before starting the game
function loadAssets(callback) {
  if (totalImages === 0) {
    setLoadingProgress(90);
    callback();
    return;
  }

  setLoadingSubtitle("Loading battle assets...");

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
    const percent = 45 + (loadedCount / totalImages) * 45;
    setLoadingProgress(percent);

    if (loadedCount === totalImages) {
      callback();
    }
  }
}

// Different states of the player's hook/game flow
const STATE = {
  SWINGING: 0,
  EXTENDING: 1,
  DIGGING_QUIZ: 2,
  RETRACTING_EMPTY: 3,
  RETRACTING_WITH_STONE: 4,
};

// Main canvas and quiz modal references
const canvas = document.getElementById("doubleCanvas");
const ctx = canvas.getContext("2d");
const quizModal = document.getElementById("quiz-modal");

// Runtime state for the quiz system
let gameState = {
  isQuizActive: false,
  currentQuestion: null,
  currentMistakes: 0,
};

// Current player object
let p1;

// Player class for controlling one miner and hook
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

  // Update hook movement and gameplay state every frame
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

      // Check whether the hook touches any available object
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

      // If hook goes out of boundary, retract without anything
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

    // Synchronize this player's hook state with the server
    socket.emit("syncHook", {
      roomId,
      hookData: {
        angle: this.hook.angle,
        length: this.hook.length,
        grabbedGemId: this.grabbedObject ? this.grabbedObject.serverId : null,
      },
    });
  }

  // Recalculate hook endpoint position from angle and length
  updateHookCoords() {
    this.hook.x = this.minerPivotX + Math.cos(this.hook.angle) * this.hook.length;
    this.hook.y = this.minerPivotY + Math.sin(this.hook.angle) * this.hook.length;
  }

  // Reset hook and player state after one grab action ends
  reset() {
    this.hook.length = this.HOOK_BASE_LEN;
    this.updateHookCoords();
    this.status = STATE.SWINGING;
    this.grabbedObject = null;
    this.quizProgress = 0;
  }

  // Notify server that a gem has been successfully collected
  collect() {
    socket.emit("collectGem", {
      roomId,
      gemId: this.grabbedObject.serverId,
      value: this.grabbedObject.value,
    });
    this.reset();
  }
}

// Main animation loop
function gameLoop() {
  p1.update();
  drawPlayerView();
  requestAnimationFrame(gameLoop);
}

// Draw the entire current game view on canvas
function drawPlayerView() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  if (IMAGES.bg && IMAGES.bg.isReady) {
    ctx.drawImage(IMAGES.bg, 0, 0, canvas.width, canvas.height);
  } else {
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue("--mine-dirt");
    ctx.fillRect(0, 0, canvas.width, canvas.height);
  }

  // Draw all mine objects except the one currently grabbed
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

  // Shared function for drawing one miner, rope, hook, and grabbed item
  function drawMiner(pivotX, pivotY, hookAngle, hookLength, grabbedGemId) {
    if (!IMAGES.miner || !IMAGES.miner.isReady) return;

    // ==============================
    // 1. Draw the miner machine itself without rotation
    // ==============================
    ctx.save();
    ctx.translate(pivotX, pivotY);
    
    // Darker effect for the opponent side
    if (pivotX === oppoPivotX) {
      ctx.filter = "brightness(0.7)";
    }
    ctx.drawImage(IMAGES.miner, -60, -60, 120, 120);
    ctx.filter = "none";
    ctx.restore();

    // ==============================
    // 2. Draw the rope and hook with rotation
    // ==============================
    ctx.save();
    ctx.translate(pivotX, pivotY);
    ctx.rotate(hookAngle - Math.PI / 2); // Only rope and hook rotate

    const ropeStartY = 40;        // Rope starts slightly inside the miner body
    const ropeEndY = hookLength;  // Rope length follows the current hook length

    if (ropeEndY > ropeStartY) {
      // Draw rope
      ctx.strokeStyle = "#5a3c26";
      ctx.lineWidth = 4;
      ctx.beginPath();
      ctx.moveTo(0, ropeStartY);
      ctx.lineTo(0, ropeEndY);
      ctx.stroke();

      // Draw hook and the object being grabbed
      ctx.save();
      ctx.translate(0, ropeEndY);

      // Draw grabbed gem if there is one
      if (grabbedGemId !== null) {
        const obj = p1.objects.find((gem) => gem.serverId === grabbedGemId);
        if (obj && IMAGES[obj.type]) {
          const r = obj.radius * (obj.drawScale || 1);
          ctx.drawImage(IMAGES[obj.type], -r, 35 - r, r * 2, r * 2);
        }
      }

      // Draw hook image if available, otherwise draw a fallback hook shape
      if (IMAGES.hook && IMAGES.hook.isReady) {
        ctx.rotate(-Math.PI / 2);
        ctx.drawImage(IMAGES.hook, -25, -5, 50, 60);
      } else {
        ctx.fillStyle = "#7f8c8d";
        ctx.strokeStyle = "#5a3c26";
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(-10, -5); ctx.lineTo(-20, 20); ctx.lineTo(-12, 35);
        ctx.lineTo(-18, 55); ctx.lineTo(-10, 50); ctx.lineTo(-5, 40);
        ctx.lineTo(-10, 30); ctx.lineTo(-5, 20); ctx.lineTo(0, -5);
        ctx.moveTo(10, -5); ctx.lineTo(20, 20); ctx.lineTo(12, 35);
        ctx.lineTo(18, 55); ctx.lineTo(10, 50); ctx.lineTo(5, 40);
        ctx.lineTo(10, 30); ctx.lineTo(5, 20); ctx.lineTo(0, -5);
        ctx.fill();
        ctx.stroke();
      }

      ctx.restore();
    }

    ctx.restore();
  }

  // Render this player's miner
  drawMiner(
    p1.minerPivotX,
    p1.minerPivotY,
    p1.hook.angle,
    p1.hook.length,
    p1.grabbedObject ? p1.grabbedObject.serverId : null,
  );

  // Render opponent's miner
  drawMiner(
    oppoPivotX,
    p1.minerPivotY,
    oppoHook.angle,
    oppoHook.length,
    oppoHook.grabbedGemId,
  );
}

// Open the quiz panel after grabbing a mine object
function triggerQuiz() {
  if (gameState.isQuizActive) return;
  gameState.isQuizActive = true;
  gameState.currentMistakes = 0;
  quizModal.classList.add("active");
  document.getElementById("player-indicator").innerText =
    `Mining [${p1.grabbedObject.type.toUpperCase()}]...`;
  loadNewWord();
}

// Load one new vocabulary question into the quiz
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

// Handle player's answer choice in the quiz
function handleAnswer(selectedIndex) {
  if (!gameState.isQuizActive) return;

  const correctIndex = gameState.currentQuestion.a;
  const btns = document.querySelectorAll(".opt-btn");
  btns.forEach((btn) => {
    btn.disabled = true;
  });

  if (selectedIndex === correctIndex) {
    // Correct answer: increase progress and pull hook a bit inward
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
    // Wrong answer: push hook outward and count one mistake
    btns[selectedIndex].classList.add("wrong-flash");
    btns[correctIndex].classList.add("correct-flash");
    p1.hook.length += 20;
    p1.updateHookCoords();
    p1.quizProgress = Math.max(0, p1.quizProgress - 1);
    gameState.currentMistakes += 1;

    setTimeout(() => {
      if (gameState.currentMistakes >= 3) {
        // Too many mistakes: fail the grab and free the gem
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

// Called after server assigns this player to left or right side
socket.on("playerAssigned", async (index) => {
  setLoadingSubtitle("Joining battle room...");
  setLoadingProgress(15);

  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  mySide = index === 0 ? "left" : "right";
  p1 = new Player(index === 0);

  setLoadingSubtitle("Loading player outfit...");
  setLoadingProgress(22);

  await loadMyOutfit();
  applyMyOutfitLocally();
  sendMyOutfitToRoom();

  // Convert shared gem data from sessionStorage into runtime objects
  p1.objects = sharedGemsData.map((g) => ({
    serverId: g.id,
    x: g.rx * canvas.width,
    y: g.ry * canvas.height,
    type: g.type,
    status: g.status,
    ...OBJECTS_CONFIG[g.type],
  }));

  try {
    setLoadingSubtitle("Loading word bank...");
    setLoadingProgress(32);

    const response = await fetch("api_get_words.php");
    const data = await response.json();

    if (data.error) {
      alert("词库加载错误: " + data.error);
    } else {
      WORD_BANK = data;
    }
  } catch (error) {
    console.error("请求词库失败:", error);
    alert("网络请求失败，请检查 api_get_words.php 是否正常工作！");
  }

  // Load game assets, then start the main game loop
  loadAssets(() => {
    setLoadingSubtitle("Entering battle...");
    setLoadingProgress(100);

    gameLoop();

    setTimeout(() => {
      hideLoadingScreen();
    }, 350);
  });
});

// Receive opponent hook synchronization data
socket.on("oppoHook", (data) => {
  oppoHook = data;
});

// Mark a gem as locked when the other player grabs it first
socket.on("gemLocked", (gemId) => {
  const gem = p1.objects.find((g) => g.serverId === gemId);
  if (gem) {
    gem.status = "locked";
  }
});

// Mark a gem as free again if the other player fails to collect it
socket.on("gemFreed", (gemId) => {
  const gem = p1.objects.find((g) => g.serverId === gemId);
  if (gem) {
    gem.status = "idle";
  }
});

// Update scores and remove collected gems after server sync
socket.on("updateGame", (data) => {
  p1.objects = p1.objects.filter((g) => g.serverId !== data.gemId);
  const oppoId = Object.keys(data.scores).find((id) => id !== socket.id);
  document.getElementById("my-score").innerText = data.scores[socket.id]?.score || 0;
  if (oppoId) {
    document.getElementById("oppo-score").innerText = data.scores[oppoId]?.score || 0;
  }
});

// Receive outfit data for both players in the room
socket.on("roomOutfits", (payload) => {//double_dress
  roomOutfitsCache = {
    left: payload?.left || null,
    right: payload?.right || null,
  };
  renderRoomOutfits(roomOutfitsCache);
});

// Update countdown timer during the match
socket.on("timeUpdate", (time) => {
  document.getElementById("game-timer").innerText = time;
});

// Handle the end of the game and reward settlement
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
          // Record one PvP win in local achievement stats
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

  // Show final result and send player back to the main mining page
  setTimeout(() => {
    alert(`Battle finished.\n${msg}\nScore: ${data.p1Score} : ${data.p2Score}`);
    window.location.href = "mining_index.php";
  }, 1000);
});

// Keyboard control for launching the hook
window.addEventListener("keydown", (e) => {
  if (gameState.isQuizActive || !p1) return;

  if (e.code === "ArrowDown" || e.code === "Space" || e.code === "KeyS") {
    e.preventDefault();

    const bgmBtn = document.getElementById("bgm-toggle");
    if (bgmBtn) {
      bgmBtn.blur();
    }

    if (p1.status === STATE.SWINGING) {
      p1.status = STATE.EXTENDING;
    }
  }
});

// Initial loading screen setup before socket events finish
setLoadingProgress(5);
setLoadingSubtitle("Preparing battle...");
initBgmControl();

// Rejoin the game room when socket reconnects
socket.on("connect", () => {
  socket.emit("rejoinGame", roomId);

  if (mySide && myOutfitData) {
    socket.emit("submitOutfit", {
      roomId,
      side: mySide,
      outfit: myOutfitData,
    });
  }
});