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
            background-color: var(--oxford-blue); 
        }
    </style>
</head>

<style>
 
    .login-page {
        display: flex; height: 100vh; width: 100%;
        background: linear-gradient(rgba(0, 33, 71, 0.4), rgba(0, 33, 71, 0.4)), 
                    url('hero_bg2.png') center/cover no-repeat; 
        align-items: center; justify-content: space-around; padding: 0 5%; box-sizing: border-box;
    }
    .left-side { flex: 1.2; color: white; padding-right: 50px; z-index: 1; }
    .brand-logo {
        font-family: 'Playfair Display', serif; font-size: 5rem; font-weight: 800;
        letter-spacing: 5px; text-transform: uppercase;
        text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.8); 
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