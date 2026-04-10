
const ACHIEVEMENTS = [
    { id: 'ach_first_game', name: 'First Mining Trip', desc: 'Complete one single-player game for the first time.', target: 1, reward: 100, trackKey: 'stat_single_played' },
    { id: 'ach_5_wins', name: 'Winning Streak', desc: 'Win a total of 5 times in two-player online mode.', target: 5, reward: 1000, trackKey: 'stat_double_wins' },
    { id: 'ach_10k_coins', name: 'Coin Tycoon', desc: 'Accumulate 10,000 gold coins from mining.', target: 10000, reward: 500, trackKey: 'stat_total_coins' },
    { id: 'ach_level_5', name: 'Ultimate Miner', desc: 'Reach Level 5 in one single run in single-player mode.', target: 5, reward: 800, trackKey: 'stat_max_level' },
    { id: 'ach_diamond_fail', name: 'Diamond Lost', desc: 'Drop a diamond due to consecutive wrong answers while attempting to grab it.', target: 1, reward: 200, trackKey: 'stat_diamond_fail' }
    
];


let playerCoins = parseInt(localStorage.getItem('playerCoins')) || 0;

let gameStats = JSON.parse(localStorage.getItem('gameStats')) || {};

let claimedAchievements = JSON.parse(localStorage.getItem('claimedAchievements')) || [];

window.onload = function() {
    updateCoinUI();
    renderAchievements();
};

function updateCoinUI() {
    document.getElementById('coin-count').innerText = playerCoins;
}

function renderAchievements() {
    const listDiv = document.getElementById('achievement-list');
    listDiv.innerHTML = '';

    ACHIEVEMENTS.forEach(ach => {

        let currentProgress = gameStats[ach.trackKey] || 0;
        if (currentProgress > ach.target) currentProgress = ach.target; 
        let percent = (currentProgress / ach.target) * 100;
        let isClaimed = claimedAchievements.includes(ach.id);
        let canClaim = currentProgress >= ach.target && !isClaimed;

        
        let btnClass = 'btn-locked';
        let btnText = `Insufficient progress`;
        let onclickAttr = '';

        if (isClaimed) {
            btnClass = 'btn-claimed';
            btnText = 'Already claimed ✓';
        } else if (canClaim) {
            btnClass = 'btn-claimable';
            btnText = `Receive ${ach.reward} 💰`;
            onclickAttr = `onclick="claimReward('${ach.id}', ${ach.reward})"`;
        }

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

function claimReward(achId, rewardAmount) {

    playerCoins += rewardAmount;
    localStorage.setItem('playerCoins', playerCoins);
    
    claimedAchievements.push(achId);
    localStorage.setItem('claimedAchievements', JSON.stringify(claimedAchievements));

    updateCoinUI();
    renderAchievements();
    alert(`Received successfully! Obtained ${rewardAmount} coin！`);
}
