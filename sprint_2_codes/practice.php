<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
session_start();

// 1. Strict login validation
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Include database connection
require_once 'db_connect.php'; 

$user_id = $_SESSION['user_id'];
$ted_id = isset($_GET['ted_id']) ? (int)$_GET['ted_id'] : 0;

if ($ted_id <= 0) {
    die("Invalid TED ID");
}

// ==========================================
// A. Fetch user profile data (fully synced with home.php)
// ==========================================
$username = ''; $nickname = 'Student'; $db_avatar = '';
$stmt_u = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
if ($stmt_u) {
    $stmt_u->bind_param("i", $user_id);
    $stmt_u->execute();
    $user_data = $stmt_u->get_result()->fetch_assoc();
    if ($user_data) {
        $username = $user_data['username'];
        $nickname = !empty($user_data['nickname']) ? $user_data['nickname'] : $username;
        $db_avatar = $user_data['avatar_url'];
    }
    $stmt_u->close();
}

// Avatar rendering logic
$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . htmlspecialchars($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . htmlspecialchars($first_letter) . '</div>';
}

// ==========================================
// B. Fetch video info and gap-fill question data
// ==========================================
$stmt = $conn->prepare("SELECT title, video_url FROM ted_talks WHERE ted_id = ?");
$stmt->bind_param("i", $ted_id);
$stmt->execute();
$talk = $stmt->get_result()->fetch_assoc();

// Core change: use subquery (SELECT ... LIMIT 10) to force select only 10 questions
$stmt2 = $conn->prepare("
    SELECT t.gapfill_text_id, t.blanked_text_en, a.question_no, a.correct_answer
    FROM (
        SELECT gapfill_text_id, blanked_text_en 
        FROM ted_gapfill_texts 
        WHERE transcript_id = ? 
        ORDER BY gapfill_text_id ASC
        LIMIT 10
    ) t
    LEFT JOIN ted_blank_answers a ON t.gapfill_text_id = a.gapfill_text_id
    ORDER BY t.gapfill_text_id, a.question_no
");

$stmt2->bind_param("i", $ted_id);
$stmt2->execute();
$result = $stmt2->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['gapfill_text_id'];
    if (!isset($questions[$id])) {
        $questions[$id] = ['text' => $row['blanked_text_en'], 'answers' => []];
    }
    if (!empty($row['correct_answer'])) {
        $questions[$id]['answers'][] = $row['correct_answer'];
    }
}
// Core addition: enforce keeping only 10 questions here!
$keys = array_keys($questions); // get all question IDs

