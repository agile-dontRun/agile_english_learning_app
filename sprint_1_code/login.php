<?php
include 'db_connect.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $hashed_password = hash('sha256', $password);
    
    $sql = "SELECT * FROM users WHERE username = ? AND password_hash = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hashed_password);  
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        
        $user = $result->fetch_assoc();  
        
        
        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nickname'] = $user['nickname'];
        
        echo "<script type='text/javascript'>
                alert('登录成功！欢迎 " . $user['nickname'] . "');
                window.location.href = 'home.php';
              </script>";
        exit();  
    } else {
        // 登录失败
        echo "<script type='text/javascript'>
                alert('登录失败：用户名或密码错误！');
                window.location.href = 'index.php';
              </script>";
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>