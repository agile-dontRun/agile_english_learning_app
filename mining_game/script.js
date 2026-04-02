// 1. Define achievement data
const ACHIEVEMENTS = [
    { id: 'oil', name: 'Age of Black Gold', desc: 'Successfully extracted your first barrel of oil. You are now a true energy tycoon.' },
    { id: 'diamond', name: 'Eternal Promise', desc: 'You uncovered an extremely rare diamond. It shines brilliantly even in the dirt.' },
    { id: 'ruby', name: 'Crimson Gem', desc: 'This ruby contains the blazing energy hidden deep within the mine.' },
    { id: 'emerald', name: 'Emerald Mystery', desc: 'A fresh and elegant green gem, symbolizing luck and wealth.' },
    { id: 'amethyst', name: 'Amethyst Dreamscape', desc: 'A dreamy purple crystal said to bring wisdom.' },
    { id: 'gold', name: 'Gold Rush', desc: 'A highly pure gold nugget, heavy with the feeling of wealth.' },
    { id: 'silver', name: 'Moonlight Silver', desc: 'Although not as valuable as gold, its shine is just as charming.' },
    { id: 'coin', name: 'First Lucky Coin', desc: 'Perhaps a lucky coin left behind by a previous miner.' }
];

// 2. Initialize state
let unlockedList = JSON.parse(localStorage.getItem('myMineAchieve')) || [];

function init() {
    const grid = document.getElementById('achievements-grid');
    grid.innerHTML = '';

    ACHIEVEMENTS.forEach(ach => {
        const isUnlocked = unlockedList.includes(ach.id);
        const card = document.createElement('div');
        card.className = `achieve-card ${isUnlocked ? 'unlocked' : 'locked'}`;
        
        // Make sure the image path matches your existing PNG files
        card.innerHTML = `
            <img src="${ach.id}.png" alt="${ach.name}">
            <span class="achieve-name">${ach.name}</span>
            <p class="achieve-desc">${isUnlocked ? ach.desc : '??? (Locked)'}</p>
        `;
        grid.appendChild(card);
    });

    updateProgress();
}

// 3. Update progress bar
function updateProgress() {
    const total = ACHIEVEMENTS.length;
    const current = unlockedList.length;
    const percent = (current / total) * 100;

    document.getElementById('progress-text').innerText = `${current}/${total}`;
    document.getElementById('progress-fill').style.width = percent + '%';
}

// 4. Test function: simulate unlocking
function simulateUnlock() {
    const lockedItems = ACHIEVEMENTS.filter(a => !unlockedList.includes(a.id));
    if (lockedItems.length === 0) return alert("All achievements have been unlocked!");

    const randomAch = lockedItems[Math.floor(Math.random() * lockedItems.length)];
    unlockedList.push(randomAch.id);
    localStorage.setItem('myMineAchieve', JSON.stringify(unlockedList));
    init(); // Re-render
}

// 5. Reset
function resetAchievements() {
    if (confirm("Are you sure you want to clear all collection records?")) {
        unlockedList = [];
        localStorage.removeItem('myMineAchieve');
        init();
    }
}

// Start
window.onload = init;