<?php
session_start();

// If already logged in, redirect to the right dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard-admin.php");
    } else {
        header("Location: dashboard-guard.php");
    }
    exit();
}

// Show error message if login failed
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Visitor Management System</title>

<link rel="stylesheet" href="style.css">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div class="container">

    <div class="left">

        <div class="overlay">

            <div class="logo">

                <img src="kpc logo.png" alt="KPC Logo">

                <div>

                    <h2>KENYA PIPELINE COMPANY</h2>

                    <p>Energy for Kenya</p>

                </div>

            </div>

            <h1>
                VISITOR<br>
                MANAGEMENT SYSTEM
            </h1>

            <p class="description">
                Secure digital visitor registration and monitoring platform.
            </p>

        </div>

    </div>

    <div class="right">

        <div class="login-card">

            <h2>Welcome Back</h2>

            <p>Sign in to your account</p>

            <!-- LOGIN FORM -->
            <form action="actions/login.php" method="POST">

                <!-- Show error if login failed -->
                <?php if ($error): ?>
                    <div style="background:#ffe5e5;color:#c8102e;padding:10px 15px;border-radius:8px;margin-bottom:15px;font-size:14px;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="input-box">

                    <i class="fa-solid fa-envelope"></i>

                    <input
                    type="email"
                    name="email"
                    id="email"
                    placeholder="Email Address"
                    required>

                </div>

                <div class="input-box">

                    <i class="fa-solid fa-lock"></i>

                    <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Password"
                    required>

                    <i class="fa-solid fa-eye eye"></i>

                </div>

                <button type="submit">

                    Sign In

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </form>

            <div class="powered">

                Powered by KPC

            </div>

        </div>

    </div>

</div>

<script src="script.js"></script>

</body>

</html>