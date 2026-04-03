<?php
session_start();

// 1. Strict login verification
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Include database connection
require_once 'db_connect.php'; 

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// ==========================================
// A. Retrieve user profile (for navbar avatar)
// ==========================================
$username = ''; $nickname = 'Student'; $db_avatar = '';
$stmt = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    if ($user_data) {
        $username = $user_data['username'];
        $nickname = !empty($user_data['nickname']) ? $user_data['nickname'] : $username;
        $db_avatar = $user_data['avatar_url'];
    }
    $stmt->close();
}

$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . htmlspecialchars($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . htmlspecialchars($first_letter) . '</div>';
}

// ==========================================
// B. Retrieve user's Calendar ID
// ==========================================
$calendar_id = null;
$cal_stmt = $conn->prepare("SELECT calendar_id FROM checkin_calendars WHERE user_id = ?");
if ($cal_stmt) {
    $cal_stmt->bind_param("i", $user_id);
    $cal_stmt->execute();
    $cal_res = $cal_stmt->get_result()->fetch_assoc();
    if ($cal_res) $calendar_id = $cal_res['calendar_id'];
    $cal_stmt->close();
}

if (!$calendar_id) {
    $ins_cal = $conn->prepare("INSERT INTO checkin_calendars (user_id) VALUES (?)");
    if ($ins_cal) {
        $ins_cal->bind_param("i", $user_id);
        $ins_cal->execute();
        $calendar_id = $ins_cal->insert_id;
        $ins_cal->close();
    }
}

// ==========================================
// C. Check today's check-in status and monthly records
// ==========================================
$isCheckedInToday = false;
$checked_days = [];

if ($calendar_id) {
    $checkin_stmt = $conn->prepare("SELECT COUNT(*) FROM daily_checkin_records WHERE calendar_id = ? AND checkin_date = ?");
    if ($checkin_stmt) {
        $checkin_stmt->bind_param("is", $calendar_id, $today);
        $checkin_stmt->execute();
        if ($checkin_stmt->get_result()->fetch_row()[0] > 0) $isCheckedInToday = true;
        $checkin_stmt->close();
    }

    $month = date('m'); $year = date('Y');
    $month_stmt = $conn->prepare("SELECT DAY(checkin_date) as d FROM daily_checkin_records WHERE calendar_id = ? AND MONTH(checkin_date) = ? AND YEAR(checkin_date) = ?");
    if ($month_stmt) {
        $month_stmt->bind_param("iii", $calendar_id, $month, $year);
        $month_stmt->execute();
        $res_chk = $month_stmt->get_result();
        while($r = $res_chk->fetch_assoc()) { $checked_days[] = $r['d']; }
        $month_stmt->close();
    }
}

