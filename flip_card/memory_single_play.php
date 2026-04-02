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
    <style>
        :root{
            --bg:#eff6f3;
            --panel:#ffffff;
            --line:#dfe9e4;
            --primary:#1b4332;
            --accent:#2563eb;
            --text:#2d3436;
            --muted:#6d7d76;
            --good:#16a34a;
            --bad:#dc2626;
            --matched:#dcfce7;
            --empty:#f8fbf9;
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:Segoe UI,Tahoma,sans-serif;
            background:linear-gradient(180deg,#f7fbf9 0%, #edf5f1 100%);
            color:var(--text);
        }
        .topbar{
            padding:18px 24px;
            background:#fff;
            box-shadow:0 2px 12px rgba(0,0,0,.05);
            display:flex;
            justify-content:space-between;
            align-items:center;
            position:sticky; top:0; z-index:10;
        }
        .topbar h1{
            margin:0;
            color:var(--primary);
            font-size:1.35rem;
        }
        .timer{
            padding:10px 16px;
            border-radius:999px;
            background:#eef2ff;
            color:#1d4ed8;
            font-weight:700;
        }
        .page{
            width:min(1400px,95vw);
            margin:24px auto;
        }
        .header-grid{
            display:grid;
            grid-template-columns:340px 1fr;
            gap:20px;
            margin-bottom:22px;
        }
        .panel{
            background:var(--panel);
            border-radius:24px;
            padding:22px;
            box-shadow:0 10px 30px rgba(27,67,50,.08);
        }
        .stats-title{
            color:var(--muted);
            font-size:14px;
            margin-bottom:8px;
        }
        .score-big{
            font-size:2.2rem;
            font-weight:900;
            color:var(--primary);
        }
        .board{
            display:grid;
            grid-template-columns:repeat(8,1fr);
            gap:10px;
        }
        .cell{
            aspect-ratio:1/1;
            border-radius:16px;
            border:1px solid var(--line);
            background:var(--empty);
            display:flex;
            align-items:center;
            justify-content:center;
            position:relative;
            overflow:hidden;
        }
        .tile{
            width:100%;
            height:100%;
            border:none;
            border-radius:16px;
            cursor:pointer;
            padding:8px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            text-align:center;
            transition:transform .15s ease, box-shadow .2s ease, background .2s ease;
            box-shadow:0 6px 18px rgba(27,67,50,.06);
            font-weight:700;
        }
        .tile:hover{transform:translateY(-2px)}
        .tile.back{
            background:linear-gradient(135deg,#1e3a8a,#2563eb);
            color:#fff;
            font-size:1.2rem;
        }
        .tile.faceup{
            background:#eef6ff;
            color:#111827;
            border:1px solid #93c5fd;
        }
        .tile.matched{
            background:var(--matched);
            color:#166534;
            border:1px solid #86efac;
            opacity:.35;
            cursor:default;
        }
        .tile.empty{
            background:transparent;
            box-shadow:none;
            cursor:default;
        }
        .cell-no{
            position:absolute;
            left:8px;
            top:6px;
            font-size:10px;
            color:rgba(255,255,255,.8);
        }
        .tile.faceup .cell-no{color:#64748b}
        .tile.matched .cell-no{color:#166534}
        .content{
            font-size:.95rem;
            line-height:1.3;
            padding:0 6px;
        }
        .audio-btn{
            margin-top:8px;
            border:none;
            border-radius:999px;
            padding:5px 10px;
            background:#111827;
            color:#fff;
            cursor:pointer;
            font-size:12px;
        }
        .status{
            margin-top:20px;
            background:#fff;
            border-radius:18px;
            padding:16px 18px;
            box-shadow:0 10px 30px rgba(27,67,50,.08);
            color:var(--muted);
        }
        .good{color:var(--good);font-weight:800}
        .bad{color:var(--bad);font-weight:800}
        .btn{
            display:inline-block;
            padding:10px 16px;
            border-radius:999px;
            text-decoration:none;
            background:#e2e8f0;
            color:#111827;
            font-weight:700;
            margin-right:10px;
        }
        .btn-primary{background:#2563eb;color:#fff}
        @media (max-width: 1100px){
            .header-grid{grid-template-columns:1fr}
            .board{grid-template-columns:repeat(8, minmax(32px,1fr))}
        }
        @media (max-width: 700px){
            .board{gap:6px}
            .content{font-size:.75rem}
        }
    </style>
</head>
<body>
    <div class="topbar">
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

                <div style="margin-top:20px;">
                    <a class="btn btn-primary" href="memory_single_result.php?match_id=<?= (int)$matchId ?>">Result</a>
                    <a class="btn" href="memory_home.php">Back</a>
                </div>
            </div>

            <div class="panel">
                <h2 style="margin-top:0;color:#1b4332;">8×8 Board</h2>
                <div style="color:#6d7d76;margin-bottom:14px;">
                    There are 16 hidden cards scattered across 64 cells. Match one word card with one pronunciation card.
                </div>
                <div class="board" id="board"></div>
            </div>
        </div>

        <div class="status" id="statusBar">Click a tile to begin.</div>
    </div>

<script>
const matchId = <?= (int)$matchId ?>;
let busy = false;
let cache = null;

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
            content.textContent = '✓';
        } else if (card.is_face_up) {
            content.textContent = card.display || '';
        } else {
            content.textContent = '?';
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

function updateInfo(data) {
    document.getElementById('timerBox').textContent =
        data.match.remaining_seconds !== null ? `${data.match.remaining_seconds}s left` : 'No timer';

    document.getElementById('scoreBox').textContent = data.me.score;
    document.getElementById('matchedBox').textContent = `${data.me.matched_pairs_count} / ${data.mode.pair_count}`;
    document.getElementById('flipBox').textContent = data.me.flip_count;
    document.getElementById('modeBox').textContent = data.mode.mode_name;
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
            if (data.is_match) {
                document.getElementById('statusBar').innerHTML = `<span class="good">Matched!</span> +1 point`;
                setTimeout(async () => {
                    await loadState();
                    busy = false;
                }, 500);
                return;
            } else {
                document.getElementById('statusBar').innerHTML = `<span class="bad">Not matched.</span> Cards will flip back.`;
                setTimeout(async () => {
                    await loadState();
                    busy = false;
                }, 900);
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
</script>
</body>
</html>