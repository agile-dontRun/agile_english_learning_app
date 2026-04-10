const ACTIVE_OUTFIT_ENDPOINT =
  "/galgame/dress_up_game/api/get_active_outfit.php";
const IMAGE_BASE_PATH = "/galgame/dress_up_game";
const MINING_BOOTSTRAP = window.MINING_BOOTSTRAP || { balance: 0 };
let miningBalance = Number(MINING_BOOTSTRAP.balance || 0);

const BGM_STORAGE_KEY = "mining_bgm_enabled";
let loadingProgress = 0;
let loadingTimer = null;

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

function buildImageCandidates(layer) {
  const filePath = layer?.file_path || "";
  const normalizedFilePath = filePath.startsWith("/")
    ? filePath
    : `/${filePath}`;

  return [`${IMAGE_BASE_PATH}${normalizedFilePath}`, layer?.url || ""].filter(
    Boolean,
  );
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
  img.src = `${candidate}${candidate.includes("?") ? "&" : "?"}t=${Date.now()}`;
}

function goBack() {
  if (document.referrer && document.referrer !== "") {
    window.history.back();
  } else {
    window.location.href = "mining_index.php";
  }
}

function startGame(mode) {
  if (mode === "single") {
    window.location.href = "mining_map.php";
  } else if (mode === "double") {
    window.location.href = "mining_match.php";
  }
}

function openMenu(menu) {
  if (menu === "collection") {
    window.location.href = "mining_collection.php";
  } else if (menu === "achievement") {
    window.location.href = "mining_achievement.php";
  }
}

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

function simulateLoadingTo(target, step = 1) {
  clearInterval(loadingTimer);
  loadingTimer = setInterval(() => {
    if (loadingProgress >= target) {
      clearInterval(loadingTimer);
      return;
    }
    setLoadingProgress(loadingProgress + step);
  }, 25);
}

function hideLoadingScreen() {
  const loadingScreen = document.getElementById("loading-screen");
  if (loadingScreen) {
    loadingScreen.classList.add("hidden");
  }
}

function updateBgmButton(enabled) {
  const btn = document.getElementById("bgm-toggle");
  if (btn) {
    btn.innerText = enabled ? "BGM ON" : "BGM OFF";
  }
}

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
      console.warn("Autoplay blocked by browser:", error);
    }
  } else {
    player.pause();
    player.currentTime = 0;
  }
}

function initBgmControl() {
  const btn = document.getElementById("bgm-toggle");
  const saved = localStorage.getItem(BGM_STORAGE_KEY);
  const enabled = saved === "0";

  updateBgmButton(enabled);
  applyBgmState(enabled);

  if (btn) {
    btn.addEventListener("click", async () => {
      const current = localStorage.getItem(BGM_STORAGE_KEY) === "0";
      const next = !current;
      localStorage.setItem(BGM_STORAGE_KEY, next ? "1" : "0");
      await applyBgmState(next);
    });
  }
}

function updateCoinDisplay() {
  const coinElement = document.getElementById("coin-count");
  if (coinElement) {
    coinElement.innerText = String(Number(miningBalance || 0));
  }
}

async function refreshMiningBalance() {
  try {
    const response = await fetch("mining_coin_api.php?action=status", {
      cache: "no-store",
    });
    const data = await response.json();
    if (response.ok && data.success && data.data) {
      miningBalance = Number(data.data.balance || 0);
      updateCoinDisplay();
    }
  } catch (error) {
    console.error("Failed to refresh mining balance:", error);
  }
}

function resetMingLookStage() {
  const stage = document.getElementById("ming-look-stage");
  const empty = document.getElementById("ming-look-empty");
  if (!stage) {
    return;
  }

  Array.from(stage.querySelectorAll(".ming-look-layer")).forEach((node) =>
    node.remove(),
  );

  if (empty) {
    empty.style.display = "flex";
  }
}

function renderMingLook(data) {
  const stage = document.getElementById("ming-look-stage");
  const empty = document.getElementById("ming-look-empty");
  const title = document.getElementById("ming-look-title");

  if (!stage || !empty || !title) {
    return;
  }

  resetMingLookStage();

  if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
    title.innerText = "Current Ming Look";
    return;
  }

  empty.style.display = "none";
  title.innerText = data.name
    ? "Current Ming Look: " + data.name
    : "Current Ming Look";

  const layerMap = new Map();
  for (const layer of data.layers) {
    if (layer && layer.layer) {
      layerMap.set(layer.layer, layer);
    }
  }

  for (const layerName of dressUpLayerOrder) {
    const layer = layerMap.get(layerName);
    if (!layer) {
      continue;
    }

    const img = document.createElement("img");
    img.className = "ming-look-layer";
    img.alt = layer.name || layerName;
    stage.appendChild(img);
    setImageWithFallback(img, buildImageCandidates(layer));
  }
}

async function loadActiveMingLook() {
  try {
    const response = await fetch(ACTIVE_OUTFIT_ENDPOINT, {
      cache: "no-store",
    });
    const rawText = await response.text();

    if (!response.ok) {
      throw new Error(
        `HTTP ${response.status} from ${ACTIVE_OUTFIT_ENDPOINT}: ${rawText.slice(0, 160)}`,
      );
    }

    let data;
    try {
      data = JSON.parse(rawText);
    } catch (parseError) {
      throw new Error(
        `Non-JSON response from ${ACTIVE_OUTFIT_ENDPOINT}: ${rawText.slice(0, 160)}`,
      );
    }

    renderMingLook(data);
  } catch (error) {
    console.error("Failed to load Ming look:", error);
    resetMingLookStage();
  }
}

window.addEventListener("load", async () => {
  setLoadingProgress(0);
  simulateLoadingTo(70, 1);

  updateCoinDisplay();
  initBgmControl();

  await refreshMiningBalance();
  setLoadingProgress(85);

  await loadActiveMingLook();
  setLoadingProgress(100);

  clearInterval(loadingTimer);

  setTimeout(() => {
    hideLoadingScreen();
  }, 350);
});