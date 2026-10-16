<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeStaff();
$activePage = 'reports';
$isAdmin = ($_SESSION['Role'] ?? '') === 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/generalize.css">
    <title>Reports</title>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/' . ($isAdmin ? 'sidebarAdmin.php' : 'sidebarStaff.php'); ?>

    <div class="main-content">
        <div class="page-header"><h2>Reports</h2></div>

        <div class="card">
            <h3>Survey Results Report</h3>
            <label>Select Survey</label>
            <select id="surveySelect"></select>
            <div id="reportMeta" style="margin:12px 0;color:var(--text-muted);font-size:0.88rem;"></div>
            <div id="reportArea"></div>
            <button class="btn small no-print" id="printReportBtn" style="margin-top:10px;">Print Report</button>
        </div>

        <div class="card">
            <h3>Health Events Report</h3>
            <div class="no-print" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:10px;">
                <div><label>From</label><input type="date" id="eventsStartDate"></div>
                <div><label>To</label><input type="date" id="eventsEndDate"></div>
                <button class="btn small secondary" id="applyEventsRangeBtn">Apply</button>
            </div>
            <div id="eventsReportMeta" style="color:var(--text-muted);font-size:0.88rem;margin-bottom:8px;"></div>
            <canvas id="eventsChart" height="110"></canvas>
            <canvas id="eventsTypeChart" height="110" style="margin-top:20px;"></canvas>
            <button class="btn small no-print" id="printEventsReportBtn" style="margin-top:10px;">Print Report</button>
        </div>

        <div class="card">
            <h3>Health History Report</h3>
            <div class="no-print" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:10px;">
                <div><label>From</label><input type="date" id="historyStartDate"></div>
                <div><label>To</label><input type="date" id="historyEndDate"></div>
                <label style="margin-bottom:8px;"><input type="checkbox" id="historyIncludeVoided"> Include voided</label>
                <button class="btn small secondary" id="applyHistoryRangeBtn">Apply</button>
            </div>
            <canvas id="conditionChart" height="110"></canvas>
            <canvas id="statusChart" height="110" style="margin-top:20px;"></canvas>
            <h4 style="margin-top:18px;">Recent Entries</h4>
            <table>
                <thead><tr><th>Date</th><th>Resident</th><th>Condition</th><th>Status</th><th>Recorded By</th></tr></thead>
                <tbody id="historyReportRows"></tbody>
            </table>
            <button class="btn small no-print" id="printHistoryReportBtn" style="margin-top:10px;">Print Report</button>
        </div>

        <div class="card no-print">
            <h3>Print Resident Information</h3>
            <label>Search Resident</label>
            <input type="text" id="printResidentSearch" placeholder="Search by resident #, name, or address...">
            <div id="printSearchResults" style="margin-top:8px;max-height:200px;overflow-y:auto;"></div>
        </div>

        <div class="card no-print">
            <h3>Activity Logs</h3>
            <div style="margin-bottom:10px;">
                <select id="actorFilter" style="width:auto;display:inline-block;margin-right:8px;">
                    <option value="">All Actors</option>
                    <option value="StaffAdmin">Staff/Admin</option>
                    <option value="Resident">Resident</option>
                </select>
                <button class="btn small secondary" id="refreshLogsBtn">Refresh</button>
            </div>
            <table>
                <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Target</th><th>IP</th></tr></thead>
                <tbody id="logRows"></tbody>
            </table>
            <div id="logPagination" style="margin-top:12px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
        </div>
    </div>
</div>

<!-- Printable resident profile, hidden until Print is triggered -->
<div id="residentPrintArea" style="display:none;"></div>

<script>
var activeCharts = [];

function destroyCharts() {
    activeCharts.forEach(function (c) { c.destroy(); });
    activeCharts = [];
}

function loadSurveySelect() {
    $.post('app/Controllers/surveyController.php', { action: 'listSurveys' }, null, 'json')
    .done(function (res) {
        var $sel = $('#surveySelect').empty();
        if (!res.success) return;
        res.surveys.forEach(function (s) {
            $sel.append('<option value="' + s.SurveyID + '">' + $('<div>').text(s.Title).html() + ' (' + s.Status + ')</option>');
        });
        if (res.surveys.length) loadReport(res.surveys[0].SurveyID);
    });
}

