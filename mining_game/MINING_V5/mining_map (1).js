
const mapsData = [
    { id: 'map1', name: 'COAL MINE', cost: 0, image: 'map1.png' }, 
    { id: 'map2', name: 'SILVER MINE', cost: 500, image: 'map2.png' },
    { id: 'map3', name: 'GOLD MINE', cost: 1200, image: 'map3.png' }, 
    { id: 'map4', name: 'DIAMOND MINE', cost: 2500, image: 'map4.png' },
    { id: 'map5', name: 'FINAL', cost: 5000, image: 'map5.png' } 
];

const mapPositions = {
    map1: { left: '17%', top: '65%' },    
    map2: { left: '35%', top: '40%' },  
    map3: { left: '50%', top: '78%' },   
    map4: { left: '56%', top: '55%' },  
    map5: { left: '73%', top: '25%' }    
};


let playerCoins = 0;
let unlockedMaps = [];


window.onload = function() {
    initPlayerData();
    renderMapsOnBackground();
};

function initPlayerData() {
 
    playerCoins = parseInt(localStorage.getItem('playerCoins')) || 1500; 
    

    const savedMaps = localStorage.getItem('unlockedMaps');
    if (savedMaps) {
        unlockedMaps = JSON.parse(savedMaps);
    } else {
        unlockedMaps = ['map1']; 

        localStorage.setItem('unlockedMaps', JSON.stringify(unlockedMaps));
    }

    updateCoinUI();
}

function updateCoinUI() {
    document.getElementById('coin-count').innerText = playerCoins;

    localStorage.setItem('playerCoins', playerCoins);
}


function renderMapsOnBackground() {
    const overlay = document.getElementById('maps-overlay');
    overlay.innerHTML = '';

    mapsData.forEach(map => {
     
        const isUnlocked = unlockedMaps.includes(map.id);
        
        const position = mapPositions[map.id] || { left: '50%', top: '50%' };

        const point = document.createElement('div');
        point.className = 'map-point';
        if (!isUnlocked) {
            point.classList.add('locked');
        }

        point.style.position = 'absolute';
        point.style.left = position.left;
        point.style.top = position.top;
        point.style.transform = 'translate(-50%, -50%)'; 
  
        point.onclick = (e) => {
            e.stopPropagation();
            handleMapClick(map, isUnlocked);
        };

        let pointHTML = `
            <div class="map-point-content">
                <img src="${map.image}" alt="${map.name}" class="map-point-img" onerror="this.src='https://via.placeholder.com/80x80?text=${map.name}'">
                <div class="map-name-label">${map.name}</div>
            </div>
        `;

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

function handleMapClick(map, isUnlocked) {
    if (isUnlocked) {

        console.log("ACCESS MAP:", map.name);
        
        sessionStorage.setItem('selectedMapId', map.id);
        sessionStorage.setItem('selectedMapName', map.name);

        let stats = JSON.parse(localStorage.getItem('gameStats')) || {};
        stats.stat_single_played = (stats.stat_single_played || 0) + 1;
        localStorage.setItem('gameStats', JSON.stringify(stats));

        
        window.location.href = 'mining_single.php';

    } else {

        if (playerCoins >= map.cost) {
  
            const confirmBuy = confirm(`SPEND ${map.cost} COIN TO UNLOCK【${map.name}】？`);
            if (confirmBuy) {
                playerCoins -= map.cost;

                unlockedMaps.push(map.id);

                localStorage.setItem('unlockedMaps', JSON.stringify(unlockedMaps));
                updateCoinUI();
                renderMapsOnBackground();
            }
        } else {

            alert(`Not enough gold coins! Unlock【${map.name}】needs ${map.cost} coins，You still lack ${map.cost - playerCoins} coins. Go to other mining areas and earn more money!`);
        }
    }
}

function goBack() {
    window.location.href = 'mining_index.php';
}
