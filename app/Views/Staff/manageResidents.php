<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeStaff();
$activePage = 'residents';
$isAdmin = ($_SESSION['Role'] ?? '') === 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/generalize.css">
    <title>Resident Management</title>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/' . ($isAdmin ? 'sidebarAdmin.php' : 'sidebarStaff.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Resident Management</h2>
            <button class="btn" id="newResidentBtn">+ New Resident</button>
        </div>

        <div class="card">
            <input type="text" id="residentSearch" placeholder="Search by resident #, name, contact, or address..." style="margin-bottom:14px;">
            <table>
                <thead><tr><th>Resident #</th><th>Name</th><th>Contact</th><th>Address</th><th>Status</th><th></th></tr></thead>
                <tbody id="residentRows"></tbody>
            </table>
            <p id="noResults" style="display:none;color:var(--text-muted);">No residents match your search.</p>
        </div>

        <div class="card">
            <div id="archivedToggle" style="cursor:pointer;display:flex;align-items:center;gap:8px;">
                <span id="archivedCaret">&#9656;</span>
                <h3 style="margin:0;">Archived Residents (<span id="archivedCount">0</span>)</h3>
            </div>
            <div id="archivedSection" style="display:none;margin-top:12px;">
                <table>
                    <thead><tr><th>Resident #</th><th>Name</th><th>Archived</th><th>Archived By</th><th></th></tr></thead>
                    <tbody id="archivedRows"></tbody>
                </table>
                <p id="noArchived" style="display:none;color:var(--text-muted);">No archived residents.</p>
                <div id="archivedPagination" style="margin-top:12px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="residentModal">
    <div class="modal-box" style="max-width:640px;max-height:85vh;overflow-y:auto;">
        <h3 id="modalTitle">New Resident</h3>
        <input type="hidden" id="editResidentId">

        <h4>Personal Information</h4>
        <label>Resident Number (Username)</label>
        <input type="text" id="rNumber">
        <label>First Name</label>
        <input type="text" id="rFirstName">
        <label>Middle Name</label>
        <input type="text" id="rMiddleName">
        <label>Last Name</label>
        <input type="text" id="rLastName">
        <label>Extension Name (Jr., Sr., III...)</label>
        <input type="text" id="rExtensionName">
        <label>Civil Status</label>
        <select id="rCivilStatus">
            <option value="">Select...</option>
            <option value="Single">Single</option>
            <option value="Married">Married</option>
            <option value="Widowed">Widowed</option>
            <option value="Separated">Separated</option>
            <option value="Divorced">Divorced</option>
        </select>
        <label>Gender</label>
        <select id="rGender">
            <option value="">Select...</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>
        <label>Birthday</label>
        <input type="date" id="rBirthday">
        <label>Bio (optional)</label>
        <textarea id="rBio" rows="2"></textarea>

        <h4>Contact Information</h4>
        <label>Address</label>
        <input type="text" id="rAddress">
        <label>2nd Address (optional)</label>
        <input type="text" id="rAddress2">
        <label>Contact Number</label>
        <input type="text" id="rContact" inputmode="numeric" maxlength="11" placeholder="11-digit number">
        <label>Home Phone (optional)</label>
        <input type="text" id="rHomePhone" inputmode="numeric" maxlength="11">
        <label>Email (optional)</label>
        <input type="email" id="rEmail">

        <h4>Emergency Contact</h4>
        <label>Name</label>
        <input type="text" id="rEmergencyName">
        <label>Relationship</label>
        <input type="text" id="rEmergencyRelationship">
        <label>Number</label>
        <input type="text" id="rEmergencyNumber" inputmode="numeric" maxlength="11">

        <h4>Occupation (optional)</h4>
        <label>Occupation</label>
        <input type="text" id="rOccupation">
        <label>Occupation Location</label>
        <input type="text" id="rOccupationLocation">

        <div id="spouseSection" style="display:none;">
            <h4>Spouse Information</h4>
            <label>Spouse Name</label>
            <input type="text" id="sName">
            <label>Spouse Birthdate</label>
            <input type="date" id="sBirthdate">
            <label>Spouse Civil Status</label>
            <select id="sCivilStatus">
                <option value="">Select...</option>
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Widowed">Widowed</option>
                <option value="Separated">Separated</option>
                <option value="Divorced">Divorced</option>
            </select>
            <label>Spouse Status</label>
            <select id="sStatus">
                <option value="Alive">Alive</option>
                <option value="Deceased">Deceased</option>
            </select>
        </div>

        <div id="childrenSection" style="display:none;">
            <h4>Children</h4>
            <div id="childrenRows"></div>
            <button type="button" class="btn small secondary" id="addChildRowBtn">+ Add Child</button>
            <p style="color:var(--text-muted);font-size:0.82rem;">Children are saved individually once the resident record exists.</p>
        </div>

        <div style="margin-top:16px;">
            <button class="btn" id="saveResidentBtn">Save</button>
            <button class="btn secondary" id="cancelResidentBtn">Cancel</button>
        </div>
        <div id="residentModalError" class="error-text"></div>
        <div id="residentModalSuccess" class="success-text"></div>
    </div>
