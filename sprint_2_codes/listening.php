<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];


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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listening Center - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@700&display=swap" rel="stylesheet">
    
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

        body { margin: 0; padding: 0; font-family: 'Open Sans', Arial, sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        h1, h2, h3 { font-family: 'PT Serif', Georgia, serif; letter-spacing: 0.5px; }

      
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }
        .college-logo:hover { transform: scale(1.02); }

        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
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
        .navbar-links a:hover { color: var(--oxford-gold); background-color: rgba(255, 255, 255, 0.05); }

        
        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none !important; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown-menu li { list-style: none !important; margin: 0; padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; justify-content: flex-start; width: 100%; box-sizing: border-box; text-decoration: none !important; display: block; font-weight: 400; height: auto; text-shadow: none; letter-spacing: 0.5px;}
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }
        .navbar-links li:hover .dropdown-menu, .dropdown:hover .dropdown-menu { display: block; }

      
        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; border: 2px solid var(--oxford-gold); line-height: 1; box-sizing: border-box; }
        .navbar-right .dropdown-menu { background-color: var(--white); border-top: none; border-radius: 0 0 8px 8px; overflow: hidden; font-family: 'Playfair Display', serif; }
        .navbar-right .dropdown-menu li div[style*="font-size:12px"] { font-family: 'Playfair Display', serif !important; font-style: italic; letter-spacing: 0.5px; color: #888 !important; }
        .navbar-right .dropdown-menu li div[style*="font-size:16px"] { font-family: 'Playfair Display', serif !important; font-weight: 800; color: var(--oxford-blue) !important; letter-spacing: 1px; text-transform: uppercase; }
        .navbar-right .dropdown-menu li a { font-family: 'Playfair Display', serif !important; font-weight: 700; font-size: 15px; color: var(--oxford-blue) !important; letter-spacing: 0.5px; transition: all 0.2s ease; }
        .navbar-right .dropdown-menu li a:hover { background-color: #f8fafc !important; color: var(--oxford-gold) !important; padding-left: 25px; }

        
        .hero {
            background: url('hero_bg2.png') center/cover no-repeat; 
            color: var(--white); 
            text-align: center; 
            padding: 140px 20px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        }
        .hero h1 { 
            font-family: 'Playfair Display', serif;
            font-size: 5rem; 
            font-weight: 800; 
            margin: 0 0 20px; 
            text-transform: uppercase; 
            letter-spacing: 5px; 
            text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.8);
        }
        .hero p {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 400;
            font-style: italic; 
            max-width: 800px;
            margin: 0 auto;
            text-shadow: 1px 2px 5px rgba(0, 0, 0, 0.8);
        }

        
        .listening-container {
            max-width: 1200px; margin: -50px auto 60px; padding: 0 20px;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;
            position: relative; z-index: 10;
        }

        .option-card {
            background: var(--white); border-radius: 8px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease; display: flex; flex-direction: column;
            text-decoration: none; color: inherit; border-top: 4px solid var(--oxford-gold);
        }

        .option-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            border-top-color: var(--oxford-blue);
        }

        .card-image {
            height: 200px; background-color: #f8fafc;
            display: flex; align-items: center; justify-content: center;
            font-size: 60px; color: var(--oxford-blue);
            border-bottom: 1px solid var(--border-color);
        }

        .card-content { padding: 35px; text-align: center; }
        .card-content h3 { 
            font-family: 'Playfair Display', serif; font-size: 1.8rem; 
            color: var(--oxford-blue); margin: 0 0 15px; font-weight: 800;
        }
        .card-content p { color: var(--text-light); line-height: 1.7; font-size: 15px; margin-bottom: 25px; }
        
        .btn-enter {
            display: inline-block; padding: 12px 30px; border: 2px solid var(--oxford-blue);
            color: var(--oxford-blue); font-weight: 700; text-transform: uppercase;
            font-size: 13px; letter-spacing: 1px; transition: 0.3s; border-radius: 4px;
        }
        .option-card:hover .btn-enter {
            background-color: var(--oxford-blue); color: var(--white);
        }

        footer { text-align: center; padding: 60px 40px; color: #888; font-size: 14px; font-family: 'Playfair Display', serif; font-style: italic; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="home.php"><img src="college_logo.png" alt="Spires Academy Logo" class="college-logo"></a>
            <ul class="navbar-links">
                <li><a href="home.php">Home</a></li>
                <li class="dropdown">
                    <a href="#" style="color:var(--oxford-gold);">Study ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="listening.php" style="color:var(--oxford-gold)!important; font-weight: bold;">Listening</a></li>
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
        <h1>Listening Center</h1>
        <p>Master the art of comprehension through curated audio resources.</p>
    </header>

    <main class="listening-container">
        
        <a href="ielts.php" class="option-card">
            <div class="card-image">🎓</div> <div class="card-content">
                <h3>Listening Practice</h3>
                <p>Simulate real exam conditions with Part 1-4 practice tests and academic lectures.</p>
                <div class="btn-enter">Enter Lab</div>
            </div>
        </a>

        <a href="TED.php" class="option-card">
            <div class="card-image">💡</div> <div class="card-content">
                <h3>TED Talks</h3>
                <p>Broaden your horizons with ideas that matter. Great for advanced vocabulary and note-taking.</p>
                <div class="btn-enter">Explore Ideas</div>
            </div>
        </a>

        <a href="daily_talk.php" class="option-card">
            <div class="card-image">💬</div> <div class="card-content">
                <h3>Daily Talk</h3>
                <p>Improve your listening for everyday situations, from casual coffee chats to travel scenarios.</p>
                <div class="btn-enter">Join Conversation</div>
            </div>
        </a>

    </main>

    <footer>
        &copy; 2026 Spires Academy. All rights reserved. <br>
        <span style="font-size: 12px; opacity: 0.6;">Cultivating Eloquence and Academic Excellence.</span>
    </footer>

</body>
</html>