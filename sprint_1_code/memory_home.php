<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$user = mm_get_user($conn, $userId);
$nickname = $user ? ($user['nickname'] ?: $user['username']) : 'User';
$walletBalance = coin_get_balance($conn, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Match</title>
    <link rel="icon" href="data:,">
    <style>
        * { box-sizing: border-box; }

        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background:
                linear-gradient(rgba(115, 175, 214, 0.20) 1px, transparent 1px),
                linear-gradient(90deg, rgba(115, 175, 214, 0.20) 1px, transparent 1px),
                linear-gradient(180deg, #fffdf6 0%, #f6efd9 100%);
            background-size: 38px 38px, 38px 38px, auto;
            color: #3b2d24;
            overflow-x: hidden;
        }

        .cover-page {
            min-height: 100vh;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cover-stage {
            position: relative;
            width: 100vw;
            height: 100vh;
            max-height: none;
        }

        .cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 0;
            box-shadow: none;
            user-select: none;
            pointer-events: none;
        }

        .menu-panel {
            position: absolute;
            left: 10%;
            bottom: 4.4%;
            z-index: 3;
            width: 20.9%;
            min-width: 250px;
            max-width: 340px;
            display: flex;
            flex-direction: column;
            gap: clamp(8px, 1vw, 14px);
        }

        .ming-look-card {
            position: absolute;
            left: 30%;
            top: 65%;
            transform: translateY(-50%);
            width: 210px;
            z-index: 3;
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
            pointer-events: none;
        }

        .ming-look-title {
            display: none !important;
            visibility: hidden;
            height: 0;
            margin: 0;
            overflow: hidden;
        }

        .ming-look-stage {
            position: relative;
            width: 100%;
            aspect-ratio: 11 / 13;
            overflow: hidden;
            animation: memoryFloat 3.2s ease-in-out infinite;
            transform-origin: center bottom;
            will-change: transform;
        }

        .ming-look-layer {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
        }

        .ming-look-empty {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            text-align: center;
            color: #996633;
            font-size: 14px;
            line-height: 1.4;
        }

        @keyframes memoryFloat {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-14px);
            }

            100% {
                transform: translateY(0);
            }
        }

        /* ========== 修改部分：四个按钮统一样式 ========== */
        .menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;     /* 水平居中 */
            text-align: center;          /* 文字居中 */
            text-decoration: none;
            padding: 16px 12px;
            border-radius: 18px;
            /* 橙色背景 */
            background: #f97316;
            background: linear-gradient(135deg, #f97316, #ea580c);
            border: 1px solid rgba(255,200,100,0.5);
            backdrop-filter: blur(10px);
            color: white;                /* 字体白色 */
            box-shadow: 0 10px 24px rgba(0,0,0,.15);
            transition: transform .16s ease, background .18s ease, box-shadow .2s ease;
        }

        .menu-btn:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #fb923c, #f97316);
            box-shadow: 0 14px 30px rgba(0,0,0,.2);
        }

        .menu-left {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            min-width: 0;
        }

        .menu-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: rgba(255,255,255,.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .menu-text {
            min-width: 0;
            text-align: center;
        }

        .menu-text strong {
            display: block;
            font-size: 1.4rem;
            margin-bottom: 4px;
            letter-spacing: .2px;
            color: white;               /* 字体白色 */
            font-weight: 700;
        }

        .menu-text span {
            display: block;
            font-size: .9rem;
            color: rgba(255,255,255,0.85);  /* 描述文字白色半透明 */
            line-height: 1.4;
        }

        .menu-arrow {
            display: none;              /* 隐藏箭头，让文字完美居中 */
        }

        .welcome-tag {
            position: absolute;
            top: 3%;
            right: 2%;
            z-index: 3;
            padding: 12px 16px;
            border-radius: 16px;
            background: rgba(255, 249, 232, 0.92);
            border: 2px solid rgba(193, 77, 61, 0.12);
            backdrop-filter: blur(10px);
            color: #3b2d24;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(125, 82, 38, 0.14);
        }

        .coin-tag {
            position: absolute;
            top: 14%;
            right: 2%;
            z-index: 3;
            padding: 12px 16px;
            border-radius: 16px;
            background: rgba(255, 235, 178, 0.95);
            border: 2px solid rgba(193, 77, 61, 0.12);
            color: #7c3f14;
            font-weight: 900;
            box-shadow: 0 10px 24px rgba(125, 82, 38, 0.14);
        }

        .exit-btn {
            position: absolute;
            left: 2.4%;
            bottom: 4.4%;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 92px;
            padding: 10px 16px;
            border-radius: 999px;
            text-decoration: none;
            color: #fffaf0;
            font-weight: 800;
            font-size: .9rem;
            background: rgba(59, 45, 36, 0.78);
            border: 1px solid rgba(255, 250, 240, 0.35);
            box-shadow: 0 10px 24px rgba(0,0,0,.16);
            transition: transform .16s ease, background .18s ease;
        }

        .exit-btn:hover {
            transform: translateY(-2px);
            background: rgba(59, 45, 36, 0.9);
        }

        @media (max-width: 1200px) {
            .cover-stage {
                width: 100vw;
                height: 100vh;
            }

            .menu-panel {
                min-width: 220px;
                width: 27%;
            }

            .ming-look-card {
                left: 37%;
                width: 190px;
            }

            .menu-btn {
                padding: 13px 10px;
            }

            .menu-text strong {
                font-size: clamp(1rem, 1.7vw, 1.35rem);
            }
        }

        @media (max-width: 860px) {
            .cover-page {
                padding-top: 0;
                align-items: center;
            }

            .cover-stage {
                width: 100vw;
                height: 100vh;
            }

            .menu-panel {
                left: 5.6%;
                bottom: 4.2%;
                width: 28.5%;
                min-width: 170px;
            }

            .ming-look-card {
                left: 38%;
                width: 150px;
            }

            .welcome-tag {
                top: 2%;
                right: 3%;
                font-size: .92rem;
                padding: 10px 14px;
            }

            .exit-btn {
                left: 2.8%;
                bottom: 4.2%;
                min-width: 84px;
                padding: 9px 14px;
                font-size: .84rem;
            }
        }

        @media (max-width: 640px) {
            .cover-page {
                padding: 0;
            }

            .cover-stage {
                width: 100vw;
                height: 100vh;
            }

            .menu-btn {
                padding: 10px 7px;
                border-radius: 14px;
            }

            .menu-text strong {
                font-size: clamp(.75rem, 1.8vw, .96rem);
            }

            .menu-text span {
                display: none;
            }

            .menu-icon {
                width: 42px;
                height: 42px;
                border-radius: 12px;
            }

            .menu-panel {
                width: 29%;
                min-width: 120px;
                gap: 8px;
            }

            .ming-look-card {
                left: 39%;
                width: 120px;
            }

            .welcome-tag {
                font-size: .76rem;
                padding: 7px 10px;
                border-radius: 12px;
            }

            .exit-btn {
                left: 2%;
                bottom: 3.4%;
                min-width: 72px;
                padding: 8px 12px;
                font-size: .76rem;
            }
        }
    </style>
</head>
<body>
    <div class="cover-page">
        <div class="cover-stage">
            <img class="cover-image" src="/m_picture/cover.jpg" alt="Memory Match cover">

            <div class="welcome-tag">
                Welcome, <?= mm_h($nickname) ?>
            </div>

            <div class="coin-tag">
                Coins: <?= (int)$walletBalance ?>
            </div>

            <!-- TODO: Team integration point: replace # with the target file/path for Exit -->
            <a class="exit-btn" href="/galgame/galgame/index.html">
                Exit
            </a>

            <div class="menu-panel">
                <a class="menu-btn" href="memory_single_start.php">
                    <div class="menu-left">
                        <div class="menu-text">
                            <strong>Solo Game</strong>
                        </div>
                    </div>
                    <div class="menu-arrow"></div>
                </a>

                <a class="menu-btn" href="memory_create_match.php">
                    <div class="menu-left">
                        <div class="menu-text">
                            <strong>Multiplayer Game</strong>
                        </div>
                    </div>
                    <div class="menu-arrow"></div>
                </a>

                <a class="menu-btn" href="memory_join_match.php">
                    <div class="menu-left">
                        <div class="menu-text">
                            <strong>Join Room</strong>
                        </div>
                    </div>
                    <div class="menu-arrow"></div>
                </a>

                <a class="menu-btn" href="memory_history.php">
                    <div class="menu-left">
                        <div class="menu-text">
                            <strong>Battle History</strong>
                        </div>
                    </div>
                    <div class="menu-arrow"></div>
                </a>
            </div>

            <div class="ming-look-card">
                <div class="ming-look-title" id="ming-look-title" style="display:none !important;">Current Ming Look</div>
                <div class="ming-look-stage" id="ming-look-stage">
                    <div class="ming-look-empty" id="ming-look-empty">No active outfit yet</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ACTIVE_OUTFIT_ENDPOINT = "/galgame/dress_up_game/api/get_active_outfit.php";
        const IMAGE_BASE_PATH = "/galgame/dress_up_game";
        const dressUpLayerOrder = [
            "background", "body", "shoes", "top", "pants", "dress", "suit",
            "eye", "eyebrows", "nose", "mouse", "hair", "character", "glass", "head"
        ];

        function buildImageCandidates(layer) {
            const filePath = layer?.file_path || "";
            const normalizedFilePath = filePath.startsWith("/") ? filePath : `/${filePath}`;
            return [
                `${IMAGE_BASE_PATH}${normalizedFilePath}`,
                layer?.url || ""
            ].filter(Boolean);
        }

        function setImageWithFallback(img, candidates, index = 0) {
            if (!img || index >= candidates.length) {
                if (img) {
                    img.remove();
                }
                return;
            }

            const candidate = candidates[index];
            img.onerror = () => setImageWithFallback(img, candidates, index + 1);
            img.src = `${candidate}${candidate.includes("?") ? "&" : "?"}t=${Date.now()}`;
        }

        function resetMingLookStage() {
            const stage = document.getElementById("ming-look-stage");
            const empty = document.getElementById("ming-look-empty");
            if (!stage) {
                return;
            }

            Array.from(stage.querySelectorAll(".ming-look-layer")).forEach((node) => node.remove());
            if (empty) {
                empty.style.display = "flex";
            }
        }

        function renderMingLook(data) {
            const stage = document.getElementById("ming-look-stage");
            const empty = document.getElementById("ming-look-empty");
            const title = document.getElementById("ming-look-title");

            if (!stage || !empty || !title) {
                return;
            }

            resetMingLookStage();

            if (!data || !Array.isArray(data.layers) || data.layers.length === 0) {
                title.innerText = "Current Ming Look";
                return;
            }

            empty.style.display = "none";
            title.innerText = data.name ? "Current Ming Look: " + data.name : "Current Ming Look";

            const layerMap = new Map();
            for (const layer of data.layers) {
                if (layer && layer.layer) {
                    layerMap.set(layer.layer, layer);
                }
            }

            for (const layerName of dressUpLayerOrder) {
                const layer = layerMap.get(layerName);
                if (!layer) {
                    continue;
                }

                const img = document.createElement("img");
                img.className = "ming-look-layer";
                img.alt = layer.name || layerName;
                stage.appendChild(img);
                setImageWithFallback(img, buildImageCandidates(layer));
            }
        }

        async function loadActiveMingLook() {
            try {
                const response = await fetch(ACTIVE_OUTFIT_ENDPOINT, {
                    cache: "no-store"
                });
                const rawText = await response.text();

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status} from ${ACTIVE_OUTFIT_ENDPOINT}: ${rawText.slice(0, 160)}`);
                }

                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (parseError) {
                    throw new Error(`Non-JSON response from ${ACTIVE_OUTFIT_ENDPOINT}: ${rawText.slice(0, 160)}`);
                }

                renderMingLook(data);
            } catch (error) {
                console.error("Failed to load Ming look:", error);
                resetMingLookStage();
            }
        }

        window.addEventListener("load", loadActiveMingLook);
    </script>
</body>
</html>
