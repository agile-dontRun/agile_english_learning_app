// Song list loaded from the global window object
const songs = window.songList || [];

// Main audio player element
const audioPlayer = document.getElementById("audioPlayer");

// UI elements for displaying current song info
const coverImage = document.getElementById("coverImage");
const songTitle = document.getElementById("songTitle");
const songArtist = document.getElementById("songArtist");
const songDescription = document.getElementById("songDescription");

// Player control buttons
const playBtn = document.getElementById("playBtn");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");

// Progress bar and time display
const progressBar = document.getElementById("progressBar");
const currentTime = document.getElementById("currentTime");
const duration = document.getElementById("duration");

// Extra status labels in the UI
const playState = document.getElementById("playState");
const deskLabel = document.getElementById("deskLabel");
const moodLabel = document.getElementById("moodLabel");

// All playlist buttons on the page
const playlistButtons = Array.from(document.querySelectorAll(".playlist-item"));

// Current song index
let currentIndex = 0;

// Flag to prevent progress bar updates while the user is dragging it
let isSeeking = false;

// Format seconds into mm:ss
function formatTime(time) {
    if (!Number.isFinite(time)) {
        return "00:00";
    }

    const minutes = Math.floor(time / 60);
    const seconds = Math.floor(time % 60);
    return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
}

// Update which playlist item is highlighted as active
function updatePlaylistState() {
    playlistButtons.forEach((button, index) => {
        button.classList.toggle("active", index === currentIndex);
    });
}

// Change the play button icon depending on play/pause state
function setPlayButtonIcon(isPlaying) {
    playBtn.setAttribute("aria-label", isPlaying ? "Pause" : "Play");
    playBtn.innerHTML = isPlaying
        ? '<span class="icon icon-pause" aria-hidden="true"><span></span><span></span></span>'
        : '<span class="icon icon-play" aria-hidden="true"><span></span></span>';
}

// Load a song by index and optionally start playing it immediately
function loadSong(index, shouldAutoplay = false) {
    const song = songs[index];

    if (!song) {
        return;
    }

    currentIndex = index;
    coverImage.src = song.cover;
    songTitle.textContent = song.title;
    songArtist.textContent = song.artist;
    songDescription.textContent = song.description;
    audioPlayer.src = song.audio;

    // Alternate the mood label depending on the song position
    moodLabel.textContent = index % 2 === 0 ? "Golden Hour Set" : "Night Crowd Pulse";

    // Update the deck label based on whether the track should start automatically
    deskLabel.textContent = shouldAutoplay ? "Stage live and rolling" : "Queued for soundcheck";

    // Reset progress and time display
    progressBar.max = 0;
    progressBar.value = 0;
    currentTime.textContent = "00:00";
    duration.textContent = "00:00";
    playState.textContent = "Ready";

    updatePlaylistState();

    if (shouldAutoplay) {
        audioPlayer.play();
    }
}

// Play or pause when the main play button is clicked
playBtn.addEventListener("click", () => {
    if (!audioPlayer.src) {
        loadSong(currentIndex, true);
        return;
    }

    if (audioPlayer.paused) {
        audioPlayer.play();
    } else {
        audioPlayer.pause();
    }
});

// Go to the previous song
prevBtn.addEventListener("click", () => {
    const nextIndex = (currentIndex - 1 + songs.length) % songs.length;
    loadSong(nextIndex, true);
});

// Go to the next song
nextBtn.addEventListener("click", () => {
    const nextIndex = (currentIndex + 1) % songs.length;
    loadSong(nextIndex, true);
});

// Click a playlist item to load and play that song
playlistButtons.forEach((button) => {
    button.addEventListener("click", () => {
        const index = Number(button.dataset.index);
        loadSong(index, true);
    });
});

// When playback starts
audioPlayer.addEventListener("play", () => {
    setPlayButtonIcon(true);
    playState.textContent = "Playing";
    deskLabel.textContent = "Live mix in progress";
    document.body.classList.add("is-playing");
});

// When playback pauses
audioPlayer.addEventListener("pause", () => {
    setPlayButtonIcon(false);
    playState.textContent = "Paused";
    deskLabel.textContent = "Paused on stage";
    document.body.classList.remove("is-playing");
});

// When song metadata is ready, update the total duration
audioPlayer.addEventListener("loadedmetadata", () => {
    progressBar.max = audioPlayer.duration || 0;
    duration.textContent = formatTime(audioPlayer.duration);
    playState.textContent = "Loaded";
    deskLabel.textContent = "Track loaded to deck";
});

// Update progress bar and current time while the song is playing
audioPlayer.addEventListener("timeupdate", () => {
    if (audioPlayer.duration && !isSeeking) {
        progressBar.value = audioPlayer.currentTime;
    }

    currentTime.textContent = formatTime(audioPlayer.currentTime);
});

// Mark that the user has started dragging/seeking
function startSeeking() {
    isSeeking = true;
}

// Update playback position while dragging the progress bar
progressBar.addEventListener("input", () => {
    if (!audioPlayer.duration) {
        return;
    }

    isSeeking = true;
    audioPlayer.currentTime = Number(progressBar.value);
    currentTime.textContent = formatTime(Number(progressBar.value));
});

// Stop seek mode after the drag is finished
progressBar.addEventListener("change", () => {
    isSeeking = false;
});

// Start seek mode on mouse or touch interaction
progressBar.addEventListener("mousedown", startSeeking);
progressBar.addEventListener("touchstart", startSeeking, { passive: true });

// After seeking is completed
audioPlayer.addEventListener("seeked", () => {
    isSeeking = false;
    playState.textContent = "Playing";
    deskLabel.textContent = "Jumped to selected cue";
});

// When the current song ends, automatically play the next one
audioPlayer.addEventListener("ended", () => {
    playState.textContent = "Ended";
    deskLabel.textContent = "Switching to next performer";
    document.body.classList.remove("is-playing");
    const nextIndex = (currentIndex + 1) % songs.length;
    loadSong(nextIndex, true);
});

// Load the first song on page startup
loadSong(0);

// Initialize the play button as "not playing"
setPlayButtonIcon(false);