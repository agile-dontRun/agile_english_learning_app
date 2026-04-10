// API endpoint used to get the currently active outfit from the dress-up system
const ACTIVE_OUTFIT_ENDPOINT =
  "/galgame/dress_up_game/api/get_active_outfit.php";

// Base path for outfit image resources
const IMAGE_BASE_PATH = "/galgame/dress_up_game";

// Initial mining page data passed from backend
const MINING_BOOTSTRAP = window.MINING_BOOTSTRAP || { balance: 0 };

// Current mining coin balance shown on the page
let miningBalance = Number(MINING_BOOTSTRAP.balance || 0);

// LocalStorage key used to save whether BGM is enabled
const BGM_STORAGE_KEY = "mining_bgm_enabled";

// Current loading progress shown on the loading screen
let loadingProgress = 0;

// Timer used for fake loading animation
let loadingTimer = null;

// Drawing order for outfit layers, from bottom to top
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

// Build possible image paths for one outfit layer
function buildImageCandidates(layer) {
  const filePath = layer?.file_path || "";
  const normalizedFilePath = filePath.startsWith("/")
    ? filePath
    : `/${filePath}`;

  return [`${IMAGE_BASE_PATH}${normalizedFilePath}`, layer?.url || ""].filter(
    Boolean,
  );
}

// Try to load one image path, and use the next path if loading fails
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

// Go back to the previous page, or return to mining index if no referrer exists
function goBack() {
  if (document.referrer && document.referrer !== "") {
    window.history.back();
  } else {
    window.location.href = "mining_index.php";
  }
}

// Start the selected mining game mode
function startGame(mode) {
  if (mode === "single") {
    window.location.href = "mining_map.php";
  } else if (mode === "double") {
    window.location.href = "mining_match.php";
  }
}

// Open one of the extra menu pages
function openMenu(menu) {
  if (menu === "collection") {
    window.location.href = "mining_collection.php";
  } else if (menu === "achievement") {
    window.location.href = "mining_achievement.php";
  }
}

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

// Simulate loading progress smoothly until it reaches the target value
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

// Hide the loading screen after initialization is done
function hideLoadingScreen() {
  const loadingScreen = document.getElementById("loading-screen");
  if (loadingScreen) {
    loadingScreen.classList.add("hidden");
  }
}

// Update the text shown on the BGM toggle button
function updateBgmButton(enabled) {
  const btn = document.getElementById("bgm-toggle");
  if (btn) {
    btn.innerText = enabled ? "BGM ON" : "BGM OFF";
  }
}

// Play or stop the background music based on current state
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
    // Stop music and reset playback position
    player.pause();
    player.currentTime = 0;
  }
}

// Initialize the BGM button and restore saved BGM setting
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

// Update the coin amount shown on the page
function updateCoinDisplay() {
  const coinElement = document.getElementById("coin-count");
  if (coinElement) {
    coinElement.innerText = String(Number(miningBalance || 0));
  }
}

// Request the latest mining coin balance from backend
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

// Clear all currently rendered Ming look layers
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

// Render the current Ming look preview on the page
function renderMingLook(data) {
  const stage = document.getElementById("ming-look-stage");
  const empty = document.getElementById("ming-look-empty");
  const title = document.getElementById("ming-look-title");

  if (!stage || !empty || !title) {
    return;
  }

  resetMingLookStage();

  // If there is no valid outfit data, only keep the default title
  if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
    title.innerText = "Current Ming Look";
    return;
  }

  empty.style.display = "none";
  title.innerText = data.name
    ? "Current Ming Look: " + data.name
    : "Current Ming Look";

  // Convert outfit layer array into a map for easier lookup
  const layerMap = new Map();
  for (const layer of data.layers) {
    if (layer && layer.layer) {
      layerMap.set(layer.layer, layer);
    }
  }

  // Render outfit layers in the correct visual order
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

// Load the active Ming look data from backend
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

// Main initialization logic after the page finishes loading
window.addEventListener("load", async () => {
  setLoadingProgress(0);
  simulateLoadingTo(70, 1);

  updateCoinDisplay();
  initBgmControl();

  // Refresh latest coin balance from backend
  await refreshMiningBalance();
  setLoadingProgress(85);

  // Load and render current Ming look preview
  await loadActiveMingLook();
  setLoadingProgress(100);

  clearInterval(loadingTimer);

  // Hide loading screen shortly after setup is finished
  setTimeout(() => {
    hideLoadingScreen();
  }, 350);
});