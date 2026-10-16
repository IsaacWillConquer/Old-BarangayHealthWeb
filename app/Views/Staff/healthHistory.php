<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeStaff();
$activePage = 'healthHistory';
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
    <title>Health History</title>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/' . ($isAdmin ? 'sidebarAdmin.php' : 'sidebarStaff.php'); ?>

    <div class="main-content">
        <div class="page-header"><h2>Health History</h2></div>

        <div class="card">
            <label>Search Resident</label>
            <input type="text" id="residentSearch" placeholder="Search by resident #, name, or address...">
            <div id="searchResults" style="margin-top:8px;max-height:220px;overflow-y:auto;"></div>
        </div>

        <div class="card" id="historyCard" style="display:none;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                <h3 id="selectedResidentName" style="margin:0;"></h3>
                <button class="btn small" id="addRecordBtn">+ Add Record</button>
            </div>
            <label style="margin-top:10px;"><input type="checkbox" id="includeVoided"> Show voided records</label>
            <table>
                <thead><tr><th>Date</th><th>Condition</th><th>Status</th><th>Notes</th><th>Recorded By</th><th></th></tr></thead>
                <tbody id="historyRows"></tbody>
            </table>
            <p id="noHistory" style="display:none;color:var(--text-muted);">No health history recorded yet.</p>
        </div>
    </div>
</div>

<div class="modal-overlay" id="recordModal">
    <div class="modal-box">
        <h3 id="recordModalTitle">Add Health Record</h3>
        <input type="hidden" id="editRecordId">
        <label>Condition</label>
        <select id="hCondition">
            <option value="">Select...</option>
            <option value="Hypertension">Hypertension</option>
            <option value="Diabetes">Diabetes</option>
            <option value="Asthma">Asthma</option>
            <option value="Tuberculosis">Tuberculosis (TB)</option>
            <option value="Dengue">Dengue</option>
            <option value="HeartDisease">Heart Disease</option>
            <option value="Allergy">Allergy</option>
            <option value="PregnancyRelated">Pregnancy-related</option>
            <option value="COVID19">COVID-19</option>
            <option value="Malnutrition">Malnutrition</option>
            <option value="Disability">Disability</option>
            <option value="SeniorCareConcern">Senior/Elderly Care Concern</option>
            <option value="Other">Other</option>
        </select>
        <div id="otherConditionWrap" style="display:none;">
            <label>Specify Condition</label>
            <input type="text" id="hOtherText">
        </div>
        <label>Record Date</label>
        <input type="date" id="hDate">
        <label>Status</label>
        <select id="hStatus">
            <option value="Ongoing">Ongoing</option>
            <option value="Resolved">Resolved</option>
            <option value="Chronic">Chronic</option>
            <option value="Monitoring">Monitoring</option>
        </select>
        <label>Notes (optional)</label>
        <textarea id="hNotes" rows="3"></textarea>
        <div style="margin-top:16px;">
            <button class="btn" id="saveRecordBtn">Save</button>
            <button class="btn secondary" id="cancelRecordBtn">Cancel</button>
        </div>
        <div id="recordModalError" class="error-text"></div>
    </div>
</div>

<script>
var HIST_CTRL = 'app/Controllers/healthHistoryController.php';
var selectedResidentId = null;
var allResidentsCache = [];

function loadResidentList() {
    $.post(HIST_CTRL, { action: 'searchResidents' }, null, 'json')
    .done(function (res) { if (res.success) allResidentsCache = res.residents; });
}

function toggleOtherCondition() {
    $('#otherConditionWrap').toggle($('#hCondition').val() === 'Other');
}

function selectResident(r) {
    selectedResidentId = r.ResidentID;
    $('#selectedResidentName').text(r.FirstName + ' ' + r.LastName + ' (' + r.ResidentNumber + ')');
    $('#historyCard').show();
    $('#searchResults').empty();
    $('#residentSearch').val('');
    loadHistory();
}

