const ACHIEVEMENTS = [
  {
    id: "ach_first_game",
    name: "First Mining Trip",
    desc: "Complete one single-player game for the first time.",
    target: 1,
    reward: 100,
    trackKey: "stat_single_played",
  },
  {
    id: "ach_5_wins",
    name: "Winning Streak",
    desc: "Win a total of 5 times in two-player online mode.",
    target: 5,
    reward: 1000,
    trackKey: "stat_double_wins",
  },
  {
    id: "ach_10k_coins",
    name: "Coin Tycoon",
    desc: "Accumulate 10,000 gold coins from mining.",
    target: 10000,
    reward: 500,
    trackKey: "stat_total_coins",
  },
  {
    id: "ach_level_5",
    name: "Ultimate Miner",
    desc: "Reach Level 5 in one single run in single-player mode.",
    target: 5,
    reward: 800,
    trackKey: "stat_max_level",
  },
  {
    id: "ach_diamond_fail",
    name: "Diamond Lost",
    desc: "Drop a diamond due to consecutive wrong answers while attempting to grab it.",
    target: 1,
    reward: 200,
    trackKey: "stat_diamond_fail",
  },
];

const achievementBootstrap = window.MINING_ACHIEVEMENT_BOOTSTRAP || {
  balance: 0,
  claimedAchievements: [],
};

const BGM_STORAGE_KEY = "mining_achievement_bgm_enabled";
let loadingProgress = 0;

let playerCoins = Number(achievementBootstrap.balance || 0);
let gameStats = JSON.parse(localStorage.getItem("gameStats")) || {};

let claimedAchievements = Array.isArray(achievementBootstrap.claimedAchievements)
  ? [...achievementBootstrap.claimedAchievements]
  : [];

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

function initBgmControl() {
  const btn = document.getElementById("bgm-toggle");
  const enabled = localStorage.getItem(BGM_STORAGE_KEY) !== "0";

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
  initBgmControl();

  setTimeout(() => setLoadingProgress(10), 80);

  await refreshAchievementStatus();
  setLoadingProgress(40);

  await renderAchievements();

  setLoadingProgress(100);

  setTimeout(() => {
    hideLoadingScreen();
  }, 500);
};

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

async function refreshAchievementStatus() {
  try {
    const response = await fetch("mining_coin_api.php?action=achievement_status", {
      cache: "no-store",
    });
    const data = await response.json();
    if (response.ok && data.success) {
      playerCoins = Number(data.balance || 0);
      claimedAchievements = Array.isArray(data.claimed_achievements)
        ? data.claimed_achievements
        : [];
    }
  } catch (error) {
    console.error("Failed to load achievement status:", error);
  }

  updateCoinUI();
}

function updateCoinUI() {
  document.getElementById("coin-count").innerText = String(playerCoins);
}

async function renderAchievements() {
  const listDiv = document.getElementById("achievement-list");
  listDiv.innerHTML = "";

  for (let index = 0; index < ACHIEVEMENTS.length; index++) {
    const ach = ACHIEVEMENTS[index];

    let currentProgress = gameStats[ach.trackKey] || 0;
    if (currentProgress > ach.target) currentProgress = ach.target;

    const progressPercent = (currentProgress / ach.target) * 100;
    const isClaimed = claimedAchievements.includes(ach.id);
    const canClaim = currentProgress >= ach.target && !isClaimed;

    let btnClass = "btn-locked";
    let btnText = "Insufficient progress";

    if (isClaimed) {
      btnClass = "btn-claimed";
      btnText = "Already claimed";
    } else if (canClaim) {
      btnClass = "btn-claimable";
      btnText = `Claim ${ach.reward} Coins`;
    }

    const card = document.createElement("div");
    card.className = "ach-card";
    card.innerHTML = `
      <div class="ach-info">
        <div class="ach-name">${ach.name}</div>
        <div class="ach-desc">${ach.desc}</div>
        <div class="progress-bg">
          <div class="progress-fill" style="width: ${progressPercent}%"></div>
          <span class="progress-text">${currentProgress} / ${ach.target}</span>
        </div>
      </div>
      <button class="claim-btn ${btnClass}">${btnText}</button>
    `;

    const button = card.querySelector("button");
    if (canClaim) {
      button.addEventListener("click", () => claimReward(ach.id));
    } else {
      button.disabled = true;
    }

    listDiv.appendChild(card);

    const loadingPercent = 40 + ((index + 1) / ACHIEVEMENTS.length) * 50;
    setLoadingProgress(loadingPercent);

    await new Promise((resolve) => setTimeout(resolve, 60));
  }
}

async function claimReward(achievementId) {
  try {
    const result = await postMiningAction("claim_achievement", {
      achievement_id: achievementId,
    });

    playerCoins = Number(result.balance || playerCoins);
    if (!claimedAchievements.includes(achievementId)) {
      claimedAchievements.push(achievementId);
    }

    updateCoinUI();
    await renderAchievements();

    const rewardAmount = Number(result.reward_amount || 0);
    if (rewardAmount > 0) {
      alert(`Congratulations! You received ${rewardAmount} coins.`);
    } else {
      alert("This reward has already been claimed.");
    }
  } catch (error) {
    alert(error.message);
  }
}
