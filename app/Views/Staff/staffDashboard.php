<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeStaff();
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/generalize.css">
    <title>Staff Dashboard</title>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/sidebarStaff.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Dashboard</h2>
            <span class="user-chip">Hi, <?php echo htmlspecialchars($_SESSION['FirstName']); ?> (Staff)</span>
        </div>

        <div class="stat-strip">
            <div class="stat-item"><div class="label">Total Residents</div><div class="value" id="statResidents">—</div></div>
            <div class="stat-item"><div class="label">Active Surveys</div><div class="value" id="statActive">—</div></div>
            <div class="stat-item"><div class="label">Total Responses</div><div class="value" id="statResponses">—</div></div>
            <div class="stat-item"><div class="label">Participation Rate</div><div class="value" id="statRate">—</div></div>
            <div class="stat-item"><div class="label">Upcoming Events</div><div class="value" id="statEvents">—</div></div>
        </div>

        <div class="card">
            <h3>Needs Attention</h3>
            <div id="attentionArea">Loading...</div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h3>Recent Activity</h3>
                <ul class="activity-feed" id="activityFeed"></ul>
            </div>
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="action-grid">
                    <a class="action-tile" href="app/Views/Staff/manageSurveys.php">
                        <div class="icon">&#9745;</div>
                        <div><div class="title">Manage Surveys</div><div class="desc">View, edit, and organize all surveys</div></div>
                    </a>
                    <a class="action-tile" href="app/Views/Staff/manageHealthEvents.php">
                        <div class="icon">&#127973;</div>
                        <div><div class="title">Manage Health Events</div><div class="desc">Create and track barangay health activities</div></div>
                    </a>
                    <a class="action-tile" href="app/Views/Staff/healthHistory.php">
                        <div class="icon">&#128218;</div>
                        <div><div class="title">Health History</div><div class="desc">Log and review resident health conditions</div></div>
                    </a>
                    <a class="action-tile" href="app/Views/Staff/manageResidents.php">
                        <div class="icon">&#128101;</div>
                        <div><div class="title">Manage Residents</div><div class="desc">Create, edit, or deactivate accounts</div></div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    $.post('app/Controllers/dashboardController.php', { action: 'getStats' }, null, 'json').done(function (res) {
        if (!res.success) return;
        var s = res.stats;
        $('#statResidents').text(s.totalResidents);
        $('#statActive').text(s.activeSurveys);
        $('#statResponses').text(s.totalResponses);
        $('#statRate').text(s.participationRate + '%');
        $('#statEvents').text(s.upcomingEvents);
    });

    $.post('app/Controllers/dashboardController.php', { action: 'getNeedsAttention' }, null, 'json').done(function (res) {
        if (!res.success) return;
        var a = res.attention;
        var html = '';
        if (a.drafts.length) {
            html += '<div class="attention-group"><h4>Drafts Not Yet Published</h4><ul class="attention-list">';
            a.drafts.forEach(function (d) {
                html += '<li>' + $('<div>').text(d.Title).html() + '<span class="attention-tag tag-draft">Draft</span></li>';
            });
            html += '</ul></div>';
        }
        if (a.closingSoon.length) {
            html += '<div class="attention-group"><h4>Closing Within 48 Hours</h4><ul class="attention-list">';
            a.closingSoon.forEach(function (d) {
                html += '<li>' + $('<div>').text(d.Title).html() + '<span class="attention-tag tag-closing">Closes ' + d.CloseAt + '</span></li>';
            });
            html += '</ul></div>';
        }
        if (a.zeroResponse.length) {
            html += '<div class="attention-group"><h4>No Responses Yet</h4><ul class="attention-list">';
            a.zeroResponse.forEach(function (d) {
                html += '<li>' + $('<div>').text(d.Title).html() + '<span class="attention-tag tag-zero">0 responses</span></li>';
            });
            html += '</ul></div>';
        }
        if (!html) html = '<p class="all-clear">Nothing needs your attention right now.</p>';
        $('#attentionArea').html(html);
    });

    $.post('app/Controllers/dashboardController.php', { action: 'getRecentActivity' }, null, 'json').done(function (res) {
        if (!res.success) return;
        var $feed = $('#activityFeed').empty();
        if (!res.activity.length) { $feed.append('<li style="color:var(--text-muted);">No activity yet.</li>'); return; }
        res.activity.forEach(function (l) {
            $feed.append('<li>' + $('<div>').text(l.Action).html() + ' <span style="color:var(--text-muted);">(' + l.ActorType + ' #' + l.ActorID + ')</span><span class="when">' + l.Timestamp + '</span></li>');
        });
    });
});
</script>
</body>
</html>
