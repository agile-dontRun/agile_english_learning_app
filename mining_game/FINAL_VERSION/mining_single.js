// Get the currently selected map from sessionStorage
const currentMapId = sessionStorage.getItem("selectedMapId") || "map1";

// Initial data passed from backend, mainly used for current balance
const MINING_BOOTSTRAP = window.MINING_BOOTSTRAP || { balance: 0 };

// API endpoint used to load the player's active outfit
const ACTIVE_OUTFIT_ENDPOINT = "/galgame/dress_up_game/api/get_active_outfit.php";

// Different background music files for different maps
const MAP_BGM = {
  map1: "ConcernedApe - Summer (Nature's Crescendo).mp3",
  map2: "ConcernedApe - Dance Of The Moonlight Jellies.mp3",
  map3: "ConcernedApe - Fall (The Smell Of Mushroom).mp3",
  map4: "ConcernedApe - Winter (Nocturne Of Ice).mp3",
  map5: "ConcernedApe - A Golden Star Is Born.mp3",
};

// Prefix used to save per-map BGM setting in localStorage
const BGM_STORAGE_PREFIX = "mining_single_bgm_";

// Current loading progress shown on the loading screen
let loadingProgress = 0;

// Visual layer order for rendering the outfit preview
const dressUpLayerOrder = ["background","body","shoes","top","pants","dress","suit","eye","eyebrows","nose","mouse","hair","character","glass","head"];

// Unique run ID used when submitting mining rewards to backend
const singleRunId = String(Date.now());

// Sequence number to make each ore reward record unique within one run
let singleRewardSequence = 0;

// Prevent repeated map clear submissions
let mapClearSubmitted = false;

// Current map name shown in the UI
const currentMapName = sessionStorage.getItem("selectedMapName") || "新手森林";

// Word bank used for quiz questions
let WORD_BANK = [];

// Total coins earned in this single run
let runCoinsEarned = 0;

