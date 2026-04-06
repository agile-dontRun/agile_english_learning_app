
<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$nickname = !empty($user_data['nickname']) ? $user_data['nickname'] : $user_data['username'];
$avatar_url = !empty($user_data['avatar_url']) ? $user_data['avatar_url'] : 'college_logo.png';
$stmt->close();
?>

<nav class="navbar"> </nav>
<header class="hero">
    <h1>Scholar Profile</h1>
    <p>Honoring academic achievement and personal growth.</p>
</header>



<main class="main-content">
    <div class="card" style="text-align: center;">
        <div class="avatar-circle">
            <img src="<?php echo $avatar_url; ?>">
        </div>
        <h2><?php echo $nickname; ?></h2>
        <div class="info-box"> </div>
    </div>

    <div class="card">
        <h2 class="section-title">Speaking Progress</h2>
        </div>
</main>