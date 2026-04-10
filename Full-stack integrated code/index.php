<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Spires Academy - Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --oxford-blue: #002147; 
            --oxford-blue-light: #003066;
            --oxford-gold: #c4a661; 
            --oxford-gold-light: #d4b671;
            --white: #ffffff; 
            --bg-light: #f4f7f6;
        }

        body, html {
            margin: 0; padding: 0; height: 100%;
            font-family: 'Open Sans', Arial, sans-serif;
            background-color: var(--oxford-blue);
        }

        /* Hero Background with full-page coverage */
        .login-page {
            display: flex; height: 100vh; width: 100%;
            background: linear-gradient(rgba(0, 33, 71, 0.4), rgba(0, 33, 71, 0.4)), 
                        url('hero_bg2.png') center/cover no-repeat;
            align-items: center; justify-content: space-around; 
            padding: 0 5%; box-sizing: border-box;
        }

        /* Brand section: The left side presence */
        .left-side { flex: 1.2; color: white; padding-right: 50px; z-index: 1; }
        
        .brand-logo {
            font-family: 'Playfair Display', serif; font-size: 5rem; font-weight: 800;
            letter-spacing: 5px; text-transform: uppercase;
            text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.8);
            margin-bottom: 10px;
        }
        
        .brand-slogan { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-style: italic; opacity: 0.9; }

        /* Auth container: The frosted glass look */
        .right-side { flex: 0.8; display: flex; justify-content: center; }
        
        .login-container {
            width: 100%; max-width: 420px; padding: 50px;
            background: rgba(255, 255, 255, 0.98); border-radius: 25px;
            box-shadow: 0 20px 50px rgba(0, 33, 71, 0.2);
            border-top: 6px solid var(--oxford-gold);
            backdrop-filter: blur(10px);
        }

        .login-container h2 { 
            font-family: 'Playfair Display', serif; color: var(--oxford-blue); 
            margin-bottom: 30px; font-size: 1.8rem; text-align: center;
        }

        /* Input aesthetics */
        input {
            width: 100%; padding: 15px 20px; margin: 12px 0;
            border: 1px solid #e0e0e0; border-radius: 12px; 
            box-sizing: border-box; transition: 0.3s; font-size: 14px;
        }
        input:focus { border-color: var(--oxford-gold); outline: none; box-shadow: 0 0 8px rgba(196, 166, 97, 0.2); }

        button {
            width: 100%; padding: 16px; background-color: var(--oxford-blue);
            color: white; border: none; border-radius: 12px; 
            font-family: 'Playfair Display', serif; font-weight: 700; 
            font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 15px;
        }
        button:hover { background-color: var(--oxford-blue-light); transform: translateY(-2px); }

        /* Form switcher text */
        .switch-text {
            text-align: center; margin-top: 25px; font-size: 14px; color: var(--text-light);
        }
        .switch-text a {
            color: var(--oxford-gold); text-decoration: none; font-weight: bold; cursor: pointer;
        }
        .switch-text a:hover { text-decoration: underline; }

        /* --- Responsive Queries --- */
        @media (max-width: 1024px) {
            .brand-logo { font-size: 3.5rem; }
        }

        @media (max-width: 768px) {
            .login-page { flex-direction: column; justify-content: center; padding: 20px; }
            .left-side { padding-right: 0; text-align: center; margin-bottom: 40px; }
            .brand-logo { font-size: 2.8rem; }
            .login-container { padding: 35px 25px; }
        }
    </style>
</head>

<body>
    <div class="login-page">
        <div class="left-side">
            <div class="brand-logo">Spires Academy</div>
            <div class="brand-slogan">Pursue Excellence, Cultivate Eloquence, and Maintain Intellectual Rigor</div>
        </div>

        <div class="right-side">
            <div class="login-container">
                <h2 id="header-title">Welcome Back</h2>
                
                <form id="login-form" action="login_process.php" method="POST">
                    <input type="text" name="username" placeholder="Scholar ID / Username" required />
                    <input type="password" name="password" placeholder="Password" required />
                    <button type="submit">Log In</button>
                    <div class="switch-text">
                        Don't have an account? <a onclick="showForm('register')">Enroll Now</a>
                    </div>
                </form>

                <form id="register-form" action="register_process.php" method="POST" style="display: none;">
                    <input type="text" name="reg_username" placeholder="Choose Username" required />
                    <input type="email" name="reg_email" placeholder="Institutional Email" required />
                    <input type="password" name="reg_password" placeholder="Set Password" required />
                    <button type="submit">Create Account</button>
                    <div class="switch-text">
                        Already a scholar? <a onclick="showForm('login')">Return to Log In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="ai-agent.js"></script>

    <script>
        /**
         * Dynamic form switcher. 
         * Manages display states for the login and registration components.
         */
        function showForm(formType) {
            const loginForm = document.getElementById("login-form");
            const registerForm = document.getElementById("register-form");
            const headerTitle = document.getElementById("header-title");

            if (formType === "login") {
                loginForm.style.display = "block";
                registerForm.style.display = "none";
                headerTitle.innerText = "Welcome Back";
            } else {
                loginForm.style.display = "none";
                registerForm.style.display = "block";
                headerTitle.innerText = "Scholar Enrollment";
            }
        }

        // Initialize view on load
        window.onload = function () { 
            showForm("login"); 
        };
    </script>
</body>
</html>