</div>

<div class="modal-overlay" id="resetPassModal">
    <div class="modal-box">
        <h3>Reset Password</h3>
        <input type="hidden" id="resetResidentId">
        <p style="color:var(--text-muted);font-size:0.85rem;margin-top:0;">
            Leave blank to auto-generate a new password. The resident will be required to change it on next login.
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
var allResidents = [];
var currentChildren = []; // children rows currently in the modal (existing + new)
var currentArchivedPage = 1;
var CTRL = 'app/Controllers/residentAccountController.php';

function loadResidents() {
    $.post(CTRL, { action: 'listResidents' }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        allResidents = res.residents;
        renderResidentRows(allResidents);
    });
}

function renderResidentRows(list) {
    var $rows = $('#residentRows').empty();
    $('#noResults').toggle(list.length === 0);
    list.forEach(function (r) {
        $rows.append(
            '<tr>' +
            '<td>' + $('<div>').text(r.ResidentNumber).html() + '</td>' +
            '<td>' + $('<div>').text(r.FirstName + ' ' + r.LastName).html() + '</td>' +
            '<td>' + $('<div>').text(r.ContactNumber || '—').html() + '</td>' +
            '<td>' + $('<div>').text(r.Address || '—').html() + '</td>' +
            '<td><span class="status-badge status-' + r.Status + '">' + r.Status + '</span></td>' +
            '<td>' +
            '<button class="btn small secondary edit-btn" data-id="' + r.ResidentID + '">Edit</button> ' +
            '<button class="btn small secondary reset-pass-btn" data-id="' + r.ResidentID + '">Reset Password</button> ' +
            (r.Status === 'Active'
                ? '<button class="btn small danger deactivate-btn" data-id="' + r.ResidentID + '">Deactivate</button>'
                : '<button class="btn small reactivate-btn" data-id="' + r.ResidentID + '">Reactivate</button>') + ' ' +
            '<button class="btn small danger archive-btn" data-id="' + r.ResidentID + '">Archive</button>' +
            '</td></tr>'
        );
    });
}

function filterResidents() {
    var q = $('#residentSearch').val().trim().toLowerCase();
    if (!q) { renderResidentRows(allResidents); return; }
    var filtered = allResidents.filter(function (r) {
        return (r.ResidentNumber || '').toLowerCase().includes(q) ||
               (r.FirstName + ' ' + r.LastName).toLowerCase().includes(q) ||
               (r.ContactNumber || '').toLowerCase().includes(q) ||
               (r.Address || '').toLowerCase().includes(q);
    });
    renderResidentRows(filtered);
}

