<?php
require_once __DIR__ . "/../../../core/auth.php";
adminOnly();
$activePage = 'staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/generalize.css">
    <title>Staff Management</title>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/sidebarAdmin.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Staff Management</h2>
            <button class="btn" id="newStaffBtn">+ New Staff/Admin</button>
        </div>

        <div class="card">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
                <tbody id="staffRows"></tbody>
            </table>
        </div>

        <div class="card">
            <div id="archivedToggle" style="cursor:pointer;display:flex;align-items:center;gap:8px;">
                <span id="archivedCaret">&#9656;</span>
                <h3 style="margin:0;">Archived Staff/Admin (<span id="archivedCount">0</span>)</h3>
            </div>
            <div id="archivedSection" style="display:none;margin-top:12px;">
                <table>
                    <thead><tr><th>Name</th><th>Role</th><th>Archived</th><th>Archived By</th><th></th></tr></thead>
                    <tbody id="archivedRows"></tbody>
                </table>
                <p id="noArchived" style="display:none;color:var(--text-muted);">No archived staff/admin accounts.</p>
                <div id="archivedPagination" style="margin-top:12px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="staffModal">
    <div class="modal-box">
        <h3 id="modalTitle">New Staff/Admin</h3>
        <input type="hidden" id="editStaffId">
        <label>First Name</label>
        <input type="text" id="fFirstName">
        <label>Last Name</label>
        <input type="text" id="fLastName">
        <label>Email (Username)</label>
        <input type="email" id="fEmail">
        <label>Role</label>
        <select id="fRole">
            <option value="Staff">Staff</option>
            <option value="Admin">Admin</option>
        </select>
        <div style="margin-top:16px;">
            <button class="btn" id="saveStaffBtn">Save</button>
            <button class="btn secondary" id="cancelStaffBtn">Cancel</button>
        </div>
        <div id="staffModalError" class="error-text"></div>
        <div id="staffModalSuccess" class="success-text"></div>
    </div>
</div>

<div class="modal-overlay" id="resetPassModal">
    <div class="modal-box">
        <h3>Reset Password</h3>
        <input type="hidden" id="resetStaffId">
        <p style="color:var(--text-muted);font-size:0.85rem;margin-top:0;">
            Leave blank to auto-generate a new password. The account will be required to change it on next login.
        </p>
        <label>New Password (optional)</label>
        <input type="text" id="resetNewPassword" placeholder="At least 7 characters, or leave blank">
        <div style="margin-top:16px;">
            <button class="btn" id="confirmResetBtn">Reset Password</button>
            <button class="btn secondary" id="cancelResetBtn">Cancel</button>
        </div>
        <div id="resetModalError" class="error-text"></div>
        <div id="resetModalResult" class="success-text"></div>
    </div>
</div>

<script>
var myStaffId = <?php echo (int)$_SESSION['StaffID']; ?>;

function loadStaff() {
    $.post('app/Controllers/staffAccountController.php', { action: 'listStaff' }, null, 'json')
    .done(function (res) {
        var $rows = $('#staffRows').empty();
        if (!res.success) return;
        res.staff.forEach(function (s) {
            var canDeactivate = s.StaffID != myStaffId;
            $rows.append(
                '<tr>' +
                '<td>' + $('<div>').text(s.FirstName + ' ' + s.LastName).html() + '</td>' +
                '<td>' + $('<div>').text(s.Email).html() + '</td>' +
                '<td>' + s.Role + '</td>' +
                '<td><span class="status-badge status-' + s.Status + '">' + s.Status + '</span></td>' +
                '<td>' +
                '<button class="btn small secondary edit-btn" data-id="' + s.StaffID + '">Edit</button> ' +
                '<button class="btn small secondary reset-pass-btn" data-id="' + s.StaffID + '">Reset Password</button> ' +
                (canDeactivate
                    ? (s.Status === 'Active'
                        ? '<button class="btn small danger deactivate-btn" data-id="' + s.StaffID + '">Deactivate</button>'
                        : '<button class="btn small reactivate-btn" data-id="' + s.StaffID + '">Reactivate</button>') +
                      ' <button class="btn small danger archive-staff-btn" data-id="' + s.StaffID + '">Archive</button>'
                    : '<span style="color:var(--text-muted);font-size:0.8rem;">(you)</span>') +
                '</td></tr>'
            );
        });
    });
}

var currentArchivedPage = 1;

function loadArchivedStaff(page) {
    if (page) currentArchivedPage = page;
    $.post('app/Controllers/staffAccountController.php', { action: 'listArchivedStaff', page: currentArchivedPage }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        $('#archivedCount').text(res.total);
        var $rows = $('#archivedRows').empty();
        $('#noArchived').toggle(res.rows.length === 0);
        res.rows.forEach(function (r) {
            $rows.append(
                '<tr><td>' + $('<div>').text(r.FirstName + ' ' + r.LastName).html() + '</td><td>' + r.Role + '</td><td>' +
                (r.ArchivedAt || '—') + '</td><td>' + $('<div>').text(r.ArchivedByName || '—').html() + '</td>' +
                '<td><button class="btn small restore-staff-btn" data-id="' + r.StaffID + '">Restore</button></td></tr>'
            );
        });
        renderArchivedStaffPagination(res.page, res.totalPages, res.total);
    });
}

