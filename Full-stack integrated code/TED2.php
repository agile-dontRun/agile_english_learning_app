<?php

function render_oxford_navbar($nickname, $avatar_html) {
    ?>
    <nav class="navbar">
        <div class="navbar-left">
            <a href="home.php"><img src="college_logo.png" alt="Spires Academy Logo" class="college-logo"></a>
            <ul class="navbar-links">
                <li><a href="home.php">Home</a></li>
                <li class="dropdown">
                    <a href="#" style="color:var(--oxford-gold);">Study ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="listening.php">Listening Center</a></li>
                        <li><a href="reading.php">Reading Room</a></li>
                        <li><a href="emma_server/speakAI.php">Emma Speaking</a></li>
                        <li><a href="writing.php">Writing Lab</a></li>
                    </ul>
                </li>
                <li><a href="forum.php">Community</a></li>
            </ul>
        </div>
        <div class="navbar-right dropdown">
            <?php echo $avatar_html; ?>
            <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?php echo htmlspecialchars($nickname); ?> ▾</span>
            <ul class="dropdown-menu" style="right:0; left:auto;">
                <li style="padding: 20px; background: #f8fafc;">
                    <div style="color:#666; font-size:12px;">Signed in as</div>
                    <div style="color:#002147; font-weight:bold;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important;">Sign Out</a></li>
            </ul>
        </div>
    </nav>
    <?php
}


function render_ted_hero($title = "TED Library", $subtitle = "Illuminating Ideas • Refined Comprehension") {
    ?>
    <header class="hero">
        <h1><?php echo $title; ?></h1>
        <p><?php echo $subtitle; ?></p>
    </header>
    <?php
}