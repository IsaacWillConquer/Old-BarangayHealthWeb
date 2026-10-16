<?php
session_start();
require_once __DIR__ . '/config/appConfig.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo BASE_URL; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Barangay Health Portal — Home</title>
    <style>
        :root {
            --bg: #F8F3E9; --ink: #24322C; --ink-muted: #6E7A70; --line: #E7DFCA;
            --accent: #1F7A63; --accent-dark: #134D3F; --accent-soft: #E4F1EA;
            --gold: #DB9A34; --gold-soft: #FBEEDA; --card: #ffffff;
        }
        * { box-sizing: border-box; }
        body { margin:0; font-family:'Inter',Arial,sans-serif; background:var(--bg); color:var(--ink); }
        h1,h2,h3 { font-family:'Poppins',Arial,sans-serif; }
        .nav { display:flex; justify-content:space-between; align-items:center; padding:18px 40px; background:var(--card); border-bottom:1px solid var(--line); }
        .nav .brand { font-weight:800; font-size:1.1rem; color:var(--accent-dark); }
        .nav a.login-btn { background:var(--accent); color:#fff; padding:9px 20px; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.9rem; }
        .hero { padding:70px 40px; text-align:center; background:linear-gradient(170deg, #0F332C 0%, #164A3F 48%, #1E6353 100%); color:#f4f1e6; }
        .hero h1 { font-size:2.3rem; margin:0 0 14px; }
        .hero p { max-width:640px; margin:0 auto; color:rgba(244,241,230,0.85); font-size:1.02rem; line-height:1.6; }
        .section { max-width:1000px; margin:0 auto; padding:56px 40px; }
        .section h2 { font-size:1.6rem; margin-bottom:10px; color:var(--accent-dark); }
        .services-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-top:24px; }
        .service-card { background:var(--card); border:1px solid var(--line); border-radius:12px; padding:20px; }
        .service-card h3 { margin:0 0 8px; font-size:1rem; }
        .service-card p { margin:0; font-size:0.88rem; color:var(--ink-muted); line-height:1.5; }
        .mv-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:20px; }
        .mv-card { background:var(--accent-soft); border-radius:12px; padding:24px; }
        .mv-card h3 { margin:0 0 10px; color:var(--accent-dark); }
        .mv-card p { margin:0; font-size:0.92rem; line-height:1.6; color:var(--ink); }
        footer { background:var(--accent-dark); color:#f4f1e6; padding:40px; }
        .footer-grid { max-width:1000px; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:24px; }
        footer h4 { margin:0 0 10px; font-size:0.9rem; color:var(--gold-soft); }
        footer p, footer a { font-size:0.85rem; color:rgba(244,241,230,0.85); line-height:1.7; text-decoration:none; }
        footer a:hover { text-decoration:underline; }
        @media (max-width:700px) { .mv-grid { grid-template-columns:1fr; } .nav { padding:16px 20px; } .hero { padding:50px 20px; } .section { padding:40px 20px; } }
    </style>
</head>
<body>

<nav class="nav">
    <span class="brand">Barangay Health Portal</span>
    <a href="index.php" class="login-btn">Login</a>
</nav>

<section class="hero">
    <h1>Health services for our community</h1>
    <p>The Barangay Health Portal connects residents with health surveys, upcoming barangay health events, and their own health records — all in one place.</p>
</section>

<section class="section" id="about">
    <h2>About Us</h2>
    <p style="color:var(--ink-muted);line-height:1.7;">
        The Barangay Health Portal is a digital initiative of our local Barangay Health Center, built to make it
        easier for residents to stay informed and engaged with community health programs. Through this portal,
        residents can view upcoming health events, participate in surveys that shape local health decisions, and
        keep their household health records up to date with the health center staff.
    </p>
</section>

<section class="section" id="services">
    <h2>Services</h2>
    <div class="services-grid">
        <div class="service-card"><h3>Health Checkups</h3><p>Regular monthly checkups and monitoring for residents.</p></div>
        <div class="service-card"><h3>Vaccination Drives</h3><p>Human and pet vaccination events held throughout the year.</p></div>
        <div class="service-card"><h3>Blood Donation</h3><p>Community blood donation drives in partnership with local health agencies.</p></div>
        <div class="service-card"><h3>Free Eye Consultation</h3><p>Vision checks and eyeglasses assistance for residents.</p></div>
        <div class="service-card"><h3>Wellness Sessions</h3><p>Free wellness and health education sessions for the barangay.</p></div>
        <div class="service-card"><h3>Health Campaigns</h3><p>Awareness campaigns on dengue, nutrition, and other community health concerns.</p></div>
    </div>
</section>

<section class="section" id="mission-vision">
    <div class="mv-grid">
        <div class="mv-card">
            <h3>Our Mission</h3>
            <p>To provide accessible, responsive, and community-centered health services that improve the well-being of every resident in our barangay.</p>
        </div>
        <div class="mv-card">
            <h3>Our Vision</h3>
            <p>A healthy, informed, and connected barangay where every resident has the support and resources they need to thrive.</p>
        </div>
    </div>
</section>

<footer id="contact">
    <div class="footer-grid">
        <div>
            <h4>Location</h4>
            <p>Barangay Health Center<br>Barangay Hall Complex<br>Bulacan, Philippines</p>
        </div>
        <div>
            <h4>Contact</h4>
            <p>Hotline: (044) 000-0000<br>Email: healthcenter@barangay.gov.ph</p>
        </div>
        <div>
            <h4>Connect</h4>
            <p><a href="#" target="_blank" rel="noopener">Facebook Page</a></p>
        </div>
        <div>
            <h4>Portal</h4>
            <p><a href="index.php">Resident / Staff Login</a></p>
        </div>
    </div>
</footer>

</body>
</html>
