// collection.js

// 1. 定义成就数据 (这里的 id 必须和 single.js 里 OBJECTS_CONFIG 的键名一致！)
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

// 2. 初始化状态
let unlockedList = JSON.parse(localStorage.getItem('myMineAchieve')) ||[];

function init() {
    const grid = document.getElementById('achievements-grid');
    grid.innerHTML = '';

    ACHIEVEMENTS.forEach(ach => {
        const isUnlocked = unlockedList.includes(ach.id);
        const card = document.createElement('div');
        card.className = `achieve-card ${isUnlocked ? 'unlocked' : 'locked'}`;
        
        // 当未解锁时，显示带问号的占位图；已解锁则显示真实矿石图
        card.innerHTML = `
            <img src="${ach.id}.png" alt="${ach.name}" onerror="this.src='https://via.placeholder.com/80?text=?'">
            <span class="achieve-name">${isUnlocked ? ach.name : 'Unknown Mineral'}</span>
            <p class="achieve-desc">${isUnlocked ? ach.desc : 'Mine hard to unlock this codex entry!'}</p>
        `;
        grid.appendChild(card);
    });

    updateProgress();
}

// 3. 更新进度条
function updateProgress() {
    const total = ACHIEVEMENTS.length;
    const current = unlockedList.length;
    const percent = (current / total) * 100;

    document.getElementById('progress-text').innerText = `${current}/${total}`;
    document.getElementById('progress-fill').style.width = percent + '%';
}


// 5. 重置
function resetAchievements() {
    if(confirm("Are you sure you want to clear all collection records?")) {
        unlockedList =[];
        localStorage.removeItem('myMineAchieve');
        init();
    }
}

window.onload = init;
