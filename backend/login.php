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

    echo "<link href='https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Open+Sans:wght@400;600&display=swap' rel='stylesheet'>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <style>
        .swal2-popup { font-family: 'Open Sans', sans-serif !important; border-radius: 15px !important; }
        .swal2-title { font-family: 'Playfair Display', serif !important; font-weight: 800 !important; color: #002147 !important; }
        .swal2-confirm { background-color: #002147 !important; font-family: 'Playfair Display', serif !important; text-transform: uppercase !important; letter-spacing: 1px !important; }
    </style>";

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();  
        
        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nickname'] = $user['nickname'];
        
        echo "<script>
            window.onload = function() {
                Swal.fire({
                    title: 'Welcome Back, Scholar',
                    text: 'Authentication successful. Accessing Spires Academy...',
                    icon: 'success',
                    confirmButtonText: 'Enter'
                }).then(() => {
                    window.location.href = 'home.php';
                });
            };
        </script>";
        exit();  
    } else {
        echo "<script>
            window.onload = function() {
                Swal.fire({
                    title: 'Authentication Failed',
                    text: 'The credentials provided do not match our records.',
                    icon: 'error',
                    confirmButtonText: 'Try Again'
                }).then(() => {
                    window.location.href = 'index.php';
                });
            };
        </script>";
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>