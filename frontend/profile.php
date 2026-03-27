<?php
session_start();

// 1. Strict Login Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Database Connection
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];
$success_msg = null;
$error_msg = null;

// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_profile') {
    $new_nickname = trim($_POST['nickname']);
    $new_email = trim($_POST['email']);
    
    if (empty($new_email)) {
        $error_msg = "Email address cannot be empty.";
    } else {
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_stmt->bind_param("si", $new_email, $user_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_msg = "This email address is already registered by another user.";
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET nickname = ?, email = ? WHERE user_id = ?");
            $update_stmt->bind_param("ssi", $new_nickname, $new_email, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['nickname'] = $new_nickname;
                $success_msg = "Your profile has been updated successfully!";
            } else {
                $error_msg = "Database error. Failed to update profile.";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

// ==========================================
$username = '';
$email = '';
$nickname = 'Student';
$joined_date = '';

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
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Word Garden</title>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            box-sizing: border-box;
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
            <a href="daily_talk.php">Daily Talk</a>
            <a href="reading.php">Reading</a>
            <a href="vocabulary.php">Vocabulary</a>
            <a href="calendar.php">Calendar</a>
            <a href="forum.php">Community</a>
            <a href="profile.php" class="active">Profile</a>
        </div>
    </nav>

    <header class="hero-mini">
        <h1>Personal Profile</h1>
    </header>
    
    <main class="main-content">
        <div class="card user-card">
            <div class="avatar"><?php echo mb_strtoupper(mb_substr($nickname, 0, 1)); ?></div>
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
            
            <button class="btn btn-primary" onclick="openEditModal()">Edit My Info</button>
            <a href="#" onclick="confirmLogout(event)" class="btn btn-outline">Log Out Account</a>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($success_msg): ?>
                Swal.fire({
                    title: 'Success!',
                    text: '<?php echo addslashes($success_msg); ?>',
                    icon: 'success',
                    confirmButtonColor: '#1b4332'
                });
            <?php endif; ?>

            <?php if ($error_msg): ?>
                Swal.fire({
                    title: 'Error',
                    text: '<?php echo addslashes($error_msg); ?>',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            <?php endif; ?>
        });

        function openEditModal() {
            Swal.fire({
                title: 'Edit Profile',
                html: `
                    <form id="editProfileForm" method="POST" action="profile.php" style="text-align: left;">
                        <input type="hidden" name="action" value="edit_profile">
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #1b4332; font-weight: bold; font-size: 14px;">Nickname</label>
                            <input type="text" name="nickname" class="swal2-input" style="margin: 0; width: 100%; box-sizing: border-box; height: 45px; font-size: 16px;" value="<?php echo htmlspecialchars($nickname, ENT_QUOTES); ?>">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; color: #1b4332; font-weight: bold; font-size: 14px;">Email Address *</label>
                            <input type="email" name="email" class="swal2-input" style="margin: 0; width: 100%; box-sizing: border-box; height: 45px; font-size: 16px;" value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>" required>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Save Changes',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#1b4332',
                focusConfirm: false,
                preConfirm: () => {
                    const form = document.getElementById('editProfileForm');
                    if (form.reportValidity()) {
                        form.submit();
                    } else {
                        return false; 
                    }
                }
            });
        }

        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of your account.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#1b4332',
                confirmButtonText: 'Yes, log out!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>
</body>
</html>