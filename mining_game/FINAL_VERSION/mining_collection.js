// collection.js

// Achievement data for the collection page
// The id here must match the key names in OBJECTS_CONFIG inside single.js
const ACHIEVEMENTS =[
    { id: 'oil', name: 'Age of Black Gold', desc: 'The first barrel of oil was successfully extracted, marking the true birth of an energy tycoon.' },
    { id: 'diamond', name: 'Eternal Promise', desc: 'Dug up an insanely rare diamond, even the dirt is sparkling.' },
    { id: 'ruby', name: 'Bloodstone Agate', desc: 'This ruby holds the blazing might forged deep within the mine.' },
    { id: 'emerald', name: 'Jade Trail', desc: 'An elegant green gem, a symbol of luck and fortune.' },
    { id: 'amethyst', name: 'Amethyst Dream', desc: 'A dreamlike purple crystal, said to bestow wisdom.' },
    { id: 'gold', name: 'Gold Rush', desc: 'High-purity gold ingot, brimming with tangible wealth.' },
    { id: 'silver', name: 'Moonsilver', desc: 'Less precious than gold, yet equally radiant.' },
    { id: 'coin', name: 'Lucky Coin', desc: 'A lucky coin, perhaps left behind by a former adventurer.' }
];

// LocalStorage key used to save whether BGM is enabled
const BGM_STORAGE_KEY = "mining_collection_bgm_enabled";

// Current loading progress shown on the loading screen
let loadingProgress = 0;

// Initialize unlocked collection data from localStorage
let unlockedList = JSON.parse(localStorage.getItem('myMineAchieve')) ||[];

// Update the loading bar and percentage text
function setLoadingProgress(value) {
    const fill = document.getElementById('loading-bar-fill');
    const percent = document.getElementById('loading-percent');

    // Keep progress value between 0 and 100
    loadingProgress = Math.max(0, Math.min(100, value));

    if (fill) {
        fill.style.width = `${loadingProgress}%`;
    }
    if (percent) {
        percent.innerText = `${Math.floor(loadingProgress)}%`;
    }
}

// Hide the loading screen after the page is ready
function hideLoadingScreen() {
    const loadingScreen = document.getElementById('loading-screen');
    if (loadingScreen) {
        loadingScreen.classList.add('hidden');
    }
}

// Update the text shown on the BGM toggle button
function updateBgmButton(enabled) {
    const btn = document.getElementById('bgm-toggle');
    if (btn) {
        btn.innerText = enabled ? 'BGM ON' : 'BGM OFF';
    }
}

// Apply the current BGM state by playing or stopping the audio
async function applyBgmState(enabled) {
    const player = document.getElementById('bgm-player');
    if (!player) return;

    updateBgmButton(enabled);

    if (enabled) {
        try {
            // Try to play BGM when enabled
            await player.play();
        } catch (error) {
            // Some browsers block autoplay before user interaction
            console.warn('Autoplay blocked by browser:', error);
        }
    } else {
        // Stop the music and reset it to the beginning
        player.pause();
        player.currentTime = 0;
    }
}

// Initialize the BGM toggle button and restore saved state
function initBgmControl() {
    const btn = document.getElementById('bgm-toggle');

    // Read BGM state from localStorage
    const enabled = localStorage.getItem(BGM_STORAGE_KEY) === '0';

    updateBgmButton(enabled);
    applyBgmState(enabled);

    if (btn) {
        btn.addEventListener('click', async () => {
            // Get current saved state
            const current = localStorage.getItem(BGM_STORAGE_KEY) === '1';

            // Switch to the opposite state
            const next = !current;

            // Save the new state into localStorage
            localStorage.setItem(BGM_STORAGE_KEY, next ? '1' : '0');

            // Apply the updated BGM state
            await applyBgmState(next);
        });
    }
}

// Initialize the collection page
function init() {
    const grid = document.getElementById('achievements-grid');

    // Clear old card content before rendering again
    grid.innerHTML = '';

    setLoadingProgress(20);

    // Create a card for each collectible item
    ACHIEVEMENTS.forEach((ach, index) => {
        const isUnlocked = unlockedList.includes(ach.id);
        const card = document.createElement('div');

        // Add unlocked or locked style based on collection status
        card.className = `achieve-card ${isUnlocked ? 'unlocked' : 'locked'}`;
        
        // Show real content if unlocked, otherwise show hidden placeholder text
        card.innerHTML = `
            <img src="${ach.id}.png" alt="${ach.name}" onerror="this.src='https://via.placeholder.com/80?text=?'">
            <span class="achieve-name">${isUnlocked ? ach.name : 'Unknown Mineral'}</span>
            <p class="achieve-desc">${isUnlocked ? ach.desc : 'Mine hard to unlock this codex entry!'}</p>
        `;
        grid.appendChild(card);

        // Gradually update loading progress while cards are being rendered
        const percent = 20 + ((index + 1) / ACHIEVEMENTS.length) * 60;
        setLoadingProgress(percent);
    });

    // Update the overall collection progress bar
    updateProgress();

    setLoadingProgress(100);

    // Slight delay before hiding the loading screen for smoother transition
    setTimeout(() => {
        hideLoadingScreen();
    }, 300);
}

// Update the overall collection progress bar and text
function updateProgress() {
    const total = ACHIEVEMENTS.length;
    const current = unlockedList.length;
    const percent = (current / total) * 100;

    document.getElementById('progress-text').innerText = `${current}/${total}`;
    document.getElementById('progress-fill').style.width = percent + '%';
}


// Clear all unlocked collection records
function resetAchievements() {
    if(confirm("Are you sure you want to clear all collection records?")) {
        // Reset local data
        unlockedList =[];

        // Remove saved collection data from localStorage
        localStorage.removeItem('myMineAchieve');

        // Re-render the page after reset
        init();
    }
}

// Run initialization logic after the page finishes loading
window.onload = function () {
    setLoadingProgress(0);
    initBgmControl();
    init();
};