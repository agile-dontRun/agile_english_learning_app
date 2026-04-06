// collection.js

// 1. 定义成就数据 (这里的 id 必须和 single.js 里 OBJECTS_CONFIG 的键名一致！)
const ACHIEVEMENTS =[
    { id: 'oil', name: '黑金时代', desc: '成功开采第一桶石油，真正的能源大亨。' },
    { id: 'diamond', name: '永恒之约', desc: '挖掘到极其稀有的钻石，土里也闪闪发光。' },
    { id: 'ruby', name: '血色玛瑙', desc: '这块红宝石蕴含着矿井深处的炙热能量。' },
    { id: 'emerald', name: '翡翠迷踪', desc: '清新脱俗的绿色宝石，运气与财富的象征。' },
    { id: 'amethyst', name: '紫晶幻境', desc: '梦幻般的紫色晶体，传说能带来智慧。' },
    { id: 'gold', name: '淘金狂潮', desc: '纯度极高的金块，沉甸甸的财富感。' },
    { id: 'silver', name: '月光白银', desc: '虽然不如金子贵，但光泽同样迷人。' },
    { id: 'coin', name: '幸运金币', desc: '或许是哪位前辈留下的幸运金币。' }
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
            <span class="achieve-name">${isUnlocked ? ach.name : '未知矿物'}</span>
            <p class="achieve-desc">${isUnlocked ? ach.desc : '努力挖矿来解锁此图鉴吧！'}</p>
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

// 4. 测试功能：模拟解锁
function simulateUnlock() {
    const lockedItems = ACHIEVEMENTS.filter(a => !unlockedList.includes(a.id));
    if (lockedItems.length === 0) return alert("全部成就已解锁！");

    const randomAch = lockedItems[Math.floor(Math.random() * lockedItems.length)];
    unlockedList.push(randomAch.id);
    localStorage.setItem('myMineAchieve', JSON.stringify(unlockedList));
    init(); // 重新渲染
}

// 5. 重置
function resetAchievements() {
    if(confirm("确定要清空所有收藏记录吗？")) {
        unlockedList =[];
        localStorage.removeItem('myMineAchieve');
        init();
    }
}

window.onload = init;