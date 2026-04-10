<?php
session_start();

// 1. Strict Login Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Database Connection (Using your common db_connect.php)
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];
$username = '';
$email = '';
$nickname = 'Student';
$joined_date = '';

// Fetch User Data
$stmt = $conn->prepare("SELECT username, email, nickname, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $username = $user['username'];
    $email = $user['email'];
    $nickname = !empty($user['nickname']) ? $user['nickname'] : $user['username'];
    $joined_date = date('M d, Y', strtotime($user['created_at'])); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Word Garden</title>
    <style>
        /* ===== Premium Green Theme System ===== */
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --soft-green-bg: #f2f7f5;
            --card-shadow: 0 10px 30px rgba(27, 67, 50, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(27, 67, 50, 0.15);
            --text-main: #2d3436;
            --text-light: #6d7d76;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--soft-green-bg);
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== 1. Navigation Header ===== */
        .nav-header {
            width: 100%;
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 50px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            z-index: 1000;
            box-sizing: border-box;
        }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a {
            text-decoration: none;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            padding: 5px 12px;
            border-radius: 8px;
            transition: 0.3s;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }

        /* ===== 2. Hero Banner ===== */
        .hero-mini {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: white;
            padding: 110px 20px 70px;
            text-align: center;
        }
        .hero-mini h1 { margin: 0; font-size: 2.4rem; letter-spacing: 1px; }

        /* ===== 3. Main Container ===== */
        .main-content {
            max-width: 1100px;
            margin: -50px auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            position: relative;
            z-index: 10;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            transition: 0.4s ease;
        }
        .card:hover { box-shadow: var(--card-shadow-hover); }

        /* Profile Sidebar */
        .user-card { text-align: center; }
        .avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--accent-green), var(--primary-green));
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 40px;
            font-weight: bold;
            box-shadow: 0 10px 20px rgba(27, 67, 50, 0.2);
        }

        .info-list {
            margin: 30px 0;
            background: #fcfdfd;
            border-radius: 15px;
            padding: 10px 20px;
            border: 1px solid #eef5f2;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .info-item:last-child { border-bottom: none; }
        .info-label { color: var(--text-light); }
        .info-value { font-weight: 600; color: var(--primary-green); }

        /* Buttons */
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-align: center;
            border: none;
            margin-bottom: 15px;
            font-size: 14px;
            text-decoration: none;
        }
        .btn-primary { background: var(--primary-green); color: white; }
        .btn-primary:hover { background: #081c15; transform: translateY(-2px); }
        .btn-outline { background: transparent; border: 1px solid #eef5f2; color: var(--accent-green); }
        .btn-outline:hover { background: #f0f7f4; border-color: var(--accent-green); }

        /* History Content */
        .history-header {
            color: var(--primary-green);
            font-size: 1.4rem;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--soft-green-bg);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .coming-soon {
            background: #fafcfb;
            border: 2px dashed #d1e5db;
            border-radius: 20px;
            padding: 80px 40px;
            text-align: center;
        }
        .coming-soon h4 { color: var(--accent-green); font-size: 1.2rem; margin: 0 0 10px; }
        .coming-soon p { color: var(--text-light); font-size: 0.95rem; margin: 0; }

        @media (max-width: 850px) {
            .main-content { grid-template-columns: 1fr; }
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
            <a href="daily_decryption.php">Daily Talk</a>
            <a href="vocabulary.php">Vocabulary</a>
            <a href="calendar.php">Calendar</a>
            <a href="profile.php" class="active">Profile</a>
        </div>
    </nav>

    <header class="hero-mini">
        <h1>Personal Profile</h1>
    </header>
    
    <main class="main-content">
        <div class="card user-card">
            <div class="avatar"><?php echo strtoupper(substr($nickname, 0, 1)); ?></div>
            <h2 style="color: var(--primary-green); margin: 0;"><?php echo htmlspecialchars($nickname); ?></h2>
            
            <div class="info-list">
                <div class="info-item">
                    <span class="info-label">Username</span>
                    <span class="info-value"><?php echo htmlspecialchars($username); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($email); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Member Since</span>
                    <span class="info-value"><?php echo htmlspecialchars($joined_date); ?></span>
                </div>
            </div>
            
            <button class="btn btn-primary" onclick="alert('Feature coming soon: Edit Profile')">Edit My Info</button>
            <a href="logout.php" class="btn btn-outline">Log Out Account</a>
        </div>

        <div class="card">
            <div class="history-header">📖 Learning History</div>
            
            <div class="coming-soon">
                <div style="font-size: 50px; margin-bottom: 20px;">🌱</div>
                <h4>Your journey is currently growing</h4>
                <p>Detailed tracking of your practice scores and vocabulary progress will be available in the next update. Stay tuned!</p>
            </div>
        </div>
    </main>

</body>
</html>