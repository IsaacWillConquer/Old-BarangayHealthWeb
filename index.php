<?php
session_start();
require_once __DIR__ . '/config/appConfig.php';

if (isset($_SESSION['StaffID'])) {
    if ($_SESSION['Role'] === 'Admin') {
        header('Location: ' . BASE_URL . 'app/Views/Admin/adminDashboard.php'); exit();
    }
    header('Location: ' . BASE_URL . 'app/Views/Staff/staffDashboard.php'); exit();
}
if (isset($_SESSION['ResidentID'])) {
    header('Location: ' . BASE_URL . 'app/Views/Resident/residentDashboard.php'); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/login.css">
    <title>Barangay Health Portal</title>
</head>
<body>

<div class="auth-shell">
    <aside class="auth-hero">
        <div class="hero-top">
            <div class="hero-badge">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 21s-7.5-4.6-10-9.2C.4 8.1 2.2 4.5 5.7 4c2-.3 3.7.6 4.9 2.1L12 7.6l1.4-1.5C14.6 4.6 16.3 3.7 18.3 4c3.5.5 5.3 4.1 3.7 7.8C19.5 16.4 12 21 12 21Z" stroke="#f4f1e6" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M12 9v6M9 12h6" stroke="#f4f1e6" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </div>
            <p class="hero-eyebrow">Barangay Health Portal</p>
            <h1>Health services for our community</h1>
            <p class="hero-desc">Surveys, health events, and resident records, gathered together so the barangay can act on what matters to its people.</p>
        </div>

        <ul class="hero-points">
            <li>
                <span class="dot"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L19 7" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>Share feedback that shapes local health programs</span>
            </li>
            <li>
                <span class="dot"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L19 7" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>Track surveys assigned to your household</span>
            </li>
            <li>
                <span class="dot"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L19 7" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>Staff can monitor response rates in real time</span>
            </li>
        </ul>

        <p class="hero-footnote">A digital initiative of your local health center</p>
    </aside>

    <main class="auth-main">
        <div class="login-box">
            <h3>Sign in</h3>
            <p class="subhead">
                Residents: use your Resident Number as both username and password (until you change it).
            </p>

            <label>Resident Number / Email</label>
            <input type="text" id="identifier" placeholder="Resident Number or Email" autocomplete="username">

            <label>Password</label>
            <div class="password-field">
                <input type="password" id="password" placeholder="Password" autocomplete="current-password">
                <button type="button" id="togglePassword" class="toggle-password" aria-label="Show password" aria-pressed="false">
                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/>
                    </svg>
                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        <path d="M10.6 5.1c.45-.07.92-.1 1.4-.1 7 0 10.5 7 10.5 7-.6 1.2-1.6 2.7-3 4M6.6 6.6C3.6 8.4 1.5 12 1.5 12s3.5 7 10.5 7c1.6 0 3-.35 4.2-.9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <button id="loginBtn">Login</button>

            <div id="error_text"></div>

            <p class="login-help">Trouble logging in? Contact your barangay health center office.</p>
            <p class="login-note">New resident accounts are created only by Barangay Health Center staff. If you're a resident without an account yet, please visit the health center in person.</p>
        </div>

        <p class="auth-footer"><a href="home.php">About this portal</a> &nbsp;&middot;&nbsp; &copy; <?php echo date('Y'); ?> Barangay Health Portal</p>
    </main>
</div>

<script src="app/Views/JS/logAuth.js"></script>
</body>
</html>