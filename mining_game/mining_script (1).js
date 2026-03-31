// script.js - HTML叠加图层版

// ==================== 角色相关变量 ====================
let customCharacterLoaded = false;

// 图层ID映射
const layerMap = {
    'body': 'layer_body',
    'shoes': 'layer_shoes',
    'pants': 'layer_pants',
    'top': 'layer_top',
    'dress': 'layer_dress',
    'suit': 'layer_suit',
    'hair': 'layer_hair',
    'eye': 'layer_eye',
    'eyebrows': 'layer_eyebrows',
    'nose': 'layer_nose',
    'mouse': 'layer_mouse',
    'glass': 'layer_glass',
    'head': 'layer_head',
    'character': 'layer_character',
    'background': 'layer_background'
};

// ==================== 加载自定义角色形象 ====================
async function loadCustomCharacter() {
    try {
        console.log('开始加载自定义角色...');
        
        const response = await fetch('mining_index.php?action=get_character');
        const data = await response.json();
        console.log('角色数据:', data);
        
        if (data.success && data.outfit && Object.keys(data.outfit).length > 0) {
            console.log('找到保存的穿搭，图层:', Object.keys(data.outfit));
            
            // 先隐藏所有图层
            for (let layerId of Object.values(layerMap)) {
                const element = document.getElementById(layerId);
                if (element) {
                    element.style.display = 'none';
                }
            }
            
            // 加载每个图层
            for (let [layerName, imageId] of Object.entries(data.outfit)) {
                const layerElementId = layerMap[layerName];
                if (layerElementId) {
                    const imgElement = document.getElementById(layerElementId);
                    if (imgElement) {
                        const imgUrl = 'mining_index.php?action=get_image&id=' + imageId + '&t=' + Date.now();
                        imgElement.src = imgUrl;
                        imgElement.style.display = 'block';
                        console.log('✅ 加载图层:', layerName);
                    }
                }
            }
            
            customCharacterLoaded = true;
            return true;
        } else {
            console.log('没有找到保存的穿搭，使用默认身体');
            // 只显示身体
            for (let layerId of Object.values(layerMap)) {
                const element = document.getElementById(layerId);
                if (element && layerId !== 'layer_body') {
                    element.style.display = 'none';
                }
            }
            const bodyElement = document.getElementById('layer_body');
            if (bodyElement) {
                bodyElement.src = 'people.png';
                bodyElement.style.display = 'block';
            }
        }
    } catch (error) {
        console.error('加载失败:', error);
    }
    
    return false;
}

// ==================== 更新角色显示 ====================
function updateCharacterDisplay() {
    console.log('角色显示已更新，自定义角色状态:', customCharacterLoaded);
}

// ==================== 返回前一个网页 ====================
function goBack() {
    if (document.referrer && document.referrer !== '') {
        window.history.back();
    } else {
        window.location.href = 'mining_index.php';
    }
}

// 游戏模式选择：单人 / 双人
function startGame(mode) {
    if (mode === 'single') {
        console.log("进入单人模式");
        window.location.href = "mining_map.php"; 
    } else if (mode === 'double') {
        console.log("进入双人模式");
        window.location.href = "mining_match.php"; 
    }
}

// 打开收藏或成就菜单
function openMenu(menu) {
    if (menu === 'collection') {
        console.log("打开收藏界面");
        window.location.href = "mining_collection.php"; 
    } else if (menu === 'achievement') {
        console.log("打开成就界面");
        window.location.href = "mining_achievement.php"; 
    }
}
// ==================== 更新金币 ====================
function updateCoinDisplay() {
    let coins = localStorage.getItem('playerCoins');
    if (coins === null) {
        coins = 1250;
        localStorage.setItem('playerCoins', coins);
    } else {
        coins = parseInt(coins, 10);
    }
    const coinElement = document.getElementById('coin-count');
    if (coinElement) {
        coinElement.innerText = coins;
    }
}

// ==================== 页面加载 ====================
window.onload = async function() {
    console.log('游戏大厅加载中...');
    
    // 更新金币显示
    updateCoinDisplay();
    
    // 加载自定义角色
    await loadCustomCharacter();
    updateCharacterDisplay();
    
    console.log('游戏大厅加载完成');
};