function loadArchived(page) {
    if (page) currentArchivedPage = page;
    $.post(CTRL, { action: 'listArchivedResidents', page: currentArchivedPage }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        $('#archivedCount').text(res.total);
        var $rows = $('#archivedRows').empty();
        $('#noArchived').toggle(res.rows.length === 0);
        res.rows.forEach(function (r) {
            $rows.append(
                '<tr>' +
                '<td>' + $('<div>').text(r.ResidentNumber).html() + '</td>' +
                '<td>' + $('<div>').text(r.FirstName + ' ' + r.LastName).html() + '</td>' +
                '<td>' + (r.ArchivedAt || '—') + '</td>' +
                '<td>' + $('<div>').text(r.ArchivedByName || '—').html() + '</td>' +
                '<td><button class="btn small restore-btn" data-id="' + r.ResidentID + '">Restore</button></td>' +
                '</tr>'
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

function toggleSpouseSection() {
    $('#spouseSection').toggle($('#rCivilStatus').val() === 'Married');
}

function renderChildRow(child) {
    var idx = currentChildren.length;
    currentChildren.push(child || { ChildID: null, LastName: '', FirstName: '', Birthdate: '', Gender: '', Status: 'Living' });
    var c = currentChildren[idx];
    var $row = $(
        '<div class="child-row" data-idx="' + idx + '" style="border:1px solid var(--border,#ddd);padding:10px;margin-bottom:8px;border-radius:6px;">' +
        '<div style="display:flex;gap:8px;flex-wrap:wrap;">' +
        '<input type="text" class="c-lastname" placeholder="Last name" style="flex:1;min-width:120px;" value="' + $('<div>').text(c.LastName || '').html() + '">' +
        '<input type="text" class="c-firstname" placeholder="First name" style="flex:1;min-width:120px;" value="' + $('<div>').text(c.FirstName || '').html() + '">' +
        '<input type="date" class="c-birthdate" style="flex:1;min-width:140px;" value="' + (c.Birthdate || '') + '">' +
        '<select class="c-gender" style="flex:1;min-width:100px;">' +
        '<option value="">Gender</option><option value="Male"' + (c.Gender==='Male'?' selected':'') + '>Male</option>' +
        '<option value="Female"' + (c.Gender==='Female'?' selected':'') + '>Female</option>' +
        '<option value="Other"' + (c.Gender==='Other'?' selected':'') + '>Other</option></select>' +
        '<select class="c-status" style="flex:1;min-width:100px;">' +
        '<option value="Living"' + (c.Status==='Living'?' selected':'') + '>Living</option>' +
        '<option value="Deceased"' + (c.Status==='Deceased'?' selected':'') + '>Deceased</option></select>' +
        '<button type="button" class="btn small danger remove-child-btn">Remove</button>' +
        '</div></div>'
    );
    $('#childrenRows').append($row);
}

function openModal(resident) {
    $('#residentModalError, #residentModalSuccess').text('');
    currentChildren = [];
    $('#childrenRows').empty();

    if (resident) {
        $('#modalTitle').text('Edit Resident');
        $('#editResidentId').val(resident.ResidentID);
        $('#rNumber').val(resident.ResidentNumber);
        $('#rFirstName').val(resident.FirstName);
        $('#rMiddleName').val(resident.MiddleName);
        $('#rLastName').val(resident.LastName);
        $('#rExtensionName').val(resident.ExtensionName);
        $('#rCivilStatus').val(resident.CivilStatus);
        $('#rGender').val(resident.Gender);
        $('#rBirthday').val(resident.Birthday);
        $('#rBio').val(resident.Bio);
        $('#rAddress').val(resident.Address);
        $('#rAddress2').val(resident.Address2);
        $('#rContact').val(resident.ContactNumber);
        $('#rHomePhone').val(resident.HomePhone);
        $('#rEmail').val(resident.Email);
        $('#rEmergencyName').val(resident.EmergencyContactName);
        $('#rEmergencyRelationship').val(resident.EmergencyContactRelationship);
        $('#rEmergencyNumber').val(resident.EmergencyContactNumber);
        $('#rOccupation').val(resident.Occupation);
        $('#rOccupationLocation').val(resident.OccupationLocation);

        if (resident.spouse) {
            $('#sName').val(resident.spouse.SpouseName);
            $('#sBirthdate').val(resident.spouse.SpouseBirthdate);
            $('#sCivilStatus').val(resident.spouse.SpouseCivilStatus);
            $('#sStatus').val(resident.spouse.SpouseStatus);
        } else {
            $('#sName, #sBirthdate, #sCivilStatus').val('');
            $('#sStatus').val('Alive');
        }

        $('#childrenSection').show();
        (resident.children || []).forEach(function (c) { renderChildRow(c); });
    } else {
        $('#modalTitle').text('New Resident');
        $('#editResidentId').val('');
        $('#residentModal .modal-box input[type=text], #residentModal .modal-box input[type=email], #residentModal .modal-box input[type=date], #residentModal .modal-box textarea').val('');
        $('#rCivilStatus, #rGender, #sCivilStatus').val('');
        $('#sStatus').val('Alive');
        $('#childrenSection').hide(); // children added after the resident exists
    }
    toggleSpouseSection();
    $('#residentModal').addClass('open');
}

$(function () {
    loadResidents();
    loadArchived(1);

    $('#archivedToggle').on('click', function () {
        var open = $('#archivedSection').is(':visible');
        $('#archivedSection').toggle(!open);
        $('#archivedCaret').html(open ? '&#9656;' : '&#9662;');
    });

    $('#residentSearch').on('input', filterResidents);
    $('#newResidentBtn').on('click', function () { openModal(null); });
    $('#cancelResidentBtn').on('click', function () { $('#residentModal').removeClass('open'); });
    $('#rCivilStatus').on('change', toggleSpouseSection);

    $('#rContact, #rHomePhone, #rEmergencyNumber').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });

    $('#addChildRowBtn').on('click', function () { renderChildRow(null); });

    $(document).on('click', '.remove-child-btn', function () {
        var $row = $(this).closest('.child-row');
        var idx = parseInt($row.data('idx'));
        var child = currentChildren[idx];
        var residentId = $('#editResidentId').val();
        if (child.ChildID && residentId) {
            if (!confirm('Remove this child record? This cannot be undone.')) return;
            $.post(CTRL, { action: 'deleteChild', childId: child.ChildID, residentId: residentId }, null, 'json')
            .done(function () { $row.remove(); });
        } else {
            $row.remove();
        }
    });

    $(document).on('click', '.edit-btn', function () {
        var id = $(this).data('id');
        $.post(CTRL, { action: 'getResident', residentId: id }, null, 'json')
        .done(function (res) { if (res.success) openModal(res.resident); });
    });

    $(document).on('click', '.deactivate-btn', function () {
        var id = $(this).data('id');
        if (!confirm('Deactivate this resident account?')) return;
        $.post(CTRL, { action: 'deactivateResident', residentId: id }, null, 'json').done(function () { loadResidents(); });
    });
    $(document).on('click', '.reactivate-btn', function () {
        var id = $(this).data('id');
        $.post(CTRL, { action: 'reactivateResident', residentId: id }, null, 'json').done(function () { loadResidents(); });
    });
    $(document).on('click', '.archive-btn', function () {
        var id = $(this).data('id');
        if (!confirm('Archive this resident? They will be hidden from the active list but can be restored later.')) return;
        $.post(CTRL, { action: 'archiveResident', residentId: id }, null, 'json').done(function () { loadResidents(); loadArchived(1); });
    });
    $(document).on('click', '.restore-btn', function () {
        var id = $(this).data('id');
        $.post(CTRL, { action: 'restoreResident', residentId: id }, null, 'json').done(function () { loadResidents(); loadArchived(currentArchivedPage); });
    });

    $(document).on('click', '.reset-pass-btn', function () {
        $('#resetModalError, #resetModalResult').text('');
        $('#resetNewPassword').val('');
        $('#resetResidentId').val($(this).data('id'));
        $('#resetPassModal').addClass('open');
    });
    $('#cancelResetBtn').on('click', function () { $('#resetPassModal').removeClass('open'); });
    $('#confirmResetBtn').on('click', function () {
        $('#resetModalError, #resetModalResult').text('');
        $.post(CTRL, {
            action: 'resetResidentPassword',
            residentId: $('#resetResidentId').val(),
            newPassword: $('#resetNewPassword').val().trim()
        }, null, 'json')
        .done(function (res) {
            if (res.success) {
                $('#resetModalResult').html('New password: <strong>' + $('<div>').text(res.newPassword).html() + '</strong><br>Share this with the resident securely.');
            } else {
                $('#resetModalError').text(res.message);
            }
        });
    });

    $('#saveResidentBtn').on('click', function () {
        $('#residentModalError, #residentModalSuccess').text('');
        var id = $('#editResidentId').val();

        var payload = {
            action: id ? 'updateResident' : 'createResident',
            residentId: id,
            residentNumber: $('#rNumber').val().trim(),
            firstName: $('#rFirstName').val().trim(),
            middleName: $('#rMiddleName').val().trim(),
            lastName: $('#rLastName').val().trim(),
            extensionName: $('#rExtensionName').val().trim(),
            civilStatus: $('#rCivilStatus').val(),
            gender: $('#rGender').val(),
            birthday: $('#rBirthday').val(),
            bio: $('#rBio').val().trim(),
            address: $('#rAddress').val().trim(),
            address2: $('#rAddress2').val().trim(),
            contactNumber: $('#rContact').val().trim(),
            homePhone: $('#rHomePhone').val().trim(),
            email: $('#rEmail').val().trim(),
            emergencyContactName: $('#rEmergencyName').val().trim(),
            emergencyContactRelationship: $('#rEmergencyRelationship').val().trim(),
            emergencyContactNumber: $('#rEmergencyNumber').val().trim(),
            occupation: $('#rOccupation').val().trim(),
            occupationLocation: $('#rOccupationLocation').val().trim()
        };

        if (payload.civilStatus === 'Married') {
            payload.spouseName = $('#sName').val().trim();
            payload.spouseBirthdate = $('#sBirthdate').val();
            payload.spouseCivilStatus = $('#sCivilStatus').val();
            payload.spouseStatus = $('#sStatus').val();
        }

        $.post(CTRL, payload, null, 'json')
        .done(function (res) {
            if (!res.success) { $('#residentModalError').text(res.message); return; }

            var residentId = id || res.residentId;
            var childPromises = [];
            $('.child-row').each(function () {
                var idx = parseInt($(this).data('idx'));
                var child = currentChildren[idx];
                var childPayload = {
                    residentId: residentId,
                    lastName: $(this).find('.c-lastname').val().trim(),
                    firstName: $(this).find('.c-firstname').val().trim(),
                    birthdate: $(this).find('.c-birthdate').val(),
                    gender: $(this).find('.c-gender').val(),
                    status: $(this).find('.c-status').val()
                };
                childPayload.action = child.ChildID ? 'updateChild' : 'addChild';
                if (child.ChildID) childPayload.childId = child.ChildID;
                childPromises.push($.post(CTRL, childPayload, null, 'json'));
            });

            $.when.apply($, childPromises).always(function () {
                $('#residentModalSuccess').text(res.message || 'Saved.');
                setTimeout(function () { $('#residentModal').removeClass('open'); loadResidents(); }, 1200);
            });
        });
    });
});
</script>
</body>
</html>
