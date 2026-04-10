// Initial data passed from backend to frontend
const bootstrap = window.MINING_BOOTSTRAP || { balance: 0, maps: [] };

// Make sure mapsData is always an array
const mapsData = Array.isArray(bootstrap.maps) ? bootstrap.maps : [];

// LocalStorage key used to save the BGM state for the map page
const BGM_STORAGE_KEY = "mining_map_bgm_enabled";

// Current loading progress value shown on the loading screen
let loadingProgress = 0;

// Timer used to simulate loading progress animation
let loadingTimer = null;

// Predefined visual positions for each map point on the background image
const mapPositions = {
  map1: { left: "17%", top: "65%" },
  map2: { left: "35%", top: "40%" },
  map3: { left: "50%", top: "78%" },
  map4: { left: "56%", top: "55%" },
  map5: { left: "73%", top: "25%" },
};

// Player's current coin balance
let playerCoins = Number(bootstrap.balance || 0);

// Stores the current state of each map for quick lookup
const mapState = new Map();

// Convert map data into a Map object using map key/id as the index
for (const map of mapsData) {
  mapState.set(map.key || map.id, {
    ...map,
    id: map.key || map.id,
  });
}

// Update the loading bar and loading percentage text
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

// Simulate loading progress gradually until it reaches the target value
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

// Hide the loading screen after everything is ready
function hideLoadingScreen() {
  const loadingScreen = document.getElementById("loading-screen");
  if (loadingScreen) {
    loadingScreen.classList.add("hidden");
  }
}

// Wait until an image finishes loading or fails to load
function waitForImageLoad(img) {
  return new Promise((resolve) => {
    if (!img) {
      resolve();
      return;
    }

    if (img.complete) {
      resolve();
      return;
    }

    img.addEventListener("load", resolve, { once: true });
    img.addEventListener("error", resolve, { once: true });
  });
}

// Update the text shown on the BGM toggle button
function updateBgmButton(enabled) {
  const btn = document.getElementById("bgm-toggle");
  if (btn) {
    btn.innerText = enabled ? "BGM ON" : "BGM OFF";
  }
}

// Apply the current BGM state by either playing or stopping the music
async function applyBgmState(enabled) {
  const player = document.getElementById("bgm-player");
  if (!player) {
    return;
  }

  updateBgmButton(enabled);

  if (enabled) {
    try {
      // Try to start playing background music
      await player.play();
    } catch (error) {
      // Some browsers block autoplay before user interaction
      console.warn("Autoplay blocked by browser:", error);
    }
  } else {
    // Stop the music and reset it to the beginning
    player.pause();
    player.currentTime = 0;
  }
}

// Initialize the BGM button and restore saved BGM state
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
      
      // Remove focus after clicking the button
      btn.blur();
    });
  }
}

// Main initialization logic after the page fully loads
window.onload = async function () {
  setLoadingProgress(0);

  // Start a fake loading animation first for smoother visual effect
  simulateLoadingTo(70, 1);

  updateCoinUI();
  initBgmControl();

  // Wait for the background map image to finish loading
  const mapImage = document.getElementById("map-bg-image");
  await waitForImageLoad(mapImage);
  setLoadingProgress(85);

  // Render all map points after the image is ready
  renderMapsOnBackground();
  setLoadingProgress(100);

  clearInterval(loadingTimer);

  // Hide loading screen shortly after rendering completes
  setTimeout(() => {
    hideLoadingScreen();
  }, 350);
};

// Update the player's coin display in the UI
function updateCoinUI() {
  const coinEl = document.getElementById("coin-count");
  if (coinEl) {
    coinEl.innerText = String(playerCoins);
  }
}

// Render all map points onto the world map background
function renderMapsOnBackground() {
  const overlay = document.getElementById("maps-overlay");
  overlay.innerHTML = "";

  mapsData.forEach((rawMap) => {
    const map = mapState.get(rawMap.key || rawMap.id) || rawMap;
    const isUnlocked = Boolean(map.is_unlocked);
    const isCleared = Boolean(map.is_cleared);
    const position = mapPositions[map.id] || { left: "50%", top: "50%" };

    const point = document.createElement("div");
    point.className = "map-point";
    if (!isUnlocked) {
      point.classList.add("locked");
    }

    // Position each map point at its corresponding place on the image
    point.style.position = "absolute";
    point.style.left = position.left;
    point.style.top = position.top;
    point.style.transform = "translate(-50%, -50%)";
    point.onclick = (e) => {
      e.stopPropagation();
      handleMapClick(map);
    };

    // Basic map point content
    let pointHTML = `
      <div class="map-point-content">
        <img src="${map.image}" alt="${map.name}" class="map-point-img" onerror="this.src='https://via.placeholder.com/80x80?text=${encodeURIComponent(map.name)}'">
        <div class="map-name-label">${map.name}</div>
      </div>
    `;

    // Show lock/cost info if not unlocked yet
    if (!isUnlocked) {
      pointHTML += `
        <div class="lock-icon-point">LOCK</div>
        <div class="price-tag-point">💰 ${map.cost}</div>
      `;
    } else if (isCleared) {
      // Show clear label if the map has already been completed
      pointHTML += `<div class="price-tag-point">CLEAR</div>`;
    }

    point.innerHTML = pointHTML;
    overlay.appendChild(point);
  });
}

// Send a mining-related POST request to the backend
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
    throw new Error(data.message || data.error || "Request failed.");
  }
  return data;
}

// Handle clicking on a map point
async function handleMapClick(map) {
  if (map.is_unlocked) {
    // Save selected map info and enter single-player mining mode
    sessionStorage.setItem("selectedMapId", map.id);
    sessionStorage.setItem("selectedMapName", map.name);
    await postMiningAction("record_map_play", { map_key: map.id }).catch(() => null);
    window.location.href = "mining_single.php";
    return;
  }

  // Ask player to confirm before spending coins to unlock a map
  const confirmBuy = confirm(
    `Spend ${map.cost} coins to unlock ${map.name}? Maps must be unlocked in order.`
  );

  if (!confirmBuy) {
    return;
  }

  try {
    // Request backend to unlock this map
    const result = await postMiningAction("unlock_map", { map_key: map.id });
    playerCoins = Number(result.balance || playerCoins);
    const current = mapState.get(map.id);
    if (current) {
      current.is_unlocked = true;
    }
    updateCoinUI();
    renderMapsOnBackground();
    alert(result.message || `Unlocked ${map.name}.`);
  } catch (error) {
    alert(error.message);
  }
}

// Return to the mining main page
function goBack() {
  window.location.href = "mining_index.php";
}