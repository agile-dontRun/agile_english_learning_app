<?php
// 1. 严格登录验证
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. 数据库连接
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];
$success_msg = null;
$error_msg = null;

// ==========================================
// 3. 处理 POST 请求 (修改资料 & 上传头像)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. 处理基本信息修改 (仅限昵称和邮箱)
    if (isset($_POST['action']) && $_POST['action'] === 'edit_profile') {
        $new_nickname = trim($_POST['nickname']);
        $new_email = trim($_POST['email']);
        
        if (!empty($new_email)) {
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check_stmt->bind_param("si", $new_email, $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error_msg = "Email already in use.";
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET nickname = ?, email = ? WHERE user_id = ?");
                $update_stmt->bind_param("ssi", $new_nickname, $new_email, $user_id);
                if ($update_stmt->execute()) {
                    $_SESSION['nickname'] = $new_nickname;
                    $success_msg = "Profile updated!";
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
    }
    
    // B. 处理头像上传
    if (isset($_POST['action']) && $_POST['action'] === 'upload_avatar' && isset($_FILES['avatar_file'])) {
        $file = $_FILES['avatar_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $dest_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                $avatar_stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE user_id = ?");
                $avatar_stmt->bind_param("si", $dest_path, $user_id);
                $avatar_stmt->execute();
                $avatar_stmt->close();
                $success_msg = "Avatar updated!";
            }
        }
    }
}

// ==========================================
// 4. 获取用户核心数据 (保持原始逻辑)
// ==========================================
$stmt = $conn->prepare("SELECT username, email, nickname, avatar_url, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data_db = $stmt->get_result()->fetch_assoc();
$username = $user_data_db['username'];
$email = $user_data_db['email'];
$nickname = !empty($user_data_db['nickname']) ? $user_data_db['nickname'] : $username;
$joined_date = date('M d, Y', strtotime($user_data_db['created_at'])); 
$avatar_url = !empty($user_data_db['avatar_url']) ? $user_data_db['avatar_url'] : 'college_logo.png';
$stmt->close();

// ==========================================
// 5. 获取口语历史统计
// ==========================================
$total_sessions = 0; $avg_score = 0; $history = [];
$stat_stmt = $conn->prepare("SELECT COUNT(*) as cnt, AVG(overall_score) as avg FROM user_speaking_attempts WHERE user_id = ? AND overall_score > 0");
$stat_stmt->bind_param("i", $user_id);
$stat_stmt->execute();
$stats = $stat_stmt->get_result()->fetch_assoc();
$total_sessions = $stats['cnt'];
$avg_score = round($stats['avg'], 1);
$stat_stmt->close();

$hist_stmt = $conn->prepare("SELECT overall_score, fluency_score, pronunciation_score, created_at FROM user_speaking_attempts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$hist_stmt->bind_param("i", $user_id);
$hist_stmt->execute();
$res = $hist_stmt->get_result();
while($row = $res->fetch_assoc()) $history[] = $row;
$hist_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Profile - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
       
        
        /* ===== 2. Hero 区域 (牛津风格重制) ===== */
        .hero {
            background: url('hero_bg2.png') center/cover no-repeat; 
            color: var(--white); text-align: center; padding: 140px 20px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        }
        .hero h1 { 
            font-family: 'Playfair Display', serif; font-size: 5rem; font-weight: 800; 
            margin: 0 0 20px; text-transform: uppercase; letter-spacing: 5px; 
            text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.8);
        }
        .hero p { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 400; font-style: italic; max-width: 800px; margin: 0 auto; text-shadow: 1px 2px 5px rgba(0, 0, 0, 0.8); }

        /* ===== 3. 主体内容布局 ===== */
        .main-content { max-width: 1100px; margin: -60px auto 60px; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 30px; position: relative; z-index: 10; }
        .card { background: white; border-radius: 8px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-top: 4px solid var(--oxford-gold); }

        .avatar-circle { width: 120px; height: 120px; margin: 0 auto 20px; border-radius: 50%; border: 3px solid var(--oxford-gold); overflow: hidden; position: relative; cursor: pointer; background: #eee; }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-overlay { position: absolute; top: 0; background: rgba(0,33,71,0.7); width: 100%; height: 100%; color: white; display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .avatar-circle:hover .avatar-overlay { opacity: 1; }

        .info-box { background: #f9f9f9; border-radius: 4px; padding: 10px 20px; margin: 25px 0; border: 1px solid var(--border-color); text-align: left; }
        .info-item { padding: 15px 0; border-bottom: 1px solid var(--border-color); }
        .info-item:last-child { border-bottom: none; }
        .label { font-size: 11px; color: #999; text-transform: uppercase; display: block; letter-spacing: 1px; margin-bottom: 5px; font-weight: bold; }
        .val { font-weight: 700; color: var(--oxford-blue); font-size: 14px; }

        .btn { display: block; width: 100%; padding: 14px; border-radius: 4px; font-weight: 800; cursor: pointer; border: none; text-transform: uppercase; transition: 0.3s; font-size: 13px; text-decoration: none; text-align: center; margin-bottom: 12px; font-family: 'Playfair Display', serif; letter-spacing: 1px; }
        .btn-gold { background: var(--oxford-gold); color: var(--oxford-blue); box-shadow: 0 4px 15px rgba(196,166,97,0.2); }
        .btn-gold:hover { background: var(--oxford-gold-light); transform: translateY(-2px); }
        .btn-logout { background: transparent; border: 2px solid var(--border-color); color: var(--text-light); }
        .btn-logout:hover { color: #dc3545; border-color: #dc3545; background: #fff5f5; }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 35px; }
        .stat-card { background: #f4f8fb; padding: 25px; border-radius: 4px; text-align: center; border: 1px solid #e1eaf0; border-bottom: 3px solid var(--oxford-blue); }
        .stat-card h3 { margin: 0; font-size: 2.2rem; color: var(--oxford-blue); font-family: 'Playfair Display', serif; font-weight: 800; }
        .stat-card p { font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; margin-top: 5px; }
        
        .history-item { display: flex; justify-content: space-between; padding: 20px; border: 1px solid var(--border-color); border-radius: 4px; margin-bottom: 12px; align-items: center; transition: 0.3s; }
        .history-item:hover { border-color: var(--oxford-blue); background: #fcfdfe; }
        .history-info strong { font-family: 'Playfair Display', serif; color: var(--oxford-blue); font-size: 16px; }
        .score-box { display: flex; gap: 10px; }
        .badge { background: #f0f4f7; padding: 6px 14px; border-radius: 4px; font-size: 12px; font-weight: 700; color: var(--oxford-blue); }
        .badge-gold { background: #fff9db; border: 1px solid var(--oxford-gold); color: #856404; }

        /* 统一标题样式 */
        .section-title { font-family: 'Playfair Display', serif; font-weight: 800; color: var(--oxford-blue); border-bottom: 2px solid var(--oxford-gold); padding-bottom: 15px; margin-bottom: 25px; display: inline-block; }
    </style>
</head>
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
                </ul>
            </li>
            <li class="dropdown">
                <a href="#">Games ▾</a>
                <ul class="dropdown-menu">
                    <li><a href="galgame/galgame/index.html">Story game</a></li>
                </ul>
            </li>
            <li><a href="forum.php">Community</a></li>
        </ul>
    </div>

    <div class="navbar-right dropdown">
        <?php echo $avatar_html = (!empty($user_data_db['avatar_url'])) ? '<img src="'.htmlspecialchars($avatar_url).'" class="user-avatar-img">' : '<div class="user-avatar-placeholder">'.strtoupper(substr($username,0,1)).'</div>'; ?>
        <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?php echo htmlspecialchars($nickname); ?> ▾</span>
        <ul class="dropdown-menu" style="right:0; left:auto; margin-top:0; min-width:220px; display: none;">
            <li style="padding: 20px; background: #f8fafc; cursor:default;">
                <div style="color:var(--text-light); font-size:12px; margin-bottom:5px; font-family:'Playfair Display', serif; font-style:italic;">Signed in as</div>
                <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px; text-transform:uppercase; font-family:'Playfair Display', serif;"><?php echo htmlspecialchars($nickname); ?></div>
            </li>
            <li><a href="profile.php">📄 My Profile</a></li>
            <li><a href="#" onclick="confirmLogout()">🚪 Sign Out</a></li>
        </ul>
    </div>
</nav>

<header class="hero">
    <h1>Scholar Profile</h1>
    <p>Honoring academic achievement and personal growth.</p>
</header>

<main class="main-content">
    <div class="card" style="text-align: center;">
        <div class="avatar-circle" onclick="document.getElementById('avatarIn').click()">
            <img src="<?php echo $avatar_url; ?>" onerror="this.src='college_logo.png'">
            <div class="avatar-overlay">Upload Photo</div>
        </div>
        <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display:none;">
            <input type="hidden" name="action" value="upload_avatar">
            <input type="file" id="avatarIn" name="avatar_file" onchange="this.form.submit()">
        </form>
        
        <h2 style="font-family:'Playfair Display'; color: var(--oxford-blue); font-weight:800; margin-bottom:5px;"><?php echo $nickname; ?></h2>
        <span style="font-size:12px; color:var(--oxford-gold); font-weight:800; text-transform:uppercase; letter-spacing:1px;">Active Scholar</span>

        <div class="info-box">
            <div class="info-item"><span class="label">Username (Lock ID)</span><span class="val" style="color:#bbb">🔒 <?php echo $username; ?></span></div>
            <div class="info-item"><span class="label">Primary Email</span><span class="val"><?php echo $email; ?></span></div>
            <div class="info-item"><span class="label">Enrolled Since</span><span class="val"><?php echo $joined_date; ?></span></div>
        </div>
        
        <button class="btn btn-gold" onclick="editProfile()">Update Information</button>
        <button class="btn btn-logout" onclick="confirmLogout()">Terminate Session</button>
    </div>

    <div class="card">
        <h2 class="section-title">Speaking Progress</h2>
        
        <div class="stats-grid">
            <div class="stat-card"><h3><?php echo $total_sessions; ?></h3><p>Sessions</p></div>
            <div class="stat-card"><h3><?php echo $total_sessions > 0 ? $avg_score : '-'; ?></h3><p>Avg Score</p></div>
        </div>

        <h3 style="font-family:'Playfair Display'; color: var(--oxford-blue); font-size: 1.3rem; font-weight:800; margin-bottom:20px;">Recent Academic Activity</h3>
        
        <?php if(empty($history)): ?>
            <p style="color:#999; text-align:center; padding: 40px; border: 1px dashed var(--border-color); border-radius:4px;">No records yet. Begin your discovery at <a href="listening.php" style="color:var(--oxford-gold); font-weight:bold; text-decoration:none;">Listening Center</a>!</p>
        <?php else: foreach($history as $h): ?>
            <div class="history-item">
                <div class="history-info">
                    <strong>AI Speaking Session</strong><br>
                    <span style="color:#999; font-size:11px; font-weight:bold;"><?php echo date('M d, H:i', strtotime($h['created_at'])); ?></span>
                </div>
                <div class="score-box">
                    <div class="badge">F: <?php echo $h['fluency_score']; ?></div>
                    <div class="badge">P: <?php echo $h['pronunciation_score']; ?></div>
                    <div class="badge badge-gold">SCORE: <?php echo $h['overall_score']; ?></div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</main>

<script>
    // 统一处理下拉菜单
    document.querySelectorAll('.dropdown').forEach(drop => {
        drop.addEventListener('mouseenter', () => {
            drop.querySelector('.dropdown-menu').style.display = 'block';
        });
        drop.addEventListener('mouseleave', () => {
            drop.querySelector('.dropdown-menu').style.display = 'none';
        });
    });

    function editProfile() {
        Swal.fire({
            title: '<span style="font-family:Playfair Display">Update Information</span>',
            html: `<form id="editF" method="POST" style="text-align:left; padding:10px;">
                <input type="hidden" name="action" value="edit_profile">
                <label style="font-size:11px; color:#999; font-weight:bold; text-transform:uppercase;">Scholar Nickname</label>
                <input name="nickname" class="swal2-input" value="<?php echo $nickname; ?>" style="margin-top:5px; margin-bottom:20px;">
                <label style="font-size:11px; color:#999; font-weight:bold; text-transform:uppercase;">Contact Email</label>
                <input name="email" type="email" class="swal2-input" value="<?php echo $email; ?>" style="margin-top:5px;">
            </form>`,
            confirmButtonColor: '#002147', confirmButtonText: 'Save Changes',
            showCancelButton: true, cancelButtonText: 'Cancel',
            preConfirm: () => document.getElementById('editF').submit()
        });
    }

    function confirmLogout() {
        Swal.fire({ 
            title: '<span style="font-family:Playfair Display">Terminate Session?</span>', 
            text: "You will be required to re-authenticate.", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#dc3545', 
            cancelButtonColor: '#002147', 
            confirmButtonText: 'Logout',
            cancelButtonText: 'Stay Enrolled'
        }).then(r => { if(r.isConfirmed) window.location.href='logout.php'; });
    }
</script>
</body>
</html>