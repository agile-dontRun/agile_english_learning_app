<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Spires Academy - Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --oxford-blue: #002147; --oxford-blue-light: #003066;
            --oxford-gold: #c4a661; --oxford-gold-light: #d4b671;
            --white: #ffffff; --bg-light: #f4f7f6;
        }
        body, html {
            margin: 0; padding: 0; height: 100%;
            font-family: 'Open Sans', Arial, sans-serif;
            background-color: var(--oxford-blue); /* 初始背景色 */
        }
    </style>
</head>

<style>
   
    .login-page {
        display: flex; height: 100vh; width: 100%;
        background: linear-gradient(rgba(0, 33, 71, 0.4), rgba(0, 33, 71, 0.4)), 
                    url('hero_bg2.png') center/cover no-repeat; /* 全屏背景图 */
        align-items: center; justify-content: space-around; padding: 0 5%; box-sizing: border-box;
    }
    .left-side { flex: 1.2; color: white; padding-right: 50px; z-index: 1; }
    .brand-logo {
        font-family: 'Playfair Display', serif; font-size: 5rem; font-weight: 800;
        letter-spacing: 5px; text-transform: uppercase;
        text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.8); /* 巨型标题阴影 */
    }
    .brand-slogan { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-style: italic; }
</style>

<body>
    <div class="login-page">
        <div class="left-side">
            <div class="brand-logo">Spires Academy</div>
            <div class="brand-slogan">Pursue Excellence, Cultivate Eloquence, and Maintain Intellectual Rigor</div>
        </div>
        <div class="right-side"> </div>
    </div>
</body>

<style>
    
    .login-container {
        width: 100%; max-width: 420px; padding: 50px;
        background: rgba(255, 255, 255, 0.98); border-radius: 25px;
        box-shadow: 0 20px 50px rgba(0, 33, 71, 0.2);
        border-top: 5px solid var(--oxford-gold); /* 金色顶边框装饰 */
    }
    input {
        width: 100%; padding: 15px 20px; margin: 12px 0;
        border: 1px solid #e0e0e0; border-radius: 12px; transition: 0.3s;
    }
    input:focus { border-color: var(--oxford-gold); outline: none; }
    button {
        width: 100%; padding: 16px; background-color: var(--oxford-blue);
        color: white; border-radius: 12px; font-family: 'Playfair Display', serif;
        font-weight: 700; cursor: pointer; transition: 0.3s;
    }
</style>

<div class="login-container">
    <h2 id="header-title">Welcome Back</h2>
    <form id="login-form" action="login.php" method="POST">
        <input type="text" name="username" placeholder="Scholar ID / Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Log In</button>
    </form>
    </div>
    /* Step 5: 新增响应式媒体查询 */
@media (max-width: 1024px) {
    .brand-logo { font-size: 3.5rem; } /* 缩小 iPad 端字号 */
}

@media (max-width: 768px) {
    .login-page { 
        flex-direction: column; /* 手机端改为上下堆叠布局 */
        justify-content: center; padding: 20px; 
    }
    .left-side { padding-right: 0; text-align: center; margin-bottom: 40px; }
    .brand-logo { font-size: 2.8rem; }
    .login-container { padding: 35px 25px; }
}

    /* Step 4: 新增交互控制函数 */
<script>
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
            headerTitle.innerText = "Scholar Enrollment"; // 切换标题文字
        }
    }
    window.onload = function () { showForm("login"); };
</script>

</body>
