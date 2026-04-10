<?php
// 包含数据库连接文件
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 获取表单数据
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // 使用 sha256 加密，确保与登录时的加密逻辑一致
    $hashed_password = hash('sha256', $password);
    
    // 默认将昵称设置为用户名
    $nickname = $username; 

    // 检查用户名或邮箱是否已存在
    $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('错误：用户名或邮箱已被占用！'); window.location.href = 'index.php';</script>";
    } else {
        // 插入新用户数据到 users 表
        $sql = "INSERT INTO users (username, email, password_hash, nickname) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $nickname);

        if ($stmt->execute()) {
            echo "<script>alert('注册成功！现在请登录。'); window.location.href = 'index.php';</script>";
        } else {
            echo "<script>alert('注册过程中出现错误，请稍后再试。'); window.location.href = 'index.php';</script>";
        }
        $stmt->close();
    }

    $check_stmt->close();
    $conn->close();
}
?>