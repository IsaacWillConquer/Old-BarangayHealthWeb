<?php
$activePage = $activePage ?? '';
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="badge">&#9906;</div>
        <div>
            <div class="name">BARANGAY<br>HEALTH PORTAL</div>
            <div class="sub">Resident Portal</div>
        </div>
    </div>
    <div class="sidebar-nav">
        <a class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Resident/residentDashboard.php"><span class="sidebar-icon">&#9638;</span> Dashboard</a>
        <a class="<?php echo $activePage === 'events' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Resident/healthEvents.php"><span class="sidebar-icon">&#127973;</span> Health Events</a>
        <a class="<?php echo $activePage === 'healthHistory' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Resident/myHealthHistory.php"><span class="sidebar-icon">&#128218;</span> My Health History</a>
        <a class="<?php echo $activePage === 'profile' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Resident/profile.php"><span class="sidebar-icon">&#128100;</span> Profile</a>
    </div>
    <div class="sidebar-footer">
        <a class="logout-link" href="<?php echo BASE_URL; ?>logout.php" onclick="return confirm('Are you sure you want to log out?');"><span class="sidebar-icon">&#8618;</span> Logout</a>
    </div>
</div>
