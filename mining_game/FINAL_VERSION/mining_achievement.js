// Achievement data used to generate all achievement cards on the page
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

// Initial achievement-related data passed from the backend to the frontend
const achievementBootstrap = window.MINING_ACHIEVEMENT_BOOTSTRAP || {
  balance: 0,
  claimedAchievements: [],
};

// Local storage key used to save whether BGM is enabled or not
const BGM_STORAGE_KEY = "mining_achievement_bgm_enabled";

// Tracks the current loading progress shown on the loading screen
let loadingProgress = 0;

// Current player coin amount, initialized from backend data
let playerCoins = Number(achievementBootstrap.balance || 0);

// Local game statistics used to calculate achievement progress
let gameStats = JSON.parse(localStorage.getItem("gameStats")) || {};

// List of achievements that have already been claimed
let claimedAchievements = Array.isArray(achievementBootstrap.claimedAchievements)
  ? [...achievementBootstrap.claimedAchievements]
  : [];

// Update the loading bar and percentage text on the loading screen
function setLoadingProgress(value) {
  const fill = document.getElementById("loading-bar-fill");
  const percent = document.getElementById("loading-percent");

  // Make sure the progress value always stays between 0 and 100
  loadingProgress = Math.max(0, Math.min(100, value));

  if (fill) {
    fill.style.width = `${loadingProgress}%`;
  }
  if (percent) {
    percent.innerText = `${Math.floor(loadingProgress)}%`;
  }
}

// Hide the loading screen after all content has finished loading
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

// Apply the BGM state by either playing or stopping the audio
async function applyBgmState(enabled) {
  const player = document.getElementById("bgm-player");
  if (!player) return;

  updateBgmButton(enabled);

  if (enabled) {
    try {
      // Try to start playing the background music
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

// Initialize the BGM button and restore the saved BGM setting
function initBgmControl() {
  const btn = document.getElementById("bgm-toggle");

  // Default is enabled unless localStorage explicitly stores "0"
  const enabled = localStorage.getItem(BGM_STORAGE_KEY) !== "0";

  updateBgmButton(enabled);
  applyBgmState(enabled);

  if (btn) {
    btn.addEventListener("click", async () => {
      const current = localStorage.getItem(BGM_STORAGE_KEY) !== "0";
      const next = !current;

      // Save the new BGM state to localStorage
      localStorage.setItem(BGM_STORAGE_KEY, next ? "1" : "0");

      await applyBgmState(next);

      // Remove focus outline after clicking
      btn.blur();
    });
  }
}

// Main page initialization logic when the window finishes loading
window.onload = async function () {
  setLoadingProgress(0);
  initBgmControl();

  // Small fake progress increase for smoother loading feedback
  setTimeout(() => setLoadingProgress(10), 80);

  // Load latest achievement claim data from backend
  await refreshAchievementStatus();
  setLoadingProgress(40);

  // Render all achievement cards
  await renderAchievements();

  setLoadingProgress(100);

  // Hide loading screen slightly later for a smoother transition
  setTimeout(() => {
    hideLoadingScreen();
  }, 500);
};

// Send a POST request to the backend for mining-related actions
async function postMiningAction(action, payload = {}) {
  const formData = new FormData();
  formData.append("action", action);

  // Add all payload fields into the request body
  Object.entries(payload).forEach(([key, value]) => {
    formData.append(key, String(value));
  });

  const response = await fetch("mining_coin_api.php", {
    method: "POST",
    body: formData,
  });

  const data = await response.json();

  // Throw an error if the request failed or backend returns unsuccessful result
  if (!response.ok || !data.success) {
    throw new Error(data.message || data.error || "Request failed.");
  }

  return data;
}

// Fetch the latest achievement status from the backend
async function refreshAchievementStatus() {
  try {
    const response = await fetch("mining_coin_api.php?action=achievement_status", {
      cache: "no-store",
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Update current coin balance from server data
      playerCoins = Number(data.balance || 0);

      // Update claimed achievement list from server data
      claimedAchievements = Array.isArray(data.claimed_achievements)
        ? data.claimed_achievements
        : [];
    }
  } catch (error) {
    console.error("Failed to load achievement status:", error);
  }

  // Refresh the coin display no matter whether request succeeds or fails
  updateCoinUI();
}

// Update the coin number shown on the page
function updateCoinUI() {
  document.getElementById("coin-count").innerText = String(playerCoins);
}

// Render all achievement cards based on current progress and claim status
async function renderAchievements() {
  const listDiv = document.getElementById("achievement-list");

  // Clear old content before re-rendering
  listDiv.innerHTML = "";

  for (let index = 0; index < ACHIEVEMENTS.length; index++) {
    const ach = ACHIEVEMENTS[index];

    // Read current progress from local game stats
    let currentProgress = gameStats[ach.trackKey] || 0;

    // Cap progress at the achievement target
    if (currentProgress > ach.target) currentProgress = ach.target;

    const progressPercent = (currentProgress / ach.target) * 100;
    const isClaimed = claimedAchievements.includes(ach.id);
    const canClaim = currentProgress >= ach.target && !isClaimed;

    // Default button state: locked
    let btnClass = "btn-locked";
    let btnText = "Insufficient progress";

    if (isClaimed) {
      // Achievement reward has already been claimed
      btnClass = "btn-claimed";
      btnText = "Already claimed";
    } else if (canClaim) {
      // Achievement is completed and reward is ready to be claimed
      btnClass = "btn-claimable";
      btnText = `Claim ${ach.reward} Coins`;
    }

    // Create the achievement card element
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
      // Only clickable when the reward can actually be claimed
      button.addEventListener("click", () => claimReward(ach.id));
    } else {
      // Disable the button if not claimable
      button.disabled = true;
    }

    listDiv.appendChild(card);

    // Update loading progress gradually while rendering cards
    const loadingPercent = 40 + ((index + 1) / ACHIEVEMENTS.length) * 50;
    setLoadingProgress(loadingPercent);

    // Small delay to make loading animation look smoother
    await new Promise((resolve) => setTimeout(resolve, 60));
  }
}

// Claim the reward for a finished achievement
async function claimReward(achievementId) {
  try {
    const result = await postMiningAction("claim_achievement", {
      achievement_id: achievementId,
    });

    // Update player balance after claiming reward
    playerCoins = Number(result.balance || playerCoins);

    // Add this achievement into claimed list if it is not already there
    if (!claimedAchievements.includes(achievementId)) {
      claimedAchievements.push(achievementId);
    }

    updateCoinUI();

    // Re-render achievements to refresh button states
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