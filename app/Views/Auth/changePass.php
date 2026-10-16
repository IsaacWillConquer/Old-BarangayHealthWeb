<?php
require_once __DIR__ . "/../../../core/auth.php";

if (!isset($_SESSION['StaffID']) && !isset($_SESSION['ResidentID'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}
$isDefault = isDefaultPassword();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/login.css">
    <title>Change Password</title>
</head>
<body>
<div class="auth-shell">
    <div class="auth-main">
    <div class="login-box">
        <?php if ($isDefault): ?>
            <h3>You're using a default password</h3>
            <p style="font-size:0.85rem;color:#7a7568;">You can set a new one now, or skip and change it later from your profile.</p>
        <?php else: ?>
            <h3>Change Your Password</h3>
            <p style="font-size:0.85rem;color:#7a7568;">Enter a new password below.</p>
        <?php endif; ?>

        <label>New Password</label>
        <input type="password" id="newpass" placeholder="At least 7 characters">

        <label>Confirm Password</label>
        <input type="password" id="confirmpass" placeholder="Re-enter password">

        <button id="changeBtn">Set New Password</button>
        <?php if ($isDefault): ?>
            <button id="skipBtn" style="background:#33312c;margin-top:10px;">Skip for now</button>
        <?php endif; ?>

        <div id="error_text"></div>
        <div id="success_text" style="color:#3f6d54;margin-top:10px;font-size:0.85rem;"></div>
    </div>
    </div>
</div>

<script>
$(function () {
    $('#changeBtn').on('click', function () {
        var np = $('#newpass').val();
        var cp = $('#confirmpass').val();
        var $err = $('#error_text');
        $err.text('');

        if (np.length < 7) { $err.text('Password must be at least 7 characters.'); return; }
        if (np !== cp) { $err.text('Passwords do not match.'); return; }

        $.post('app/Controllers/authController.php', { action: 'changepass', newpass: np }, null, 'json')
            .done(function (res) {
                if (res.success) window.location.href = res.redirect;
                else $err.text(res.message || 'Something went wrong.');
            });
    });

    $('#skipBtn').on('click', function () {
        $.post('app/Controllers/authController.php', { action: 'skipChangepass' }, null, 'json')
            .done(function (res) {
                if (res.success) window.location.href = res.redirect;
            });
    });
});
</script>
</body>
</html>