// Local fallback word bank used in single-player mode
const LOCAL_WORD_BANK = [
  { en: "ancient", cn: ["古老的", "现代的", "明亮的", "安静的"], a: 0 },
  { en: "balance", cn: ["平衡", "角度", "距离", "速度"], a: 0 },
  { en: "capture", cn: ["捕获", "逃跑", "隐藏", "修理"], a: 0 },
  { en: "decline", cn: ["下降", "增加", "停留", "扩大"], a: 0 },
  { en: "effort", cn: ["努力", "运气", "方法", "规则"], a: 0 },
  { en: "feature", cn: ["特点", "高度", "数量", "方向"], a: 0 },
  { en: "gather", cn: ["收集", "分开", "拒绝", "比较"], a: 0 },
  { en: "harmful", cn: ["有害的", "有益的", "便宜的", "稀有的"], a: 0 },
  { en: "improve", cn: ["改善", "破坏", "模仿", "限制"], a: 0 },
  { en: "journey", cn: ["旅行", "比赛", "课程", "演出"], a: 0 },

  { en: "limited", cn: ["有限的", "重要的", "公平的", "相似的"], a: 0 },
  { en: "measure", cn: ["测量", "绘画", "借用", "折叠"], a: 0 },
  { en: "narrow", cn: ["狭窄的", "宽阔的", "危险的", "诚实的"], a: 0 },
  { en: "observe", cn: ["观察", "触摸", "扔掉", "搬运"], a: 0 },
  { en: "prevent", cn: ["阻止", "允许", "发现", "加入"], a: 0 },
  { en: "quality", cn: ["质量", "速度", "温度", "面积"], a: 0 },
  { en: "replace", cn: ["替换", "保留", "修建", "连接"], a: 0 },
  { en: "respect", cn: ["尊重", "争论", "提醒", "命令"], a: 0 },
  { en: "shelter", cn: ["避难所", "田野", "桥梁", "工厂"], a: 0 },
  { en: "suffer", cn: ["遭受", "庆祝", "记录", "解释"], a: 0 },

  { en: "surface", cn: ["表面", "底部", "中心", "边界"], a: 0 },
  { en: "talent", cn: ["天赋", "压力", "责任", "习惯"], a: 0 },
  { en: "wander", cn: ["漫游", "停靠", "跳跃", "倒下"], a: 0 },
  { en: "wealth", cn: ["财富", "麻烦", "贫穷", "疾病"], a: 0 },
  { en: "abandon", cn: ["放弃", "开始", "完成", "支持"], a: 0 },
  { en: "absorb", cn: ["吸收", "释放", "生产", "分享"], a: 0 },
  { en: "benefit", cn: ["益处", "损失", "错误", "借口"], a: 0 },
  { en: "confirm", cn: ["确认", "怀疑", "忘记", "拒绝"], a: 0 },
  { en: "consume", cn: ["消耗", "节省", "归还", "修理"], a: 0 },
  { en: "contest", cn: ["比赛", "假期", "会议", "采访"], a: 0 },

  { en: "deserve", cn: ["值得", "逃避", "依靠", "误导"], a: 0 },
  { en: "device", cn: ["设备", "习俗", "景象", "材料"], a: 0 },
  { en: "distant", cn: ["遥远的", "附近的", "昂贵的", "湿润的"], a: 0 },
  { en: "educate", cn: ["教育", "运输", "检查", "混合"], a: 0 },
  { en: "encourage", cn: ["鼓励", "责备", "阻挡", "欺骗"], a: 0 },
  { en: "escape", cn: ["逃脱", "攻击", "到达", "停留"], a: 0 },
  { en: "estimate", cn: ["估计", "宣布", "争论", "修正"], a: 0 },
  { en: "exhausted", cn: ["筋疲力尽的", "精力充沛的", "十分寒冷的", "完全正确的"], a: 0 },
  { en: "expand", cn: ["扩大", "缩小", "弯曲", "分离"], a: 0 },
  { en: "explore", cn: ["探索", "隐藏", "覆盖", "限制"], a: 0 },

  { en: "familiar", cn: ["熟悉的", "奇怪的", "宽松的", "危险的"], a: 0 },
  { en: "fortunate", cn: ["幸运的", "诚实的", "紧张的", "严格的"], a: 0 },
  { en: "frequent", cn: ["频繁的", "罕见的", "便宜的", "平坦的"], a: 0 },
  { en: "generate", cn: ["产生", "浪费", "隐藏", "借入"], a: 0 },
  { en: "graduate", cn: ["毕业", "失败", "停课", "参观"], a: 0 },
  { en: "hesitate", cn: ["犹豫", "坚持", "赞成", "准备"], a: 0 },
  { en: "identify", cn: ["识别", "隐藏", "改变", "借用"], a: 0 },
  { en: "ignore", cn: ["忽视", "原谅", "邀请", "跟随"], a: 0 },
  { en: "independent", cn: ["独立的", "相似的", "传统的", "温柔的"], a: 0 },
  { en: "inform", cn: ["通知", "惩罚", "保护", "比较"], a: 0 },

  { en: "injure", cn: ["伤害", "治疗", "训练", "提醒"], a: 0 },
  { en: "influence", cn: ["影响", "边界", "选择", "收入"], a: 0 },
  { en: "intend", cn: ["打算", "忘记", "避免", "后悔"], a: 0 },
  { en: "interrupt", cn: ["打断", "继续", "修理", "邀请"], a: 0 },
  { en: "maintain", cn: ["维持", "丢失", "拆除", "误解"], a: 0 },
  { en: "method", cn: ["方法", "形状", "结果", "比例"], a: 0 },
  { en: "nevertheless", cn: ["然而", "例如", "因此", "事实上"], a: 0 },
  { en: "occasion", cn: ["场合", "工具", "邻居", "意见"], a: 0 },
  { en: "ordinary", cn: ["普通的", "特别的", "稀有的", "安静的"], a: 0 },
  { en: "organize", cn: ["组织", "抱怨", "测量", "修补"], a: 0 },

  { en: "patient", cn: ["耐心的", "粗心的", "自私的", "失望的"], a: 0 },
  { en: "permit", cn: ["允许", "拒绝", "怀疑", "提醒"], a: 0 },
  { en: "persuade", cn: ["说服", "打断", "反驳", "隐藏"], a: 0 },
  { en: "pollute", cn: ["污染", "净化", "保护", "改善"], a: 0 },
  { en: "practical", cn: ["实用的", "昂贵的", "遥远的", "神秘的"], a: 0 },
  { en: "predict", cn: ["预测", "回顾", "争论", "决定"], a: 0 },
  { en: "prefer", cn: ["更喜欢", "反对", "接受", "取消"], a: 0 },
  { en: "preserve", cn: ["保护", "破坏", "浪费", "忽视"], a: 0 },
  { en: "pressure", cn: ["压力", "诚实", "耐心", "友情"], a: 0 },
  { en: "previous", cn: ["先前的", "随后的", "困难的", "必要的"], a: 0 },

  { en: "process", cn: ["过程", "终点", "资源", "条件"], a: 0 },
  { en: "promote", cn: ["促进", "阻止", "拒绝", "打断"], a: 0 },
  { en: "protect", cn: ["保护", "袭击", "交换", "误导"], a: 0 },
  { en: "react", cn: ["反应", "比较", "计划", "通知"], a: 0 },
  { en: "reduce", cn: ["减少", "增加", "确认", "连接"], a: 0 },
  { en: "reflect", cn: ["反映", "隐藏", "打断", "宣布"], a: 0 },
  { en: "refuse", cn: ["拒绝", "允许", "依靠", "建议"], a: 0 },
  { en: "relax", cn: ["放松", "担心", "训练", "集中"], a: 0 },
  { en: "reliable", cn: ["可靠的", "脆弱的", "陌生的", "沉重的"], a: 0 },
  { en: "remind", cn: ["提醒", "宽恕", "支持", "测量"], a: 0 },

  { en: "remove", cn: ["移开", "保留", "种植", "绘制"], a: 0 },
  { en: "request", cn: ["请求", "命令", "争论", "影响"], a: 0 },
  { en: "require", cn: ["需要", "拒绝", "完成", "携带"], a: 0 },
  { en: "rescue", cn: ["营救", "攻击", "等待", "抱怨"], a: 0 },
  { en: "resource", cn: ["资源", "结果", "距离", "机会"], a: 0 },
  { en: "responsible", cn: ["负责任的", "粗心的", "紧张的", "虚弱的"], a: 0 },
  { en: "satisfy", cn: ["使满意", "使困惑", "使失望", "使疲惫"], a: 0 },
  { en: "separate", cn: ["分开", "连接", "覆盖", "允许"], a: 0 },
  { en: "solution", cn: ["解决办法", "错误原因", "特别习惯", "表面现象"], a: 0 },
  { en: "specific", cn: ["具体的", "普遍的", "自然的", "随意的"], a: 0 },

  { en: "struggle", cn: ["挣扎", "休息", "庆祝", "观察"], a: 0 },
  { en: "sudden", cn: ["突然的", "缓慢的", "安静的", "诚实的"], a: 0 },
  { en: "support", cn: ["支持", "怀疑", "降低", "逃避"], a: 0 },
  { en: "survive", cn: ["生存", "下沉", "消失", "破裂"], a: 0 },
  { en: "traditional", cn: ["传统的", "现代的", "稀有的", "正式的"], a: 0 },
  { en: "transfer", cn: ["转移", "停留", "跌落", "拆毁"], a: 0 },
  { en: "unique", cn: ["独特的", "普通的", "便宜的", "狭窄的"], a: 0 },
  { en: "variety", cn: ["多样性", "速度", "压力", "友谊"], a: 0 },
  { en: "visible", cn: ["看得见的", "隐藏的", "冰冷的", "脆弱的"], a: 0 },
  { en: "volunteer", cn: ["志愿者", "邻居", "游客", "教练"], a: 0 }
];

