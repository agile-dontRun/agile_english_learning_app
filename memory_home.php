<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$user = mm_get_user($conn, $userId);
$nickname = $user ? ($user['nickname'] ?: $user['username']) : 'User';
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
            padding: 24px 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cover-stage {
            position: relative;
            width: min(1320px, calc(100vw - 36px));
            aspect-ratio: 1365 / 768;
            max-height: calc(100vh - 150px);
        }

        .cover-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            border-radius: 24px;
            box-shadow: 0 18px 48px rgba(125, 82, 38, 0.18);
            user-select: none;
            pointer-events: none;
        }

        .menu-panel {
            position: absolute;
            left: 7%;
            bottom: 4.4%;
            z-index: 3;
            width: 24.9%;
            min-width: 250px;
            max-width: 340px;
            display: flex;
            flex-direction: column;
            gap: clamp(8px, 1vw, 14px);
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
            font-size: 1.7rem;
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
            right: 4.4%;
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
                width: min(1200px, calc(100vw - 28px));
            }

            .menu-panel {
                min-width: 220px;
                width: 27%;
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
                padding-top: 20px;
                align-items: flex-start;
            }

            .cover-stage {
                width: calc(100vw - 20px);
                max-height: none;
            }

            .menu-panel {
                left: 5.6%;
                bottom: 4.2%;
                width: 28.5%;
                min-width: 170px;
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
                padding: 18px 10px;
            }

            .cover-stage {
                width: calc(100vw - 20px);
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

            <!-- TODO: Team integration point: replace # with the target file/path for Exit -->
            <a class="exit-btn" href="#exit-link-placeholder">
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
        </div>
    </div>
</body>
</html>
