// ==================== 成就配置表 ====================
const ACHIEVEMENTS = [
    { id: 'ach_first_game', name: '初入矿区', desc: '首次完成一局单人游戏', target: 1, reward: 100, trackKey: 'stat_single_played' },
    { id: 'ach_5_wins', name: '常胜将军', desc: '在双人联机模式中累计获胜 5 次', target: 5, reward: 1000, trackKey: 'stat_double_wins' },
    { id: 'ach_10k_coins', name: '万贯家财', desc: '累计挖掘获取 10000 金币', target: 10000, reward: 500, trackKey: 'stat_total_coins' },
    { id: 'ach_level_5', name: '无敌矿工', desc: '在单人模式中一次性打到第 5 关', target: 5, reward: 800, trackKey: 'stat_max_level' },
    { id: 'ach_diamond_fail', name: '失之交臂', desc: '抓取钻石时因连续答错导致掉落', target: 1, reward: 200, trackKey: 'stat_diamond_fail' }
    // 未来在这里继续添加新成就即可...
];

// ==================== 初始化与读取数据 ====================
let playerCoins = parseInt(localStorage.getItem('playerCoins')) || 0;
// 所有的统计数据 (比如玩了几次，赢了几次)
let gameStats = JSON.parse(localStorage.getItem('gameStats')) || {};
// 记录已经领过奖的成就 ID
let claimedAchievements = JSON.parse(localStorage.getItem('claimedAchievements')) || [];

window.onload = function() {
    updateCoinUI();
    renderAchievements();
};

function updateCoinUI() {
    document.getElementById('coin-count').innerText = playerCoins;
}

// ==================== 渲染成就列表 ====================
function renderAchievements() {
    const listDiv = document.getElementById('achievement-list');
    listDiv.innerHTML = '';

    ACHIEVEMENTS.forEach(ach => {
        // 读取当前进度
        let currentProgress = gameStats[ach.trackKey] || 0;
        if (currentProgress > ach.target) currentProgress = ach.target; // 进度不超过目标
        
        let percent = (currentProgress / ach.target) * 100;
        let isClaimed = claimedAchievements.includes(ach.id);
        let canClaim = currentProgress >= ach.target && !isClaimed;

        // 决定按钮的状态和文字
        let btnClass = 'btn-locked';
        let btnText = `进度不足`;
        let onclickAttr = '';

        if (isClaimed) {
            btnClass = 'btn-claimed';
            btnText = '已领取 ✓';
        } else if (canClaim) {
            btnClass = 'btn-claimable';
            btnText = `领取 ${ach.reward} 💰`;
            onclickAttr = `onclick="claimReward('${ach.id}', ${ach.reward})"`;
        }

        // 生成卡片 HTML
        const cardHTML = `
            <div class="ach-card">
                <div class="ach-info">
                    <div class="ach-name">${ach.name}</div>
                    <div class="ach-desc">${ach.desc}</div>
                    <div class="progress-bg">
                        <div class="progress-fill" style="width: ${percent}%"></div>
                        <span class="progress-text">${currentProgress} / ${ach.target}</span>
                    </div>
                </div>
                <button class="claim-btn ${btnClass}" ${onclickAttr}>${btnText}</button>
            </div>
        `;
        listDiv.insertAdjacentHTML('beforeend', cardHTML);
    });
}

// ==================== 领取奖励 ====================
function claimReward(achId, rewardAmount) {
    // 加钱
    playerCoins += rewardAmount;
    localStorage.setItem('playerCoins', playerCoins);
    
    // 记录该成就已领取
    claimedAchievements.push(achId);
    localStorage.setItem('claimedAchievements', JSON.stringify(claimedAchievements));

    // 刷新画面
    updateCoinUI();
    renderAchievements();
    alert(`领取成功！获得了 ${rewardAmount} 金币！`);
}