// Update the loading bar and percentage text
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

// Update loading subtitle text
function setLoadingSubtitle(text) {
  const subtitle = document.getElementById("loading-subtitle");
  if (subtitle) {
    subtitle.innerText = text;
  }
}

// Hide the loading screen after setup is complete
function hideLoadingScreen() {
  const loadingScreen = document.getElementById("loading-screen");
  if (loadingScreen) {
    loadingScreen.classList.add("hidden");
  }
}

// Get the current map's background music file
function getCurrentBgmFile() {
  return MAP_BGM[currentMapId] || MAP_BGM.map1;
}

// Get the localStorage key for the current map's BGM setting
function getCurrentBgmStorageKey() {
  return `${BGM_STORAGE_PREFIX}${currentMapId}`;
}

// Update the BGM toggle button text
function updateBgmButton(enabled) {
  const btn = document.getElementById("bgm-toggle");
  if (btn) {
    btn.innerText = enabled ? "BGM ON" : "BGM OFF";
  }
}

// Change audio source based on the selected map
function setBgmSource() {
  const player = document.getElementById("bgm-player");
  const source = document.getElementById("bgm-source");
  if (!player || !source) {
    return;
  }

  source.src = getCurrentBgmFile();
  player.load();
}

// Play or stop background music
async function applyBgmState(enabled) {
  const player = document.getElementById("bgm-player");
  if (!player) {
    return;
  }

  updateBgmButton(enabled);

  if (enabled) {
    try {
      await player.play();
    } catch (error) {
      // Some browsers block autoplay before user interaction
      console.warn("Autoplay blocked by browser:", error);
    }
  } else {
    player.pause();
    player.currentTime = 0;
  }
}

