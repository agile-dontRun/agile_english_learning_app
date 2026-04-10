<?php
require_once 'forum_common.php';
$currentUser = find_user($conn, current_user_id());
$unreadCount = unread_message_count($conn, current_user_id());

$currentPage = basename($_SERVER['PHP_SELF']);

function nav_active($page, $currentPage) {
    return $page === $currentPage ? 'subnav-link active' : 'subnav-link';
}

$displayName = $currentUser['nickname'] ?? $currentUser['username'] ?? current_nickname();
$initial = strtoupper(substr($displayName, 0, 1));
$username = $currentUser['username'] ?? '';
$nickname = $displayName;
$db_avatar = $currentUser['avatar_url'] ?? '';

$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . h($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . h($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . h($first_letter) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Community') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --oxford-blue: #002147;
            --oxford-blue-light: #0d3b73;
            --oxford-blue-deep: #00152d;
            --oxford-gold: #c4a661;
            --bg-light: #f4f6f8;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --text-dark: #243447;
            --text-gray: #5f6f82;
            --border-color: #dce3ea;
            --shadow-soft: 0 16px 40px rgba(0, 33, 71, 0.08);
            --shadow-card: 0 10px 28px rgba(0, 33, 71, 0.08);
            --danger: #b42318;
            --danger-soft: #fdecec;
            --info: #0d3b73;
            --info-soft: #e8f0fa;
            --warning: #9a6700;
            --warning-soft: #fff3d6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(196, 166, 97, 0.16), transparent 28%),
                linear-gradient(180deg, #f7f9fb 0%, #eef3f8 100%);
            color: var(--text-dark);
            font-family: 'Open Sans', Arial, sans-serif;
        }

        h1, h2, h3, h4, h5, h6 {
            margin: 0;
            font-family: 'PT Serif', Georgia, serif;
            font-weight: 400;
            color: var(--oxford-blue);
        }

        a {
            color: inherit;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 40px;
            height: 80px;
            background-color: var(--oxford-blue);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .navbar-left {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .college-logo {
            height: 50px;
            width: auto;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .college-logo:hover {
            transform: scale(1.02);
        }

        .navbar-links {
            display: flex;
            gap: 10px;
            list-style: none;
            margin: 0 0 0 40px;
            padding: 0;
            height: 100%;
            align-items: center;
        }

        .navbar-links > li {
            display: flex;
            align-items: center;
            position: relative;
            height: 100%;
        }

        .navbar-links a {
            color: #ffffff;
            text-decoration: none;
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 800;
            padding: 0 20px;
            height: 100%;
            display: flex;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
            transition: all 0.3s ease;
            -webkit-font-smoothing: antialiased;
        }

        .navbar-links a:hover {
            color: var(--oxford-gold);
            background-color: rgba(255, 255, 255, 0.05);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 80px;
            left: 0;
            background-color: var(--oxford-blue-light);
            min-width: 220px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            list-style: none;
            padding: 0;
            margin: 0;
            border-top: 2px solid var(--oxford-gold);
        }

        .dropdown-menu li {
            margin: 0;
            padding: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .dropdown-menu li:last-child {
            border-bottom: none;
        }

        .dropdown-menu li a {
            color: #e0e0e0 !important;
            padding: 15px 20px;
            text-transform: none;
            justify-content: flex-start;
            width: 100%;
            box-sizing: border-box;
            text-decoration: none !important;
            display: block;
            font-weight: 400;
            height: auto;
        }

        .dropdown-menu li a:hover {
            background-color: var(--oxford-blue) !important;
            color: var(--surface) !important;
            padding-left: 25px;
        }

        .navbar-links li:hover .dropdown-menu,
        .dropdown:hover .dropdown-menu {
            display: block;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            height: 100%;
            position: relative;
        }

        .user-avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--oxford-gold);
            object-fit: cover;
        }

        .user-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--oxford-gold);
            color: var(--oxford-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            border: 2px solid var(--oxford-gold);
            line-height: 1;
            box-sizing: border-box;
        }

        .navbar-right .dropdown-menu {
            background-color: var(--surface);
            border-top: none;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
            font-family: 'Playfair Display', serif;
        }

        .navbar-right .dropdown-menu li div[style*="font-size:12px"] {
            font-family: 'Playfair Display', serif !important;
            font-style: italic;
            letter-spacing: 0.5px;
            color: #888 !important;
        }

        .navbar-right .dropdown-menu li div[style*="font-size:16px"] {
            font-family: 'Playfair Display', serif !important;
            font-weight: 800;
            color: var(--oxford-blue) !important;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .navbar-right .dropdown-menu li a {
            font-family: 'Playfair Display', serif !important;
            font-weight: 700;
            font-size: 15px;
            color: var(--oxford-blue) !important;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }

        .navbar-right .dropdown-menu li a:hover {
            background-color: #f8fafc !important;
            color: var(--oxford-gold) !important;
            padding-left: 25px;
        }

        .forum-hero {
            position: relative;
            overflow: hidden;
            padding: 56px 40px 34px;
            background:
                linear-gradient(120deg, rgba(0, 21, 45, 0.94), rgba(13, 59, 115, 0.82)),
                linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0));
            color: white;
        }

        .forum-hero::after {
            content: '';
            position: absolute;
            right: -120px;
            top: -80px;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(196, 166, 97, 0.28), transparent 68%);
            pointer-events: none;
        }

        .forum-hero-inner {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, 420px);
            gap: 28px;
            align-items: end;
        }

        .hero-copy {
            max-width: 760px;
        }

        .hero-kicker {
            margin-bottom: 12px;
            color: rgba(255, 255, 255, 0.76);
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 0.78rem;
        }

        .hero-copy h1 {
            color: #fff;
            font-size: clamp(2rem, 4vw, 3.25rem);
            margin-bottom: 14px;
        }

        .hero-copy p {
            margin: 0;
            max-width: 680px;
            color: rgba(255, 255, 255, 0.84);
            line-height: 1.7;
            font-size: 1rem;
        }

        .hero-panel {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 24px;
            padding: 22px 24px;
            backdrop-filter: blur(10px);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
        }

        .hero-panel-label {
            font-size: 0.78rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 12px;
        }

        .hero-panel-user {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .hero-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--oxford-gold);
            color: var(--oxford-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .hero-panel-user strong {
            display: block;
            font-size: 1rem;
            color: #fff;
        }

        .hero-panel-user span {
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.92rem;
        }

        .forum-subnav {
            max-width: 1200px;
            margin: -18px auto 0;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .subnav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(220, 227, 234, 0.9);
            border-radius: 22px;
            padding: 16px 18px;
            box-shadow: var(--shadow-soft);
        }

        .subnav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .subnav-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--oxford-blue);
            background: var(--surface-muted);
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 0.92rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .subnav-link:hover {
            border-color: rgba(13, 59, 115, 0.2);
            background: #fff;
        }

        .subnav-link.active {
            background: var(--oxford-blue);
            color: #fff;
            border-color: var(--oxford-blue);
        }

        .forum-search-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .forum-search-input {
            width: min(280px, 100%);
            margin: 0;
            padding: 12px 16px;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-dark);
        }

        .forum-search-btn {
            border: 1px solid var(--oxford-blue);
            cursor: pointer;
            padding: 12px 18px;
            border-radius: 999px;
            background: var(--oxford-blue);
            color: #fff;
            font-weight: 600;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: var(--oxford-gold);
            color: var(--oxford-blue-deep);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .container {
            width: min(1200px, calc(100% - 40px));
            margin: 28px auto 48px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 26px;
            box-shadow: var(--shadow-card);
            margin-bottom: 22px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .row-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .section-intro {
            margin: 0;
            color: var(--text-gray);
            line-height: 1.7;
        }

        .meta {
            color: var(--text-gray);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .meta-line {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 8px;
        }

        .separator {
            color: #9aa8b8;
        }

        .post-title-link,
        .content-link {
            color: var(--oxford-blue);
            text-decoration: none;
        }

        .post-title-link:hover,
        .content-link:hover {
            text-decoration: underline;
        }

        .post-content {
            white-space: pre-wrap;
            line-height: 1.8;
            color: var(--text-dark);
            margin-top: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 18px;
            border-radius: 999px;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            font-size: 0.92rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--oxford-blue);
            border-color: var(--oxford-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--oxford-blue-light);
            border-color: var(--oxford-blue-light);
        }

        .btn-secondary {
            background: var(--surface-muted);
            border-color: var(--border-color);
            color: var(--oxford-blue);
        }

        .btn-secondary:hover {
            background: #eef3f8;
        }

        .btn-dark {
            background: var(--oxford-gold);
            border-color: var(--oxford-gold);
            color: var(--oxford-blue-deep);
        }

        .btn-dark:hover {
            filter: brightness(0.98);
        }

        .btn-danger {
            background: var(--danger-soft);
            border-color: rgba(180, 35, 24, 0.12);
            color: var(--danger);
        }

        .btn-danger:hover {
            background: #f9dddd;
        }

        textarea, input, select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            margin-top: 8px;
            margin-bottom: 14px;
            background: #fff;
            color: var(--text-dark);
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        textarea:focus, input:focus, select:focus {
            outline: none;
            border-color: rgba(13, 59, 115, 0.5);
            box-shadow: 0 0 0 4px rgba(13, 59, 115, 0.1);
        }

        label {
            display: block;
            margin-top: 4px;
            font-weight: 600;
            color: var(--oxford-blue);
        }

        .comment {
            border-left: 3px solid rgba(196, 166, 97, 0.85);
            padding: 0 0 0 18px;
            margin-top: 18px;
        }

        .reply-tag {
            display: inline-block;
            color: var(--oxford-blue-light);
            font-weight: 700;
            margin-left: 8px;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 14px;
            margin-bottom: 16px;
            border: 1px solid transparent;
        }

        .alert-danger {
            background: var(--danger-soft);
            border-color: rgba(180, 35, 24, 0.14);
            color: var(--danger);
        }

        .alert-info {
            background: var(--info-soft);
            border-color: rgba(13, 59, 115, 0.16);
            color: var(--info);
        }

        .alert-warning {
            background: var(--warning-soft);
            border-color: rgba(154, 103, 0, 0.18);
            color: var(--warning);
        }

        .link-user {
            color: var(--oxford-blue);
            text-decoration: none;
            font-weight: 700;
        }

        .link-user:hover {
            text-decoration: underline;
        }

        .actions-row,
        .inline-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .stat-item {
            padding: 18px;
            border-radius: 18px;
            border: 1px solid var(--border-color);
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .stat-value {
            font-family: 'PT Serif', Georgia, serif;
            font-size: 2rem;
            color: var(--oxford-blue);
        }

        .stat-label {
            margin-top: 6px;
            color: var(--text-gray);
            font-size: 0.92rem;
        }

        .list-stack {
            display: grid;
            gap: 14px;
        }

        .list-item {
            padding: 16px 0;
            border-top: 1px solid #edf2f7;
        }

        .list-item:first-child {
            border-top: none;
            padding-top: 0;
        }

        .empty-state {
            padding: 14px 0 4px;
            color: var(--text-gray);
        }

        .media-grid {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .media-card {
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 18px;
            background: var(--surface-muted);
        }

        .media-card img,
        .media-card video,
        .media-card audio {
            display: block;
            width: 100%;
            max-width: 100%;
            border-radius: 12px;
        }

        .split-layout {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 22px;
        }

        .chat-sidebar,
        .chat-window {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: var(--shadow-card);
        }

        .chat-sidebar {
            overflow: hidden;
        }

        .chat-sidebar-title,
        .chat-header {
            padding: 20px 22px;
            border-bottom: 1px solid #edf2f7;
            font-family: 'PT Serif', Georgia, serif;
            font-size: 1.25rem;
            color: var(--oxford-blue);
        }

        .chat-contact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 18px 20px;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #edf2f7;
            background: transparent;
            transition: background-color 0.2s ease;
        }

        .chat-contact:hover,
        .chat-contact.active {
            background: #f4f8fc;
        }

        .chat-messages {
            padding: 22px;
            min-height: 520px;
            max-height: 68vh;
            overflow-y: auto;
            background: linear-gradient(180deg, #fbfdff 0%, #f2f6fb 100%);
        }

        .msg-row {
            display: flex;
            margin-bottom: 16px;
        }

        .msg-row.me {
            justify-content: flex-end;
        }

        .msg-bubble {
            width: min(100%, 560px);
            padding: 16px 18px;
            border-radius: 20px;
            line-height: 1.65;
            background: #fff;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 24px rgba(0, 33, 71, 0.05);
        }

        .msg-row.me .msg-bubble {
            background: #eaf2fb;
            border-color: rgba(13, 59, 115, 0.12);
        }

        .msg-time {
            font-size: 0.78rem;
            color: var(--text-gray);
            margin-top: 10px;
        }

        .chat-input {
            padding: 22px;
            border-top: 1px solid #edf2f7;
        }

        .two-column {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        @media (max-width: 980px) {
            .navbar,
            .forum-hero {
                padding-left: 20px;
                padding-right: 20px;
            }

            .forum-hero-inner,
            .split-layout,
            .two-column,
            .stat-grid {
                grid-template-columns: 1fr;
            }

            .subnav-bar {
                padding: 14px;
            }
        }

        @media (max-width: 720px) {
            .navbar {
                padding-top: 14px;
                padding-bottom: 14px;
                height: auto;
                align-items: flex-start;
                flex-direction: column;
            }

            .navbar-left {
                width: 100%;
            }

            .navbar-links {
                margin-left: 0;
                flex-wrap: wrap;
            }

            .forum-search-form,
            .forum-search-input {
                width: 100%;
            }

            .container {
                width: min(100% - 24px, 1200px);
            }

            .card,
            .chat-input,
            .chat-sidebar-title,
            .chat-header,
            .chat-messages {
                padding-left: 18px;
                padding-right: 18px;
            }
        }
    </style>
</head>
<script src="ai-agent.js?v=1.4"></script>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <a href="home.php"><img src="college_logo.png" alt="Spires Academy Logo" class="college-logo"></a>

        <ul class="navbar-links">
            <li><a href="home.php">Home</a></li>
            <li class="dropdown">
                <a href="#">Study ▾</a>
                <ul class="dropdown-menu">
                    <li><a href="listening.php">Listening</a></li>
                    <li><a href="reading.php">Reading</a></li>
                    <li><a href="emma_server/speakAI.php">Speaking</a></li>
                    <li><a href="writing.php">Writing</a></li>
                    <li><a href="vocabulary.php">Vocabulary</a></li>
                </ul>
            </li>
            <li class="dropdown">
                <a href="#">Games ▾</a>
                <ul class="dropdown-menu">
                    <li><a href="galgame/galgame/index.html">Story game</a></li>
                </ul>
            </li>
            
            <a href="#" style="color:var(--oxford-gold);">Community</a>
        </ul>
    </div>

    <div class="navbar-right dropdown">
        <?= $avatar_html ?>
        <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?= h($nickname) ?> ▾</span>
        <ul class="dropdown-menu" style="right:0; left:auto; margin-top:0; min-width:220px;">
            <li style="padding: 20px; background: #f8fafc; cursor:default;">
                <div style="color:var(--text-gray); font-size:12px; margin-bottom:5px;">Signed in as</div>
                <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px;"><?= h($nickname) ?></div>
            </li>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="logout.php" style="color:#dc3545 !important; font-weight: 600;">Sign Out</a></li>
        </ul>
    </div>
</nav>

<section class="forum-hero">
    <div class="forum-hero-inner">
        <div class="hero-copy">
            <div class="hero-kicker">Community Forum</div>
            <h1><?= h($pageTitle ?? 'Community') ?></h1>
            <p>Connect with learners, share progress, exchange feedback, and keep every conversation in one calm academic space.</p>
        </div>

        <div class="hero-panel">
            <div class="hero-panel-label">Current learner</div>
            <div class="hero-panel-user">
                <div class="hero-avatar"><?= h($initial) ?></div>
                <div>
                    <strong><?= h($displayName) ?></strong>
                    <span>Unread messages: <?= (int)$unreadCount ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="forum-subnav">
    <div class="subnav-bar">
        <div class="subnav-links">
            <a class="<?= nav_active('forum.php', $currentPage) ?>" href="forum.php">Community</a>
            <a class="<?= nav_active('forum_post_create.php', $currentPage) ?>" href="forum_post_create.php">New Post</a>
            <a class="<?= nav_active('forum_friends.php', $currentPage) ?>" href="forum_friends.php">Friends</a>
            <a class="<?= nav_active('forum_following.php', $currentPage) ?>" href="forum_following.php">Following</a>
            <a class="<?= nav_active('forum_inbox.php', $currentPage) ?>" href="forum_inbox.php">
                Message<?php if ($unreadCount > 0): ?><span class="badge"><?= (int)$unreadCount ?></span><?php endif; ?>
            </a>
        </div>

        <form class="forum-search-form" method="get" action="forum_search.php">
            <input class="forum-search-input" type="text" name="keyword" placeholder="Search users or posts..." value="<?= h($_GET['keyword'] ?? '') ?>">
            <button class="forum-search-btn" type="submit">Search</button>
        </form>
    </div>
</div>

<div class="container">