$year = date('Y');
$month = date('m');
$days_in_month = date('t', strtotime("$year-$month-01"));
$first_day_of_month = date('w', strtotime("$year-$month-01")); 
$month_name = date('F Y');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --oxford-blue: #002147; 
            --oxford-blue-light: #003066;
            --oxford-gold: #c4a661; 
            --oxford-gold-light: #d4b671;
            --white: #ffffff; 
            --bg-light: #f4f7f6;
            --text-dark: #333333; 
            --text-light: #666666;
            --border-color: #e0e0e0;
        }

        body { margin: 0; padding: 0; font-family: 'Open Sans', Arial, sans-serif; background-color: var(--bg-light); color: var(--text-dark); overflow-x: hidden; }
        h1, h2, h3, h4 { font-family: 'Playfair Display', Georgia, serif; letter-spacing: 0.5px; }

        /* ===== 导航栏 ===== */
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }

        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
        .navbar-links a { color: #ffffff; text-decoration: none; font-family: 'Playfair Display', serif; font-size: 16px; font-weight: 800; padding: 0 20px; height: 100%; display: flex; align-items: center; text-transform: uppercase; letter-spacing: 1.8px; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6); transition: all 0.3s ease; -webkit-font-smoothing: antialiased; }
        .navbar-links a:hover { color: var(--oxford-gold); background-color: rgba(255, 255, 255, 0.05); }

        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none !important; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown-menu li { list-style: none !important; margin: 0; padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; display: block; font-weight: 400; height: auto; text-shadow: none; letter-spacing: 0.5px;}
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }
        .navbar-links li:hover .dropdown-menu, .dropdown:hover .dropdown-menu { display: block; }

        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img, .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; line-height: 1; box-sizing: border-box; }
        .navbar-right .dropdown-menu { background-color: var(--white); border-top: none; border-radius: 0 0 8px 8px; right: 0; left: auto; font-family: 'Playfair Display', serif; }
        .navbar-right:hover .dropdown-menu { display: block; }
        .navbar-right .dropdown-menu li a { color: var(--oxford-blue) !important; font-weight: 700; font-size: 15px; letter-spacing: 0.5px; }
        .navbar-right .dropdown-menu li a:hover { background-color: #f8fafc !important; color: var(--oxford-gold) !important; padding-left: 25px; }

        /* ===== Hero 区域 ===== */
        .hero {
            background: url('hero_bg2.png') center/cover no-repeat; 
            color: var(--white); text-align: center; padding: 140px 20px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        }
        .hero h1 { font-family: 'Playfair Display', serif; font-size: 5rem; font-weight: 800; margin: 0 0 20px; text-transform: uppercase; letter-spacing: 5px; text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.8); }
        .hero p { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 400; font-style: italic; max-width: 800px; margin: 0 auto; text-shadow: 1px 2px 5px rgba(0, 0, 0, 0.8); }

        /* ===== 🌟 挪动并优化的 Plagiarism 版块 (Subtle Insight) ===== */
        .academic-notice {
            background: var(--white);
            max-width: 1260px;
            margin: 40px auto 0;
            padding: 30px 40px;
            border-radius: 8px;
            border-left: 5px solid var(--oxford-gold);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        }
        .notice-content h3 { color: var(--oxford-blue); margin: 0 0 5px; font-size: 1.5rem; }
        .notice-content p { color: var(--text-light); margin: 0; font-family: 'Lora', serif; font-size: 0.95rem; }
        .btn-notice {
            padding: 10px 25px; border: 2px solid var(--oxford-blue); color: var(--oxford-blue);
            text-decoration: none; font-weight: 800; font-family: 'Playfair Display', serif;
            text-transform: uppercase; font-size: 12px; letter-spacing: 1px; border-radius: 4px; transition: 0.3s;
        }
        .btn-notice:hover { background: var(--oxford-blue); color: var(--white); }

    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="home.php"><img src="college_logo.png" alt="Spires Academy Logo" class="college-logo"></a>
            <ul class="navbar-links">
               <li><a href="#" style="color:var(--oxford-gold);">Home</a></li>
                <li class="dropdown">
                    <a href="#">Study ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="listening.php">Listening</a></li>
                        <li><a href="reading.php">Reading</a></li>
                        <li><a href="emma_server/speakAI.php">Speaking</a></li>
                        <li><a href="writing.php">Writing</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Games ▾</a>
                    <ul class="dropdown-menu"><li><a href="galgame/galgame/index.html">Story game</a></li></ul>
                </li>
                <li><a href="forum.php">Community</a></li>
            </ul>
        </div>
        <div class="navbar-right dropdown">
            <?php echo $avatar_html; ?>
            <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?php echo htmlspecialchars($nickname); ?> ▾</span>
            <ul class="dropdown-menu" style="right:0; left:auto; margin-top:0; min-width:220px;">
                <li style="padding: 20px; background: #f8fafc; cursor:default;">
                    <div style="color:var(--text-light); font-size:12px; margin-bottom:5px;">Signed in as</div>
                    <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important; font-weight: 600;">Sign Out</a></li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <h1>Spires Academy</h1>
        <p>Pursue Excellence, Cultivate Eloquence, and Maintain Intellectual Rigor.</p>
    </header>

    <main class="main-container">
        <section class="tasks-section">
            <div class="section-header"><h2>Today's Academic Objectives</h2></div>
            <div class="task-card">
                <div class="task-icon">🎧</div>
                <div class="task-info"><h3>Listening</h3><p>Practice Part 3: Multiple Choice & Table Completion based on academic lectures.</p></div>
                <div class="task-action"><a href="listening.php" class="btn-go">Begin Task</a></div>
            </div>
            <div class="task-card">
                <div class="task-icon">📖</div>
                <div class="task-info"><h3>Reading</h3><p>Analyze scholarly articles, extract core arguments, and improve comprehension speed.</p></div>
                <div class="task-action"><a href="reading.php" class="btn-go">Begin Task</a></div>
            </div>
            <div class="task-card">
                <div class="task-icon">🗣️</div>
                <div class="task-info"><h3>Speaking</h3><p>Complete a 5-minute academic conversation and receive immediate scoring and feedback.</p></div>
                <div class="task-action"><a href="emma_server/speakAI.php" class="btn-go">Begin Task</a></div>
            </div>
        </section>

        <section class="calendar-section">
            <div class="calendar-header"><h3><?php echo $month_name; ?></h3></div>
            <div class="calendar-grid">
                <div class="calendar-day-label">Sun</div><div class="calendar-day-label">Mon</div><div class="calendar-day-label">Tue</div><div class="calendar-day-label">Wed</div><div class="calendar-day-label">Thu</div><div class="calendar-day-label">Fri</div><div class="calendar-day-label">Sat</div>
                <?php
                for ($i = 0; $i < $first_day_of_month; $i++) { echo '<div class="day empty" style="border:none; background:transparent;"></div>'; }
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $classes = 'day';
                    $current_date_str = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                    if ($current_date_str === $today) $classes .= ' today';
                    if (in_array($day, $checked_days)) $classes .= ' checked-in';
                    echo "<div class='$classes'>$day</div>";
                }
                ?>
            </div>
            <div class="checkin-area">
                <div id="checkinFeedback" style="margin-bottom:10px;"></div>
                <?php if ($isCheckedInToday): ?>
                    <button class="btn-checkin" disabled>Attendance Logged</button>
                <?php else: ?>
                    <button id="checkinBtn" class="btn-checkin">Log Attendance</button>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <section class="academic-notice">
        <div class="notice-content">
            <h3>Intellectual Honesty & Integrity</h3>
            <p>Originality is the foundation of your academic career. Learn how to cite correctly and avoid plagiarism.</p>
        </div>
        <a href="plagiarism.php" class="btn-notice">Review Code of Conduct</a>
    </section>

    <section class="resources-section">
        <div class="section-header" style="text-align:center; width:100%;"><h2>Global Academic Resources</h2></div>
        <div class="resource-grid">
            <a href="https://scholar.google.com/" target="_blank" class="resource-card"><div class="resource-icon">🔍</div><h3>Google Scholar</h3><p>Access diverse scholarly articles and theses.</p></a>
            <a href="https://www.nature.com/" target="_blank" class="resource-card"><div class="resource-icon">🧬</div><h3>Nature Journal</h3><p>Cutting-edge multidisciplinary science research.</p></a>
            <a href="https://www.ted.com/talks" target="_blank" class="resource-card"><div class="resource-icon">💡</div><h3>TED Talks</h3><p>Expert insights on education, science, and tech.</p></a>
            <a href="https://www.jstor.org/" target="_blank" class="resource-card"><div class="resource-icon">📚</div><h3>JSTOR</h3><p>Primary sources and academic journal library.</p></a>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-about"><h3>Spires Academy</h3><p>Fostering intellectual curiosity through rigorous language training and AI-assisted evaluations.</p></div>
            <div class="footer-links"><h4>Quick Links</h4><ul><li><a href="plagiarism.php">Integrity Policy</a></li><li><a href="listening.php">Listening</a></li><li><a href="reading.php">Reading</a></li><li><a href="writing.php">Writing</a></li></ul></div>
        </div>
        <div class="footer-bottom">&copy; 2026 Spires Academy. All rights reserved.</div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkinBtn = document.getElementById('checkinBtn');
            const feedbackArea = document.getElementById('checkinFeedback');
            if (checkinBtn) {
                checkinBtn.addEventListener('click', function() {
                    feedbackArea.innerText = 'Verifying...';
                    fetch('api_checkin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'daily_checkin' }) })
                    .then(r => r.json()).then(data => {
                        if (data.status === 'success') {
                            feedbackArea.innerText = '✅ Success'; feedbackArea.style.color = 'var(--oxford-blue)';
                            checkinBtn.innerText = 'Attendance Logged'; checkinBtn.disabled = true;
                            document.querySelector('.day.today').classList.add('checked-in');
                        } else { feedbackArea.innerText = '❌ Error'; feedbackArea.style.color = '#dc3545'; }
                    });
                });
            }
        });
    </script>
</body>
</html>