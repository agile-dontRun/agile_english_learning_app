// ================= 1. 地图配置数据 =================
const mapsData = [
    { id: 'map1', name: '煤矿', cost: 0, image: 'map1.png' },    // 初始免费
    { id: 'map2', name: '银矿', cost: 500, image: 'map2.png' },  // 500金币解锁
    { id: 'map3', name: '金矿', cost: 1200, image: 'map3.png' }, // 1200金币解锁
    { id: 'map4', name: '钻石矿', cost: 2500, image: 'map4.png' }, // 2500金币解锁
    { id: 'map5', name: 'FINAL', cost: 5000, image: 'map5.png' }  // 5000金币解锁
];

// ================= 地图在背景图上的位置配置（百分比）=================
// 这些百分比需要根据你的 map.png 实际地图位置来调整
const mapPositions = {
    map1: { left: '17%', top: '65%' },    // 煤矿位置
    map2: { left: '35%', top: '40%' },    // 银矿位置
    map3: { left: '50%', top: '78%' },    // 金矿位置
    map4: { left: '56%', top: '55%' },    // 钻石矿位置
    map5: { left: '73%', top: '25%' }     // FINAL位置
};

// ================= 2. 玩家数据初始化 =================
let playerCoins = 0;
let unlockedMaps = [];

// 页面加载时执行
window.onload = function() {
    initPlayerData();
    renderMapsOnBackground();
};

function initPlayerData() {
    // 从 localStorage 读取玩家金币，如果没有记录，默认给 1500 金币供你测试
    playerCoins = parseInt(localStorage.getItem('playerCoins')) || 1500; 
    
    // 从 localStorage 读取已解锁的地图列表，如果没有，默认只包含 map1
    const savedMaps = localStorage.getItem('unlockedMaps');
    if (savedMaps) {
        unlockedMaps = JSON.parse(savedMaps);
    } else {
        unlockedMaps = ['map1']; 
        // 将初始状态保存下来
        localStorage.setItem('unlockedMaps', JSON.stringify(unlockedMaps));
    }

    updateCoinUI();
}

function updateCoinUI() {
    document.getElementById('coin-count').innerText = playerCoins;
    // 每次更新 UI 时，同步保存金币到本地，防止丢失
    localStorage.setItem('playerCoins', playerCoins);
}

// ================= 3. 在背景图上渲染地图点（带图片）=================
function renderMapsOnBackground() {
    const overlay = document.getElementById('maps-overlay');
    overlay.innerHTML = ''; // 清空之前的容器

    mapsData.forEach(map => {
        // 判断当前地图是否在已解锁列表中
        const isUnlocked = unlockedMaps.includes(map.id);
        
        // 获取位置配置
        const position = mapPositions[map.id] || { left: '50%', top: '50%' };
        
        // 创建地图点容器
        const point = document.createElement('div');
        point.className = 'map-point';
        if (!isUnlocked) {
            point.classList.add('locked');
        }
        
        // 设置位置（使用绝对定位）
        point.style.position = 'absolute';
        point.style.left = position.left;
        point.style.top = position.top;
        point.style.transform = 'translate(-50%, -50%)'; // 使点居中于坐标
        
        // 点击事件
        point.onclick = (e) => {
            e.stopPropagation();
            handleMapClick(map, isUnlocked);
        };
        
        // 生成内部 HTML - 包含地图图片
        let pointHTML = `
            <div class="map-point-content">
                <img src="${map.image}" alt="${map.name}" class="map-point-img" onerror="this.src='https://via.placeholder.com/80x80?text=${map.name}'">
                <div class="map-name-label">${map.name}</div>
            </div>
        `;
        
        // 如果未解锁，添加锁和价格标签
        if (!isUnlocked) {
            pointHTML += `
                <div class="lock-icon-point">🔒</div>
                <div class="price-tag-point">💰 ${map.cost}</div>
            `;
        }
        
        point.innerHTML = pointHTML;
        overlay.appendChild(point);
    });
}

// ================= 4. 处理点击交互 =================
function handleMapClick(map, isUnlocked) {
    if (isUnlocked) {
        // 【情况 A】已解锁：直接进入游戏
        console.log("进入地图:", map.name);
        
        // 使用 sessionStorage 把选中的地图传给下一个页面 (single.html)
        sessionStorage.setItem('selectedMapId', map.id);
        sessionStorage.setItem('selectedMapName', map.name);
        
        // 🌟【新增】记录成就：初入矿区 (累计游玩次数)
        let stats = JSON.parse(localStorage.getItem('gameStats')) || {};
        stats.stat_single_played = (stats.stat_single_played || 0) + 1;
        localStorage.setItem('gameStats', JSON.stringify(stats));
        // 🌟 新增结束
        
        window.location.href = 'mining_single.php';

    } else {
        // 【情况 B】未解锁：尝试购买
        if (playerCoins >= map.cost) {
            // 弹窗确认是否购买
            const confirmBuy = confirm(`是否花费 ${map.cost} 金币解锁【${map.name}】？`);
            if (confirmBuy) {
                // 扣除金币
                playerCoins -= map.cost;
                // 将新地图加入解锁列表
                unlockedMaps.push(map.id);
                
                // 保存数据并刷新画面
                localStorage.setItem('unlockedMaps', JSON.stringify(unlockedMaps));
                updateCoinUI();
                renderMapsOnBackground(); // 重新渲染地图点
            }
        } else {
            // 金币不足
            alert(`金币不足！解锁【${map.name}】需要 ${map.cost} 金币，你还差 ${map.cost - playerCoins} 金币。去其它矿区多赚点钱吧！`);
        }
    }
}

// 返回大厅
function goBack() {
    window.location.href = 'mining_index.php';
}