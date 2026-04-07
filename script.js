// 第一章剧情数据结构
const prologueData = [].concat(chapter1, chapter2, chapter3);

let storyData = [];
let isPlayingTutorial = false;

// 游戏状态变量
let currentStep = 0;

// 获取DOM元素
const dialogueBox = document.getElementById("dialogue-box");
const speakerName = document.getElementById("speaker-name");
const dialogueText = document.getElementById("dialogue-text");
const optionsContainer = document.getElementById("options-container");
const mentorOverlay = document.getElementById("mentor-overlay");
const mentorSprite = document.getElementById("mentor-sprite");
const characterSprite = document.getElementById("character-sprite");
const avatarBox = document.getElementById("speaker-avatar-box");
const avatarImg = document.getElementById("speaker-avatar");
const homeAvatarStage = document.getElementById("home-avatar-stage");
const homeAvatarEmpty = document.getElementById("home-avatar-empty");
const ACTIVE_PLAYER_AVATAR_ENDPOINT = "../dress_up_game/api/get_active_avatar.php";
const ACTIVE_OUTFIT_ENDPOINT = "../dress_up_game/api/get_active_outfit.php";
const DEFAULT_PLAYER_AVATAR = "../frontend/assets/player.jpg";
let currentPlayerAvatar = DEFAULT_PLAYER_AVATAR;
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

// 获取新界面的DOM
const homeScreen = document.getElementById("home-screen");
const floor6Screen = document.getElementById("floor6-screen");

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

function buildDressUpImageCandidates(layer) {
  const filePath = layer?.file_path || "";
  const normalizedFilePath = filePath.startsWith("/") ? filePath : `/${filePath}`;
  return [
    `/galgame/dress_up_game${normalizedFilePath}`,
    layer?.url || "",
  ].filter(Boolean);
}

function setDressUpImageWithFallback(img, candidates, index = 0) {
  if (!img || index >= candidates.length) {
    if (img) {
      img.remove();
    }
    return;
  }

  const candidate = candidates[index];
  img.onerror = () => setDressUpImageWithFallback(img, candidates, index + 1);
  img.src = `${candidate}${candidate.includes("?") ? "&" : "?"}t=${Date.now()}`;
}

function resetHomeAvatarStage() {
  if (!homeAvatarStage) {
    return;
  }

  Array.from(homeAvatarStage.querySelectorAll(".home-avatar-layer")).forEach((node) =>
    node.remove(),
  );

  if (homeAvatarEmpty) {
    homeAvatarEmpty.style.display = "flex";
  }
}

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

function getPlayerAvatarUrl() {
  return currentPlayerAvatar || DEFAULT_PLAYER_AVATAR;
}

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
    console.error("获取教程状态失败：", err);
    return {
      success: false,
      is_tutorial_completed: false,
    };
  }
}

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
    console.error("标记教程完成失败：", err);
    return { success: false };
  }
}

// 初始化游戏
async function initGame() {
  await loadPlayerAvatar();
  await loadHomeAvatarLook();
  const data = await getTutorialStatus();

  if (data.success && data.is_tutorial_completed) {
    showHomeScreen();
  } else {
    startStory(prologueData, true);
  }
}

// 渲染当前剧情步骤
function renderStep() {
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

  if (currentData.type === "dialogue") {
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
      } else {
        characterSprite.classList.add("hidden");
        avatarImg.src = getPlayerAvatarUrl();
      }
    }

    if (currentData.bg) {
      document.getElementById("bg-image").src = currentData.bg;
    }
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
  } else if (currentData.type === "transition") {
    dialogueBox.classList.add("hidden");
    optionsContainer.classList.add("hidden");
    characterSprite.classList.add("hidden");

    const bgElement = document.getElementById("bg-image");
    const delayTime = currentData.timePerImage || 1000;

    if (!currentData.images || currentData.images.length === 0) {
      advanceStory();
      return;
    }

    bgElement.src = currentData.images[0];

    if (currentData.images.length === 1) {
      setTimeout(() => {
        advanceStory();
      }, delayTime);
    } else {
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

// 处理玩家的选择 (带对错判断)
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
    mentorSprite.classList.remove("hidden");
    characterSprite.style.filter = "brightness(30%)";

    speakerName.innerText = "😎 口语导师";
    speakerName.style.color = "#FF6347";
    dialogueText.innerText = option.mentorText + " (点击屏幕重新选择)";
    avatarBox.classList.add("hidden");

    dialogueBox.onclick = resetFromMentor;
  }
}

// 导师退场，时光倒流重新选择
function resetFromMentor(e) {
  e.stopPropagation();
  mentorOverlay.classList.add("hidden");
  mentorSprite.classList.add("hidden");
  characterSprite.style.filter = "brightness(100%)";
  speakerName.style.color = "#87CEEB";
  dialogueBox.onclick = advanceStory;
  renderStep();
}

// 点击对话框推进剧情
function advanceStory() {
  if (!optionsContainer.classList.contains("hidden")) {
    return;
  }
  currentStep++;
  renderStep();
}

// 处理探索选择 (无对错，接入子剧情)
function handleExploreChoice(option) {
  optionsContainer.classList.add("hidden");
  dialogueBox.classList.remove("hidden");

  if (option.subStory && option.subStory.length > 0) {
    storyData.splice(currentStep + 1, 0, ...option.subStory);
  }
  advanceStory();
}

// ================= 界面跳转与游戏启动逻辑 =================
function startStory(newChapterData, tutorialMode = false) {
  storyData = newChapterData;
  currentStep = 0;
  isPlayingTutorial = tutorialMode;

  homeScreen.classList.add("hidden");
  floor6Screen.classList.add("hidden");
  dialogueBox.classList.remove("hidden");
  optionsContainer.classList.add("hidden");
  mentorOverlay.classList.add("hidden");
  mentorSprite.classList.add("hidden");
  characterSprite.style.filter = "brightness(100%)";

  if (storyData[0] && storyData[0].bg) {
    document.getElementById("bg-image").src = storyData[0].bg;
  }

  renderStep();
}

function goToFloor6() {
  homeScreen.classList.add("hidden");
  floor6Screen.classList.remove("hidden");
}

function goToHome() {
  floor6Screen.classList.add("hidden");
  homeScreen.classList.remove("hidden");
}

function launchGame(gameName) {
  if (gameName === "miner") {
    //alert("跳转接口已准备好！日后在这里跳转到 词汇矿工大对决");
    window.location.href = "../../mining_index.php";
  } else if (gameName === "match") {
    window.location.href = "../../memory_home.php";
  }
}

// ================= 新增：万能场景跳转函数 =================
function goToScenario(bgUrl, chapterData) {
  // 1. 强制把底层的背景图，换成你指定的场景图
  document.getElementById("bg-image").src = bgUrl;

  // 2. 调用已有的 startStory 函数，加载对应的剧本并开始播放
  startStory(chapterData);
}

// // 确保你的 startStory 函数是长这样的：
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

// 绑定全局点击事件
dialogueBox.onclick = advanceStory;

// 启动！
initGame();
