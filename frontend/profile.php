
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

<?php



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

