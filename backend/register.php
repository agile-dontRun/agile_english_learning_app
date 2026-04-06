<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $hashed_password = hash('sha256', $password);
    $nickname = $username; 

    $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    echo "<link href='https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Open+Sans:wght@400;600&display=swap' rel='stylesheet'>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <style>
        .swal2-popup { font-family: 'Open Sans', sans-serif !important; border-radius: 15px !important; }
        .swal2-title { font-family: 'Playfair Display', serif !important; font-weight: 800 !important; color: #002147 !important; }
        .swal2-confirm { background-color: #002147 !important; font-family: 'Playfair Display', serif !important; text-transform: uppercase !important; letter-spacing: 1px !important; }
    </style>";

    if ($result->num_rows > 0) {
        echo "<script>
            window.onload = function() {
                Swal.fire({
                    title: 'Registration Error',
                    text: 'This username or academic email is already enrolled.',
                    icon: 'warning',
                    confirmButtonText: 'Return'
                }).then(() => {
                    window.location.href = 'index.php';
                });
            };
        </script>";
    } else {
        $sql = "INSERT INTO users (username, email, password_hash, nickname) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $nickname);

        if ($stmt->execute()) {
            echo "<script>
                window.onload = function() {
                    Swal.fire({
                        title: 'Enrollment Complete',
                        text: 'Your scholar account has been created. Please sign in.',
                        icon: 'success',
                        confirmButtonText: 'Sign In Now'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                };
            </script>";
        } else {
            echo "<script>
                window.onload = function() {
                    Swal.fire({
                        title: 'System Error',
                        text: 'An unexpected error occurred during enrollment.',
                        icon: 'error',
                        confirmButtonText: 'Contact Support'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                };
            </script>";
        }
        $stmt->close();
    }

    $check_stmt->close();
    $conn->close();
}
?>