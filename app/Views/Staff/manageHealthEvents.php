<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeStaff();
$activePage = 'healthEvents';
$isAdmin = ($_SESSION['Role'] ?? '') === 'Admin';
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
        .event-card-actions { margin-top:auto; display:flex; gap:6px; flex-wrap:wrap; }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/' . ($isAdmin ? 'sidebarAdmin.php' : 'sidebarStaff.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Health Events</h2>
            <button class="btn" id="newEventBtn">+ New Event</button>
        </div>

        <div class="card">
            <label>Filter by Status</label>
            <select id="statusFilter" style="max-width:220px;">
                <option value="">All</option>
                <option value="Draft">Draft</option>
                <option value="Upcoming">Upcoming</option>
                <option value="Active">Active</option>
                <option value="Closed">Closed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
            <div class="event-grid" id="eventGrid"></div>
            <p id="noEvents" style="display:none;color:var(--text-muted);">No events yet.</p>
        </div>

        <div class="card">
            <div id="archivedToggle" style="cursor:pointer;display:flex;align-items:center;gap:8px;">
                <span id="archivedCaret">&#9656;</span>
                <h3 style="margin:0;">Archived Events (<span id="archivedCount">0</span>)</h3>
            </div>
            <div id="archivedSection" style="display:none;margin-top:12px;">
                <table>
                    <thead><tr><th>Title</th><th>Event Date</th><th>Archived</th><th>Archived By</th><th></th></tr></thead>
                    <tbody id="archivedRows"></tbody>
                </table>
                <p id="noArchived" style="display:none;color:var(--text-muted);">No archived events.</p>
                <div id="archivedPagination" style="margin-top:12px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="eventModal">
    <div class="modal-box" style="max-width:560px;max-height:85vh;overflow-y:auto;">
        <h3 id="modalTitle">New Health Event</h3>
        <input type="hidden" id="editEventId">

        <label>Event Image (optional)</label>
        <div id="imagePreviewWrap" style="margin-bottom:8px;">
            <img id="eventImgPreview" src="" style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;display:none;">
        </div>
        <input type="file" id="eventImageInput" accept="image/jpeg,image/png">
        <p style="color:var(--text-muted);font-size:0.78rem;">JPG or PNG only, up to 3MB. If skipped, a default icon is shown based on event type.</p>
        <button type="button" class="btn small secondary" id="uploadEventImgBtn" style="display:none;">Upload Image</button>

        <label>Title</label>
        <input type="text" id="eTitle">
        <label>Event Type</label>
        <select id="eType">
            <option value="">Select...</option>
            <option value="Checkup">Checkup</option>
            <option value="VaccinationHuman">Vaccination (Human)</option>
            <option value="VaccinationDog">Vaccination (Dog)</option>
            <option value="Cleanup">Bayanihan Clean-up</option>
            <option value="Wellness">Wellness Session</option>
            <option value="BloodDonation">Blood Donation</option>
            <option value="EyeConsult">Free Eye Consultation</option>
            <option value="Campaign">Health Campaign</option>
            <option value="FreeVitamins">Free Vitamins</option>
            <option value="Other">Other</option>
        </select>
        <label>Description</label>
        <textarea id="eDescription" rows="3"></textarea>
        <label>Location</label>
        <input type="text" id="eLocation">
        <label>Event Date</label>
        <input type="date" id="eDate">
        <label>Start Time (optional)</label>
        <input type="time" id="eStart">
        <label>End Time (optional)</label>
        <input type="time" id="eEnd">
        <label>Status</label>
        <select id="eStatus">
            <option value="Draft">Draft</option>
            <option value="Upcoming">Upcoming</option>
            <option value="Active">Active</option>
            <option value="Closed">Closed</option>
            <option value="Cancelled">Cancelled</option>
        </select>

        <div style="margin-top:16px;">
            <button class="btn" id="saveEventBtn">Save</button>
            <button class="btn secondary" id="cancelEventBtn">Cancel</button>
        </div>
        <div id="eventModalError" class="error-text"></div>
        <div id="eventModalSuccess" class="success-text"></div>
    </div>
</div>

<div class="modal-overlay" id="signupsModal">
    <div class="modal-box">
        <h3>Sign-ups</h3>
        <table>
            <thead><tr><th>Resident #</th><th>Name</th><th>Contact</th><th>Signed Up</th></tr></thead>
            <tbody id="signupRows"></tbody>
        </table>
        <p id="noSignups" style="display:none;color:var(--text-muted);">No sign-ups yet.</p>
        <button class="btn secondary" id="closeSignupsBtn" style="margin-top:14px;">Close</button>
    </div>
