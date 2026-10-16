<?php
$activePage = $activePage ?? '';
function navClass($page, $active) { return 'sidebar-nav-link' . ($page === $active ? ' active' : ''); }
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="badge">&#9906;</div>
        <div>
            <div class="name">BARANGAY<br>HEALTH PORTAL</div>
            <div class="sub">Staff Panel</div>
        </div>
    </div>
    <div class="sidebar-nav">
        <a class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Staff/staffDashboard.php"><span class="sidebar-icon">&#9638;</span> Dashboard</a>
        <a class="<?php echo $activePage === 'surveys' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Staff/manageSurveys.php"><span class="sidebar-icon">&#9745;</span> Survey Management</a>
        <a class="<?php echo $activePage === 'healthEvents' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Staff/manageHealthEvents.php"><span class="sidebar-icon">&#127973;</span> Health Events</a>
        <a class="<?php echo $activePage === 'healthHistory' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Staff/healthHistory.php"><span class="sidebar-icon">&#128218;</span> Health History</a>
        <a class="<?php echo $activePage === 'residents' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Staff/manageResidents.php"><span class="sidebar-icon">&#128101;</span> Resident Management</a>
        <a class="<?php echo $activePage === 'reports' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>app/Views/Staff/reports.php"><span class="sidebar-icon">&#128203;</span> Reports</a>
    </div>
    <div class="sidebar-footer">
        <a class="logout-link" href="<?php echo BASE_URL; ?>logout.php" onclick="return confirm('Are you sure you want to log out?');"><span class="sidebar-icon">&#8618;</span> Logout</a>
    </div>
</div>
