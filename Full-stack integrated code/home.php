<?php
/**
 * Spires Academy - Student Portal (Home)
 * Core landing page providing daily objectives, attendance tracking, 
 * and academic resource links.
 */
session_start();

// 1. Authenticate session or bounce to login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connect.php'; 

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- User Profile Retrieval ---
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

// Build avatar logic (prioritize DB image, fallback to initial)
$avatar_html = '';
$first_letter = strtoupper(substr($username ?: 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . htmlspecialchars($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . htmlspecialchars($first_letter) . '</div>';
}

// --- Attendance/Calendar Logic ---
$calendar_id = null;
$cal_stmt = $conn->prepare("SELECT calendar_id FROM checkin_calendars WHERE user_id = ?");
if ($cal_stmt) {
    $cal_stmt->bind_param("i", $user_id);
    $cal_stmt->execute();
    $cal_res = $cal_stmt->get_result()->fetch_assoc();
    if ($cal_res) $calendar_id = $cal_res['calendar_id'];
    $cal_stmt->close();
}

// Auto-generate calendar if missing
if (!$calendar_id) {
    $ins_cal = $conn->prepare("INSERT INTO checkin_calendars (user_id) VALUES (?)");
    if ($ins_cal) {
        $ins_cal->bind_param("i", $user_id);
        $ins_cal->execute();
        $calendar_id = $ins_cal->insert_id;
        $ins_cal->close();
    }
}

$isCheckedInToday = false;
$checked_days = [];
if ($calendar_id) {
    // Check current status
    $checkin_stmt = $conn->prepare("SELECT COUNT(*) FROM daily_checkin_records WHERE calendar_id = ? AND checkin_date = ?");
    if ($checkin_stmt) {
        $checkin_stmt->bind_param("is", $calendar_id, $today);
        $checkin_stmt->execute();
        if ($checkin_stmt->get_result()->fetch_row()[0] > 0) $isCheckedInToday = true;
        $checkin_stmt->close();
    }

    // Load monthly history for grid highlights
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

// Pre-calc calendar dates
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
            --white: #ffffff; 
            --bg-light: #f4f7f6;
            --text-dark: #333333; 
            --text-light: #666666;
            --border-color: #e0e0e0;
        }

        body { margin: 0; padding: 0; font-family: 'Open Sans', Arial, sans-serif; background-color: var(--bg-light); color: var(--text-dark); overflow-x: hidden; }
        h1, h2, h3, h4 { font-family: 'Playfair Display', Georgia, serif; letter-spacing: 0.5px; }

        /* Navbar & Dropdowns */
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; }
        .college-logo { height: 50px; cursor: pointer; }
        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; align-items: center; }
        .navbar-links a { color: #ffffff; text-decoration: none; font-family: 'Playfair Display', serif; font-size: 15px; font-weight: 800; padding: 10px 15px; text-transform: uppercase; letter-spacing: 1.2px; transition: 0.3s; }
        .navbar-links a:hover { color: var(--oxford-gold); }

        .dropdown { position: relative; }
        .dropdown-menu { display: none; position: absolute; top: 100%; left: 0; background: var(--oxford-blue-light); min-width: 200px; padding: 10px 0; border-top: 3px solid var(--oxford-gold); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .dropdown:hover .dropdown-menu { display: block; }
        .dropdown-menu li { list-style: none; }
        .dropdown-menu a { display: block; padding: 10px 20px; font-weight: 400; font-size: 14px; text-transform: none; }

        /* Hero / Header Section */
        .hero { background: linear-gradient(rgba(0,33,71,0.7), rgba(0,33,71,0.7)), url('hero_bg2.png') center/cover; color: var(--white); text-align: center; padding: 120px 20px; }
        .hero h1 { font-size: 4.5rem; margin: 0 0 15px; text-transform: uppercase; letter-spacing: 4px; }
        .hero p { font-size: 1.3rem; font-style: italic; opacity: 0.9; }

        /* Main Grid Layout */
        .main-container { max-width: 1300px; margin: -50px auto 60px; padding: 0 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 30px; position: relative; z-index: 10; }
        
        .tasks-section, .calendar-section { background: var(--white); border-radius: 8px; padding: 35px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); }
        
        .task-card { display: flex; gap: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border-color); border-left: 5px solid var(--oxford-blue); transition: 0.3s; align-items: center; }
        .task-card:hover { transform: translateX(5px); border-left-color: var(--oxford-gold); }
        .task-icon { font-size: 30px; }
        .btn-go { background: var(--oxford-blue); color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }

        /* Calendar Grid */
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; margin-top: 20px; }
        .day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; font-size: 14px; background: #f8fafc; border-radius: 4px; }
        .day.today { border: 2px solid var(--oxford-gold); color: var(--oxford-blue); font-weight: bold; }
        .day.checked-in { background: var(--oxford-blue); color: var(--white); }

        .btn-checkin { width: 100%; padding: 15px; background: var(--oxford-blue); color: #fff; border: none; border-radius: 4px; cursor: pointer; text-transform: uppercase; font-weight: 800; font-family: 'Playfair Display'; margin-top: 20px; }
        .btn-checkin:hover:not(:disabled) { background: var(--oxford-gold); color: var(--oxford-blue); }

        /* Floating AI Assistant Spot */
        .ai-assistant-wrapper { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }

        .footer { background: var(--oxford-blue); color: #fff; padding: 40px 0; text-align: center; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="home.php"><img src="college_logo.png" alt="Spires Academy" class="college-logo"></a>
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
                <li><a href="forum.php">Community</a></li>
            </ul>
        </div>
        <div class="navbar-right dropdown">
            <?php echo $avatar_html; ?>
            <span style="margin-left:8px;"><?php echo htmlspecialchars($nickname); ?> ▾</span>
            <ul class="dropdown-menu" style="right:0; left:auto;">
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#ff4d4d;">Sign Out</a></li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <h1>Spires Academy</h1>
        <p>Pursue Excellence, Cultivate Eloquence, Maintain Rigor.</p>
    </header>

    <main class="main-container">
        <section class="tasks-section">
            <h2 style="color:var(--oxford-blue); margin-bottom:25px;">Daily Academic Objectives</h2>
            
            <div class="task-card">
                <div class="task-icon">🎧</div>
                <div style="flex:1;"><h3>Listening Proficiency</h3><p>Lecture comprehension and table completion exercises.</p></div>
                <a href="listening.php" class="btn-go">Begin</a>
            </div>

            <div class="task-card">
                <div class="task-icon">📖</div>
                <div style="flex:1;"><h3>Analytical Reading</h3><p>Extracting arguments from multidisciplinary scholarly texts.</p></div>
                <a href="reading.php" class="btn-go">Begin</a>
            </div>

            <div class="task-card">
                <div class="task-icon">🗣️</div>
                <div style="flex:1;"><h3>Spoken Eloquence</h3><p>5-minute conversational assessment with Emma AI.</p></div>
                <a href="emma_server/speakAI.php" class="btn-go">Begin</a>
            </div>
        </section>

        <aside class="calendar-section">
            <h3 style="text-align:center; color:var(--oxford-blue);"><?php echo $month_name; ?></h3>
            <div class="calendar-grid">
                <?php
                $days_labels = ['S','M','T','W','T','F','S'];
                foreach($days_labels as $dl) echo "<div style='text-align:center; font-weight:bold; font-size:12px;'>$dl</div>";

                for ($i = 0; $i < $first_day_of_month; $i++) echo '<div></div>';
                
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $classes = 'day';
                    $cur = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                    if ($cur === $today) $classes .= ' today';
                    if (in_array($day, $checked_days)) $classes .= ' checked-in';
                    echo "<div class='$classes'>$day</div>";
                }
                ?>
            </div>
            
            <div id="checkinFeedback" style="text-align:center; margin-top:15px; font-size:14px;"></div>
            <?php if ($isCheckedInToday): ?>
                <button class="btn-checkin" disabled style="background:#ccc;">Attendance Logged</button>
            <?php else: ?>
                <button id="checkinBtn" class="btn-checkin">Log Attendance</button>
            <?php endif; ?>
        </aside>
    </main>

    <footer class="footer">
        <p>&copy; 2026 Spires Academy. Fostering Intellectual Curiosity.</p>
    </footer>

    <div class="ai-assistant-wrapper">
        </div>
    <script src="ai-agent.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('checkinBtn');
            const fb = document.getElementById('checkinFeedback');

            if (btn) {
                btn.addEventListener('click', () => {
                    fb.innerText = 'Verifying...';
                    fetch('api_checkin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'daily_checkin' })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            fb.innerHTML = '<span style="color:green;">✅ Logged</span>';
                            btn.innerText = 'Attendance Logged';
                            btn.disabled = true;
                            document.querySelector('.day.today').classList.add('checked-in');
                        } else {
                            fb.innerHTML = '<span style="color:red;">❌ Sync Error</span>';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
