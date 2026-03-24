<?php
if (!isset($page_title)) $page_title = 'Forum';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?> - Word Garden</title>
    <style>
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --soft-green-bg: #f2f7f5;
            --card-shadow: 0 10px 30px rgba(27, 67, 50, 0.08);
            --text-main: #2d3436;
        }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--soft-green-bg); color: var(--text-main); }
        .nav-header { width: 100%; height: 70px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); position: fixed; top: 0; z-index: 1000; box-sizing: border-box; }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links { display: flex; gap: 16px; }
        .nav-links a { text-decoration: none; color: #666; font-size: 14px; font-weight: 500; padding: 5px 12px; border-radius: 8px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }
        .hero-mini { background: linear-gradient(135deg, #081c15 0%, #1b4332 100%); color: white; padding: 110px 20px 60px; text-align: center; }
        .hero-mini h1 { margin: 0; font-size: 2.4rem; letter-spacing: 1px; }
        .hero-mini p { opacity: .85; margin-top: 10px; }
        .main-content { max-width: 1100px; margin: -30px auto 60px; padding: 0 20px; position: relative; z-index: 10; }
        .card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: var(--card-shadow); margin-bottom: 20px; }
        .meta { color: #6d7d76; font-size: 14px; }
        .btn { display: inline-block; padding: 11px 18px; border-radius: 999px; text-decoration: none; border: none; cursor: pointer; font-weight: 600; }
        .btn-primary { background: var(--primary-green); color: white; }
        .btn-secondary { background: #e2e8f0; color: #111827; }
        .btn-dark { background: #111827; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-outline { background: transparent; color: var(--accent-green); border: 1px solid #b7d3c7; }
        .row-between { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        textarea, input, select { width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid #d7e3dc; border-radius: 12px; margin-top: 6px; margin-bottom: 14px; font-size: 14px; }
        .post-content { white-space: pre-wrap; line-height: 1.7; }
        .comment { border-left: 3px solid #93c5fd; padding-left: 12px; margin-bottom: 18px; }
        .reply-tag { color: #2563eb; font-weight: bold; margin-left: 6px; }
        .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .alert-info { background: #dbeafe; color: #1d4ed8; }
        .alert-warning { background: #fef3c7; color: #92400e; }
        .grid-two { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
        .message-bubble { padding: 14px 16px; border-radius: 16px; margin-bottom: 12px; }
        .message-own { background: #dbeafe; }
        .message-other { background: #f8fafc; }
        @media (max-width: 900px) {
            .nav-header { padding: 0 16px; }
            .nav-links { gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
            .grid-two { grid-template-columns: 1fr; }
            .row-between { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<nav class="nav-header">
    <a href="home.php" class="nav-logo">Word Garden</a>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="TED.php">TED Talk</a>
        <a href="ielts.php">IELTS</a>
        <a href="vocabulary.php">Vocabulary</a>
        <a href="forum.php" class="active">Group</a>
        <a href="profile.php">Profile</a>
    </div>
</nav>
<header class="hero-mini">
    <h1><?= h($page_title) ?></h1>
    <p>Share learning notes, connect with classmates, and chat together.</p>
</header>
<main class="main-content">