// Initialize BGM control and restore saved state for this map
function initBgmControl() {
  const btn = document.getElementById("bgm-toggle");
  const storageKey = getCurrentBgmStorageKey();
  const enabled = localStorage.getItem(storageKey) !== "0";

  setBgmSource();
  updateBgmButton(enabled);
  applyBgmState(enabled);

  if (btn) {
    btn.addEventListener("click", async () => {
      const current = localStorage.getItem(storageKey) !== "0";
      const next = !current;
      localStorage.setItem(storageKey, next ? "1" : "0");
      await applyBgmState(next);
      
      btn.blur();
    });
  }
}

// Build possible image paths for one outfit layer
function buildLookCandidates(layer) {
  const filePath = layer?.file_path || "";
  const normalized = filePath.startsWith("/") ? filePath : `/${filePath}`;
  return [`/galgame/dress_up_game${normalized}`, layer?.url || ""].filter(Boolean);
}

// Try loading one outfit image candidate, then use the next if it fails
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

// Render the miner outfit preview in the page
function renderMinerLook(data) {
  const stage = document.getElementById("minerLookStage");
  const empty = document.getElementById("minerLookEmpty");
  if (!stage || !empty) {
    return;
  }

  // Clear old rendered layers first
  Array.from(stage.querySelectorAll(".miner-look-layer")).forEach((node) => node.remove());

  // If no outfit data exists, show fallback text
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

  // Render outfit layers in the correct order
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

// Load active outfit data for the miner preview
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

// Image assets used in single-player mining mode
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

// Cache of all loaded image objects
const IMAGES = {};
let loadedCount = 0;
const totalImages = Object.keys(ASSETS).length;

// Load all required game assets before starting
function loadAssets(callback) {
  if (totalImages === 0) {
    setLoadingProgress(90);
    callback();
    return;
  }

  setLoadingSubtitle("Loading game assets...");

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
    const percent = 30 + (loadedCount / totalImages) * 60;
    setLoadingProgress(percent);

    if (loadedCount === totalImages) {
      callback();
    }
  }
}

