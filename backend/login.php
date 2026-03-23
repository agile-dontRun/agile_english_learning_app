<?php
session_start();
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

    $html_header = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Status</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            body { background-color: #f3f4f6; font-family: sans-serif; }
        </style>
    </head>
    <body>';
    
    $html_footer = '</body></html>';

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();  
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nickname'] = $user['nickname'];
        
        echo $html_header;
        echo "<script>
                Swal.fire({
                    title: 'Login Successful!',
                    text: 'Welcome back, " . htmlspecialchars($user['nickname']) . "!',
                    icon: 'success',
                    confirmButtonText: 'Continue',
                    confirmButtonColor: '#3085d6',
                    timer: 2000,
                    timerProgressBar: true
                }).then((result) => {
                    window.location.href = 'home.php';
                });
              </script>";
        echo $html_footer;
        exit();  
    } else {
        echo $html_header;
        echo "<script>
                Swal.fire({
                    title: 'Login Failed',
                    text: 'Incorrect username or password. Please try again!',
                    icon: 'error',
                    confirmButtonText: 'Retry',
                    confirmButtonColor: '#d33'
                }).then((result) => {
                    window.location.href = 'index.php';
                });
              </script>";
        echo $html_footer;
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>