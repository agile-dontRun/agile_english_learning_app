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
    <title>Memory Match Battle</title>
    <link rel="icon" href="data:,">
    <?php mm_memory_theme_styles('
        .mm-topbar h1{margin:0;color:var(--mm-red-deep);font-size:1.35rem}
        .page{width:min(1520px,95vw);margin:24px auto 40px}
        .score-row{display:grid;grid-template-columns:1fr auto 1fr;gap:14px;align-items:center;margin-bottom:14px}
        .score-panel{background:rgba(255,250,240,.92);border-radius:22px;padding:16px 18px;box-shadow:var(--mm-shadow);border:2px solid rgba(201,72,59,.14)}
        .score-panel.me{border-color:rgba(47,139,216,.28)}
        .score-panel.opponent{border-color:rgba(242,173,63,.42)}
        .score-title{font-size:14px;color:var(--mm-muted);margin-bottom:10px;font-weight:800;text-transform:uppercase}
        .score-main{font-size:2rem;font-weight:900;color:var(--mm-red-deep)}
        .vs{font-size:2.2rem;font-weight:900;color:var(--mm-red-deep)}
        .boards-wrap{display:grid;grid-template-columns:1fr 8px 1fr;gap:14px;align-items:stretch}
        .board-panel{position:relative;background:rgba(255,250,240,.92);border-radius:26px;padding:18px 18px 190px;box-shadow:var(--mm-shadow);border:2px solid rgba(201,72,59,.14);display:flex;flex-direction:column}
        .board-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px}
        .board-head h2{margin:0;color:var(--mm-red-deep)}
        .board-meta{color:var(--mm-muted);font-size:13px}
        .divider{min-height:100%;background:linear-gradient(180deg, rgba(47,139,216,.2), rgba(201,72,59,.5), rgba(242,173,63,.2));border-radius:999px}
        .board-shell{
            position:relative;
            width:min(620px, 42vw);
            aspect-ratio:1 / 1;
            margin:0 auto;
            background-image:url("/m_picture/board.png");
            background-size:contain;
            background-position:center;
            background-repeat:no-repeat;
            padding:3.8%;
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
            border-radius:10px;
            cursor:pointer;
            padding:5px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            text-align:center;
            transition:transform .15s ease, box-shadow .2s ease, background .2s ease, opacity .25s ease;
            box-shadow:0 4px 12px rgba(121,82,40,.12);
            font-weight:700;
        }
        .tile:hover{transform:translateY(-2px)}
        .tile.back{background:url("/m_picture/blue.png") center/cover no-repeat;color:transparent;cursor:pointer;font-size:1.2rem}
        .tile.faceup{background:#eef6ff;color:#111827;border:1px solid #93c5fd}
        .tile.matched{background:#ecf8d8;color:#166534;border:1px solid #9ad381;cursor:default;opacity:.35}
        .tile.opp-back{background:url("/m_picture/red.png") center/cover no-repeat;color:transparent;cursor:default;font-size:1.2rem}
        .tile.opp-faceup{background:#fff7ed;color:#111827;border:1px solid #fdba74;cursor:default}
        .tile.opp-matched{background:#fff3c8;color:#92400e;border:1px solid #f4b740;cursor:default;opacity:.35}
        .tile.empty{background:transparent;box-shadow:none;cursor:default}
        .cell-no{position:absolute;left:7px;top:5px;font-size:10px;color:rgba(255,255,255,.82)}
        .tile.faceup .cell-no,.tile.opp-faceup .cell-no{color:#64748b}
        .tile.matched .cell-no,.tile.opp-matched .cell-no{color:#64748b}
        .content{font-size:.68rem;line-height:1.08;padding:0 3px;width:100%;word-break:break-word;overflow-wrap:anywhere;text-align:center}
        .audio-btn{
            margin-top:8px;border:none;border-radius:999px;padding:6px 11px;
            background:linear-gradient(180deg, #ffffff 0%, #e7f3ff 100%);color:#185487;cursor:pointer;font-size:12px;font-weight:900
        }
        .status-bar{margin-top:12px;padding:13px 16px}
        .battle-look{
            position:absolute;left:22px;bottom:18px;width:160px;padding:10px 10px 0;
            border-radius:22px;background:rgba(255,247,231,.94);border:2px solid rgba(201,72,59,.14);
            box-shadow:0 12px 26px rgba(125,82,38,.12);animation:mmBattleLookFloat 3.2s ease-in-out infinite
        }
        .battle-look-stage{position:relative;width:100%;height:190px;overflow:hidden}
        .battle-look-layer{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;object-position:bottom center;pointer-events:none}
        .battle-look-empty{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#9a7a60;font-weight:700;text-align:center;padding:12px}
        @keyframes mmBattleLookFloat{0%{transform:translateY(0)}50%{transform:translateY(-12px)}100%{transform:translateY(0)}}
        .fade-out{animation:fadeOutMatch 0.45s ease forwards}
        @keyframes fadeOutMatch{0%{opacity:1;transform:scale(1)}100%{opacity:0;transform:scale(.75)}}
        .footer-actions{margin-top:18px;display:flex;gap:12px;flex-wrap:wrap}
        @media (max-width: 1200px){.boards-wrap{grid-template-columns:1fr}.divider{display:none}.score-row{grid-template-columns:1fr}.vs{text-align:center}}
        @media (max-width: 700px){.board{gap:6px}.content{font-size:.72rem;line-height:1.15}}
    '); ?>
</head>
<body>
<div class="mm-topbar">
    <h1>Memory Match Battle</h1>
    <div class="timer" id="timerBox">Loading...</div>
</div>

<div class="page">
    <div class="score-row">
        <div class="score-panel opponent" id="leftScorePanel">
            <div class="score-title" id="leftTitle">Loading...</div>
            <div class="score-main" id="leftScore">0</div>
            <div class="board-meta" id="leftMeta">0 matched | 0 flips</div>
        </div>

        <div class="vs">VS</div>

        <div class="score-panel me" id="rightScorePanel">
            <div class="score-title" id="rightTitle">Loading...</div>
            <div class="score-main" id="rightScore">0</div>
            <div class="board-meta" id="rightMeta">0 matched | 0 flips</div>
        </div>
    </div>

    <div class="boards-wrap">
        <div class="board-panel">
            <div class="board-head">
                <h2 id="leftBoardName">Left Board</h2>
                <div class="board-meta" id="leftBoardMeta">Loading...</div>
            </div>
            <div class="board-shell">
                <div class="board" id="leftBoard"></div>
            </div>
            <div class="battle-look">
                <div class="battle-look-stage" id="leftLookStage">
                    <div class="battle-look-empty" id="leftLookEmpty">Loading look...</div>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="board-panel">
            <div class="board-head">
                <h2 id="rightBoardName">Right Board</h2>
                <div class="board-meta" id="rightBoardMeta">Loading...</div>
            </div>
            <div class="board-shell">
                <div class="board" id="rightBoard"></div>
            </div>
            <div class="battle-look">
                <div class="battle-look-stage" id="rightLookStage">
                    <div class="battle-look-empty" id="rightLookEmpty">Loading look...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="status-bar" id="statusBar">Waiting for game state...</div>

    <div class="footer-actions">
        <a class="mm-button" href="memory_match_result.php?match_id=<?= (int)$matchId ?>">Result</a>
        <a class="mm-button alt" href="memory_home.php">Back</a>
    </div>
</div>

<script>
const matchId = <?= (int)$matchId ?>;
const ACTIVE_OUTFIT_ENDPOINT = '/galgame/dress_up_game/api/get_active_outfit.php';
const dressUpLayerOrder = ["background","body","shoes","top","pants","dress","suit","eye","eyebrows","nose","mouse","hair","character","glass","head"];
let busy = false;
let stateCache = null;
const lookCache = new Map();

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

function renderPlayerLook(stageId, emptyId, data) {
    const stage = document.getElementById(stageId);
    const empty = document.getElementById(emptyId);
    if (!stage || !empty) return;

    Array.from(stage.querySelectorAll('.battle-look-layer')).forEach((node) => node.remove());

    if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
        empty.style.display = 'flex';
        empty.textContent = 'No active look';
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
        img.className = 'battle-look-layer';
        img.alt = layer.name || layerName;
        stage.appendChild(img);
        setLookImage(img, buildLookCandidates(layer));
    });
}

async function loadLookForUser(userId, stageId, emptyId) {
    if (!userId) {
        renderPlayerLook(stageId, emptyId, null);
        return;
    }

    if (lookCache.has(userId)) {
        renderPlayerLook(stageId, emptyId, lookCache.get(userId));
        return;
    }

    try {
        const response = await fetch(`${ACTIVE_OUTFIT_ENDPOINT}?user_id=${userId}&_=${Date.now()}`, { cache: 'no-store' });
        const data = await response.json();
        lookCache.set(userId, data);
        renderPlayerLook(stageId, emptyId, data);
    } catch (error) {
        renderPlayerLook(stageId, emptyId, null);
    }
}

function playAudio(url) {
    if (!url) return;
    const audio = new Audio(url);
    audio.play().catch(() => {});
}

function renderBoard(container, cells, isMine) {
    container.innerHTML = '';

    cells.forEach(cell => {
        const wrap = document.createElement('div');
        wrap.className = 'cell';

        if (!cell.has_card) {
            const empty = document.createElement('div');
            empty.className = 'tile empty';
            wrap.appendChild(empty);
            container.appendChild(wrap);
            return;
        }

        const card = cell.card;
        const tile = document.createElement('button');
        tile.type = 'button';
        tile.className = 'tile';

        if (isMine) {
            if (card.is_matched) tile.classList.add('matched');
            else if (card.is_face_up) tile.classList.add('faceup');
            else tile.classList.add('back');
        } else {
            if (card.is_matched) tile.classList.add('opp-matched');
            else if (card.is_face_up) tile.classList.add('opp-faceup');
            else tile.classList.add('opp-back');
        }

        const label = document.createElement('div');
        label.className = 'cell-no';
        label.textContent = `#${cell.cell_no}`;
        tile.appendChild(label);

        const content = document.createElement('div');
        content.className = 'content';

        if (card.is_matched || card.is_face_up) {
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

        if (isMine && !card.is_matched && !card.is_face_up) {
            tile.onclick = () => flipCard(card.card_id);
        } else {
            tile.disabled = true;
        }

        wrap.appendChild(tile);
        container.appendChild(wrap);
    });
}

function patchOwnBoard(firstCard, secondCard, isMatch) {
    if (!stateCache || !stateCache.my_board_cells) return;

    stateCache.my_board_cells = stateCache.my_board_cells.map(cell => {
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
}

function updateLayout(data) {
    const meOnLeft = data.me.player_slot === 1;

    const leftIsMine = meOnLeft;
    const rightIsMine = !meOnLeft;

    const leftPlayer = leftIsMine ? data.me : data.opponent;
    const rightPlayer = rightIsMine ? data.me : data.opponent;

    const leftBoardCells = leftIsMine ? data.my_board_cells : data.opponent_board_cells;
    const rightBoardCells = rightIsMine ? data.my_board_cells : data.opponent_board_cells;

    document.getElementById('timerBox').textContent =
        data.match.remaining_seconds !== null ? `${data.match.remaining_seconds}s left` : 'No timer';

    document.getElementById('leftTitle').textContent = leftIsMine ? 'You' : (leftPlayer ? leftPlayer.nickname : 'Opponent');
    document.getElementById('rightTitle').textContent = rightIsMine ? 'You' : (rightPlayer ? rightPlayer.nickname : 'Opponent');

    document.getElementById('leftScore').textContent = leftPlayer ? leftPlayer.score : 0;
    document.getElementById('rightScore').textContent = rightPlayer ? rightPlayer.score : 0;

    document.getElementById('leftMeta').textContent = leftPlayer
        ? `${leftPlayer.matched_pairs_count}/${data.mode.pair_count} matched | ${leftPlayer.flip_count} flips`
        : 'Waiting...';
    document.getElementById('rightMeta').textContent = rightPlayer
        ? `${rightPlayer.matched_pairs_count}/${data.mode.pair_count} matched | ${rightPlayer.flip_count} flips`
        : 'Waiting...';

    document.getElementById('leftBoardName').textContent = leftIsMine ? 'Your Board' : `${leftPlayer ? leftPlayer.nickname : 'Opponent'} Board`;
    document.getElementById('rightBoardName').textContent = rightIsMine ? 'Your Board' : `${rightPlayer ? rightPlayer.nickname : 'Opponent'} Board`;

    document.getElementById('leftBoardMeta').textContent = leftIsMine
    ? `${data.mode.pair_count} pairs | operable`
    : 'View only';

    document.getElementById('rightBoardMeta').textContent = rightIsMine
    ? `${data.mode.pair_count} pairs | operable`
    : 'View only';

    renderBoard(document.getElementById('leftBoard'), leftBoardCells || [], leftIsMine);
    renderBoard(document.getElementById('rightBoard'), rightBoardCells || [], rightIsMine);
    loadLookForUser(leftPlayer ? Number(leftPlayer.user_id || 0) : 0, 'leftLookStage', 'leftLookEmpty');
    loadLookForUser(rightPlayer ? Number(rightPlayer.user_id || 0) : 0, 'rightLookStage', 'rightLookEmpty');
}

async function loadState() {
    try {
        const res = await fetch(`memory_board_state.php?match_id=${matchId}`);
        if (!res.ok) {
            document.getElementById('statusBar').textContent = `Failed to load board state: ${res.status}`;
            return;
        }

        const data = await res.json();
        if (!data.ok) {
            document.getElementById('statusBar').textContent = data.message || 'Failed to load state.';
            return;
        }

        stateCache = data;
        updateLayout(data);

        if (data.match.status === 'finished') {
            window.location.href = `memory_match_result.php?match_id=${matchId}`;
            return;
        }

        document.getElementById('statusBar').textContent =
            'Win by clearing all pairs first. If time runs out, higher score wins. If scores tie, fewer flips wins.';
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
        const res = await fetch('memory_flip_submit.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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
            document.getElementById('statusBar').textContent = 'First card flipped. Choose the second card.';
            await loadState();
            busy = false;
            return;
        }

        if (data.phase === 'second') {
            if (data.first_card && data.second_card) {
                patchOwnBoard(data.first_card, data.second_card, data.is_match);
                updateLayout(stateCache);
            }

            if (data.is_match) {
                document.getElementById('statusBar').innerHTML = `<span class="good">Matched!</span> +1 point`;

                setTimeout(() => {
                    const matchedTiles = document.querySelectorAll('.tile.matched');
                    matchedTiles.forEach(tile => tile.classList.add('fade-out'));
                }, 80);

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

        busy = false;
        await loadState();
    } catch (e) {
        document.getElementById('statusBar').textContent = 'Network error while flipping card.';
        busy = false;
    }
}

setInterval(() => {
    if (!busy) loadState();
}, 1500);

loadState();
</script>
</body>
</html>