// Config data for each mine object type
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

// Different states of the hook/gameplay flow
const STATE = {
  SWINGING: 0,
  EXTENDING: 1,
  DIGGING_QUIZ: 2,
  RETRACTING_EMPTY: 3,
  RETRACTING_WITH_STONE: 4,
};

// Main canvas, context, and UI references
const p1Canvas = document.getElementById("p1Canvas");
const ctx1 = p1Canvas.getContext("2d");
const quizModal = document.getElementById("quiz-modal");
const gameContainer = document.getElementById("game-container");

// Runtime game state
let gameState = {
  isQuizActive: false,
  currentQuestion: null,
  currentLevel: 1,
  currentMistakes: 0,
  isPaused: false,
};

let p1;

// Send mining-related POST request to backend
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

// Sync one collected ore reward with backend
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

// Submit current map clear result to backend
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

// Record unlocked collection achievement in localStorage
function recordAchievement(oreId) {
  let unlockedList = JSON.parse(localStorage.getItem("myMineAchieve")) || [];
  if (!unlockedList.includes(oreId)) {
    unlockedList.push(oreId);
    localStorage.setItem("myMineAchieve", JSON.stringify(unlockedList));
    console.log(`✨unlock new  achievement!${oreId}`);
  }
}

// Player class used for controlling the single-player miner
class Player {
  constructor(id, canvas, ctx) {
    this.id = id;
    this.canvas = canvas;
    this.ctx = ctx;
    this.objects = [];

    // Current score starts from backend balance
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

  // Update hook movement and player state every frame
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

  // Recalculate hook endpoint coordinates
  updateHookCoords() {
    this.hook.x =
      this.minerPivotX + Math.cos(this.hook.angle) * this.hook.length;
    this.hook.y =
      this.minerPivotY + Math.sin(this.hook.angle) * this.hook.length;
  }

  // Reset hook and temporary state after one grab cycle
  reset() {
    this.hook.length = this.HOOK_BASE_LEN;
    this.updateHookCoords();
    this.status = STATE.SWINGING;
    this.grabbedObject = null;
    this.grabbedObjectIdx = -1;
    this.quizProgress = 0;
    this.isCollecting = false;
  }

  // Handle successful ore collection
  async collect() {
    this.isCollecting = true;
    
    const oreType = this.grabbedObject.type;
    const oreValue = this.grabbedObject.value;
    
    // Reset hook first so it does not keep shrinking while waiting for network response
    this.hook.length = this.HOOK_BASE_LEN;
    this.updateHookCoords();
    this.status = STATE.SWINGING;

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

// Initialize the single-player game
function initGame() {
  p1Canvas.width = window.innerWidth;
  p1Canvas.height = window.innerHeight;

  p1 = new Player(1, p1Canvas, ctx1);

  document.getElementById("map-name-display").innerText = `[${currentMapName}]`;
  updateScoreDisplay();

  generateObjects();
  setLoadingSubtitle("Entering mine...");
  setLoadingProgress(100);

  gameLoop();

  setTimeout(() => {
    hideLoadingScreen();
  }, 350);
}

// Generate mine objects based on current map difficulty
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

// Check whether all mine objects have been collected
function checkLevelComplete() {
  if (p1.objects.length === 0) {
    gameState.isPaused = true;
    submitMapClear();
  }
}

// Main game animation loop
function gameLoop() {
  if (!gameState.isPaused) p1.update();
  drawPlayerView(p1, p1.objects);
  requestAnimationFrame(gameLoop);
}

// Draw the whole current single-player scene
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

  // Draw all mine objects except the one currently being grabbed
  objects.forEach((obj, idx) => {
    if (
      (p.status === STATE.DIGGING_QUIZ ||
        p.status === STATE.RETRACTING_WITH_STONE) &&
      idx === p.grabbedObjectIdx
    )
      return;
    drawGameObject(ctx, obj);
  });

  const machineSize = 120;

  // ==============================
  // 1. Draw the miner machine itself without rotation
  // ==============================
  if (IMAGES.miner && IMAGES.miner.isReady) {
    ctx.save();
    ctx.translate(pivotX, pivotY); 
    ctx.drawImage(
      IMAGES.miner,
      -machineSize / 2,
      -machineSize / 2,
      machineSize,
      machineSize,
    );
    ctx.restore();
  }

  // ==============================
  // 2. Draw the swinging rope and hook with rotation
  // ==============================
  ctx.save();
  ctx.translate(pivotX, pivotY);
  ctx.rotate(p.hook.angle - Math.PI / 2);

  const ropeStartY = machineSize / 2 - 20;
  const ropeEndY = p.hook.length;

  if (ropeEndY > ropeStartY) {
    // Draw rope
    ctx.strokeStyle = "#5a3c26";
    ctx.lineWidth = 4;
    ctx.beginPath();
    ctx.moveTo(0, ropeStartY);
    ctx.lineTo(0, ropeEndY);
    ctx.stroke();

    // Draw hook and the grabbed object
    ctx.save();
    ctx.translate(0, ropeEndY);
    
    // Draw grabbed ore if there is one
    if (
      (p.status === STATE.DIGGING_QUIZ ||
        p.status === STATE.RETRACTING_WITH_STONE) &&
      p.grabbedObject
    ) {
      drawGameObject(ctx, { ...p.grabbedObject, x: 0, y: 35 });
    }

    // Draw hook image if available, otherwise draw fallback shape
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

// Draw one mine object or fallback circle
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

// Open the quiz modal after grabbing an object
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

// Load a new vocabulary question into the quiz
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

// Handle answer choice in the quiz
function handleAnswer(selectedIndex) {
  if (!gameState.isQuizActive) return;
  const correctIndex = gameState.currentQuestion.a;
  const btns = document.querySelectorAll(".opt-btn");
  btns.forEach((b) => (b.disabled = true));

  if (selectedIndex === correctIndex) {
    // Correct answer: increase progress and pull hook inward
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
    // Wrong answer: shake screen and reduce progress
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
        // Special stat record if diamond is lost after too many mistakes
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

// Update score display in the top panel
function updateScoreDisplay() {
  const scoreElement = document.getElementById("p1-score");
  if (scoreElement && p1) {
    scoreElement.innerText = p1.score;
  }
}

// Keyboard controls for pause and hook launch
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

// Adjust canvas size and miner position when window size changes
window.addEventListener("resize", () => {
  p1Canvas.width = window.innerWidth;
  p1Canvas.height = window.innerHeight;
  p1.minerPivotX = p1Canvas.width / 2;
  p1.reset();
});

const pauseOverlay = document.getElementById("pause-overlay");
const pauseBtn = document.getElementById("pause-btn");
const resumeBtn = document.getElementById("resume-btn");

// Toggle game pause state and pause overlay
function togglePause() {
  gameState.isPaused = !gameState.isPaused;
  if (gameState.isPaused) pauseOverlay.classList.add("active");
  else pauseOverlay.classList.remove("active");
}

// Pause/resume button events
pauseBtn.addEventListener("click", togglePause);
resumeBtn.addEventListener("click", togglePause);

// Load local word bank first, then load assets and start the game
async function fetchWordsAndStart() {
  setLoadingSubtitle("Loading local word bank...");
  setLoadingProgress(20);

  WORD_BANK = LOCAL_WORD_BANK;
  setLoadingProgress(30);

  loadAssets(() => {
    initGame();
  });
}

// Initial setup before entering the mine
setLoadingProgress(5);
setLoadingSubtitle("Preparing map data...");

initBgmControl();

fetchWordsAndStart();
loadMinerLook();