let customCharacterLoaded = false;

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

async function loadCustomCharacter() {
    try {
        console.log('SELF...');
        
        const response = await fetch('mining_index.php?action=get_character');
        const data = await response.json();
        console.log('SELF_DATA:', data);
        
        if (data.success && data.outfit && Object.keys(data.outfit).length > 0) {
            console.log('FIND SAVED DATA', Object.keys(data.outfit));
            
            for (let layerId of Object.values(layerMap)) {
                const element = document.getElementById(layerId);
                if (element) {
                    element.style.display = 'none';
                }
            }
            
            for (let [layerName, imageId] of Object.entries(data.outfit)) {
                const layerElementId = layerMap[layerName];
                if (layerElementId) {
                    const imgElement = document.getElementById(layerElementId);
                    if (imgElement) {
                        const imgUrl = 'mining_index.php?action=get_image&id=' + imageId + '&t=' + Date.now();
                        imgElement.src = imgUrl;
                        imgElement.style.display = 'block';
                        console.log('✅ UPLOAD:', layerName);
                    }
                }
            }
            
            customCharacterLoaded = true;
            return true;
        } else {
            console.log('DEFAULT BODY');
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
        console.error('UPLOAD FAIL:', error);
    }
    
    return false;
}

function updateCharacterDisplay() {
    console.log('PLAEASE WAIT:', customCharacterLoaded);
}

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
        console.log("ENTER SINGLE MODEL");
        window.location.href = "mining_map.php"; 
    } else if (mode === 'double') {
        console.log("ENTER DOUBLE MODEL");
        window.location.href = "mining_match.php"; 
    }
}

function openMenu(menu) {
    if (menu === 'collection') {
        console.log("OPEN COLLECTION PAGE");
        window.location.href = "mining_collection.php"; 
    } else if (menu === 'achievement') {
        console.log("OPEN ACHIEVEMENT PAGE");
        window.location.href = "mining_achievement.php"; 
    }
}

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

window.onload = async function() {
    console.log('HOME PAGE LOADING...');
    
    updateCoinDisplay();

    await loadCustomCharacter();
    updateCharacterDisplay();
    
    console.log('LOADING FINISHED');
};
