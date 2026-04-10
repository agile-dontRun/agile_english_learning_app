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

// ==========================================
// Get user profile (for navbar avatar)
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Integrity - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@400;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --oxford-blue: #002147; 
            --oxford-blue-light: #003066;
            --oxford-gold: #c4a661; 
            --oxford-gold-light: #d4b671;
            --alert-red: #8B0000; 
            --alert-red-light: #B22222;
            --white: #ffffff; 
            --bg-light: #f4f7f6;
            --text-dark: #333333; 
            --text-light: #666666;
            --border-color: #e0e0e0;
        }

        body { margin: 0; padding: 0; font-family: 'Open Sans', Arial, sans-serif; background-color: var(--bg-light); color: var(--text-dark); overflow-x: hidden; }
        h1, h2, h3, h4 { font-family: 'Playfair Display', Georgia, serif; letter-spacing: 0.5px; }

        /* ===== Navbar (Fully adopting Spires Academy standard) ===== */
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }
        .college-logo:hover { transform: scale(1.02); }

        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
        .navbar-links a { color: #ffffff; text-decoration: none; font-family: 'Playfair Display', serif; font-size: 16px; font-weight: 800; padding: 0 20px; height: 100%; display: flex; align-items: center; text-transform: uppercase; letter-spacing: 1.8px; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6); transition: all 0.3s ease; -webkit-font-smoothing: antialiased; }
        .navbar-links a:hover { color: var(--oxford-gold); background-color: rgba(255, 255, 255, 0.05); }

        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none !important; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown-menu li { list-style: none !important; margin: 0; padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; display: block; font-weight: 400; font-family: 'Open Sans', sans-serif; text-shadow: none; letter-spacing: normal; font-size: 14px; }
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }
        .navbar-links li:hover .dropdown-menu, .dropdown:hover .dropdown-menu { display: block; }

        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img, .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; line-height: 1; box-sizing: border-box; }
        .navbar-right .dropdown-menu { background-color: var(--white); border-top: none; border-radius: 0 0 8px 8px; right: 0; left: auto; font-family: 'Playfair Display', serif; }
        .navbar-right:hover .dropdown-menu { display: block; }
        .navbar-right .dropdown-menu li a { color: var(--oxford-blue) !important; font-weight: 700; font-size: 15px; letter-spacing: 0.5px; }
        .navbar-right .dropdown-menu li a:hover { background-color: #f8fafc !important; color: var(--oxford-gold) !important; }

        /* ===== Hero Section ===== */
        .hero {
            background: linear-gradient(rgba(0, 33, 71, 0.7), rgba(0, 33, 71, 0.8)), url('hero_bg2.png') center/cover no-repeat; 
            color: var(--white); text-align: center; padding: 120px 20px 100px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
            border-bottom: 5px solid var(--oxford-gold);
        }
        .hero h1 { font-size: 4.5rem; font-weight: 800; margin: 0 0 15px; text-transform: uppercase; letter-spacing: 5px; }
        .hero p { font-family: 'Lora', serif; font-size: 1.4rem; font-weight: 400; font-style: italic; max-width: 800px; margin: 0 auto; color: var(--oxford-gold-light); }

        /* ===== Main Content Area ===== */
        .content-container { max-width: 1200px; margin: -40px auto 60px; padding: 0 20px; position: relative; z-index: 10; }

        /* Intro Card */
        .intro-card {
            background: var(--white); padding: 50px; border-radius: 8px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08); text-align: center; margin-bottom: 60px;
        }
        .intro-card h2 { color: var(--oxford-blue); font-size: 2.2rem; margin-top: 0; }
        .intro-card p { font-family: 'Lora', serif; font-size: 1.2rem; line-height: 1.8; color: var(--text-dark); max-width: 900px; margin: 0 auto; }
        .highlight-quote { font-size: 1.4rem; color: var(--oxford-gold); font-weight: bold; margin-top: 20px; display: block; font-family: 'Playfair Display', serif; }

        /* Plagiarism Types Grid */
        .section-title { text-align: center; color: var(--oxford-blue); font-size: 2.5rem; margin-bottom: 40px; position: relative; }
        .section-title::after { content: ''; display: block; width: 80px; height: 3px; background: var(--oxford-gold); margin: 15px auto 0; }

        .type-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 60px; }
        .type-card {
            background: var(--white); padding: 40px 30px; border-radius: 8px;
            border-top: 4px solid var(--oxford-blue); box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: 0.3s;
        }
        .type-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); border-top-color: var(--oxford-gold); }
        .type-icon { font-size: 40px; margin-bottom: 15px; }
        .type-card h3 { color: var(--oxford-blue); font-size: 1.4rem; margin: 0 0 15px; }
        .type-card p { font-size: 15px; color: var(--text-light); line-height: 1.6; margin: 0; }

        /* ===== Severe Consequences Section (Red Card Warning) ===== */
        .consequences-section {
            background: var(--alert-red); color: var(--white); padding: 60px;
            border-radius: 8px; box-shadow: 0 20px 50px rgba(139, 0, 0, 0.2);
            margin-bottom: 60px; border-left: 10px solid var(--oxford-gold);
        }
        .consequences-section h2 { color: var(--oxford-gold-light); font-size: 2.5rem; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 15px; }
        .consequences-list { list-style: none; padding: 0; margin: 30px 0 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .consequences-list li {
            background: rgba(0,0,0,0.2); padding: 25px; border-radius: 8px;
            font-family: 'Open Sans', sans-serif; font-size: 1.1rem; line-height: 1.6;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .consequences-list li strong { color: var(--oxford-gold-light); font-size: 1.2rem; display: block; margin-bottom: 8px; font-family: 'Playfair Display', serif; letter-spacing: 1px; }

        /* Prevention Guide Section */
        .prevention-section { background: var(--white); padding: 60px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 40px; }
        .prevention-section h2 { color: var(--oxford-blue); font-size: 2.2rem; margin-top: 0; text-align: center; margin-bottom: 40px; }
        .tips-container { display: flex; flex-direction: column; gap: 20px; }
        .tip-row { display: flex; gap: 20px; align-items: flex-start; padding-bottom: 20px; border-bottom: 1px dashed var(--border-color); }
        .tip-row:last-child { border-bottom: none; padding-bottom: 0; }
        .tip-number { background: var(--oxford-blue); color: var(--white); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 1.2rem; font-family: 'Playfair Display', serif; flex-shrink: 0; }
        .tip-content h4 { margin: 0 0 5px; color: var(--oxford-blue); font-size: 1.3rem; }
        .tip-content p { margin: 0; color: var(--text-light); line-height: 1.6; }

        /* Pledge Button */
        .pledge-area { text-align: center; margin-top: 50px; padding-top: 40px; border-top: 2px solid var(--border-color); }
        .btn-pledge { background: var(--oxford-blue); color: var(--white); border: none; padding: 18px 50px; font-size: 16px; font-weight: 800; font-family: 'Playfair Display', serif; text-transform: uppercase; letter-spacing: 2px; border-radius: 4px; cursor: pointer; transition: 0.3s; box-shadow: 0 10px 20px rgba(0,33,71,0.2); }
        .btn-pledge:hover { background: var(--oxford-gold); color: var(--oxford-blue); transform: translateY(-3px); }

        /* ===== Footer (Replicating home.php) ===== */
        .footer { background-color: var(--oxford-blue); color: var(--white); padding: 60px 40px 30px; margin-top: 60px; border-top: 5px solid var(--oxford-gold); }
        .footer-content { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 40px; margin-bottom: 30px; }
        .footer-about h3 { color: var(--oxford-gold); font-size: 1.8rem; margin-top: 0; }
        .footer-about p { font-family: 'Lora', serif; font-size: 15px; line-height: 1.8; opacity: 0.8; }
        .footer-links h4 { color: var(--white); font-size: 1.2rem; margin-top: 0; letter-spacing: 1px; text-transform: uppercase; }
        .footer-links ul { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .footer-links a { color: #aaa; text-decoration: none; transition: 0.3s; font-size: 15px; }
        .footer-links a:hover { color: var(--oxford-gold); }
        .footer-bottom { text-align: center; font-size: 13px; opacity: 0.6; font-family: 'Playfair Display', serif; letter-spacing: 1px; }
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
                        <li><a href="listening.php">Listening Center</a></li>
                        <li><a href="reading.php">Reading Room</a></li>
                        <li><a href="emma_server/speakAI.php">Emma Speaking</a></li>
                        <li><a href="writing.php">Writing Lab</a></li>
                        <li><a href="daily_talk.php">Daily Talk</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Games ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="vocabulary_game.php">Vocabulary Game</a></li>
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
                    <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px; text-transform:uppercase;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important; font-weight: 600;">Sign Out</a></li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <h1>Academic Integrity</h1>
        <p>Understanding Plagiarism & Upholding the Highest Standards of Scholarship.</p>
    </header>

    <main class="content-container">
        
        <div class="intro-card">
            <h2>What is Plagiarism?</h2>
            <p>At university level, plagiarism is defined as the act of presenting another person's ideas, words, data, or concepts as your own, without providing proper acknowledgment or citation. It is not merely "copying"; it is a fundamental breach of academic trust and intellectual honesty.</p>
            <span class="highlight-quote">"Integrity is the currency of academia. Without it, your degree loses its value."</span>
        </div>

        <h2 class="section-title">Common Types of Plagiarism</h2>
        <div class="type-grid">
            <div class="type-card">
                <div class="type-icon">✂️</div>
                <h3>Direct Plagiarism</h3>
                <p>The verbatim (word-for-word) copying of a section of someone else's work without quotation marks or proper citation. This is the most severe and obvious form.</p>
            </div>
            <div class="type-card">
                <div class="type-icon">🧩</div>
                <h3>Mosaic Plagiarism</h3>
                <p>Also known as "patchwriting." Occurs when a student borrows phrases from a source without using quotation marks, or finds synonyms for the author's language while keeping to the same general structure.</p>
            </div>
            <div class="type-card">
                <div class="type-icon">♻️</div>
                <h3>Self-Plagiarism</h3>
                <p>Submitting your own previous work, or parts of it, for a current assignment without permission from all professors involved. You cannot get credit twice for the same work.</p>
            </div>
            <div class="type-card">
                <div class="type-icon">⚠️</div>
                <h3>Accidental Plagiarism</h3>
                <p>Occurs when a person neglects to cite their sources, or misquotes their sources, or unintentionally paraphrases a source by using similar words/sentence structure without attribution.</p>
            </div>
        </div>

        <div class="consequences-section">
            <h2>The Consequences of Academic Misconduct</h2>
            <p style="font-size: 1.2rem; opacity: 0.9; margin-bottom: 20px;">Universities maintain a <strong>Zero-Tolerance Policy</strong> regarding plagiarism. If you are caught, the penalties are severe and escalate quickly:</p>
            <ul class="consequences-list">
                <li>
                    <strong>01. Grade Penalties</strong>
                    Receiving an automatic Zero (0%) on the specific assignment or exam, often leading to a failing grade for the entire course.
                </li>
                <li>
                    <strong>02. Academic Probation</strong>
                    A formal disciplinary record on your student file, which can severely impact scholarship eligibility and future graduate school applications.
                </li>
                <li>
                    <strong>03. Suspension or Expulsion</strong>
                    For repeated or egregious offenses, the university has the right to suspend or permanently expel the student, nullifying their academic career.
                </li>
            </ul>
        </div>

        <div class="prevention-section">
            <h2>How to Avoid Plagiarism</h2>
            <div class="tips-container">
                <div class="tip-row">
                    <div class="tip-number">1</div>
                    <div class="tip-content">
                        <h4>Cite Your Sources Properly</h4>
                        <p>Whenever you use an idea, a statistic, or a direct quote from an external source, cite it immediately using the required academic format (APA, MLA, Harvard, etc.).</p>
                    </div>
                </div>
                <div class="tip-row">
                    <div class="tip-number">2</div>
                    <div class="tip-content">
                        <h4>Use Quotation Marks</h4>
                        <p>If you are copying more than three consecutive words from a source, you must enclose the text in quotation marks and provide a citation indicating the page number.</p>
                    </div>
                </div>
                <div class="tip-row">
                    <div class="tip-number">3</div>
                    <div class="tip-content">
                        <h4>Master Paraphrasing</h4>
                        <p>True paraphrasing means rewriting the author's idea entirely in your own words and sentence structure. Even when properly paraphrased, <strong>you still must cite the original source</strong>.</p>
                    </div>
                </div>
                <div class="tip-row">
                    <div class="tip-number">4</div>
                    <div class="tip-content">
                        <h4>Manage Your Time</h4>
                        <p>Most intentional plagiarism occurs because students leave assignments until the last minute and panic. Start your research early to give yourself time to write originally.</p>
                    </div>
                </div>
            </div>

            <div class="pledge-area">
                <h3 style="color: var(--oxford-blue); font-size: 1.5rem; margin-bottom: 10px;">The Scholar's Pledge</h3>
                <p style="color: var(--text-light); margin-bottom: 30px;">"I acknowledge the policies regarding academic integrity and pledge to produce original, ethically sourced work."</p>
                <button class="btn-pledge" onclick="window.location.href='home.php'">I Understand & Agree</button>
            </div>
        </div>

    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-about">
                <h3>Spires Academy</h3>
                <p>Dedicated to fostering intellectual curiosity and academic excellence. We provide rigorous language training, scholarly resources, and advanced AI-assisted evaluations to prepare students for global academic challenges.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="plagiarism.php">Plagiarism Policy</a></li>
                    <li><a href="listening.php">Listening Center</a></li>
                    <li><a href="reading.php">Reading Room</a></li>
                    <li><a href="writing.php">Writing Lab</a></li>
                    <li><a href="forum.php">Community Forum</a></li>
                    <li><a href="profile.php">Scholar Profile</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2026 Spires Academy. All rights reserved. Cultivating Eloquence and Academic Excellence.
        </div>
    </footer>

</body>
</html>