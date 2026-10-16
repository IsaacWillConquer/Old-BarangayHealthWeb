<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeResident();
$activePage = 'events';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script>var BASE_URL_JS = '<?php echo BASE_URL; ?>';</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/generalize.css">
    <title>Health Events</title>
    <style>
        .event-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; margin-top:14px; }
        .event-card { border:1px solid var(--border,#ddd); border-radius:10px; overflow:hidden; background:#fff; display:flex; flex-direction:column; }
        .event-card-img { width:100%; height:130px; object-fit:cover; background:var(--bg-soft,#f3f1ea); }
        .event-card-icon { width:100%; height:130px; display:flex; align-items:center; justify-content:center; font-size:2.6rem; background:var(--bg-soft,#f3f1ea); }
        .event-card-body { padding:12px; display:flex; flex-direction:column; gap:6px; flex:1; }
        .event-card-body h4 { margin:0; font-size:0.98rem; }
        .event-card-meta { font-size:0.8rem; color:var(--text-muted); }
        .event-card-actions { margin-top:auto; }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/sidebarResident.php'; ?>

    <div class="main-content">
        <div class="page-header"><h2>Health Events</h2></div>
        <p style="color:var(--text-muted);">Barangay health activities you can join. Signing up is optional.</p>

        <div class="card">
            <div class="event-grid" id="eventGrid"></div>
            <p id="noEvents" style="display:none;color:var(--text-muted);">No active or upcoming health events right now — check back soon.</p>
        </div>
    </div>
</div>

<script>
var CTRL = 'app/Controllers/residentEventController.php';

var TYPE_ICONS = {
    Checkup: '\u{1FA7A}', VaccinationHuman: '\u{1F489}', VaccinationDog: '\u{1F415}',
    Cleanup: '\u{1F9F9}', Wellness: '\u{1F9D8}', BloodDonation: '\u{1FA78}',
    EyeConsult: '\u{1F453}', Campaign: '\u{1F4E3}', FreeVitamins: '\u{1F48A}', Other: '\u{2695}\u{FE0F}'
};
var TYPE_LABELS = {
    Checkup:'Checkup', VaccinationHuman:'Vaccination (Human)', VaccinationDog:'Vaccination (Dog)',
    Cleanup:'Bayanihan Clean-up', Wellness:'Wellness Session', BloodDonation:'Blood Donation',
    EyeConsult:'Free Eye Consultation', Campaign:'Health Campaign', FreeVitamins:'Free Vitamins', Other:'Other'
};

function loadEvents() {
    $.post(CTRL, { action: 'listActiveEvents' }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        var $grid = $('#eventGrid').empty();
        $('#noEvents').toggle(res.events.length === 0);

        res.events.forEach(function (e) {
            var imgHtml = e.ImagePath
                ? '<img class="event-card-img" src="' + BASE_URL_JS + e.ImagePath + '">'
                : '<div class="event-card-icon">' + (TYPE_ICONS[e.EventType] || '\u2695\uFE0F') + '</div>';

            var actionBtn = e.isSignedUp
                ? '<button class="btn small danger cancel-signup-btn" data-id="' + e.EventID + '">Cancel Sign-up</button>'
                : '<button class="btn small signup-btn" data-id="' + e.EventID + '">Sign Up</button>';

            $grid.append(
                '<div class="event-card">' + imgHtml +
                '<div class="event-card-body">' +
                '<h4>' + $('<div>').text(e.Title).html() + '</h4>' +
                '<div class="event-card-meta">' + (TYPE_LABELS[e.EventType] || e.EventType) + '</div>' +
                '<div class="event-card-meta">' + e.EventDate + (e.StartTime ? ' &middot; ' + e.StartTime : '') + '</div>' +
                '<div class="event-card-meta">' + $('<div>').text(e.Location).html() + '</div>' +
                (e.Description ? '<div class="event-card-meta">' + $('<div>').text(e.Description).html() + '</div>' : '') +
                '<div class="event-card-meta">' + e.signupCount + ' resident(s) signed up</div>' +
                '<div class="event-card-actions">' + actionBtn + '</div>' +
                '</div></div>'
            );
        });
    });
}

$(function () {
    loadEvents();

    $(document).on('click', '.signup-btn', function () {
        var id = $(this).data('id');
        $.post(CTRL, { action: 'signUp', eventId: id }, null, 'json')
        .done(function (res) {
            if (res.success) loadEvents();
            else alert(res.message);
        });
    });

    $(document).on('click', '.cancel-signup-btn', function () {
        var id = $(this).data('id');
        if (!confirm('Cancel your sign-up for this event?')) return;
        $.post(CTRL, { action: 'cancelSignup', eventId: id }, null, 'json')
        .done(function () { loadEvents(); });
    });
});
</script>
</body>
</html>
