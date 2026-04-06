
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
