<?php
session_start();
require_once '../../db_connect.php';
require_once 'includes/functions.php';

$dbImages = [];
$result = $conn->query("SELECT * FROM images WHERE is_enabled = 1");
if ($result instanceof mysqli_result) {
    $dbImages = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

$groupedImages = [];
foreach ($dbImages as $img) {
    $groupedImages[$img['layer_code']][] = $img;
}

$sessionOutfit = applyConflictRules($_SESSION['current_outfit'] ?? []);
$returnUrl = $_GET['return'] ?? '/galgame/index.html';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dream Wardrobe - Dress Up Game</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1400px; margin: 0 auto; }
        .game-panel { background: rgba(255,255,255,0.95); border-radius: 30px; padding: 25px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .top-actions { margin-bottom: 18px; display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap; }
        .game-area { display: flex; gap: 30px; flex-wrap: wrap; }
        .canvas-area { flex: 1.2; min-width: 400px; text-align: center; }
        canvas { background: #f0f0f0; background-image: linear-gradient(45deg, #ddd 25%, transparent 25%), linear-gradient(-45deg, #ddd 25%, transparent 25%); background-size: 20px 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 550px; height: auto; cursor: pointer; }
        .controls-area { flex: 1.8; min-width: 450px; }
        .main-nav { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e0e0e0; }
        .main-nav-btn { padding: 12px 24px; border: none; border-radius: 40px; background: #f0f0f0; cursor: pointer; transition: all 0.3s; font-size: 15px; font-weight: bold; color: #555; }
        .main-nav-btn.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; box-shadow: 0 4px 15px rgba(102,126,234,0.4); }
        .main-nav-btn:hover { transform: translateY(-2px); }
        .sub-nav { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; padding: 10px 15px; background: #f5f5f5; border-radius: 50px; justify-content: center; min-height: 55px; }
        .sub-nav-btn { padding: 8px 20px; border: none; border-radius: 30px; background: white; cursor: pointer; transition: all 0.2s; font-size: 13px; font-weight: 500; color: #666; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .sub-nav-btn.active { background: #764ba2; color: white; }
        .images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 15px; max-height: 420px; overflow-y: auto; padding: 15px; background: #f9f9f9; border-radius: 20px; min-height: 350px; }
        .image-card { background: white; border-radius: 15px; padding: 10px; text-align: center; cursor: pointer; transition: all 0.2s; border: 2px solid transparent; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .image-card.selected { border-color: #764ba2; box-shadow: 0 0 0 3px rgba(118,75,162,0.3); transform: scale(1.02); }
        .image-card img { width: 100%; height: 100px; object-fit: contain; border-radius: 10px; background: #f5f5f5; }
        .image-name { font-size: 12px; margin-top: 8px; color: #666; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .toolbar { margin-top: 25px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        button { padding: 12px 28px; border: none; border-radius: 40px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; cursor: pointer; font-size: 15px; font-weight: bold; transition: all 0.2s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
        .secondary-btn { background: linear-gradient(135deg, #ff9a62, #ff6b6b); }
        .saved-outfits { margin-top: 25px; padding: 20px; background: #f0f0f0; border-radius: 20px; }
        .saved-list { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
        .saved-item { background: white; padding: 8px 18px; border-radius: 30px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .saved-item.used { background: #e8f7ed; border: 2px solid #28a745; }
        .saved-item:hover { background: #764ba2; color: white; }
        .delete-saved { background: #ff6b6b; padding: 2px 8px; border-radius: 50%; font-size: 12px; cursor: pointer; }
        .used-badge { background: #28a745; padding: 3px 8px; border-radius: 999px; font-size: 11px; color: white; }
        .loading { text-align: center; padding: 40px; color: #999; }
        .stats { margin-top: 10px; padding: 8px; text-align: center; font-size: 12px; color: #666; }
        h3 { margin-bottom: 12px; color: #764ba2; }
        .images-grid::-webkit-scrollbar { width: 8px; }
        .images-grid::-webkit-scrollbar-track { background: #e0e0e0; border-radius: 10px; }
        .images-grid::-webkit-scrollbar-thumb { background: #764ba2; border-radius: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="game-panel">
        <div class="top-actions">
            <button id="bindBtn" type="button" class="secondary-btn">Use This Look In Ming_game</button>
            <button type="button" onclick="window.location.href='<?php echo htmlspecialchars($returnUrl, ENT_QUOTES); ?>'">Back to Galgame</button>
        </div>
        <div class="game-area">
            <div class="canvas-area">
                <canvas id="gameCanvas" width="550" height="650"></canvas>
            </div>
            <div class="controls-area">
                <div class="main-nav" id="mainNav"></div>
                <div class="sub-nav" id="subNav"></div>
                <div class="images-grid" id="imagesGrid"><div class="loading">Loading images...</div></div>
            </div>
        </div>
        <div class="toolbar">
            <button id="randomBtn">Random Outfit</button>
            <button id="saveBtn">Save Outfit</button>
            <button id="resetBtn">Reset All</button>
        </div>
        <div class="saved-outfits">
            <h3>My Outfits</h3>
            <div class="saved-list" id="savedList"></div>
        </div>
        <div class="stats" id="stats"></div>
    </div>
</div>

<script>
    const phpImages = <?php echo json_encode($groupedImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const initialOutfit = <?php echo json_encode($sessionOutfit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    let currentOutfit = initialOutfit || {};
    let allImages = {};
    let savedOutfits = [];
    let currentMain = 'body';
    let currentSub = 'body';

    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');

    const mainNavConfig = {
        body: { name: 'Body', layers: ['body'] },
        face: { name: 'Face', subLayers: ['eye', 'eyebrows', 'nose', 'mouse'] },
        hair: { name: 'Hair', layers: ['hair'] },
        clothes: { name: 'Clothes', subLayers: ['top', 'pants', 'dress', 'suit'] },
        shoes: { name: 'Shoes', layers: ['shoes'] },
        sunglasses: { name: 'Glasses', layers: ['glass'] },
        headwear: { name: 'Headwear', layers: ['head'] },
        character: { name: 'Character', layers: ['character'] },
        background: { name: 'Background', layers: ['background'] }
    };

    const subNames = {
        body: 'Body',
        eye: 'Eyes',
        eyebrows: 'Eyebrows',
        nose: 'Nose',
        mouse: 'Mouth',
        hair: 'Hair',
        top: 'Top',
        pants: 'Pants',
        dress: 'Dress',
        suit: 'Suit',
        shoes: 'Shoes',
        glass: 'Glasses',
        head: 'Headwear',
        character: 'Character',
        background: 'Background'
    };

    const layerOrder = [
        'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
        'eye', 'eyebrows', 'nose', 'mouse', 'hair', 'character', 'glass', 'head'
    ];

    for (const [layer, images] of Object.entries(phpImages)) {
        allImages[layer] = images.map((img) => ({
            id: Number(img.id),
            name: img.name,
            full_url: img.full_url || '.' + img.file_path
        }));
    }

    function applyConflictRules(outfit) {
        const result = { ...outfit };
        if (result.dress) {
            delete result.top;
            delete result.pants;
            delete result.suit;
        }
        if (result.suit) {
            delete result.top;
            delete result.pants;
            delete result.dress;
        }
        if (result.character) {
            delete result.eye;
            delete result.eyebrows;
            delete result.nose;
            delete result.mouse;
            delete result.hair;
        }
        return result;
    }

    async function autoImport() {
        try {
            const res = await fetch('api/auto_import.php');
            const data = await res.json();
            if (data.success && data.imported > 0) {
                showMessage(`Auto imported ${data.imported} images`);
                setTimeout(() => location.reload(), 1500);
            }
        } catch (error) {
            console.error(error);
        }
    }

    async function syncCurrentOutfit(showFeedback = false) {
        try {
            const res = await fetch('api/set_current_outfit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: applyConflictRules(currentOutfit) })
            });
            const data = await res.json();
            if (showFeedback) {
                showMessage(data.success ? 'Current look is linked to the galgame' : 'Bind failed', !data.success);
            }
        } catch (error) {
            console.error(error);
            if (showFeedback) {
                showMessage('Bind failed', true);
            }
        }
    }

    async function useThisLookInMingGame() {
        const normalizedOutfit = applyConflictRules(currentOutfit);
        if (Object.keys(normalizedOutfit).length === 0) {
            showMessage('Please create a look first', true);
            return;
        }

        const name = prompt('Name this look for Ming_game:');
        if (!name) {
            return;
        }

        try {
            await syncCurrentOutfit();
            const res = await fetch('api/save_outfit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name,
                    items: normalizedOutfit,
                    is_used: true
                })
            });
            const data = await res.json();
            if (data.success) {
                showMessage('Current look is now active in Ming_game');
                loadSavedOutfits();
            } else {
                showMessage(data.error || 'Failed to apply look', true);
            }
        } catch (error) {
            console.error(error);
            showMessage('Failed to apply look', true);
        }
    }

    async function randomOutfit() {
        try {
            const res = await fetch('api/random_outfit.php');
            const data = await res.json();
            if (data.success) {
                currentOutfit = applyConflictRules(data.outfit || {});
                await renderCanvas();
                renderImagesGrid();
                await syncCurrentOutfit();
                showMessage(`${data.mode} random outfit`);
            }
        } catch (error) {
            console.error(error);
        }
    }

    async function saveOutfit() {
        const name = prompt('Name this outfit:');
        if (!name) {
            return;
        }
        const res = await fetch('api/save_outfit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, items: applyConflictRules(currentOutfit) })
        });
        const data = await res.json();
        if (data.success) {
            await syncCurrentOutfit();
            showMessage('Saved successfully');
            loadSavedOutfits();
        } else {
            showMessage('Save failed', true);
        }
    }

    async function loadSavedOutfits() {
        const res = await fetch('api/get_outfits.php');
        const data = await res.json();
        if (data.success) {
            savedOutfits = data.data;
            renderSavedList();
        }
    }

    function renderSavedList() {
        const container = document.getElementById('savedList');
        if (savedOutfits.length === 0) {
            container.innerHTML = '<div style="color:#999;">No saved outfits</div>';
            return;
        }
        container.innerHTML = savedOutfits.map((outfit) => `
            <div class="saved-item ${Number(outfit.is_used) === 1 ? 'used' : ''}" data-id="${outfit.id}">
                ${outfit.name}
                ${Number(outfit.is_used) === 1 ? '<span class="used-badge">In Use</span>' : ''}
                <span class="delete-saved" data-id="${outfit.id}">x</span>
            </div>
        `).join('');

        document.querySelectorAll('.saved-item').forEach((item) => {
            item.addEventListener('click', async (e) => {
                if (e.target.classList.contains('delete-saved')) {
                    e.stopPropagation();
                    await fetch('api/delete_outfit.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${item.dataset.id}`
                    });
                    loadSavedOutfits();
                    return;
                }

                const res = await fetch(`api/get_outfit.php?id=${item.dataset.id}`);
                const data = await res.json();
                if (data.success) {
                    currentOutfit = applyConflictRules(data.data || {});
                    await renderCanvas();
                    renderImagesGrid();
                    await syncCurrentOutfit();
                    const outfitName = item.textContent.replace('x', '').trim();
                    showMessage(`Loaded: ${outfitName}`);
                }
            });
        });
    }

    function updateOutfit(layer, imageId) {
        if (imageId === null) {
            delete currentOutfit[layer];
        } else {
            currentOutfit[layer] = imageId;
            if (layer === 'dress') {
                delete currentOutfit.top;
                delete currentOutfit.pants;
                delete currentOutfit.suit;
            }
            if (layer === 'suit') {
                delete currentOutfit.top;
                delete currentOutfit.pants;
                delete currentOutfit.dress;
            }
            if (layer === 'character') {
                delete currentOutfit.eye;
                delete currentOutfit.eyebrows;
                delete currentOutfit.nose;
                delete currentOutfit.mouse;
                delete currentOutfit.hair;
            }
            const faceParts = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
            if (faceParts.includes(layer)) {
                delete currentOutfit.character;
            }
            if (layer === 'top' || layer === 'pants') {
                delete currentOutfit.dress;
                delete currentOutfit.suit;
            }
        }
        currentOutfit = applyConflictRules(currentOutfit);
        renderCanvas();
        renderImagesGrid();
        syncCurrentOutfit();
    }

    async function renderCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const outfitToRender = applyConflictRules(currentOutfit);
        for (const layer of layerOrder) {
            const imageId = outfitToRender[layer];
            if (!imageId) {
                continue;
            }
            const imgData = allImages[layer]?.find((img) => img.id === Number(imageId));
            if (!imgData) {
                continue;
            }
            const img = new Image();
            await new Promise((resolve) => {
                img.onload = () => {
                    const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
                    const w = img.width * scale;
                    const h = img.height * scale;
                    ctx.drawImage(img, (canvas.width - w) / 2, (canvas.height - h) / 2, w, h);
                    resolve();
                };
                img.onerror = () => resolve();
                img.src = imgData.full_url;
            });
        }
    }

    function renderMainNav() {
        const container = document.getElementById('mainNav');
        container.innerHTML = Object.entries(mainNavConfig).map(([key, config]) => `
            <button class="main-nav-btn ${currentMain === key ? 'active' : ''}" data-main="${key}">${config.name}</button>
        `).join('');

        document.querySelectorAll('.main-nav-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                currentMain = btn.dataset.main;
                if (currentMain === 'face') {
                    currentSub = 'eye';
                } else if (currentMain === 'clothes') {
                    currentSub = 'top';
                } else {
                    const config = mainNavConfig[currentMain];
                    if (config.layers?.length) {
                        currentSub = config.layers[0];
                    }
                }
                renderMainNav();
                renderSubNav();
                renderImagesGrid();
            });
        });
    }

    function renderSubNav() {
        const container = document.getElementById('subNav');
        const config = mainNavConfig[currentMain];
        const subLayers = config.subLayers || config.layers || [];
        if (subLayers.length <= 1) {
            container.style.display = 'none';
            return;
        }
        container.style.display = 'flex';
        container.innerHTML = subLayers.map((layer) => `
            <button class="sub-nav-btn ${currentSub === layer ? 'active' : ''}" data-layer="${layer}">${subNames[layer] || layer}</button>
        `).join('');

        document.querySelectorAll('.sub-nav-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                currentSub = btn.dataset.layer;
                renderSubNav();
                renderImagesGrid();
            });
        });
    }

    function getCurrentLayer() {
        const config = mainNavConfig[currentMain];
        if (config.subLayers) {
            return currentSub;
        }
        if (config.layers) {
            return config.layers[0];
        }
        return 'body';
    }

    function renderImagesGrid() {
        const container = document.getElementById('imagesGrid');
        const currentLayer = getCurrentLayer();
        const images = allImages[currentLayer] || [];
        if (images.length === 0) {
            container.innerHTML = '<div class="loading">No images</div>';
            return;
        }
        container.innerHTML = images.map((img) => `
            <div class="image-card ${Number(currentOutfit[currentLayer]) === img.id ? 'selected' : ''}" data-layer="${currentLayer}" data-id="${img.id}">
                <img src="${img.full_url}" alt="${img.name}">
                <div class="image-name">${img.name}</div>
            </div>
        `).join('');

        document.querySelectorAll('.image-card').forEach((card) => {
            card.addEventListener('click', () => {
                const layer = card.dataset.layer;
                const imageId = Number(card.dataset.id);
                if (Number(currentOutfit[layer]) === imageId) {
                    updateOutfit(layer, null);
                } else {
                    updateOutfit(layer, imageId);
                }
            });
        });
    }

    function reset() {
        currentOutfit = {};
        renderCanvas();
        renderImagesGrid();
        syncCurrentOutfit();
        showMessage('Reset');
    }

    function showMessage(msg, isError = false) {
        const statsDiv = document.getElementById('stats');
        statsDiv.innerHTML = msg;
        statsDiv.style.color = isError ? '#dc3545' : '#28a745';
        setTimeout(() => {
            statsDiv.innerHTML = `Loaded ${Object.values(allImages).flat().length} images`;
            statsDiv.style.color = '#666';
        }, 2000);
    }

    async function init() {
        await autoImport();
        renderMainNav();
        renderSubNav();
        renderImagesGrid();
        await renderCanvas();
        loadSavedOutfits();
        document.getElementById('randomBtn').onclick = randomOutfit;
        document.getElementById('saveBtn').onclick = saveOutfit;
        document.getElementById('resetBtn').onclick = reset;
        document.getElementById('bindBtn').onclick = useThisLookInMingGame;
        showMessage(`Loaded ${Object.values(allImages).flat().length} images`);
    }

    init();
</script>
</body>
</html>