function loadReport(surveyId) {
    $.post('app/Controllers/surveyController.php', { action: 'getSurveyDetail', surveyId: surveyId }, null, 'json')
    .done(function (detailRes) {
        if (!detailRes.success) return;
        var s = detailRes.survey;
        $('#reportMeta').text(s.participantCount + ' respondent(s) — Status: ' + s.Status);

        $.post('app/Controllers/surveyController.php', { action: 'getSurveyResults', surveyId: surveyId }, null, 'json')
        .done(function (res) {
            var $area = $('#reportArea').empty();
            if (!res.success || res.results.length === 0) {
                $area.html('<p style="color:var(--text-muted);">No responses yet.</p>');
                return;
            }
            res.results.forEach(function (r, idx) {
                var q = s.questions.find(function (q) { return q.QuestionID == r.QuestionID; });
                var title = q ? q.QuestionText : ('Question #' + r.QuestionID);
                var block = $('<div class="card" style="background:#fafcfa;margin-bottom:16px;"></div>');
                block.append('<h4>' + $('<div>').text(title).html() + '</h4>');

                if (r.Type === 'Rating' && r.average !== undefined) {
                    block.append('<p style="color:var(--gold);font-weight:600;">Average rating: ' + (r.average ?? '—') + '</p>');
                }

                if (r.Type === 'ShortAnswer' && r.rawAnswers) {
                    var list = $('<ul class="activity-feed"></ul>');
                    r.rawAnswers.forEach(function (a) { list.append('<li>' + $('<div>').text(a).html() + '</li>'); });
                    block.append(list);
                } else {
                    var canvasId = 'chart_' + r.QuestionID + '_' + idx;
                    block.append('<canvas id="' + canvasId + '" height="120"></canvas>');
                    $area.append(block);
                    var labels = r.tally.map(function (t) { return String(t.value); });
                    var data = r.tally.map(function (t) { return t.count; });
                    var chart = new Chart(document.getElementById(canvasId), {
                        type: 'bar',
                        data: { labels: labels, datasets: [{ label: 'Responses', data: data, backgroundColor: '#3f6d54', borderRadius: 4 }] },
                        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                    });
                    activeCharts.push(chart);
                    return;
                }
                $area.append(block);
            });
        });
    });
}

// ---------- Health Events Report ----------
var eventsChartInst = null, eventsTypeChartInst = null;