if (count($keys) > 10) {
    
    $selected_keys = array_slice($keys, 0, 10); // force slice to 10
    
    $limited_questions = [];
    foreach ($selected_keys as $key) {
        $limited_questions[$key] = $questions[$key];
    }
    $questions = $limited_questions; // overwrite original array, keep only 10 questions
}
// ==========================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice Lab - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
        h1, h2, h3, h4 { font-family: 'PT Serif', Georgia, serif; letter-spacing: 0.5px; }

        /* ===== Navigation Bar (Oxford Style aligned) ===== */
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

        /* Dropdown menu */
        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown-menu li { border-bottom: 1px solid rgba(255,255,255,0.05); }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; display: block; font-weight: 400; font-family: 'Open Sans', sans-serif; text-shadow: none; letter-spacing: normal; font-size: 14px; }
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }
        .navbar-links li:hover .dropdown-menu { display: block; }

        /* Top-right user avatar */
        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; border: 2px solid var(--oxford-gold); line-height: 1; box-sizing: border-box; }
        
        .navbar-right .dropdown-menu { background-color: var(--white); border-top: none; border-radius: 0 0 8px 8px; right: 0; left: auto; overflow: hidden; font-family: 'Playfair Display', serif; }
        .navbar-right:hover .dropdown-menu { display: block; }
        .navbar-right .dropdown-menu li a { color: var(--oxford-blue) !important; font-weight: 700; font-size: 15px; letter-spacing: 0.5px; }
        .navbar-right .dropdown-menu li a:hover { background-color: #f8fafc !important; color: var(--oxford-gold) !important; }

        /* ===== 2. Hero Section (Oxford-style redesign) ===== */
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

        /* ===== 3. Exercise Layout ===== */
        .main-content { max-width: 1400px; margin: -50px auto 60px; padding: 0 30px; display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; position: relative; z-index: 10; }
        
        .media-column { display: flex; flex-direction: column; gap: 20px; }
        .video-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-top: 4px solid var(--oxford-blue); }
        .video-wrapper { width: 100%; aspect-ratio: 16/9; background: #000; border-radius: 4px; overflow: hidden; }
        .video-wrapper iframe { width: 100%; height: 100%; border: none; }
        
        .video-info { text-align: center; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); border-bottom: 3px solid var(--oxford-gold); }
        .video-info h2 { font-family: 'Playfair Display', serif; color: var(--oxford-blue); margin: 0; font-weight: 800; }

        .exercise-column { background: white; border-radius: 8px; padding: 50px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); border-top: 4px solid var(--oxford-gold); }
        .exercise-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; border-bottom: 2px solid var(--oxford-blue); padding-bottom: 20px; }
        .exercise-header h3 { font-family: 'Playfair Display', serif; color: var(--oxford-blue); text-transform: uppercase; letter-spacing: 2px; margin: 0; font-weight: 800; }
        .back-link { font-family: 'Playfair Display', serif; text-decoration: none; color: var(--oxford-gold); font-weight: 800; font-size: 14px; text-transform: uppercase; transition: 0.3s; }
        .back-link:hover { color: var(--oxford-blue); }

        .transcript-content { font-size: 1.2rem; line-height: 2.4; color: #444; }
        .blank-input { border: none; border-bottom: 2px solid var(--border-color); background-color: #f8fafc; width: 140px; text-align: center; font-size: 1.1rem; font-weight: 700; color: var(--oxford-blue-light); outline: none; transition: 0.3s; margin: 0 8px; border-radius: 4px 4px 0 0; }
        .blank-input:focus { border-bottom-color: var(--oxford-gold); background: #fffdf5; }

        .check-btn { 
            background: var(--oxford-blue); color: white; border: none; padding: 20px 50px; border-radius: 4px; 
            font-family: 'Playfair Display', serif; font-weight: 800; font-size: 15px; text-transform: uppercase; 
            letter-spacing: 2px; margin-top: 45px; width: 100%; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,33,71,0.2);
        }
        .check-btn:hover { background: var(--oxford-blue-light); transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,33,71,0.3); }

        @media (max-width: 1200px) { .main-content { grid-template-columns: 1fr; margin-top: 20px; } }
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
                <li><a href="forum.php">Community</a></li>
            </ul>
        </div>

        <div class="navbar-right dropdown">
            <?php echo $avatar_html; ?>
            <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?php echo htmlspecialchars($nickname); ?> ▾</span>
            <ul class="dropdown-menu" style="right:0; left:auto; margin-top:0; min-width:220px;">
                <li style="padding: 20px; background: #f8fafc; cursor:default;">
                    <div style="color:var(--text-light); font-size:12px; margin-bottom:5px; font-family:'Playfair Display', serif; font-style:italic;">Signed in as</div>
                    <div style="color:var(--oxford-blue); font-weight:800; font-size:16px; font-family:'Playfair Display', serif; text-transform:uppercase;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important; font-weight: 600;">Sign Out</a></li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <h1>Practice Lab</h1>
        <p>Refining Perception • Mastering Articulation</p>
    </header>

    <main class="main-content">
        <div class="media-column">
            <div class="video-card">
                <div class="video-wrapper">
                    <?php
                    $video_src = htmlspecialchars($talk['video_url']);
                    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $video_src, $m) ||
                        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $video_src, $m)) {
                        $video_src = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
                    }
                    ?>
                    <iframe src="<?= $video_src ?>" allowfullscreen></iframe>
                </div>
            </div>
            <div class="video-info">
                <h2><?= htmlspecialchars($talk['title']) ?></h2>
                <p style="margin-top:10px; color:var(--text-light); font-family:'Playfair Display', serif; font-style:italic; font-size: 1.1rem;">Academic Listening Comprehension</p>
            </div>
        </div>

        <div class="exercise-column">
            <div class="exercise-header">
                <h3>Fill in the Blanks</h3>
                <a href="TED.php" class="back-link">« Return to Library</a>
            </div>
                <div class="transcript-content">
    <?php
    $blankLimit = 10;
    $blankCount = 0;
    $stopRendering = false;
    ?>

    <?php foreach ($questions as $q): ?>
        <?php
        $parts = preg_split('/_{3,}/', $q['text']);
        $answers = $q['answers'];
        ?>
        <p>
            <?php for ($i = 0; $i < count($parts); $i++): ?>

                <?php
                // If already reached 10 blanks, stop rendering current and later content
                if ($stopRendering) {
                    break;
                }

                echo htmlspecialchars($parts[$i]);

                if ($i < count($parts) - 1 && isset($answers[$i])) {
                    if ($blankCount < $blankLimit) {
                        ?>
                        <input type="text"
                               class="blank-input"
                               data-answer="<?= htmlspecialchars($answers[$i]) ?>"
                               placeholder="..." />
                        <?php
                        $blankCount++;

                        // After rendering the 10th blank, stop immediately and do not display following text
                        if ($blankCount >= $blankLimit) {
                            $stopRendering = true;
                            break;
                        }
                    }
                }
                ?>

            <?php endfor; ?>
        </p>

        <?php if ($stopRendering) break; ?>
    <?php endforeach; ?>
