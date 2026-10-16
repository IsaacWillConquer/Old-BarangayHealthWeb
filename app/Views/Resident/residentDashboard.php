<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeResident();
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
    <title>My Dashboard — Barangay Health Portal</title>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/sidebarResident.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Dashboard</h2>
            <span class="user-chip">Hi, <?php echo htmlspecialchars($_SESSION['FirstName']); ?></span>
        </div>

        <div class="card">
            <h3 style="margin-bottom:4px;">Your Progress</h3>
            <p id="progressLabel" style="color:var(--text-muted);font-size:0.88rem;margin:0 0 8px;">Loading...</p>
            <div class="progress-bar-track"><div class="progress-bar-fill" id="progressFill" style="width:0%;"></div></div>
        </div>

        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;">Health Reminders</h3>
            </div>
            <p style="color:var(--ink,#24322C);background:var(--accent-soft,#E4F1EA);padding:10px 12px;border-radius:8px;font-size:0.88rem;margin:10px 0 8px;">
                &#128198; Monthly checkup reminder: Visit the Barangay Health Center for your regular checkup if it's been over a month since your last visit.
            </p>
            <p style="color:var(--ink,#24322C);background:var(--gold-soft,#FBEEDA);padding:10px 12px;border-radius:8px;font-size:0.88rem;margin:0;">
                &#9742; Barangay Health Hotline: (044) 000-0000
            </p>
        </div>

        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;">Active &amp; Upcoming Health Events</h3>
                <a class="btn small secondary" href="app/Views/Resident/healthEvents.php">View All</a>
            </div>
            <div class="survey-tile-grid" id="eventTiles" style="margin-top:12px;"></div>
            <p id="noEvents" style="display:none;color:var(--text-muted);">No active or upcoming events right now.</p>
        </div>

        <div class="card">
            <h3>Active Surveys</h3>
            <div class="survey-tile-grid" id="activeTiles"></div>
            <p id="noActive" style="display:none;color:var(--text-muted);">No active surveys right now — check back later.</p>
        </div>

        <div class="card">
            <h3>Completed Surveys</h3>
            <ul class="activity-feed" id="completedList"></ul>
            <p id="noCompleted" style="display:none;color:var(--text-muted);">You haven't completed any surveys yet.</p>
        </div>
    </div>
</div>

<script>
$(function () {
    var activeCount = 0, completedCount = 0;

    function updateProgress() {
        var total = activeCount + completedCount;
        var pct = total > 0 ? Math.round((completedCount / total) * 100) : 0;
        $('#progressLabel').text(completedCount + ' of ' + total + ' available survey(s) completed');
        $('#progressFill').css('width', pct + '%');
    }

    function loadActive() {
        $.post('app/Controllers/responseController.php', { action: 'getActiveSurveys' }, null, 'json')
        .done(function (res) {
            var $grid = $('#activeTiles').empty();
            if (!res.success || res.surveys.length === 0) { $('#noActive').show(); activeCount = 0; updateProgress(); return; }
            $('#noActive').hide();
            activeCount = res.surveys.length;
            res.surveys.forEach(function (s) {
                $grid.append(
                    '<div class="survey-tile">' +
                    '<h4>' + $('<div>').text(s.Title).html() + '</h4>' +
                    '<div class="desc">' + $('<div>').text(s.Description || 'No description provided.').html() + '</div>' +
                    '<div class="due">' + (s.CloseAt ? 'Closes ' + s.CloseAt : 'No deadline') + '</div>' +
                    '<a class="btn small" href="app/Views/Resident/takeSurvey.php?surveyId=' + s.SurveyID + '">Answer Survey</a>' +
                    '</div>'
                );
            });
            updateProgress();
        });
    }

    function loadCompleted() {
        $.post('app/Controllers/responseController.php', { action: 'getCompletedSurveys' }, null, 'json')
        .done(function (res) {
            var $list = $('#completedList').empty();
            if (!res.success || res.surveys.length === 0) { $('#noCompleted').show(); completedCount = 0; updateProgress(); return; }
            $('#noCompleted').hide();
            completedCount = res.surveys.length;
            res.surveys.forEach(function (s) {
                $list.append(
                    '<li>' + $('<div>').text(s.Title).html() +
                    ' <a href="app/Views/Resident/completedSurveys.php?responseId=' + s.ResponseID + '" style="float:right;">View</a>' +
                    '<span class="when">Submitted ' + s.SubmittedAt + '</span></li>'
                );
            });
            updateProgress();
        });
    }

    function loadDashboardEvents() {
        $.post('app/Controllers/residentEventController.php', { action: 'listActiveEvents' }, null, 'json')
        .done(function (res) {
            var $grid = $('#eventTiles').empty();
            if (!res.success || res.events.length === 0) { $('#noEvents').show(); return; }
            $('#noEvents').hide();
            res.events.slice(0, 4).forEach(function (e) {
                var actionBtn = e.isSignedUp
                    ? '<span class="btn small secondary" style="pointer-events:none;">Signed Up</span>'
                    : '<a class="btn small" href="app/Views/Resident/healthEvents.php">Sign Up</a>';
                $grid.append(
                    '<div class="survey-tile">' +
                    '<h4>' + $('<div>').text(e.Title).html() + '</h4>' +
                    '<div class="desc">' + $('<div>').text(e.Location).html() + '</div>' +
                    '<div class="due">' + e.EventDate + (e.StartTime ? ' &middot; ' + e.StartTime : '') + '</div>' +
                    actionBtn +
                    '</div>'
                );
            });
        });
    }

    loadActive();
    loadCompleted();
    loadDashboardEvents();
});
</script>
</body>
</html>