function loadHistory() {
    if (!selectedResidentId) return;
    $.post(HIST_CTRL, {
        action: 'listRecords', residentId: selectedResidentId,
        includeVoided: $('#includeVoided').is(':checked') ? 1 : 0
    }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        var $rows = $('#historyRows').empty();
        $('#noHistory').toggle(res.records.length === 0);
        res.records.forEach(function (r) {
            var condLabel = r.ConditionType === 'Other' ? r.OtherConditionText : r.ConditionType;
            var voidedTag = r.IsVoided ? ' <span style="color:#b3462c;font-size:0.78rem;">(voided)</span>' : '';
            var actions = r.IsVoided
                ? '<button class="btn small restore-record-btn" data-id="' + r.RecordID + '">Restore</button>'
                : '<button class="btn small secondary edit-record-btn" data-id="' + r.RecordID + '">Edit</button> ' +
                  '<button class="btn small danger void-record-btn" data-id="' + r.RecordID + '">Void</button>';
            $rows.append(
                '<tr><td>' + r.RecordDate + '</td><td>' + $('<div>').text(condLabel).html() + voidedTag + '</td><td>' +
                r.Status + '</td><td>' + $('<div>').text(r.Notes || '—').html() + '</td><td>' +
                $('<div>').text(r.RecordedByName || '—').html() + '</td><td>' + actions + '</td></tr>'
            );
        });
    });
}

$(function () {
    loadResidentList();

    $('#residentSearch').on('input', function () {
        var q = $(this).val().trim().toLowerCase();
        var $results = $('#searchResults').empty();
        if (!q) return;
        var matches = allResidentsCache.filter(function (r) {
            return (r.ResidentNumber || '').toLowerCase().includes(q) ||
                   (r.FirstName + ' ' + r.LastName).toLowerCase().includes(q) ||
                   (r.Address || '').toLowerCase().includes(q);
        }).slice(0, 15);
        matches.forEach(function (r) {
            var $item = $('<div style="padding:8px;border-bottom:1px solid var(--border,#eee);cursor:pointer;">' +
                $('<div>').text(r.ResidentNumber + ' — ' + r.FirstName + ' ' + r.LastName).html() + '</div>');
            $item.on('click', function () { selectResident(r); });
            $results.append($item);
        });
    });

    $('#includeVoided').on('change', loadHistory);
    $('#hCondition').on('change', toggleOtherCondition);

    $('#addRecordBtn').on('click', function () {
        $('#recordModalTitle').text('Add Health Record');
        $('#editRecordId').val('');
        $('#hCondition, #hOtherText, #hDate, #hNotes').val('');
        $('#hStatus').val('Ongoing');
        toggleOtherCondition();
        $('#recordModalError').text('');
        $('#recordModal').addClass('open');
    });

    $(document).on('click', '.edit-record-btn', function () {
        var id = $(this).data('id');
        $.post(HIST_CTRL, { action: 'listRecords', residentId: selectedResidentId, includeVoided: 1 }, null, 'json')
        .done(function (res) {
            var record = res.records.find(function (r) { return r.RecordID == id; });
            if (!record) return;
            $('#recordModalTitle').text('Edit Health Record');
            $('#editRecordId').val(record.RecordID);
            $('#hCondition').val(record.ConditionType);
            $('#hOtherText').val(record.OtherConditionText);
            $('#hDate').val(record.RecordDate);
            $('#hStatus').val(record.Status);
            $('#hNotes').val(record.Notes);
            toggleOtherCondition();
            $('#recordModalError').text('');
            $('#recordModal').addClass('open');
        });
    });

    $('#cancelRecordBtn').on('click', function () { $('#recordModal').removeClass('open'); });

    $('#saveRecordBtn').on('click', function () {
        $('#recordModalError').text('');
        var id = $('#editRecordId').val();
        var payload = {
            action: id ? 'updateRecord' : 'addRecord',
            recordId: id,
            residentId: selectedResidentId,
            conditionType: $('#hCondition').val(),
            otherConditionText: $('#hOtherText').val().trim(),
            recordDate: $('#hDate').val(),
            status: $('#hStatus').val(),
            notes: $('#hNotes').val().trim()
        };
        $.post(HIST_CTRL, payload, null, 'json')
        .done(function (res) {
            if (!res.success) { $('#recordModalError').text(res.message); return; }
            $('#recordModal').removeClass('open');
            loadHistory();
        });
    });

    $(document).on('click', '.void-record-btn', function () {
        var id = $(this).data('id');
        if (!confirm('Void this health record? It will be hidden from the resident but kept in the archive and can be restored.')) return;
        $.post(HIST_CTRL, { action: 'voidRecord', recordId: id }, null, 'json').done(function () { loadHistory(); });
    });
    $(document).on('click', '.restore-record-btn', function () {
        var id = $(this).data('id');
        $.post(HIST_CTRL, { action: 'restoreRecord', recordId: id }, null, 'json').done(function () { loadHistory(); });
    });
});
</script>
</body>
</html>
