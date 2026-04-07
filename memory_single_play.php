<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$matchId = (int)($_GET['match_id'] ?? 0);

$match = mm_get_match($conn, $matchId);
if (!$match) {
    die('Match not found.');
}

$player = mm_get_match_player($conn, $matchId, $userId);
if (!$player) {
    die('You are not in this match.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solo Memory Board</title>
    <link rel="icon" href="data:,">
    <?php mm_memory_theme_styles('
        .mm-topbar h1{margin:0;color:var(--mm-red-deep);font-size:1.35rem}
        .page{width:min(1420px,95vw);margin:24px auto 40px}
        .header-grid{display:grid;grid-template-columns:320px 1fr;gap:18px;margin-bottom:14px}
        .panel{background:rgba(255,250,240,.92);border:2px solid rgba(201,72,59,.14);border-radius:26px;padding:20px;box-shadow:var(--mm-shadow)}
        .stats-title{color:var(--mm-muted);font-size:14px;margin-bottom:8px;font-weight:800;text-transform:uppercase}
        .score-big{font-size:2.5rem;font-weight:900;color:var(--mm-red-deep)}
        .solo-look-wrap{margin-top:20px;display:flex;justify-content:flex-start}
        .solo-look-card{
            width:170px;
            padding:10px 10px 0;
            border-radius:24px;
            background:rgba(255,247,231,.92);
            border:2px solid rgba(201,72,59,.14);
            box-shadow:0 12px 26px rgba(125,82,38,.12);
            animation:mmLookFloat 3.2s ease-in-out infinite;
        }
        .solo-look-stage{position:relative;width:100%;height:210px;overflow:hidden}
        .solo-look-layer{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;object-position:bottom center;pointer-events:none}
        .solo-look-empty{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#9a7a60;font-weight:700;text-align:center;padding:12px}
        @keyframes mmLookFloat{0%{transform:translateY(0)}50%{transform:translateY(-12px)}100%{transform:translateY(0)}}
        .board-shell{
            position:relative;
            width:min(760px, 78vw);
            aspect-ratio:1 / 1;
            margin:0 auto;
            background-image:url("/m_picture/board.png");
            background-size:contain;
            background-position:center;
            background-repeat:no-repeat;
            padding:3.6%;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .board{width:100%;height:100%;display:grid;grid-template-columns:repeat(8,1fr);grid-template-rows:repeat(8,1fr);gap:8px}
        .cell{
            width:100%;
            height:100%;
            border-radius:14px;
            border:2px solid rgba(255,255,255,.45);
            background:rgba(255,255,255,.10);
            display:flex;
            align-items:center;
            justify-content:center;
            position:relative;
            overflow:hidden;
            box-shadow:inset 0 0 0 1px rgba(0,0,0,.12), 0 2px 6px rgba(0,0,0,.06);
        }
        .tile{
            width:100%;
            height:100%;
            border:none;
            border-radius:14px;
            cursor:pointer;
            padding:8px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            text-align:center;
            transition:transform .15s ease, box-shadow .2s ease, background .2s ease, opacity .25s ease;
            box-shadow:0 6px 18px rgba(121,82,40,.12);
            font-weight:700;
        }
        .tile:hover{transform:translateY(-2px)}
        .tile.back{background:url("/m_picture/blue.png") center/cover no-repeat;color:transparent;font-size:1.2rem}
        .tile.faceup{background:#eef6ff;color:#111827;border:1px solid #93c5fd}
        .tile.matched{background:#ecf8d8;color:#166534;border:1px solid #9ad381;opacity:.35;cursor:default}
        .tile.empty{background:transparent;box-shadow:none;cursor:default}
        .cell-no{position:absolute;left:8px;top:6px;font-size:10px;color:rgba(255,255,255,.84)}
        .tile.faceup .cell-no{color:#64748b}
        .tile.matched .cell-no{color:#166534}
        .content{font-size:.72rem;line-height:1.1;padding:0 4px;width:100%;word-break:break-word;overflow-wrap:anywhere;text-align:center}
        .audio-btn{
            margin-top:8px;border:none;border-radius:999px;padding:6px 11px;
            background:linear-gradient(180deg, #ffffff 0%, #e7f3ff 100%);color:#185487;cursor:pointer;font-size:12px;font-weight:900
        }
        .status{margin-top:10px;padding:13px 16px}
        .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
        .fade-out{animation:fadeOutMatch 0.45s ease forwards}
        @keyframes fadeOutMatch{0%{opacity:1;transform:scale(1)}100%{opacity:0;transform:scale(0.75)}}
        @media (max-width: 1100px){.header-grid{grid-template-columns:1fr}}
        @media (max-width: 700px){.board{gap:6px}.content{font-size:.72rem;line-height:1.15}}
    '); ?>
</head>
<body>
    <div class="mm-topbar">
        <h1>Solo Memory Match</h1>
        <div class="timer" id="timerBox">Loading...</div>
    </div>

    <div class="page">
        <div class="header-grid">
            <div class="panel">
                <div class="stats-title">Your Score</div>
                <div class="score-big" id="scoreBox">0</div>
                <div class="stats-title" style="margin-top:18px;">Matched Pairs</div>
                <div id="matchedBox">0 / 8</div>
                <div class="stats-title" style="margin-top:18px;">Flip Count</div>
                <div id="flipBox">0</div>
                <div class="stats-title" style="margin-top:18px;">Mode</div>
                <div id="modeBox">Loading...</div>

                <div class="actions">
                    <a class="mm-button" href="memory_single_result.php?match_id=<?= (int)$matchId ?>">Result</a>
                    <a class="mm-button alt" href="memory_home.php">Back</a>
                </div>

                <div class="solo-look-wrap">
                    <div class="solo-look-card">
                        <div class="solo-look-stage" id="soloLookStage">
                            <div class="solo-look-empty" id="soloLookEmpty">Loading your look...</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2>8x8 Board</h2>
                <div class="mm-muted" style="margin-bottom:14px;">
                    There are 16 hidden cards scattered across 64 cells. Match one word card with one pronunciation card.
                </div>
                <div class="board-shell">
                    <div class="board" id="board"></div>
                </div>
            </div>
        </div>

        <div class="status" id="statusBar">Click a tile to begin.</div>
    </div>

<script>
const matchId = <?= (int)$matchId ?>;
const currentUserId = <?= (int)$userId ?>;
const ACTIVE_OUTFIT_ENDPOINT = '/galgame/dress_up_game/api/get_active_outfit.php';
const dressUpLayerOrder = ["background","body","shoes","top","pants","dress","suit","eye","eyebrows","nose","mouse","hair","character","glass","head"];
let busy = false;
let cache = null;

function buildLookCandidates(layer) {
    const filePath = layer?.file_path || '';
    const normalized = filePath.startsWith('/') ? filePath : `/${filePath}`;
    return [`/galgame/dress_up_game${normalized}`, layer?.url || ''].filter(Boolean);
}

function setLookImage(img, candidates, index = 0) {
    if (!img || index >= candidates.length) {
        if (img) img.remove();
        return;
    }
    const candidate = candidates[index];
    img.onerror = () => setLookImage(img, candidates, index + 1);
    img.src = `${candidate}${candidate.includes('?') ? '&' : '?'}t=${Date.now()}`;
}

function renderSoloLook(data) {
    const stage = document.getElementById('soloLookStage');
    const empty = document.getElementById('soloLookEmpty');
    if (!stage || !empty) return;

    Array.from(stage.querySelectorAll('.solo-look-layer')).forEach((node) => node.remove());

    if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
        empty.style.display = 'flex';
        empty.textContent = 'No active look yet';
        return;
    }

    empty.style.display = 'none';
    const layerMap = new Map();
    data.layers.forEach((layer) => {
        if (layer && layer.layer) layerMap.set(layer.layer, layer);
    });

    dressUpLayerOrder.forEach((layerName) => {
        if (layerName === 'background') return;
        const layer = layerMap.get(layerName);
        if (!layer) return;
        const img = document.createElement('img');
        img.className = 'solo-look-layer';
        img.alt = layer.name || layerName;
        stage.appendChild(img);
        setLookImage(img, buildLookCandidates(layer));
    });
}

async function loadSoloLook() {
    try {
        const response = await fetch(`${ACTIVE_OUTFIT_ENDPOINT}?user_id=${currentUserId}&_=${Date.now()}`, { cache: 'no-store' });
        const data = await response.json();
        renderSoloLook(data);
    } catch (error) {
        const empty = document.getElementById('soloLookEmpty');
        if (empty) {
            empty.style.display = 'flex';
            empty.textContent = 'Look unavailable';
        }
    }
}

function playAudio(url) {
    if (!url) return;
    const audio = new Audio(url);
    audio.play().catch(() => {});
}

function renderBoard(cells) {
    const board = document.getElementById('board');
    board.innerHTML = '';

    cells.forEach(cell => {
        const wrap = document.createElement('div');
        wrap.className = 'cell';

        if (!cell.has_card) {
            const empty = document.createElement('div');
            empty.className = 'tile empty';
            wrap.appendChild(empty);
            board.appendChild(wrap);
            return;
        }

        const card = cell.card;
        const tile = document.createElement('button');
        tile.type = 'button';
        tile.className = 'tile';

        if (card.is_matched) tile.classList.add('matched');
        else if (card.is_face_up) tile.classList.add('faceup');
        else tile.classList.add('back');

        const cellNo = document.createElement('div');
        cellNo.className = 'cell-no';
        cellNo.textContent = `#${cell.cell_no}`;
        tile.appendChild(cellNo);

        const content = document.createElement('div');
        content.className = 'content';

        if (card.is_matched) {
            content.textContent = 'OK';
        } else if (card.is_face_up) {
            content.textContent = card.display || '';
        } else {
            content.textContent = '';
        }
        tile.appendChild(content);

        if ((card.is_face_up || card.is_matched) && card.card_type === 'audio' && card.audio_url) {
            const btn = document.createElement('button');
            btn.className = 'audio-btn';
            btn.type = 'button';
            btn.textContent = 'Play';
            btn.onclick = (e) => {
                e.stopPropagation();
                playAudio(card.audio_url);
            };
            tile.appendChild(btn);
        }

        if (!card.is_matched && !card.is_face_up) {
            tile.onclick = () => flipCard(card.card_id);
        } else {
            tile.disabled = true;
        }

        wrap.appendChild(tile);
        board.appendChild(wrap);
    });
}
function revealTemporaryCards(firstCard, secondCard, isMatch) {
    if (!cache || !cache.board_cells) return;

    cache.board_cells = cache.board_cells.map(cell => {
        if (!cell.has_card || !cell.card) return cell;

        if (cell.card.card_id === firstCard.card_id) {
            cell.card = { ...cell.card, ...firstCard, is_face_up: true };
        }
        if (cell.card.card_id === secondCard.card_id) {
            cell.card = { ...cell.card, ...secondCard, is_face_up: true };
        }

        if (isMatch) {
            if (cell.card.card_id === firstCard.card_id || cell.card.card_id === secondCard.card_id) {
                cell.card.is_matched = true;
            }
        }

        return cell;
    });

    renderBoard(cache.board_cells);

    if (isMatch) {
        setTimeout(() => {
            const tiles = document.querySelectorAll('.tile.matched');
            tiles.forEach(tile => tile.classList.add('fade-out'));
        }, 80);
    }
}

function updateInfo(data) {
    document.getElementById('timerBox').textContent =
        data.match.remaining_seconds !== null ? `${data.match.remaining_seconds}s left` : 'No timer';

    document.getElementById('scoreBox').textContent = data.me.score;
    document.getElementById('matchedBox').textContent = `${data.me.matched_pairs_count} / ${data.mode.pair_count}`;
    document.getElementById('flipBox').textContent = data.me.flip_count;
    document.getElementById('modeBox').textContent =
    `${data.mode.pair_count} pairs | clear all before time runs out`;
}

async function loadState() {
    try {
        const res = await fetch(`memory_single_state.php?match_id=${matchId}`);
        const data = await res.json();

        if (!data.ok) {
            document.getElementById('statusBar').textContent = data.message || 'Failed to load board.';
            return;
        }

        cache = data;
        updateInfo(data);
        renderBoard(data.board_cells);

        if (data.match.status === 'finished') {
            window.location.href = `memory_single_result.php?match_id=${matchId}`;
            return;
        }
    } catch (e) {
        document.getElementById('statusBar').textContent = 'Network error while loading board.';
    }
}

async function flipCard(cardId) {
    if (busy) return;
    busy = true;

    const body = new URLSearchParams();
    body.append('match_id', matchId);
    body.append('card_id', cardId);

    try {
        const res = await fetch('memory_single_flip.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });

        const data = await res.json();

        if (!data.ok) {
            document.getElementById('statusBar').textContent = data.message || 'Flip failed.';
            busy = false;
            await loadState();
            return;
        }

        if (data.phase === 'first') {
            document.getElementById('statusBar').textContent = 'First card flipped. Now choose the second card.';
            await loadState();
            busy = false;
            return;
        }

        if (data.phase === 'second') {
            if (data.first_card && data.second_card) {
                revealTemporaryCards(data.first_card, data.second_card, data.is_match);
            }

            if (data.is_match) {
                document.getElementById('statusBar').innerHTML = `<span class="good">Matched!</span> +1 point`;

                setTimeout(async () => {
                    await loadState();
                    busy = false;
                }, 550);

                return;
            } else {
                document.getElementById('statusBar').innerHTML = `<span class="bad">Not matched.</span> Cards will flip back.`;

                if (data.first_card && data.first_card.card_type === 'audio' && data.first_card.audio_url) {
                    playAudio(data.first_card.audio_url);
                }
                if (data.second_card && data.second_card.card_type === 'audio' && data.second_card.audio_url) {
                    playAudio(data.second_card.audio_url);
                }

                setTimeout(async () => {
                    await loadState();
                    busy = false;
                }, 950);

                return;
            }
        }

        if (data.finished) {
            window.location.href = `memory_single_result.php?match_id=${matchId}`;
            return;
        }

        busy = false;
        await loadState();
    } catch (e) {
        document.getElementById('statusBar').textContent = 'Network error while flipping card.';
        busy = false;
    }
}

setInterval(() => {
    if (!busy) loadState();
}, 2000);

loadState();
loadSoloLook();
</script>
</body>
</html>