</div>

<script>
var CTRL = 'app/Controllers/healthEventController.php';
var allEvents = [];
var currentArchivedPage = 1;

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
    $.post(CTRL, { action: 'listEvents' }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        allEvents = res.events;
        renderEvents();
    });
}

function renderEvents() {
    var filter = $('#statusFilter').val();
    var list = filter ? allEvents.filter(function (e) { return e.Status === filter; }) : allEvents;
    var $grid = $('#eventGrid').empty();
    $('#noEvents').toggle(list.length === 0);

    list.forEach(function (e) {
        var imgHtml = e.ImagePath
            ? '<img class="event-card-img" src="' + BASE_URL_JS + e.ImagePath + '">'
            : '<div class="event-card-icon">' + (TYPE_ICONS[e.EventType] || '\u2695\uFE0F') + '</div>';

        $grid.append(
            '<div class="event-card">' + imgHtml +
            '<div class="event-card-body">' +
            '<h4>' + $('<div>').text(e.Title).html() + '</h4>' +
            '<div class="event-card-meta">' + (TYPE_LABELS[e.EventType] || e.EventType) + '</div>' +
            '<div class="event-card-meta">' + e.EventDate + ' &middot; ' + $('<div>').text(e.Location).html() + '</div>' +
            '<div class="event-card-meta">' + e.SignupCount + ' signed up</div>' +
            '<span class="status-badge status-' + e.Status + '">' + e.Status + '</span>' +
            '<div class="event-card-actions">' +
            '<button class="btn small secondary edit-event-btn" data-id="' + e.EventID + '">Manage</button>' +
            '<button class="btn small secondary view-signups-btn" data-id="' + e.EventID + '">Sign-ups</button>' +
            '<button class="btn small danger archive-event-btn" data-id="' + e.EventID + '">Archive</button>' +
            '</div></div></div>'
        );
    });
}

function loadArchived(page) {
    if (page) currentArchivedPage = page;
    $.post(CTRL, { action: 'listArchivedEvents', page: currentArchivedPage }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        $('#archivedCount').text(res.total);
        var $rows = $('#archivedRows').empty();
        $('#noArchived').toggle(res.rows.length === 0);
        res.rows.forEach(function (r) {
            $rows.append(
                '<tr><td>' + $('<div>').text(r.Title).html() + '</td><td>' + r.EventDate + '</td><td>' +
                (r.ArchivedAt || '—') + '</td><td>' + $('<div>').text(r.ArchivedByName || '—').html() + '</td>' +
                '<td><button class="btn small restore-event-btn" data-id="' + r.EventID + '">Restore</button></td></tr>'
            );
        });
        renderArchivedPagination(res.page, res.totalPages, res.total);
    });
}

function renderArchivedPagination(page, totalPages, total) {
    var $pag = $('#archivedPagination').empty();
    if (total === 0) return;
    var $prev = $('<button class="btn small secondary">&laquo; Prev</button>');
    $prev.prop('disabled', page <= 1).on('click', function () { loadArchived(page - 1); });
    $pag.append($prev);
    for (var p = 1; p <= totalPages; p++) {
        var $btn = $('<button class="btn small ' + (p === page ? '' : 'secondary') + '">' + p + '</button>');
        $btn.on('click', (function (pg) { return function () { loadArchived(pg); }; })(p));
        $pag.append($btn);
    }
    var $next = $('<button class="btn small secondary">Next &raquo;</button>');
    $next.prop('disabled', page >= totalPages).on('click', function () { loadArchived(page + 1); });
    $pag.append($next);
}

function openModal(event) {
    $('#eventModalError, #eventModalSuccess').text('');
    $('#eventImageInput').val('');
    if (event) {
        $('#modalTitle').text('Manage Event');
        $('#editEventId').val(event.EventID);
        $('#eTitle').val(event.Title);
        $('#eType').val(event.EventType);
        $('#eDescription').val(event.Description);
        $('#eLocation').val(event.Location);
        $('#eDate').val(event.EventDate);
        $('#eStart').val(event.StartTime);
        $('#eEnd').val(event.EndTime);
        $('#eStatus').val(event.Status);
        $('#uploadEventImgBtn').show();
        if (event.ImagePath) {
            $('#eventImgPreview').attr('src', BASE_URL_JS + event.ImagePath).show();
        } else {
            $('#eventImgPreview').hide();
        }
    } else {
        $('#modalTitle').text('New Health Event');
        $('#editEventId').val('');
        $('#eTitle, #eDescription, #eLocation, #eDate, #eStart, #eEnd').val('');
        $('#eType').val('');
        $('#eStatus').val('Draft');
        $('#eventImgPreview').hide();
        $('#uploadEventImgBtn').hide(); // no event id yet — image upload happens after first save
    }
    $('#eventModal').addClass('open');
}

