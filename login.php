<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="icon" href="images/sti_logo.png" type="image/png">
    <style>
        .logo-container {
            position: absolute;
            top: 20px;
            left: 20px;
        }
        .logo-container img {
            width: 350px; /* Adjust size as needed */
            height: auto;
        }
    </style>
</head>

<body>
    <div class="logo-container">
        <img src="images/STI_symbol.png" alt="STI Logo">
    </div>
    <div class="login-container">
        <h2>Login</h2>
        <?php
        if (isset($_GET['error'])) {
            echo '<p class="error-message">';
            if ($_GET['error'] == 'invalid') {
                echo 'Invalid username or password!';
            } elseif ($_GET['error'] == 'empty') {
                echo 'Please fill in all fields!';
            } elseif ($_GET['error'] == 'dberror') {
                echo 'Database connection error. Try again later.';
            }
            echo '</p>';
        }
        if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
            echo '<p class="success-message">You have been logged out successfully!</p>';
        }
        ?>
        <form action="process_login.php" method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required>
                    <div class="password-toggle" id="passwordToggle"
                        style="background-image: url('images/password_hide_icon.png');">
                    </div>
                </div>
            </div>
            <button type="submit" name="login_submit">Login</button>
        </form>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');
        let passwordVisible = false;

        passwordToggle.addEventListener('click', function() {
            passwordVisible = !passwordVisible;
            passwordInput.type = passwordVisible ? 'text' : 'password';
            passwordToggle.style.backgroundImage = passwordVisible ?
                "url('images/password_show_icon.png')" :
                "url('images/password_hide_icon.png')";
        });

        document.getElementById('dev-text').addEventListener('click', function() {
            var internInfo = document.getElementById('intern-info');
            internInfo.style.display = (internInfo.style.display === 'none' || internInfo.style.display === '') ? 'flex' : 'none';
        });
    </script>
    <div class="footer">
        <span id="dev-text">Developed by MIS Department Interns 2025</span>
        <div class="interns" id="intern-info">
            <div class="intern">
                <img src="images/Villalon.jpg" alt="Villalon">
                <p>Villalon</p>
            </div>
            <div class="intern">
                <img src="images/Tabora.jpg" alt="Tabora">
                <p>Tabora</p>
            </div>
            <div class="intern">
                <img src="images/Dela_Luna.jpg" alt="Dela Luna">
                <p>Dela Luna</p>
            </div>
        </div>
    </div>
</body>

</html>