const bootstrap = window.MINING_BOOTSTRAP || { balance: 0, maps: [] };
const mapsData = Array.isArray(bootstrap.maps) ? bootstrap.maps : [];

const BGM_STORAGE_KEY = "mining_map_bgm_enabled";
let loadingProgress = 0;
let loadingTimer = null;

const mapPositions = {
  map1: { left: "17%", top: "65%" },
  map2: { left: "35%", top: "40%" },
  map3: { left: "50%", top: "78%" },
  map4: { left: "56%", top: "55%" },
  map5: { left: "73%", top: "25%" },
};

let playerCoins = Number(bootstrap.balance || 0);
const mapState = new Map();

for (const map of mapsData) {
  mapState.set(map.key || map.id, {
    ...map,
    id: map.key || map.id,
  });
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
  }
}

window.onload = async function () {
  setLoadingProgress(0);
  simulateLoadingTo(70, 1);

  updateCoinUI();
  initBgmControl();

  const mapImage = document.getElementById("map-bg-image");
  await waitForImageLoad(mapImage);
  setLoadingProgress(85);

  renderMapsOnBackground();
  setLoadingProgress(100);

  clearInterval(loadingTimer);

  setTimeout(() => {
    hideLoadingScreen();
  }, 350);
};

function updateCoinUI() {
  const coinEl = document.getElementById("coin-count");
  if (coinEl) {
    coinEl.innerText = String(playerCoins);
  }
}

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

    point.style.position = "absolute";
    point.style.left = position.left;
    point.style.top = position.top;
    point.style.transform = "translate(-50%, -50%)";
    point.onclick = (e) => {
      e.stopPropagation();
      handleMapClick(map);
    };

    let pointHTML = `
      <div class="map-point-content">
        <img src="${map.image}" alt="${map.name}" class="map-point-img" onerror="this.src='https://via.placeholder.com/80x80?text=${encodeURIComponent(map.name)}'">
        <div class="map-name-label">${map.name}</div>
      </div>
    `;

    if (!isUnlocked) {
      pointHTML += `
        <div class="lock-icon-point">LOCK</div>
        <div class="price-tag-point">💰 ${map.cost}</div>
      `;
    } else if (isCleared) {
      pointHTML += `<div class="price-tag-point">CLEAR</div>`;
    }

    point.innerHTML = pointHTML;
    overlay.appendChild(point);
  });
}

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

async function handleMapClick(map) {
  if (map.is_unlocked) {
    sessionStorage.setItem("selectedMapId", map.id);
    sessionStorage.setItem("selectedMapName", map.name);
    await postMiningAction("record_map_play", { map_key: map.id }).catch(() => null);
    window.location.href = "mining_single.php";
    return;
  }

  const confirmBuy = confirm(
    `Spend ${map.cost} coins to unlock ${map.name}? Maps must be unlocked in order.`
  );

  if (!confirmBuy) {
    return;
  }

  try {
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

function goBack() {
  window.location.href = "mining_index.php";
}
