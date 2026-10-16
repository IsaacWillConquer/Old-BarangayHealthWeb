<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeResident();
$activePage = 'healthHistory';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/generalize.css">
    <title>My Health History</title>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/sidebarResident.php'; ?>

    <div class="main-content">
        <div class="page-header"><h2>My Health History</h2></div>
        <p style="color:var(--text-muted);">Recorded by Barangay Health Center staff during checkups and visits. This is a read-only view — see staff if anything needs correcting.</p>

        <div class="card">
            <table>
                <thead><tr><th>Date</th><th>Condition</th><th>Status</th><th>Notes</th></tr></thead>
                <tbody id="historyRows"></tbody>
            </table>
            <p id="noHistory" style="display:none;color:var(--text-muted);">No health history recorded yet.</p>
        </div>
    </div>
</div>

<script>
$(function () {
    $.post('app/Controllers/residentHealthHistoryController.php', { action: 'listMyHistory' }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        var $rows = $('#historyRows').empty();
        $('#noHistory').toggle(res.records.length === 0);
        res.records.forEach(function (r) {
            var condLabel = r.ConditionType === 'Other' ? r.OtherConditionText : r.ConditionType;
            $rows.append(
                '<tr><td>' + r.RecordDate + '</td><td>' + $('<div>').text(condLabel).html() + '</td><td>' +
                r.Status + '</td><td>' + $('<div>').text(r.Notes || '—').html() + '</td></tr>'
            );
        });
    });
});
</script>
</body>
</html>
