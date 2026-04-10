<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nickname = $_SESSION['nickname'] ?? 'Learner';

$cal_query = $conn->prepare("SELECT calendar_id FROM checkin_calendars WHERE user_id = ?");
$cal_query->bind_param("i", $user_id);
$cal_query->execute();
$cal_res = $cal_query->get_result();
$calendar = $cal_res->fetch_assoc();

if (!$calendar) {
    $ins_cal = $conn->prepare("INSERT INTO checkin_calendars (user_id) VALUES (?)");
    $ins_cal->bind_param("i", $user_id);
    $ins_cal->execute();
    $calendar_id = $conn->insert_id;
} else {
    $calendar_id = $calendar['calendar_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkin') {
    header('Content-Type: application/json');
    $today = date('Y-m-d');
    
    $check_sql = $conn->prepare("SELECT record_id FROM daily_checkin_records WHERE calendar_id = ? AND checkin_date = ?");
    $check_sql->bind_param("is", $calendar_id, $today);
    $check_sql->execute();
    
    if ($check_sql->get_result()->num_rows === 0) {
        $ins_record = $conn->prepare("INSERT INTO daily_checkin_records (calendar_id, checkin_date, study_minutes) VALUES (?, ?, 30)");
        $ins_record->bind_param("is", $calendar_id, $today);
        if ($ins_record->execute()) {
            echo json_encode(['status' => 'success', 'date' => $today]);
        } else {
            echo json_encode(['status' => 'error']);
        }
    } else {
        echo json_encode(['status' => 'exists']);
    }
    exit;
}

$current_month = date('Y-m');
$records_sql = $conn->prepare("SELECT checkin_date FROM daily_checkin_records WHERE calendar_id = ? AND checkin_date LIKE ?");
$search_month = $current_month . '%';
$records_sql->bind_param("is", $calendar_id, $search_month);
$records_sql->execute();
$records_res = $records_sql->get_result();
$checked_dates = [];
while($row = $records_res->fetch_assoc()) {
    $checked_dates[] = $row['checkin_date'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Learning Calendar - Word Garden</title>
    <style>
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --soft-green-bg: #f2f7f5;
            --card-shadow: 0 10px 30px rgba(27, 67, 50, 0.08);
            --text-main: #2d3436;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--soft-green-bg);
            margin: 0;
            color: var(--text-main);
        }

        .nav-header { /* 保持原样 */ 
            width: 100%; height: 70px; background: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 50px; box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: fixed; top: 0; z-index: 1000; box-sizing: border-box;
        }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a { text-decoration: none; color: #666; font-size: 14px; font-weight: 500; padding: 5px 12px; border-radius: 8px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }

        .hero-mini {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: white; padding: 110px 20px 70px; text-align: center;
        }

        .main-content {
            max-width: 900px; margin: -50px auto 60px; padding: 0 20px;
            position: relative; z-index: 10;
        }

        .calendar-card {
            background: white; border-radius: 25px; padding: 40px;
            box-shadow: var(--card-shadow); text-align: center;
        }

        .calendar-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; border-bottom: 2px solid var(--soft-green-bg);
            padding-bottom: 20px;
        }

        .current-month-text { font-size: 1.5rem; font-weight: 700; color: var(--primary-green); }

        .calendar-grid {
            display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px;
        }
        .weekday { font-weight: bold; color: var(--accent-green); padding: 10px 0; }
        .day {
            aspect-ratio: 1 / 1; border-radius: 12px; display: flex;
            flex-direction: column; align-items: center; justify-content: center;
            background: #fdfdfd; border: 1px solid #f0f0f0; transition: 0.3s;
            position: relative; font-weight: 500;
        }
        .day.today { border: 2px solid var(--accent-green); color: var(--accent-green); }
        .day.checked { background: #e9f5ef; border-color: var(--accent-green); }
        .day.checked::after {
            content: '✓'; position: absolute; bottom: 5px; right: 5px;
            font-size: 12px; color: var(--accent-green); font-weight: bold;
        }

        .checkin-btn {
            background: var(--primary-green); color: white; border: none;
            padding: 15px 45px; border-radius: 50px; font-weight: bold;
            font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 30px;
        }
        .checkin-btn:hover { background: var(--accent-green); transform: translateY(-3px); }
        .checkin-btn:disabled { background: #d1d8d5; cursor: not-allowed; }

        /* ====================== 自定义弹窗 ====================== */
        .custom-modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            width: 380px;
            border-radius: 24px;
            text-align: center;
            padding: 40px 30px;
            box-shadow: 0 20px 50px rgba(27, 67, 50, 0.25);
            animation: modalPop 0.3s ease;
        }
        @keyframes modalPop {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .modal-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .modal-title {
            font-size: 24px;
            font-weight: bold;
            color: #1b4332;
            margin-bottom: 12px;
        }
        .modal-text {
            font-size: 17px;
            color: #555;
            line-height: 1.5;
        }
        .modal-btn {
            margin-top: 30px;
            background: #40916c;
            color: white;
            border: none;
            padding: 14px 40px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
        }
        .modal-btn:hover { background: #1b4332; }

        .side-controls { position: fixed; bottom: 40px; right: 40px; z-index: 100; }
        .ai-assistant { display: flex; align-items: center; gap: 15px; }
        .chat-bubble { background: white; padding: 12px 20px; border-radius: 20px 20px 5px 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); font-size: 14px; border: 1px solid #eef5f2; }
        .ai-icon-circle { width: 60px; height: 60px; background: linear-gradient(135deg, var(--accent-green), var(--primary-green)); border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold; }
    </style>
</head>
<body>

    <!-- 原有导航和内容保持不变 -->
    <nav class="nav-header">
        <a href="home.php" class="nav-logo">Word Garden</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="TED.php">TED Talk</a>
            <a href="ielts.php">IELTS</a>
            <a href="daily_talk.php">Daily Talk</a>
            <a href="reading.php">Reading</a>
            <a href="vocabulary.php">Vocabulary</a>
            <a href="calendar.php" class="active">Calendar</a>
            <a href="forum.php">Community</a>
            <a href="profile.php">Profile</a>
        </div>
    </nav>

    <header class="hero-mini">
        <h1>Learning Tracker</h1>
        <p>Your consistency is the soil for your growth</p>
    </header>

    <main class="main-content">
        <div class="calendar-card">
            <div class="calendar-header">
                <div class="current-month-text"><?php echo date('F Y'); ?></div>
                <div style="font-size: 14px; color: #666;">Total Checked: <strong><?php echo count($checked_dates); ?> Days</strong></div>
            </div>

            <div class="calendar-grid">
                <div class="weekday">Sun</div><div class="weekday">Mon</div><div class="weekday">Tue</div>
                <div class="weekday">Wed</div><div class="weekday">Thu</div><div class="weekday">Fri</div>
                <div class="weekday">Sat</div>

                <?php
                $first_day = date('w', strtotime(date('Y-m-01')));
                $days_in_month = date('t');
                $today_str = date('Y-m-d');

                for ($i = 0; $i < $first_day; $i++) {
                    echo '<div class="day" style="opacity:0;"></div>';
                }

                for ($d = 1; $d <= $days_in_month; $d++) {
                    $current_date = date('Y-m-') . sprintf('%02d', $d);
                    $is_today = ($current_date === $today_str);
                    $is_checked = in_array($current_date, $checked_dates);
                    
                    $classes = 'day';
                    if ($is_today) $classes .= ' today';
                    if ($is_checked) $classes .= ' checked';
                    
                    echo '<div class="' . $classes . '" id="date-' . $current_date . '">' . $d . '</div>';
                }
                ?>
            </div>

            <?php if (!in_array($today_str, $checked_dates)): ?>
                <button class="checkin-btn" id="checkinBtn" onclick="doCheckin()">CHECK IN TODAY</button>
            <?php else: ?>
                <button class="checkin-btn" disabled>ALREADY CHECKED IN ✓</button>
            <?php endif; ?>
        </div>
    </main>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Hi <?php echo $nickname; ?>, don't forget to record your progress!</div>
            <div class="ai-icon-circle">AI</div>
        </div>
    </aside>

    <!-- ====================== 自定义弹窗 ====================== -->
    <div id="customModal" class="custom-modal">
        <div class="modal-content">
            <div id="modalIcon" class="modal-icon"></div>
            <div id="modalTitle" class="modal-title"></div>
            <div id="modalText" class="modal-text"></div>
            <button class="modal-btn" onclick="hideModal()">Got it, thanks!</button>
        </div>
    </div>

    <script>
    function showCustomModal(icon, title, text) {
        document.getElementById('modalIcon').innerHTML = icon;
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalText').innerHTML = text;
        document.getElementById('customModal').style.display = 'flex';
    }

    function hideModal() {
        document.getElementById('customModal').style.display = 'none';
    }

    function doCheckin() {
        const btn = document.getElementById('checkinBtn');
        btn.disabled = true;
        btn.innerText = "Processing...";

        const params = new URLSearchParams();
        params.append('action', 'checkin');

        fetch('calendar.php', {
            method: 'POST',
            body: params
        })
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                showCustomModal(
                    '✅', 
                    'Great job!', 
                    'You have successfully checked in today!<br><strong>Keep growing your garden 🌱</strong>'
                );
                const todayCell = document.getElementById('date-' + res.date);
                if (todayCell) todayCell.classList.add('checked');
                btn.innerText = "ALREADY CHECKED IN ✓";
            } 
            else if (res.status === 'exists') {
                showCustomModal(
                    '🌱', 
                    'Already checked in', 
                    'You have already checked in today.<br>Come back tomorrow!'
                );
                btn.innerText = "ALREADY CHECKED IN ✓";
            } 
            else {
                showCustomModal(
                    '⚠️', 
                    'Oops...', 
                    'Something went wrong.<br>Please try again.'
                );
                btn.disabled = false;
                btn.innerText = "CHECK IN TODAY";
            }
        })
        .catch(err => {
            console.error(err);
            showCustomModal('⚠️', 'Oops...', 'Network error.<br>Please check your connection.');
            btn.disabled = false;
            btn.innerText = "CHECK IN TODAY";
        });
    }
    </script>
</body>
</html>