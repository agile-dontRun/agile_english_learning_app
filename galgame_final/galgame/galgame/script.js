// Chapter 1: main story data for the tutorial / prologue
const prologueData = [].concat(chapter1, chapter2, chapter3);

// storyData stores the chapter currently being played
let storyData = [];

// whether the current story is the tutorial
let isPlayingTutorial = false;

let bgmAudio = null;
let currentBgmSrc = "";

let sfxAudio = null;

// current step index in the story array
let currentStep = 0;

// lock: prevent advancing while scene/background is still switching
let isSceneBusy = false;

// ===== Utility helpers =====
function wait(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function collectStoryImages(story) {
  const result = new Set();

  story.forEach((step) => {
    if (step.bg) result.add(step.bg);
    if (Array.isArray(step.images)) {
      step.images.forEach((img) => result.add(img));
    }
  });

  return [...result];
}

async function preloadImages(imageUrls) {
  const total = imageUrls.length;

  if (total === 0) {
    updateLoadingProgress(1, 1, "Loading complete");
    return;
  }

  let loaded = 0;
  updateLoadingProgress(0, total, "Loading campus scenes...");

  await Promise.all(
    imageUrls.map((url) => {
      return new Promise((resolve) => {
        const img = new Image();

        const finish = () => {
          loaded += 1;
          updateLoadingProgress(
            loaded,
            total,
            `Loading scene ${loaded}/${total}...`
          );
          resolve(url);
        };

        img.onload = finish;
        img.onerror = () => {
          console.warn("Preload failed:", url);
          finish();
        };

        img.src = url;
      });
    })
  );
}

// Switch background only after the next image is ready
function setBackgroundImage(url) {
  return new Promise((resolve) => {
    if (!url) {
      resolve();
      return;
    }

    const bg = document.getElementById("bg-image");
    const img = new Image();

    img.onload = async () => {
      bg.src = url;

      // small wait to let browser finish decode/render
      await wait(50);

      // wait one frame so the image is more likely painted on screen
      requestAnimationFrame(() => resolve());
    };

    img.onerror = () => {
      console.error("Background load failed:", url);
      resolve();
    };

    img.src = url;
  });
}

// ===== Main dialogue / UI elements =====
const dialogueBox = document.getElementById("dialogue-box");
const speakerName = document.getElementById("speaker-name");
const dialogueText = document.getElementById("dialogue-text");
const optionsContainer = document.getElementById("options-container");
const mentorOverlay = document.getElementById("mentor-overlay");
const mentorSprite = document.getElementById("mentor-sprite");
const characterSprite = document.getElementById("character-sprite");
const avatarBox = document.getElementById("speaker-avatar-box");
const avatarImg = document.getElementById("speaker-avatar");

const loadingScreen = document.getElementById("loading-screen");
const loadingBarInner = document.getElementById("loading-bar-inner");
const loadingPercent = document.getElementById("loading-percent");
const loadingSubtitle = document.getElementById("loading-subtitle");

function updateLoadingProgress(current, total, label = "Loading campus scenes...") {
  const safeTotal = Math.max(total, 1);
  const percent = Math.round((current / safeTotal) * 100);

  if (loadingBarInner) {
    loadingBarInner.style.width = `${percent}%`;
  }

  if (loadingPercent) {
    loadingPercent.innerText = `${percent}%`;
  }

  if (loadingSubtitle) {
    loadingSubtitle.innerText = label;
  }
}
// ===== Home screen avatar display =====
const homeAvatarStage = document.getElementById("home-avatar-stage");
const homeAvatarEmpty = document.getElementById("home-avatar-empty");

// API endpoints for loading avatar / outfit info
const ACTIVE_PLAYER_AVATAR_ENDPOINT = "../dress_up_game/api/get_active_avatar.php";
const ACTIVE_OUTFIT_ENDPOINT = "../dress_up_game/api/get_active_outfit.php";

// fallback avatar if player has not customized one yet
const DEFAULT_PLAYER_AVATAR = "../frontend/assets/player.jpg";
let currentPlayerAvatar = DEFAULT_PLAYER_AVATAR;

// render order for dress-up layers
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

// ===== Different major screens =====
const homeScreen = document.getElementById("home-screen");
const floor6Screen = document.getElementById("floor6-screen");

// Show home page and hide story-related UI
function showHomeScreen() {
  stopBgm();
  dialogueBox.classList.add("hidden");
  optionsContainer.classList.add("hidden");
  characterSprite.classList.add("hidden");
  avatarBox.classList.add("hidden");
  mentorOverlay.classList.add("hidden");
  mentorSprite.classList.add("hidden");
  floor6Screen.classList.add("hidden");

  document.getElementById("bg-image").src =
    "../frontend/assets/home_page/home_bg.jpg";
  homeScreen.classList.remove("hidden");
}

// Build possible image paths for one dress-up layer
function buildDressUpImageCandidates(layer) {
  const filePath = layer?.file_path || "";
  const normalizedFilePath = filePath.startsWith("/")
    ? filePath
    : `/${filePath}`;

  return [`/galgame/dress_up_game${normalizedFilePath}`, layer?.url || ""].filter(
    Boolean,
  );
}

// Try candidate image URLs one by one until one works
function setDressUpImageWithFallback(img, candidates, index = 0) {
  if (!img || index >= candidates.length) {
    if (img) {
      img.remove();
    }
    return;
  }

  const candidate = candidates[index];
  img.onerror = () => setDressUpImageWithFallback(img, candidates, index + 1);

  // add timestamp to avoid browser cache issues
  img.src = `${candidate}${candidate.includes("?") ? "&" : "?"}t=${Date.now()}`;
}

// Clear all rendered avatar layers on the home page
function resetHomeAvatarStage() {
  if (!homeAvatarStage) {
    return;
  }

  Array.from(homeAvatarStage.querySelectorAll(".home-avatar-layer")).forEach(
    (node) => node.remove(),
  );

  if (homeAvatarEmpty) {
    homeAvatarEmpty.style.display = "flex";
  }
}

// Render the player's current outfit on the home screen
function renderHomeAvatarLook(data) {
  if (!homeAvatarStage) {
    return;
  }

  resetHomeAvatarStage();

  if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
    return;
  }

  if (homeAvatarEmpty) {
    homeAvatarEmpty.style.display = "none";
  }

  const layerMap = new Map();
  for (const layer of data.layers) {
    if (layer && layer.layer) {
      layerMap.set(layer.layer, layer);
    }
  }

  for (const layerName of dressUpLayerOrder) {
    const layer = layerMap.get(layerName);

    if (!layer || layerName === "background") {
      continue;
    }

    const img = document.createElement("img");
    img.className = "home-avatar-layer";
    img.alt = layer.name || layerName;
    homeAvatarStage.appendChild(img);

    setDressUpImageWithFallback(img, buildDressUpImageCandidates(layer));
  }
}

