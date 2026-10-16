<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeResident();
$responseId = (int)($_GET['responseId'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/generalize.css">
    <title>Completed Survey</title>
</head>
<body>
<div class="page-wrap" style="max-width:760px;">
    <a href="app/Views/Resident/residentDashboard.php" class="btn secondary small" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;margin-bottom:18px;background:#1F7A63;color:#F8F3E9;">&larr; Back to Dashboard</a>
    <div class="card">
        <h3 id="rTitle" style="text-transform:none;letter-spacing:normal;font-size:1.3rem;color:var(--text);border-bottom:none;padding-bottom:0;margin-bottom:4px;">Loading...</h3>
        <p id="rSubmitted" style="color:var(--text-muted);font-size:0.85rem;margin:0 0 18px;"></p>
        <div id="rAnswers" style="display:flex;flex-direction:column;gap:14px;"></div>
        <p style="color:var(--text-muted);font-size:0.8rem;margin-top:20px;padding-top:16px;border-top:1px solid var(--border-soft);">
            This is a read-only record — responses cannot be edited once submitted.
        </p>
    </div>
</div>

<script>
$(function () {
    $.post('app/Controllers/responseController.php', { action: 'viewCompletedResponse', responseId: <?php echo $responseId; ?> }, null, 'json')
    .done(function (res) {
        if (!res.success) { $('#rTitle').text('Not found'); return; }
        var r = res.response;
        $('#rTitle').text(r.Title);
        $('#rSubmitted').text('Submitted ' + r.SubmittedAt);
        var html = '';
        r.answers.forEach(function (a) {
            var val = a.ChoiceText || a.AnswerText || '(no answer)';
            html += '<div style="background:#FDFBF6;border:1px solid var(--border-soft);border-radius:var(--radius-sm);padding:12px 14px;">'
                  + '<div style="font-size:0.8rem;font-weight:600;color:var(--text-muted);margin-bottom:4px;">' + $('<div>').text(a.QuestionText).html() + '</div>'
                  + '<div style="font-size:0.95rem;color:var(--text);">' + $('<div>').text(val).html() + '</div>'
                  + '</div>';
        });
        $('#rAnswers').html(html);
    });
});
</script>
</body>
</html>
