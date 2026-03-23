<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Word Garden - Login & Register</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
      
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --soft-green-bg: #f2f7f5;
            --card-shadow: 0 15px 35px rgba(27, 67, 50, 0.1);
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-color: var(--soft-green-bg);
        }

       
        .login-page {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        
        .left-side {
            flex: 1.2;
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 40px;
            position: relative;
        }

        .left-side::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0);
            background-size: 30px 30px;
        }

        .brand-logo {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: 2px;
            z-index: 1;
        }

        .brand-slogan {
            font-size: 1.2rem;
            opacity: 0.8;
            font-weight: 300;
            letter-spacing: 4px;
            text-transform: uppercase;
            z-index: 1;
        }

       
        .right-side {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--soft-green-bg);
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 50px;
            background: white;
            border-radius: 25px;
            box-shadow: var(--card-shadow);
            text-align: center;
        }

        .login-header h2 {
            color: var(--primary-green);
            font-size: 1.8rem;
            margin-bottom: 30px;
        }

        
        input {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
            background-color: #fcfdfd;
        }

        input:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 4px rgba(64, 145, 108, 0.1);
        }

      
        button {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-green);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
        }

        button:hover {
            background-color: var(--accent-green);
            transform: translateY(-2px);
        }

        
        .switch-text {
            margin-top: 25px;
            font-size: 0.9rem;
            color: #666;
        }

        .switch-text a {
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
        }

        .switch-text a:hover {
            text-decoration: underline;
        }

      
        @media (max-width: 768px) {
            .left-side { display: none; }
            .right-side { padding: 20px; }
        }
    </style>
    <script>
        
        function showForm(formType) {
            const loginForm = document.getElementById("login-form");
            const registerForm = document.getElementById("register-form");
            const switchRegister = document.getElementById("switch-to-register");
            const switchToLogin = document.getElementById("switch-to-login");
            const headerTitle = document.getElementById("header-title");

            if (formType === "login") {
                loginForm.style.display = "block";
                registerForm.style.display = "none";
                switchRegister.style.display = "block";
                switchToLogin.style.display = "none";
                headerTitle.innerText = "Welcome Back";
            } else {
                loginForm.style.display = "none";
                registerForm.style.display = "block";
                switchRegister.style.display = "none";
                switchToLogin.style.display = "block";
                headerTitle.innerText = "Create Account";
            }
        }
        window.onload = function () {
            showForm("login");
        };
    </script>
</head>
<body class="login-page">
    <div class="left-side">
        <div class="brand-logo">Word Garden</div>
        <div class="brand-slogan">Cultivate Your Mind, One Word at a Time</div>
    </div>

    <div class="right-side">
        <div class="login-container">
            <div class="login-header">
                <h2 id="header-title">Welcome Back</h2>
            </div>

            <form id="login-form" action="login.php" method="POST" class="login-form">
                <input type="text" name="username" placeholder="Username" required />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit">LOGIN</button>
            </form>

            <form id="register-form" action="register.php" method="POST" class="register-form" style="display: none">
                <input type="text" name="username" placeholder="Username" required />
                <input type="email" name="email" placeholder="Email Address" required />
                <input type="password" name="password" placeholder="Create Password" required />
                <button type="submit">SIGN UP</button>
            </form>

            <div id="switch-to-register" class="switch-text">
                Don't have an account? <a href="javascript:void(0);" onclick="showForm('register')">Register Now</a>
            </div>
            <div id="switch-to-login" class="switch-text" style="display: none">
                Already have an account? <a href="javascript:void(0);" onclick="showForm('login')">Login Here</a>
            </div>
        </div>
    </div>
</body>
</html>