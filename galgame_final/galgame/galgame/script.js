// Chapter 1: main story data for the tutorial / prologue
const prologueData = [].concat(chapter1, chapter2, chapter3);

// storyData stores the chapter currently being played
let storyData = [];

// whether the current story is the tutorial
let isPlayingTutorial = false;

// current step index in the story array
let currentStep = 0;

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
// lower items are drawn first, upper ones later
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
// This is mainly for fallback: if one path fails, try the next one
function buildDressUpImageCandidates(layer) {
  const filePath = layer?.file_path || "";
  const normalizedFilePath = filePath.startsWith("/") ? filePath : `/${filePath}`;
  return [
    `/galgame/dress_up_game${normalizedFilePath}`,
    layer?.url || "",
  ].filter(Boolean);
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

  Array.from(homeAvatarStage.querySelectorAll(".home-avatar-layer")).forEach((node) =>
    node.remove(),
  );

  // show empty placeholder if nothing is rendered
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

  // no valid outfit data, keep empty state
  if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
    return;
  }

  if (homeAvatarEmpty) {
    homeAvatarEmpty.style.display = "none";
  }

  // map layers by layer name for easier lookup
  const layerMap = new Map();
  for (const layer of data.layers) {
    if (layer && layer.layer) {
      layerMap.set(layer.layer, layer);
    }
  }

  // render layer by layer in correct order
  for (const layerName of dressUpLayerOrder) {
    const layer = layerMap.get(layerName);

    // skip missing layers and background
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
      // add timestamp to force refresh if avatar was recently updated
      currentPlayerAvatar = `${data.avatar_url}${data.avatar_url.includes("?") ? "&" : "?"}t=${Date.now()}`;
      return;
    }
  } catch (err) {
    console.error("Failed to load player avatar:", err);
  }

  // fallback to default avatar
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
    await loadPlayerAvatar();
    await loadHomeAvatarLook();

    const data = await getTutorialStatus();
    const params = new URLSearchParams(window.location.search);
    const view = params.get("view");

    // if tutorial is already done, go straight to home page
    if (data.success && data.is_tutorial_completed) {
        showHomeScreen();

        // optional direct jump to floor6 via URL param
        if (view === "floor6") {
            goToFloor6();
        }
    } else {
    // otherwise start tutorial story
    startStory(prologueData, true);
  }
}