</div>
            </div>

            <button class="check-btn" onclick="checkAnswers()">Evaluate Performance</button>
        </div>
    </main>

    <script>
    function checkAnswers() {
        const inputs = document.querySelectorAll('.blank-input');
        let correctCount = 0;
        let wrongDetails = [];

        inputs.forEach((input, index) => {
            const correct = input.dataset.answer.trim().toLowerCase();
            const user = input.value.trim().toLowerCase();

            if (user === correct && user !== "") {
                input.style.borderBottom = "3px solid #2d6a4f";
                input.style.background = "#f0f7f4";
                correctCount++;
            } else {
                input.style.borderBottom = "3px solid #c0392b";
                input.style.background = "#fff5f5";
                wrongDetails.push(`
                    <div style="text-align:left; margin-bottom:10px; font-family:'Open Sans', sans-serif; font-size: 0.95rem;">
                        <b style="color:var(--oxford-blue)">Blank ${index + 1}:</b> 
                        <span style="color:#c0392b; text-decoration: line-through;">${user || "(empty)"}</span> → 
                        <span style="color:#2d6a4f; font-weight:800; background: #e8f5e9; padding: 2px 6px; border-radius: 3px;">${correct}</span>
                    </div>
                `);
            }
        });

        const accuracy = correctCount / inputs.length;
        Swal.fire({
            title: `<span style="font-family:'Playfair Display'; font-weight:800; color:var(--oxford-blue);">${accuracy >= 0.8 ? 'Excellent Work!' : 'Keep Practicing!'}</span>`,
            html: `
                <div style="font-size:1.2rem; margin-bottom:20px; font-family:'Open Sans';">Score: <b style="color:var(--oxford-gold); font-size: 1.5rem;">${correctCount} / ${inputs.length}</b></div>
                <div style="max-height:250px; overflow-y:auto; border-top:2px solid #f0f0f0; padding-top:20px; margin-top: 10px;">
                    ${wrongDetails.length ? wrongDetails.join('') : '<b style="color:#2d6a4f; font-size: 1.1rem;">Perfect Score! Academic Distinction. 🎉</b>'}
                </div>
            `,
            icon: accuracy >= 0.8 ? 'success' : (accuracy >= 0.5 ? 'warning' : 'error'),
            confirmButtonText: 'Continue',
            confirmButtonColor: '#002147',
            showCancelButton: true,
            cancelButtonText: 'Try Again',
            cancelButtonColor: '#c4a661'
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                inputs.forEach(i => {
                    i.value = ""; i.style.borderBottom = "2px solid var(--border-color)"; i.style.background = "#f8fafc";
                });
            }
        });
    }
    </script>
</body>
</html>
