const introVideoScreen = document.getElementById("intro-video-screen");
const introVideo = document.getElementById("intro-video");
const startIntroBtn = document.getElementById("start-intro-btn");
const skipIntroBtn = document.getElementById("skip-intro-btn");

// ===== 新增：开场视频独立音频 =====
let introAudio = null;
let introFinished = false;
let introSyncTimer = null;

// ===== 新增：清理开场视频资源 =====
function cleanupIntroVideo() {
  if (introSyncTimer) {
    clearInterval(introSyncTimer);
    introSyncTimer = null;
  }

  if (introAudio) {
    introAudio.pause();
    introAudio.currentTime = 0;
    introAudio = null;
  }

  if (introVideo) {
    introVideo.pause();
    introVideo.currentTime = 0;
  }
}

// ===== 新增：结束开场视频，进入后续剧情 =====
function finishIntro(onFinish) {
  if (introFinished) return;
  introFinished = true;

  cleanupIntroVideo();

  if (introVideoScreen) {
    introVideoScreen.classList.add("hidden");
  }

  if (typeof onFinish === "function") {
    onFinish();
  }
}

// ===== 新增：正式开始播放视频和独立音频 =====
function startIntroPlayback(onFinish) {
  introAudio = new Audio("../frontend/assets/intro_video/videoplayback.m4a");
  introAudio.preload = "auto";
  introAudio.volume = 1.0;

  introVideo.onended = function () {
    finishIntro(onFinish);
  };

  introVideo.onpause = function () {
    if (introAudio && !introAudio.paused) {
      introAudio.pause();
    }
  };

  introVideo.onplay = function () {
    if (introAudio && introAudio.paused) {
      introAudio.play().catch(function (err) {
        console.warn("Intro audio play failed:", err);
      });
    }
  };

  introVideo.onseeking = function () {
    if (introAudio) {
      introAudio.currentTime = introVideo.currentTime;
    }
  };

  introVideo.onseeked = function () {
    if (introAudio) {
      introAudio.currentTime = introVideo.currentTime;
    }
  };

  Promise.all([introVideo.play(), introAudio.play()])
    .then(function () {
      introSyncTimer = setInterval(function () {
        if (!introAudio || !introVideo) return;

        const diff = Math.abs(introVideo.currentTime - introAudio.currentTime);

        if (diff > 0.3) {
          introAudio.currentTime = introVideo.currentTime;
        }
      }, 200);
    })
    .catch(function (err) {
      console.warn("Intro video/audio play failed:", err);
      finishIntro(onFinish);
    });
}

// ===== 新增：显示开场视频层 =====
function showIntroVideo(onFinish) {
  if (!introVideoScreen || !introVideo) {
    if (typeof onFinish === "function") {
      onFinish();
    }
    return;
  }

  introFinished = false;
  introVideoScreen.classList.remove("hidden");

  if (skipIntroBtn) {
    skipIntroBtn.onclick = function () {
      finishIntro(onFinish);
    };
  }

  if (startIntroBtn) {
    startIntroBtn.onclick = function () {
      startIntroBtn.disabled = true;
      startIntroBtn.innerText = "Playing...";
      startIntroPlayback(onFinish);
    };
  } else {
    startIntroPlayback(onFinish);
  }
}