function loadEventsReport() {
    var payload = { action: 'getEventsReport', startDate: $('#eventsStartDate').val(), endDate: $('#eventsEndDate').val() };
    $.post('app/Controllers/reportsController.php', payload, null, 'json')
    .done(function (res) {
        if (!res.success) { alert(res.message); return; }
        $('#eventsStartDate').val(res.startDate);
        $('#eventsEndDate').val(res.endDate);
        $('#eventsReportMeta').text(res.events.length + ' event(s) in this range');

        if (eventsChartInst) eventsChartInst.destroy();
        eventsChartInst = new Chart(document.getElementById('eventsChart'), {
            type: 'bar',
            data: {
                labels: res.events.map(function (e) { return e.Title; }),
                datasets: [{ label: 'Sign-ups', data: res.events.map(function (e) { return e.SignupCount; }), backgroundColor: '#3f6d54', borderRadius: 4 }]
            },
            options: { plugins: { legend: { display: false }, title: { display: true, text: 'Sign-ups per Event' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        if (eventsTypeChartInst) eventsTypeChartInst.destroy();
        var typeLabels = Object.keys(res.byType);
        eventsTypeChartInst = new Chart(document.getElementById('eventsTypeChart'), {
            type: 'bar',
            data: { labels: typeLabels, datasets: [{ label: 'Events', data: typeLabels.map(function (t) { return res.byType[t]; }), backgroundColor: '#8a9a5b', borderRadius: 4 }] },
            options: { plugins: { legend: { display: false }, title: { display: true, text: 'Events by Type' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    });
}

// ---------- Health History Report ----------
var conditionChartInst = null, statusChartInst = null;

function loadHistoryReport() {
    var payload = {
        action: 'getHealthHistoryReport', startDate: $('#historyStartDate').val(), endDate: $('#historyEndDate').val(),
        includeVoided: $('#historyIncludeVoided').is(':checked') ? 1 : 0
    };
    $.post('app/Controllers/reportsController.php', payload, null, 'json')
    .done(function (res) {
        if (!res.success) { alert(res.message); return; }
        $('#historyStartDate').val(res.startDate);
        $('#historyEndDate').val(res.endDate);

        if (conditionChartInst) conditionChartInst.destroy();
        conditionChartInst = new Chart(document.getElementById('conditionChart'), {
            type: 'bar',
            data: {
                labels: res.conditionSummary.map(function (c) { return c.ConditionType; }),
                datasets: [{ label: 'Residents', data: res.conditionSummary.map(function (c) { return c.Total; }), backgroundColor: '#3f6d54', borderRadius: 4 }]
            },
            options: { plugins: { legend: { display: false }, title: { display: true, text: 'Residents per Condition' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        if (statusChartInst) statusChartInst.destroy();
        statusChartInst = new Chart(document.getElementById('statusChart'), {
            type: 'bar',
            data: {
                labels: res.statusSummary.map(function (s) { return s.Status; }),
                datasets: [{ label: 'Records', data: res.statusSummary.map(function (s) { return s.Total; }), backgroundColor: '#8a9a5b', borderRadius: 4 }]
            },
            options: { plugins: { legend: { display: false }, title: { display: true, text: 'Ongoing / Resolved / Chronic / Monitoring' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        var $rows = $('#historyReportRows').empty();
        res.recent.forEach(function (r) {
            var condLabel = r.ConditionType === 'Other' ? r.OtherConditionText : r.ConditionType;
            $rows.append(
                '<tr><td>' + r.RecordDate + '</td><td>' + $('<div>').text(r.ResidentName).html() + '</td><td>' +
                $('<div>').text(condLabel).html() + (r.IsVoided ? ' (voided)' : '') + '</td><td>' + r.Status + '</td><td>' +
                $('<div>').text(r.RecordedByName || '—').html() + '</td></tr>'
            );
        });
    });
}

// ---------- Resident Print ----------
var printResidentsCache = [];

function loadPrintResidentList() {
    $.post('app/Controllers/residentAccountController.php', { action: 'listResidents' }, null, 'json')
    .done(function (res) { if (res.success) printResidentsCache = res.residents; });
}

function printResident(id) {
    $.post('app/Controllers/reportsController.php', { action: 'getResidentPrintData', residentId: id }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        var r = res.resident;
        var html = '<div class="card"><h2>' + $('<div>').text(r.FirstName + ' ' + r.LastName).html() + '</h2>' +
            '<p><strong>Resident #:</strong> ' + $('<div>').text(r.ResidentNumber).html() + '</p>' +
            '<p><strong>Civil Status:</strong> ' + (r.CivilStatus || '—') + ' &nbsp; <strong>Gender:</strong> ' + (r.Gender || '—') + '</p>' +
            '<p><strong>Birthday:</strong> ' + (r.Birthday || '—') + '</p>' +
            '<p><strong>Address:</strong> ' + $('<div>').text(r.Address || '—').html() + '</p>' +
            '<p><strong>Contact:</strong> ' + (r.ContactNumber || '—') + ' &nbsp; <strong>Email:</strong> ' + (r.Email || '—') + '</p>' +
            '<p><strong>Emergency Contact:</strong> ' + $('<div>').text((r.EmergencyContactName || '—') + ' (' + (r.EmergencyContactRelationship || '—') + ') — ' + (r.EmergencyContactNumber || '—')).html() + '</p>' +
            '<p><strong>Occupation:</strong> ' + $('<div>').text(r.Occupation || '—').html() + '</p>';

        if (r.spouse) {
            html += '<h4>Spouse</h4><p>' + $('<div>').text(r.spouse.SpouseName).html() + ' — ' + (r.spouse.SpouseBirthdate || '—') + ' — ' + r.spouse.SpouseStatus + '</p>';
        }
        if (r.children && r.children.length) {
            html += '<h4>Children</h4><ul>';
            r.children.forEach(function (c) {
                html += '<li>' + $('<div>').text(c.FirstName + ' ' + c.LastName).html() + ' — ' + c.Birthdate + ' — ' + c.Status + '</li>';
            });
            html += '</ul>';
        }
        html += '</div>';

        $('#residentPrintArea').html(html).show();
        window.print();
        $('#residentPrintArea').hide();
    });
}

var currentLogPage = 1;

function loadLogs(page) {
    if (page) currentLogPage = page;
    $.post('app/Controllers/logController.php', { action: 'listLogs', actorType: $('#actorFilter').val(), page: currentLogPage }, null, 'json')
    .done(function (res) {
        var $rows = $('#logRows').empty();
        if (!res.success) return;
        if (res.logs.length === 0) {
            $rows.append('<tr><td colspan="5" style="color:var(--text-muted);">No log entries.</td></tr>');
        }
        res.logs.forEach(function (l) {
            $rows.append(
                '<tr><td>' + l.Timestamp + '</td><td>' + l.ActorType + ' #' + l.ActorID + '</td>' +
                '<td>' + $('<div>').text(l.Action).html() + '</td>' +
                '<td>' + (l.TargetType ? l.TargetType + ' #' + l.TargetID : '—') + '</td>' +
                '<td>' + (l.IPAddress || '—') + '</td></tr>'
            );
        });
        renderLogPagination(res.page, res.totalPages, res.total);
    });
}

function renderLogPagination(page, totalPages, total) {
    var $pag = $('#logPagination').empty();
    $pag.append('<span style="color:var(--text-muted);font-size:0.82rem;margin-right:8px;">' + total + ' total entries</span>');

    var $prev = $('<button class="btn small secondary">&laquo; Prev</button>');
    $prev.prop('disabled', page <= 1).on('click', function () { loadLogs(page - 1); });
    $pag.append($prev);

    var startPage = Math.max(1, page - 2);
    var endPage = Math.min(totalPages, startPage + 4);
    for (var p = startPage; p <= endPage; p++) {
        var $btn = $('<button class="btn small ' + (p === page ? '' : 'secondary') + '">' + p + '</button>');
        $btn.on('click', (function (pg) { return function () { loadLogs(pg); }; })(p));
        $pag.append($btn);
    }

    var $next = $('<button class="btn small secondary">Next &raquo;</button>');
    $next.prop('disabled', page >= totalPages).on('click', function () { loadLogs(page + 1); });
    $pag.append($next);
}

$(function () {
    loadSurveySelect();
    loadEventsReport();
    loadHistoryReport();
    loadPrintResidentList();
    loadLogs();

    $('#surveySelect').on('change', function () { loadReport($(this).val()); });
    $('#actorFilter').on('change', function () { loadLogs(1); });
    $('#refreshLogsBtn').on('click', function () { loadLogs(1); });
    $('#printReportBtn').on('click', function () { window.print(); });

    $('#applyEventsRangeBtn').on('click', loadEventsReport);
    $('#printEventsReportBtn').on('click', function () { window.print(); });

    $('#applyHistoryRangeBtn').on('click', loadHistoryReport);
    $('#historyIncludeVoided').on('change', loadHistoryReport);
    $('#printHistoryReportBtn').on('click', function () { window.print(); });

    $('#printResidentSearch').on('input', function () {
        var q = $(this).val().trim().toLowerCase();
        var $results = $('#printSearchResults').empty();
        if (!q) return;
        var matches = printResidentsCache.filter(function (r) {
            return (r.ResidentNumber || '').toLowerCase().includes(q) ||
                   (r.FirstName + ' ' + r.LastName).toLowerCase().includes(q) ||
                   (r.Address || '').toLowerCase().includes(q);
        }).slice(0, 15);
        matches.forEach(function (r) {
            var $item = $('<div style="padding:8px;border-bottom:1px solid var(--border,#eee);display:flex;justify-content:space-between;align-items:center;">' +
                '<span>' + $('<div>').text(r.ResidentNumber + ' — ' + r.FirstName + ' ' + r.LastName).html() + '</span>' +
                '<button class="btn small">Print</button></div>');
            $item.find('button').on('click', function () { printResident(r.ResidentID); });
            $results.append($item);
        });
    });
});
</script>
</body>
</html>