$(function () {
    loadEvents();
    loadArchived(1);

    $('#archivedToggle').on('click', function () {
        var open = $('#archivedSection').is(':visible');
        $('#archivedSection').toggle(!open);
        $('#archivedCaret').html(open ? '&#9656;' : '&#9662;');
    });

    $('#statusFilter').on('change', renderEvents);
    $('#newEventBtn').on('click', function () { openModal(null); });
    $('#cancelEventBtn').on('click', function () { $('#eventModal').removeClass('open'); });

    $(document).on('click', '.edit-event-btn', function () {
        var id = $(this).data('id');
        $.post(CTRL, { action: 'getEvent', eventId: id }, null, 'json')
        .done(function (res) { if (res.success) openModal(res.event); });
    });

    $(document).on('click', '.archive-event-btn', function () {
        var id = $(this).data('id');
        if (!confirm('Archive this event? It will be hidden from residents and staff but can be restored.')) return;
        $.post(CTRL, { action: 'archiveEvent', eventId: id }, null, 'json').done(function () { loadEvents(); loadArchived(1); });
    });
    $(document).on('click', '.restore-event-btn', function () {
        var id = $(this).data('id');
        $.post(CTRL, { action: 'restoreEvent', eventId: id }, null, 'json').done(function () { loadEvents(); loadArchived(currentArchivedPage); });
    });

    $(document).on('click', '.view-signups-btn', function () {
        var id = $(this).data('id');
        $.post(CTRL, { action: 'listSignups', eventId: id }, null, 'json')
        .done(function (res) {
            if (!res.success) return;
            var $rows = $('#signupRows').empty();
            $('#noSignups').toggle(res.signups.length === 0);
            res.signups.forEach(function (s) {
                $rows.append(
                    '<tr><td>' + $('<div>').text(s.ResidentNumber).html() + '</td><td>' +
                    $('<div>').text(s.FirstName + ' ' + s.LastName).html() + '</td><td>' +
                    $('<div>').text(s.ContactNumber || '—').html() + '</td><td>' + s.SignedUpAt + '</td></tr>'
                );
            });
            $('#signupsModal').addClass('open');
        });
    });
    $('#closeSignupsBtn').on('click', function () { $('#signupsModal').removeClass('open'); });

    $('#uploadEventImgBtn').on('click', function () {
        var fileInput = document.getElementById('eventImageInput');
        var eventId = $('#editEventId').val();
        if (!fileInput.files.length || !eventId) return;
        var formData = new FormData();
        formData.append('action', 'uploadEventImage');
        formData.append('eventId', eventId);
        formData.append('eventImage', fileInput.files[0]);

        $.ajax({ url: CTRL, type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json' })
        .done(function (res) {
            if (res.success) { $('#eventImgPreview').attr('src', res.imagePath).show(); }
            else { $('#eventModalError').text(res.message); }
        });
    });

    $('#saveEventBtn').on('click', function () {
        $('#eventModalError, #eventModalSuccess').text('');
        var id = $('#editEventId').val();

        var payload = {
            action: id ? 'updateEvent' : 'createEvent',
            eventId: id,
            title: $('#eTitle').val().trim(),
            eventType: $('#eType').val(),
            description: $('#eDescription').val().trim(),
            location: $('#eLocation').val().trim(),
            eventDate: $('#eDate').val(),
            startTime: $('#eStart').val(),
            endTime: $('#eEnd').val(),
            status: $('#eStatus').val()
        };

        $.post(CTRL, payload, null, 'json')
        .done(function (res) {
            if (!res.success) { $('#eventModalError').text(res.message); return; }

            var newEventId = id || res.eventId;
            var fileInput = document.getElementById('eventImageInput');

            if (!id && fileInput.files.length) {
                var formData = new FormData();
                formData.append('action', 'uploadEventImage');
                formData.append('eventId', newEventId);
                formData.append('eventImage', fileInput.files[0]);
                $.ajax({ url: CTRL, type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json' })
                .always(function () { finishSave(); });
            } else {
                finishSave();
            }

            function finishSave() {
                $('#eventModalSuccess').text('Saved.');
                setTimeout(function () { $('#eventModal').removeClass('open'); loadEvents(); }, 900);
            }
        });
    });
});
</script>
</body>
</html>