// Load avatar outfit data for the home page
async function loadHomeAvatarLook() {
  try {
    const response = await fetch(`${ACTIVE_OUTFIT_ENDPOINT}?_=${Date.now()}`, {
      cache: "no-store",
      credentials: "include",
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();
    renderHomeAvatarLook(data);
  } catch (error) {
    console.error("Failed to load home avatar look:", error);
    resetHomeAvatarStage();
  }
}

// Load the player's profile avatar used in dialogue UI
async function loadPlayerAvatar() {
  try {
    const res = await fetch(`${ACTIVE_PLAYER_AVATAR_ENDPOINT}?_=${Date.now()}`, {
      credentials: "include",
      cache: "no-store",
    });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    const data = await res.json();

    if (data && data.success && data.avatar_url) {
      currentPlayerAvatar = `${data.avatar_url}${data.avatar_url.includes("?") ? "&" : "?"}t=${Date.now()}`;
      return;
    }
  } catch (err) {
    console.error("Failed to load player avatar:", err);
  }

  currentPlayerAvatar = DEFAULT_PLAYER_AVATAR;
}

// Return current avatar URL safely
function getPlayerAvatarUrl() {
  return currentPlayerAvatar || DEFAULT_PLAYER_AVATAR;
}

// Ask backend whether tutorial has already been completed
async function getTutorialStatus() {
  try {
    const res = await fetch("../api/get_tutorial_status.php", {
      credentials: "include",
    });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    return await res.json();
  } catch (err) {
    console.error("Failed to get tutorial status:", err);
    return {
      success: false,
      is_tutorial_completed: false,
    };
  }
}

// Mark tutorial as completed in backend
async function markTutorialCompleted() {
  try {
    const res = await fetch("../api/complete_tutorial.php", {
      method: "POST",
      credentials: "include",
    });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    return await res.json();
  } catch (err) {
    console.error("Failed to mark tutorial as completed:", err);
    return { success: false };
  }
}

// Game startup logic
async function initGame() {
   try {
    updateLoadingProgress(0, 1, "Loading player profile...");
    await loadPlayerAvatar();

    updateLoadingProgress(0, 1, "Loading avatar outfit...");
    await loadHomeAvatarLook();

    const storyImages = collectStoryImages(prologueData);
    await preloadImages(storyImages);

    updateLoadingProgress(1, 1, "Loading complete");

    const data = await getTutorialStatus();
    const params = new URLSearchParams(window.location.search);
    const view = params.get("view");

    if (data.success && data.is_tutorial_completed) {
      showHomeScreen();

      if (view === "floor6") {
        goToFloor6();
      }
    } else {
      startStory(prologueData, true);
    }
  } finally {
    setTimeout(() => {
      hideLoadingScreen();
    }, 250);
  }
}

function hideLoadingScreen() {
  if (!loadingScreen) return;
  loadingScreen.classList.add("hidden-loading");
}

// Render whatever the current step is
async function renderStep() {
  // story finished
  if (currentStep >= storyData.length) {
    dialogueBox.classList.add("hidden");
    characterSprite.classList.add("hidden");
    avatarBox.classList.add("hidden");

    if (isPlayingTutorial) {
      markTutorialCompleted();
      isPlayingTutorial = false;
    }

    showHomeScreen();
    return;
  }

  const currentData = storyData[currentStep];

  if (currentData.bgm) {
    playBgm(currentData.bgm, currentData.loop !== false);
  }

  if (currentData.stopBgm) {
    stopBgm();
  }

  if (currentData.sfx) {
    playSfx(currentData.sfx);
  }

  // ===== Normal dialogue step =====
  if (currentData.type === "dialogue") {
    isSceneBusy = true;

    if (currentData.bg) {
      await setBackgroundImage(currentData.bg);
    }

    dialogueBox.classList.remove("hidden");
    optionsContainer.classList.add("hidden");
    speakerName.innerText = currentData.speaker;
    dialogueText.innerText = currentData.text;

    if (
      currentData.speaker === "Narration" ||
      currentData.speaker === "System Prompt"
    ) {
      avatarBox.classList.add("hidden");
      characterSprite.classList.add("hidden");
    } else {
      avatarBox.classList.remove("hidden");

      if (currentData.speaker === "Karen") {
        characterSprite.classList.remove("hidden");
        avatarImg.src = "../frontend/assets/karen.png";
        characterSprite.src = "../frontend/assets/karen.png";
      } else if (currentData.speaker === "Xiaowang") {
        characterSprite.classList.remove("hidden");
        avatarImg.src = "../frontend/assets/XiaoWang.png";
        characterSprite.src = "../frontend/assets/XiaoWang.png";
      } else if (currentData.speaker === "barista") {
        characterSprite.classList.remove("hidden");
        avatarImg.src = "../frontend/assets/coffee_maker.png";
        characterSprite.src = "../frontend/assets/coffee_maker.png";
      } else if (currentData.speaker === "canteen server") {
        characterSprite.classList.remove("hidden");
        avatarImg.src = "../frontend/assets/chef.png";
        characterSprite.src = "../frontend/assets/chef.png";
      } else if (currentData.speaker === "😎 Oral Tutor") {
        avatarBox.classList.add("hidden");
        characterSprite.classList.add("hidden");
      } else {
        characterSprite.classList.add("hidden");
        avatarImg.src = getPlayerAvatarUrl();
      }
    }

    isSceneBusy = false;

    // ===== Choice step =====
  } else if (currentData.type === "choice") {
    dialogueBox.classList.add("hidden");
    optionsContainer.classList.remove("hidden");
    optionsContainer.innerHTML = "";

    currentData.options.forEach((option) => {
      const btn = document.createElement("button");
      btn.className = "option-btn";
      btn.innerText = option.text;
      btn.onclick = () => handleChoice(option);
      optionsContainer.appendChild(btn);
    });

    // ===== Transition step =====
  } else if (currentData.type === "transition") {
    isSceneBusy = true;

    dialogueBox.classList.add("hidden");
    optionsContainer.classList.add("hidden");
    characterSprite.classList.add("hidden");
    avatarBox.classList.add("hidden");

    const delayTime = currentData.timePerImage || 1000;

    if (!currentData.images || currentData.images.length === 0) {
      isSceneBusy = false;
      advanceStory();
      return;
    }

    for (const imgUrl of currentData.images) {
      await setBackgroundImage(imgUrl);
      await wait(delayTime);
    }

    isSceneBusy = false;
    advanceStory();

    // ===== Exploration choice step =====
  } else if (currentData.type === "explore_choice") {
    dialogueBox.classList.add("hidden");
    optionsContainer.classList.remove("hidden");
    optionsContainer.innerHTML = "";

    currentData.options.forEach((option) => {
      const btn = document.createElement("button");
      btn.className = "option-btn";
      btn.innerText = option.text;
      btn.onclick = () => handleExploreChoice(option);
      optionsContainer.appendChild(btn);
    });
  }
}

// Handle normal choices with right / wrong feedback
function handleChoice(option) {
  if (option.isCorrect) {
    optionsContainer.classList.add("hidden");
    dialogueBox.classList.remove("hidden");

    speakerName.innerText = "You";
    dialogueText.innerText = option.response;

    avatarBox.classList.remove("hidden");
    avatarImg.src = getPlayerAvatarUrl();
    characterSprite.classList.add("hidden");
  } else {
    optionsContainer.classList.add("hidden");
    dialogueBox.classList.remove("hidden");

    mentorOverlay.classList.remove("hidden");
    characterSprite.style.filter = "brightness(30%)";

    speakerName.innerText = "😎 Oral Tutor";
    speakerName.style.color = "#FF6347";
    dialogueText.innerText =
      option.mentorText + " (Click on the screen to select again)";
    avatarBox.classList.add("hidden");

    dialogueBox.onclick = resetFromMentor;
  }
}

// Close mentor feedback and return to current choice step
function resetFromMentor(e) {
  e.stopPropagation();
  mentorOverlay.classList.add("hidden");
  mentorSprite.classList.add("hidden");
  characterSprite.style.filter = "brightness(100%)";
  speakerName.style.color = "#87CEEB";

  dialogueBox.onclick = advanceStory;
  renderStep();
}

// Advance to next story step
function advanceStory() {
  if (isSceneBusy) {
    return;
  }

  if (!optionsContainer.classList.contains("hidden")) {
    return;
  }

  currentStep++;
  renderStep();
}

// Handle explore choice
function handleExploreChoice(option) {
  optionsContainer.classList.add("hidden");
  dialogueBox.classList.remove("hidden");

  if (option.subStory && option.subStory.length > 0) {
    storyData.splice(currentStep + 1, 0, ...option.subStory);
  }

  advanceStory();
}

// ================= Interface switching + story startup =================

// Start a new story chapter
function startStory(newChapterData, tutorialMode = false) {
  storyData = newChapterData;
  currentStep = 0;
  isPlayingTutorial = tutorialMode;
  isSceneBusy = false;

  homeScreen.classList.add("hidden");
  floor6Screen.classList.add("hidden");
  dialogueBox.classList.remove("hidden");
  optionsContainer.classList.add("hidden");
  mentorOverlay.classList.add("hidden");
  mentorSprite.classList.add("hidden");
  characterSprite.style.filter = "brightness(100%)";

  renderStep();
}

// Enter floor 6 game selection screen
function goToFloor6() {
  homeScreen.classList.add("hidden");
  floor6Screen.classList.remove("hidden");
}

// Back to home page from floor 6
function goToHome() {
  floor6Screen.classList.add("hidden");
  homeScreen.classList.remove("hidden");
}

// Launch mini-game by name
function launchGame(gameName) {
  if (gameName === "miner") {
    window.location.href = "../../mining_index.php";
  } else if (gameName === "match") {
    window.location.href = "../../memory_home.php";
  }
}

// ================= Universal scene jump helper =================
function goToScenario(bgUrl, chapterData) {
  document.getElementById("bg-image").src = bgUrl;
  startStory(chapterData);
}

// Audio controls
function playBgm(src, loop = true) {
  if (!src) return;

  if (currentBgmSrc === src && bgmAudio) {
    return;
  }

  stopBgm();

  bgmAudio = new Audio(src);
  bgmAudio.loop = loop;
  bgmAudio.volume = 0.4;
  bgmAudio.play().catch((err) => {
    console.warn("BGM Playback failed:", err);
  });

  currentBgmSrc = src;
}

function stopBgm() {
  if (bgmAudio) {
    bgmAudio.pause();
    bgmAudio.currentTime = 0;
    bgmAudio = null;
  }
  currentBgmSrc = "";
}

function playSfx(src, volume = 0.8) {
  if (!src) return;

  if (sfxAudio) {
    sfxAudio.pause();
    sfxAudio.currentTime = 0;
  }

  sfxAudio = new Audio(src);
  sfxAudio.volume = volume;
  sfxAudio.play().catch((err) => {
    console.warn("SFX Playback failed:", err);
  });
}

// Global click binding: click dialogue box to continue story
dialogueBox.onclick = advanceStory;

// Start the whole game
initGame();