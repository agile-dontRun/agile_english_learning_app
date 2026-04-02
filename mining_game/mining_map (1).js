// ================= 1. Map configuration data =================
const mapsData = [
    { id: 'map1', name: 'Coal Mine', cost: 0, image: 'map1.png' },    // Free at the start
    { id: 'map2', name: 'Silver Mine', cost: 500, image: 'map2.png' },  // Unlocks for 500 coins
    { id: 'map3', name: 'Gold Mine', cost: 1200, image: 'map3.png' }, // Unlocks for 1200 coins
    { id: 'map4', name: 'Diamond Mine', cost: 2500, image: 'map4.png' }, // Unlocks for 2500 coins
    { id: 'map5', name: 'FINAL', cost: 5000, image: 'map5.png' }  // Unlocks for 5000 coins
];

// ================= Map positions on the background image (percentages) =================
// Adjust these percentages based on the actual locations in your map.png
const mapPositions = {
    map1: { left: '17%', top: '65%' },    // Coal mine position
    map2: { left: '35%', top: '40%' },    // Silver mine position
    map3: { left: '50%', top: '78%' },    // Gold mine position
    map4: { left: '56%', top: '55%' },    // Diamond mine position
    map5: { left: '73%', top: '25%' }     // FINAL position
};

// ================= 2. Player data initialization =================
let playerCoins = 0;
let unlockedMaps = [];

// Run when the page loads
window.onload = function() {
    initPlayerData();
    renderMapsOnBackground();
};

function initPlayerData() {
    // Read the player's coins from localStorage; default to 1500 for testing
    playerCoins = parseInt(localStorage.getItem('playerCoins')) || 1500; 
    
    // Read the unlocked map list from localStorage; default to map1 only
    const savedMaps = localStorage.getItem('unlockedMaps');
    if (savedMaps) {
        unlockedMaps = JSON.parse(savedMaps);
    } else {
        unlockedMaps = ['map1']; 
        // Save the initial state
        localStorage.setItem('unlockedMaps', JSON.stringify(unlockedMaps));
    }

    updateCoinUI();
}

function updateCoinUI() {
    document.getElementById('coin-count').innerText = playerCoins;
    // Save coins locally whenever the UI is updated to avoid data loss
    localStorage.setItem('playerCoins', playerCoins);
}

// ================= 3. Render map points on the background image =================
function renderMapsOnBackground() {
    const overlay = document.getElementById('maps-overlay');
    overlay.innerHTML = ''; // Clear the previous content

    mapsData.forEach(map => {
        // Check whether the current map is unlocked
        const isUnlocked = unlockedMaps.includes(map.id);
        
        // Get position settings
        const position = mapPositions[map.id] || { left: '50%', top: '50%' };
        
        // Create the map point container
        const point = document.createElement('div');
        point.className = 'map-point';
        if (!isUnlocked) {
            point.classList.add('locked');
        }
        
        // Set the position using absolute positioning
        point.style.position = 'absolute';
        point.style.left = position.left;
        point.style.top = position.top;
        point.style.transform = 'translate(-50%, -50%)'; // Center the point on its coordinates
        
        // Click handler
        point.onclick = (e) => {
            e.stopPropagation();
            handleMapClick(map, isUnlocked);
        };
        
        // Generate inner HTML, including the map image
        let pointHTML = `
            <div class="map-point-content">
                <img src="${map.image}" alt="${map.name}" class="map-point-img" onerror="this.src='https://via.placeholder.com/80x80?text=${map.name}'">
                <div class="map-name-label">${map.name}</div>
            </div>
        `;
        
        // If locked, show the lock and the price tag
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

// ================= 4. Handle click interactions =================
function handleMapClick(map, isUnlocked) {
    if (isUnlocked) {
        // [Case A] Already unlocked: enter the game directly
        console.log("Entering map:", map.name);
        
        // Pass the selected map to the next page via sessionStorage (single.html)
        sessionStorage.setItem('selectedMapId', map.id);
        sessionStorage.setItem('selectedMapName', map.name);
        
        // Record achievement progress: first trip into the mining area
        let stats = JSON.parse(localStorage.getItem('gameStats')) || {};
        stats.stat_single_played = (stats.stat_single_played || 0) + 1;
        localStorage.setItem('gameStats', JSON.stringify(stats));
        
        window.location.href = 'mining_single.php';

    } else {
        // [Case B] Locked: try to buy it
        if (playerCoins >= map.cost) {
            // Ask for purchase confirmation
            const confirmBuy = confirm(`Spend ${map.cost} coins to unlock [${map.name}]?`);
            if (confirmBuy) {
                // Deduct coins
                playerCoins -= map.cost;
                // Add the new map to the unlocked list
                unlockedMaps.push(map.id);
                
                // Save data and refresh the view
                localStorage.setItem('unlockedMaps', JSON.stringify(unlockedMaps));
                updateCoinUI();
                renderMapsOnBackground(); // Re-render map points
            }
        } else {
            // Not enough coins
            alert(`Not enough coins! Unlocking [${map.name}] costs ${map.cost} coins, and you still need ${map.cost - playerCoins} more. Earn more in other mining areas first!`);
        }
    }
}

// Return to the lobby
function goBack() {
    window.location.href = 'mining_index.php';
}