// Render whatever the current step is
function renderStep() {
  // story finished
  if (currentStep >= storyData.length) {
    dialogueBox.classList.add("hidden");
    characterSprite.classList.add("hidden");
    avatarBox.classList.add("hidden");

    // if tutorial just ended, mark it as completed
    if (isPlayingTutorial) {
      markTutorialCompleted();
      isPlayingTutorial = false;
    }

    showHomeScreen();
    return;
  }

  const currentData = storyData[currentStep];

  // ===== Normal dialogue step =====
  if (currentData.type === "dialogue") {
    dialogueBox.classList.remove("hidden");
    optionsContainer.classList.add("hidden");
    speakerName.innerText = currentData.speaker;
    dialogueText.innerText = currentData.text;

    // narration / system lines do not need avatar or character sprite
    if (
      currentData.speaker === "Narration" ||
      currentData.speaker === "System Prompt"
    ) {
      avatarBox.classList.add("hidden");
      characterSprite.classList.add("hidden");
    } else {
      avatarBox.classList.remove("hidden");

      // switch character sprite/avatar depending on speaker
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
      } else if(currentData.speaker === "😎 Oral Tutor"){
        // tutor currently does not use normal avatar display
        avatarBox.classList.add("hidden");
        characterSprite.classList.add("hidden");
      } else {
        // default case: assume the speaker is the player
        characterSprite.classList.add("hidden");
        avatarImg.src = getPlayerAvatarUrl();
      }
    }

    // update background if current step specifies one
    if (currentData.bg) {
      document.getElementById("bg-image").src = currentData.bg;
    }

  // ===== Choice step =====
  } else if (currentData.type === "choice") {
    dialogueBox.classList.add("hidden");
    optionsContainer.classList.remove("hidden");
    optionsContainer.innerHTML = "";

    // render all choice buttons
    currentData.options.forEach((option) => {
      const btn = document.createElement("button");
      btn.className = "option-btn";
      btn.innerText = option.text;
      btn.onclick = () => handleChoice(option);
      optionsContainer.appendChild(btn);
    });

  // ===== Transition step (used for scene switching / walking) =====
  } else if (currentData.type === "transition") {
    dialogueBox.classList.add("hidden");
    optionsContainer.classList.add("hidden");
    characterSprite.classList.add("hidden");

    const bgElement = document.getElementById("bg-image");
    const delayTime = currentData.timePerImage || 1000;

    // if no images provided, just skip
    if (!currentData.images || currentData.images.length === 0) {
      advanceStory();
      return;
    }

    bgElement.src = currentData.images[0];

    // if only one image, wait once and continue
    if (currentData.images.length === 1) {
      setTimeout(() => {
        advanceStory();
      }, delayTime);
    } else {
      // otherwise play image sequence like a mini slideshow
      let imgIndex = 0;
      const walkInterval = setInterval(() => {
        imgIndex++;
        if (imgIndex < currentData.images.length) {
          bgElement.src = currentData.images[imgIndex];
        } else {
          clearInterval(walkInterval);
          advanceStory();
        }
      }, delayTime);
    }

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
    // correct answer: show player's spoken response
    optionsContainer.classList.add("hidden");
    dialogueBox.classList.remove("hidden");

    speakerName.innerText = "You";
    dialogueText.innerText = option.response;

    avatarBox.classList.remove("hidden");
    avatarImg.src = getPlayerAvatarUrl();
    characterSprite.classList.add("hidden");
  } else {
    // wrong answer: trigger mentor feedback and let player retry
    optionsContainer.classList.add("hidden");
    dialogueBox.classList.remove("hidden");

    mentorOverlay.classList.remove("hidden");
    // mentorSprite.classList.remove("hidden");
    characterSprite.style.filter = "brightness(30%)";

    speakerName.innerText = "😎 Oral Tutor";
    speakerName.style.color = "#FF6347";
    dialogueText.innerText = option.mentorText + " (Click on the screen to select again)";
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

  // restore normal click-to-continue behavior
  dialogueBox.onclick = advanceStory;
  renderStep();
}

// Advance to next story step
function advanceStory() {
  // prevent advancing while choices are on screen
  if (!optionsContainer.classList.contains("hidden")) {
    return;
  }
  currentStep++;
  renderStep();
}

// Handle explore choice
// No correct/incorrect logic here; may inject a sub-story into the main flow
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

  // hide menu-like screens, show story UI
  homeScreen.classList.add("hidden");
  floor6Screen.classList.add("hidden");
  dialogueBox.classList.remove("hidden");
  optionsContainer.classList.add("hidden");
  mentorOverlay.classList.add("hidden");
  mentorSprite.classList.add("hidden");
  characterSprite.style.filter = "brightness(100%)";

  // set initial background if provided
  if (storyData[0] && storyData[0].bg) {
    document.getElementById("bg-image").src = storyData[0].bg;
  }

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
  // first force the target background
  document.getElementById("bg-image").src = bgUrl;

  // then start the corresponding chapter data
  startStory(chapterData);
}

// old version of startStory kept here for reference during development
// function startStory(newChapterData) {
//     storyData = newChapterData;
//     currentStep = 0;
//     isPlayingTutorial = tutorialMode;

//     homeScreen.classList.add('hidden');
//     floor6Screen.classList.add('hidden');
//     dialogueBox.classList.remove('hidden');
//     optionsContainer.classList.add('hidden');

//     if (storyData[0] && storyData[0].bg) {
//         document.getElementById('bg-image').src = storyData[0].bg;
//     }

//     renderStep();
// }

// Global click binding: click dialogue box to continue story
dialogueBox.onclick = advanceStory;

// Start the whole game
initGame();