function renderArchivedStaffPagination(page, totalPages, total) {
    var $pag = $('#archivedPagination').empty();
    if (total === 0) return;
    var $prev = $('<button class="btn small secondary">&laquo; Prev</button>');
    $prev.prop('disabled', page <= 1).on('click', function () { loadArchivedStaff(page - 1); });
    $pag.append($prev);
    for (var p = 1; p <= totalPages; p++) {
        var $btn = $('<button class="btn small ' + (p === page ? '' : 'secondary') + '">' + p + '</button>');
        $btn.on('click', (function (pg) { return function () { loadArchivedStaff(pg); }; })(p));
        $pag.append($btn);
    }
    var $next = $('<button class="btn small secondary">Next &raquo;</button>');
    $next.prop('disabled', page >= totalPages).on('click', function () { loadArchivedStaff(page + 1); });
    $pag.append($next);
}

function openModal(staff) {
    $('#staffModalError, #staffModalSuccess').text('');
    if (staff) {
        $('#modalTitle').text('Edit Staff/Admin');
        $('#editStaffId').val(staff.StaffID);
        $('#fFirstName').val(staff.FirstName);
        $('#fLastName').val(staff.LastName);
        $('#fEmail').val(staff.Email);
        $('#fRole').val(staff.Role);
    } else {
        $('#modalTitle').text('New Staff/Admin');
        $('#editStaffId').val('');
        $('#fFirstName, #fLastName, #fEmail').val('');
        $('#fRole').val('Staff');
    }
    $('#staffModal').addClass('open');
}

$(function () {
    loadStaff();
    loadArchivedStaff(1);

    $('#archivedToggle').on('click', function () {
        var open = $('#archivedSection').is(':visible');
        $('#archivedSection').toggle(!open);
        $('#archivedCaret').html(open ? '&#9656;' : '&#9662;');
    });

    $(document).on('click', '.archive-staff-btn', function () {
        var id = $(this).data('id');
        if (!confirm('Archive this staff/admin account? It will be hidden from the active list but can be restored later.')) return;
        $.post('app/Controllers/staffAccountController.php', { action: 'archiveStaff', staffId: id }, null, 'json')
        .done(function (res) { if (res.success) { loadStaff(); loadArchivedStaff(1); } else alert(res.message); });
    });
    $(document).on('click', '.restore-staff-btn', function () {
        var id = $(this).data('id');
        $.post('app/Controllers/staffAccountController.php', { action: 'restoreStaff', staffId: id }, null, 'json')
        .done(function () { loadStaff(); loadArchivedStaff(currentArchivedPage); });
    });

    $('#newStaffBtn').on('click', function () { openModal(null); });
    $('#cancelStaffBtn').on('click', function () { $('#staffModal').removeClass('open'); });

    $(document).on('click', '.edit-btn', function () {
        var id = $(this).data('id');
        $.post('app/Controllers/staffAccountController.php', { action: 'getStaff', staffId: id }, null, 'json')
        .done(function (res) { if (res.success) openModal(res.staff); });
    });
    $(document).on('click', '.deactivate-btn', function () {
        if (!confirm('Deactivate this account?')) return;
        $.post('app/Controllers/staffAccountController.php', { action: 'deactivateStaff', staffId: $(this).data('id') }, null, 'json')
        .done(function (res) { if (res.success) loadStaff(); else alert(res.message); });
    });
    $(document).on('click', '.reactivate-btn', function () {
        $.post('app/Controllers/staffAccountController.php', { action: 'reactivateStaff', staffId: $(this).data('id') }, null, 'json')
        .done(function () { loadStaff(); });
    });

    $(document).on('click', '.reset-pass-btn', function () {
        $('#resetModalError, #resetModalResult').text('');
        $('#resetNewPassword').val('');
        $('#resetStaffId').val($(this).data('id'));
        $('#resetPassModal').addClass('open');
    });
    $('#cancelResetBtn').on('click', function () { $('#resetPassModal').removeClass('open'); });
    $('#confirmResetBtn').on('click', function () {
        $('#resetModalError, #resetModalResult').text('');
        $.post('app/Controllers/staffAccountController.php', {
            action: 'resetStaffPassword',
            staffId: $('#resetStaffId').val(),
            newPassword: $('#resetNewPassword').val().trim()
        }, null, 'json')
        .done(function (res) {
            if (res.success) {
                $('#resetModalResult').html('New password: <strong>' + $('<div>').text(res.newPassword).html() + '</strong><br>Share this with the account holder securely.');
            } else {
                $('#resetModalError').text(res.message);
            }
        });
    });

    $('#saveStaffBtn').on('click', function () {
        var id = $('#editStaffId').val();
        var payload = {
            action: id ? 'updateStaff' : 'createStaff',
            staffId: id,
            firstName: $('#fFirstName').val().trim(),
            lastName: $('#fLastName').val().trim(),
            email: $('#fEmail').val().trim(),
            role: $('#fRole').val()
        };
        $.post('app/Controllers/staffAccountController.php', payload, null, 'json')
        .done(function (res) {
            if (res.success) {
                $('#staffModalSuccess').text(res.message || 'Saved.');
                setTimeout(function () { $('#staffModal').removeClass('open'); loadStaff(); }, 800);
            } else {
                $('#staffModalError').text(res.message);
            }
        });
    });
});
</script>
</body>
</html>
