<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeStaff();
$activePage = 'surveys';
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
    <title>Survey Management</title>
    <style>
        .survey-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; margin-top:14px; }
        .survey-card { border:1px solid var(--border,#ddd); border-radius:10px; background:#fff; padding:14px; display:flex; flex-direction:column; gap:6px; }
        .survey-card h4 { margin:0; font-size:0.98rem; }
        .survey-card-meta { font-size:0.8rem; color:var(--text-muted); }
        .survey-card-actions { margin-top:auto; padding-top:6px; }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/' . ($isAdmin ? 'sidebarAdmin.php' : 'sidebarStaff.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Survey Management</h2>
            <button class="btn" id="newSurveyBtn">+ New Survey</button>
        </div>

        <div class="card">
            <div style="margin-bottom:12px;">
                <label style="display:inline-block;width:auto;margin-right:8px;">Filter:</label>
                <select id="statusFilter" style="width:auto;display:inline-block;">
                    <option value="">All</option>
                    <option value="Draft">Draft</option>
                    <option value="Active">Active</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div class="survey-grid" id="surveyGrid"></div>
            <p id="noSurveys" style="display:none;color:var(--text-muted);">No surveys yet.</p>
        </div>

        <div class="card">
            <div id="archivedToggle" style="cursor:pointer;display:flex;align-items:center;gap:8px;">
                <span id="archivedCaret">&#9656;</span>
                <h3 style="margin:0;">Archived Surveys (<span id="archivedCount">0</span>)</h3>
            </div>
            <div id="archivedSection" style="display:none;margin-top:12px;">
                <table>
                    <thead><tr><th>Title</th><th>Closed</th><th>Responses</th><th></th></tr></thead>
                    <tbody id="archivedRows"></tbody>
                </table>
                <p id="noArchived" style="display:none;color:var(--text-muted);">No archived surveys.</p>
                <div id="archivedPagination" style="margin-top:12px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="surveyModal">
    <div class="modal-box">
        <h3 id="modalTitle">New Survey</h3>
        <input type="hidden" id="editSurveyId">
        <label>Title</label>
        <input type="text" id="sTitle">
        <label>Description</label>
        <textarea id="sDescription" rows="2"></textarea>
        <label>Opens</label>
        <input type="datetime-local" id="sOpenAt">
        <label>Closes</label>
        <input type="datetime-local" id="sCloseAt">
        <div style="margin-top:16px;">
            <button class="btn" id="saveSurveyBtn">Save</button>
            <button class="btn secondary" id="cancelSurveyBtn">Cancel</button>
        </div>
        <div id="surveyModalError" class="error-text"></div>
    </div>
</div>

<div class="modal-overlay" id="detailModal">
    <div class="modal-box" style="width:640px;">
        <h3 id="detailTitle">Survey</h3>
        <div id="detailStatusRow" style="margin-bottom:12px;"></div>

        <div id="questionListArea"></div>

        <div id="addQuestionArea" class="card" style="background:#fafcfc;">
            <h4 id="qFormTitle" style="margin-top:0;">Add Question</h4>
            <input type="hidden" id="editingQuestionId">
            <label>Question Text</label>
            <input type="text" id="qText">
            <label>Type</label>
            <select id="qType">
                <option value="MultipleChoice">Multiple Choice</option>
                <option value="YesNo">Yes/No</option>
                <option value="Rating">Rating Scale</option>
                <option value="ShortAnswer">Short Answer</option>
            </select>
            <div id="choicesArea">
                <label>Choices (one per line, min 2)</label>
                <textarea id="qChoices" rows="3"></textarea>
            </div>
            <div id="ratingArea" style="display:none;">
                <label>Min</label><input type="number" id="qRatingMin" value="1">
                <label>Max</label><input type="number" id="qRatingMax" value="5">
            </div>
            <label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" id="qRequired" style="width:auto;" checked> Required</label>
            <button class="btn small" id="addQuestionBtn">Add Question</button>
            <button class="btn small secondary" id="cancelEditQuestionBtn" style="display:none;">Cancel Edit</button>
            <div id="addQError" class="error-text"></div>
        </div>

        <div id="resultsArea"></div>

        <div style="margin-top:16px;">
            <button class="btn secondary" id="closeDetailBtn">Close</button>
        </div>
    </div>
</div>

<script>
var currentSurveyId = null;
var currentArchivedPage = 1;

function loadSurveys() {
    $.post('app/Controllers/surveyController.php', { action: 'listSurveys', status: $('#statusFilter').val() }, null, 'json')
    .done(function (res) {
        var $grid = $('#surveyGrid').empty();
        if (!res.success) return;
        var surveys = res.surveys.filter(function (s) { return s.Status !== 'Archived'; });
        $('#noSurveys').toggle(surveys.length === 0);
        surveys.forEach(function (s) {
            $grid.append(
                '<div class="survey-card">' +
                '<h4>' + $('<div>').text(s.Title).html() + '</h4>' +
                '<span class="status-badge status-' + s.Status + '">' + s.Status + '</span>' +
                '<div class="survey-card-meta">Open: ' + (s.OpenAt || '—') + '</div>' +
                '<div class="survey-card-meta">Close: ' + (s.CloseAt || '—') + '</div>' +
                '<div class="survey-card-meta">' + s.participantCount + ' response(s)</div>' +
                '<div class="survey-card-actions"><button class="btn small secondary manage-btn" data-id="' + s.SurveyID + '">Manage</button></div>' +
                '</div>'
            );
        });
    });
}

function loadArchivedSurveys(page) {
    if (page) currentArchivedPage = page;
    $.post('app/Controllers/surveyController.php', { action: 'listArchivedSurveys', page: currentArchivedPage }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        $('#archivedCount').text(res.total);
        var $rows = $('#archivedRows').empty();
        $('#noArchived').toggle(res.rows.length === 0);
        res.rows.forEach(function (s) {
            $rows.append(
                '<tr><td>' + $('<div>').text(s.Title).html() + '</td><td>' + (s.CloseAt || '—') + '</td><td>' +
                s.participantCount + '</td><td>' +
                '<button class="btn small secondary manage-btn" data-id="' + s.SurveyID + '">View</button> ' +
                '<button class="btn small restore-survey-btn" data-id="' + s.SurveyID + '">Restore</button></td></tr>'
            );
        });
        renderArchivedSurveyPagination(res.page, res.totalPages, res.total);
    });
}

function renderArchivedSurveyPagination(page, totalPages, total) {
    var $pag = $('#archivedPagination').empty();
    if (total === 0) return;
    var $prev = $('<button class="btn small secondary">&laquo; Prev</button>');
    $prev.prop('disabled', page <= 1).on('click', function () { loadArchivedSurveys(page - 1); });
    $pag.append($prev);
    for (var p = 1; p <= totalPages; p++) {
        var $btn = $('<button class="btn small ' + (p === page ? '' : 'secondary') + '">' + p + '</button>');
        $btn.on('click', (function (pg) { return function () { loadArchivedSurveys(pg); }; })(p));
        $pag.append($btn);
    }
    var $next = $('<button class="btn small secondary">Next &raquo;</button>');
    $next.prop('disabled', page >= totalPages).on('click', function () { loadArchivedSurveys(page + 1); });
    $pag.append($next);
}

function openSurveyModal(survey) {
    $('#surveyModalError').text('');
    if (survey) {
        $('#modalTitle').text('Edit Survey');
        $('#editSurveyId').val(survey.SurveyID);
        $('#sTitle').val(survey.Title);
        $('#sDescription').val(survey.Description);
        $('#sOpenAt').val(survey.OpenAt ? survey.OpenAt.replace(' ', 'T').substring(0,16) : '');
        $('#sCloseAt').val(survey.CloseAt ? survey.CloseAt.replace(' ', 'T').substring(0,16) : '');
    } else {
        $('#modalTitle').text('New Survey');
        $('#editSurveyId').val('');
        $('#sTitle, #sDescription, #sOpenAt, #sCloseAt').val('');
    }
    $('#surveyModal').addClass('open');
}

function openDetailModal(surveyId) {
    currentSurveyId = surveyId;
    $.post('app/Controllers/surveyController.php', { action: 'getSurveyDetail', surveyId: surveyId }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        var s = res.survey;
        $('#detailTitle').text(s.Title);

        var actions = '';
        if (s.Status === 'Draft') {
            actions += '<button class="btn small" id="activateBtn">Activate</button> ';
            actions += '<button class="btn small secondary" id="editMetaBtn">Edit Details</button> ';
            if (s.participantCount === 0) actions += '<button class="btn small danger" id="deleteBtn">Delete</button>';
        } else if (s.Status === 'Active') {
            actions += '<button class="btn small secondary" id="closeSurveyBtn">Close Now</button> ';
        } else if (s.Status === 'Closed') {
            actions += '<button class="btn small secondary" id="archiveBtn">Archive</button> ';
        }
        actions += ' <button class="btn small secondary" id="printBtn">Print Report</button>';
        actions += ' <span class="status-badge status-' + s.Status + '" style="margin-left:8px;">' + s.Status + '</span>';
        actions += ' <span style="margin-left:8px;color:var(--text-muted);font-size:0.85rem;">' + s.participantCount + ' respondent(s)</span>';
        $('#detailStatusRow').html(actions);

        var qHtml = '<h4>Questions</h4>';
        if (s.questions.length === 0) qHtml += '<p style="color:var(--text-muted);">No questions yet.</p>';
        var canEditQuestions = (s.Status === 'Draft' && s.participantCount === 0);
        s.questions.forEach(function (q, i) {
            qHtml += '<p><strong>' + (i+1) + '. ' + $('<div>').text(q.QuestionText).html() + '</strong> <span style="color:var(--text-muted);font-size:0.8rem;">(' + q.Type + (q.IsRequired == 1 ? ', required' : ', optional') + ')</span>';
            if (canEditQuestions) {
                qHtml += ' <button class="link-btn edit-q-btn" data-id="' + q.QuestionID + '">Edit</button>';
                qHtml += ' <button class="link-btn danger delete-q-btn" data-id="' + q.QuestionID + '">Delete</button>';
            }
            if (q.Type === 'MultipleChoice') {
                qHtml += '<br><span style="font-size:0.85rem;color:var(--text-muted);">' + q.choices.map(c => $('<div>').text(c.ChoiceText).html()).join(' • ') + '</span>';
            }
            if (q.Type === 'Rating') {
                qHtml += '<br><span style="font-size:0.85rem;color:var(--text-muted);">Scale ' + q.RatingMin + ' to ' + q.RatingMax + '</span>';
            }
            qHtml += '</p>';
        });
        $('#questionListArea').html(qHtml);
        window._currentQuestions = s.questions;

        $('#addQuestionArea').toggle(canEditQuestions);
        resetQuestionForm();

        if (s.participantCount > 0) {
            loadResults(surveyId);
        } else {
            $('#resultsArea').empty();
        }

        $('#detailModal').addClass('open');
    });
}

function resetQuestionForm() {
    $('#editingQuestionId').val('');
    $('#qFormTitle').text('Add Question');
    $('#addQuestionBtn').text('Add Question');
    $('#cancelEditQuestionBtn').hide();
    $('#qText, #qChoices').val('');
    $('#qType').val('MultipleChoice');
    $('#qRequired').prop('checked', true);
    $('#qRatingMin').val(1);
    $('#qRatingMax').val(5);
    $('#choicesArea').show();
    $('#ratingArea').hide();
    $('#addQError').text('');
}

function editQuestion(questionId) {
    var q = (window._currentQuestions || []).find(function (x) { return x.QuestionID == questionId; });
    if (!q) return;
    $('#editingQuestionId').val(q.QuestionID);
    $('#qFormTitle').text('Edit Question');
    $('#addQuestionBtn').text('Save Changes');
    $('#cancelEditQuestionBtn').show();
    $('#qText').val(q.QuestionText);
    $('#qType').val(q.Type);
    $('#qRequired').prop('checked', q.IsRequired == 1);
    $('#qChoices').val((q.choices || []).map(c => c.ChoiceText).join('\n'));
    $('#qRatingMin').val(q.RatingMin ?? 1);
    $('#qRatingMax').val(q.RatingMax ?? 5);
    $('#choicesArea').toggle(q.Type === 'MultipleChoice');
    $('#ratingArea').toggle(q.Type === 'Rating');
    $('#addQError').text('');
    $('html, body').animate({ scrollTop: $('#addQuestionArea').offset().top }, 200);
}

function loadResults(surveyId) {
    $.post('app/Controllers/surveyController.php', { action: 'getSurveyResults', surveyId: surveyId }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        var html = '<h4>Results</h4>';
        var questions = window._currentQuestions || [];
        res.results.forEach(function (r) {
            var idx = questions.findIndex(function (q) { return q.QuestionID == r.QuestionID; });
            var label = idx >= 0
                ? ((idx + 1) + '. ' + $('<div>').text(questions[idx].QuestionText).html())
                : ('Question #' + r.QuestionID);
            html += '<div style="margin-bottom:10px;"><strong>' + label + '</strong> (' + r.Type + ', ' + r.totalResponses + ' answered)<br>';
            if (r.Type === 'Rating' && r.average !== undefined) {
                html += 'Average: ' + (r.average ?? '—') + '<br>';
            }
            if (r.Type === 'ShortAnswer' && r.rawAnswers) {
                html += '<em style="font-size:0.82rem;color:var(--text-muted);">' + r.rawAnswers.slice(0, 5).map(a => $('<div>').text(a).html()).join(' | ') + (r.rawAnswers.length > 5 ? ' ...' : '') + '</em>';
            } else {
                r.tally.forEach(function (t) {
                    html += $('<div>').text(t.value).html() + ': ' + t.count + '<br>';
                });
            }
            html += '</div>';
        });
        $('#resultsArea').html(html);
    });
}

$(function () {
    loadSurveys();
    loadArchivedSurveys(1);

    $('#archivedToggle').on('click', function () {
        var open = $('#archivedSection').is(':visible');
        $('#archivedSection').toggle(!open);
        $('#archivedCaret').html(open ? '&#9656;' : '&#9662;');
    });

    $(document).on('click', '.restore-survey-btn', function () {
        var id = $(this).data('id');
        $.post('app/Controllers/surveyController.php', { action: 'restoreSurvey', surveyId: id }, null, 'json')
        .done(function () { loadSurveys(); loadArchivedSurveys(1); });
    });

    $('#statusFilter').on('change', loadSurveys);
    $('#newSurveyBtn').on('click', function () { openSurveyModal(null); });
    $('#cancelSurveyBtn').on('click', function () { $('#surveyModal').removeClass('open'); });
    $('#closeDetailBtn').on('click', function () { $('#detailModal').removeClass('open'); loadSurveys(); });

    $(document).on('click', '.manage-btn', function () { openDetailModal($(this).data('id')); });
    $(document).on('click', '#editMetaBtn', function () {
        $.post('app/Controllers/surveyController.php', { action: 'getSurveyDetail', surveyId: currentSurveyId }, null, 'json')
        .done(function (res) { if (res.success) { $('#detailModal').removeClass('open'); openSurveyModal(res.survey); } });
    });

    $('#saveSurveyBtn').on('click', function () {
        var id = $('#editSurveyId').val();
        var payload = {
            action: id ? 'updateSurveyMeta' : 'createSurvey',
            surveyId: id,
            title: $('#sTitle').val().trim(),
            description: $('#sDescription').val().trim(),
            openAt: $('#sOpenAt').val() ? $('#sOpenAt').val().replace('T', ' ') + ':00' : '',
            closeAt: $('#sCloseAt').val() ? $('#sCloseAt').val().replace('T', ' ') + ':00' : ''
        };
        $.post('app/Controllers/surveyController.php', payload, null, 'json')
        .done(function (res) {
            if (res.success) { $('#surveyModal').removeClass('open'); loadSurveys(); }
            else $('#surveyModalError').text(res.message);
        });
    });

    $(document).on('click', '#activateBtn', function () {
        $.post('app/Controllers/surveyController.php', { action: 'activateSurvey', surveyId: currentSurveyId }, null, 'json')
        .done(function (res) { if (res.success) openDetailModal(currentSurveyId); else alert(res.message); });
    });
    $(document).on('click', '#closeSurveyBtn', function () {
        $.post('app/Controllers/surveyController.php', { action: 'closeSurvey', surveyId: currentSurveyId }, null, 'json')
        .done(function () { openDetailModal(currentSurveyId); });
    });
    $(document).on('click', '#archiveBtn', function () {
        $.post('app/Controllers/surveyController.php', { action: 'archiveSurvey', surveyId: currentSurveyId }, null, 'json')
        .done(function () { openDetailModal(currentSurveyId); });
    });
    $(document).on('click', '#deleteBtn', function () {
        if (!confirm('Delete this draft survey?')) return;
        $.post('app/Controllers/surveyController.php', { action: 'deleteSurvey', surveyId: currentSurveyId }, null, 'json')
        .done(function (res) { if (res.success) { $('#detailModal').removeClass('open'); loadSurveys(); } else alert(res.message); });
    });
    $(document).on('click', '#printBtn', function () { window.print(); });

    $('#qType').on('change', function () {
        var t = $(this).val();
        $('#choicesArea').toggle(t === 'MultipleChoice');
        $('#ratingArea').toggle(t === 'Rating');
    });

    $(document).on('click', '.edit-q-btn', function () { editQuestion($(this).data('id')); });
    $('#cancelEditQuestionBtn').on('click', resetQuestionForm);

    $(document).on('click', '.delete-q-btn', function () {
        var questionId = $(this).data('id');
        if (!confirm('Delete this question? This cannot be undone.')) return;
        $.post('app/Controllers/surveyController.php', { action: 'deleteQuestion', questionId: questionId }, null, 'json')
        .done(function (res) {
            if (res.success) openDetailModal(currentSurveyId);
            else alert(res.message);
        });
    });

    $('#addQuestionBtn').on('click', function () {
        var editingId = $('#editingQuestionId').val();
        var text = $('#qText').val().trim();
        var type = $('#qType').val();
        var choices = $('#qChoices').val().split('\n').map(c => c.trim()).filter(c => c);
        $('#addQError').text('');
        if (!text) { $('#addQError').text('Question text is required.'); return; }

        var payload = {
            action: editingId ? 'updateQuestion' : 'addQuestion',
            surveyId: currentSurveyId,
            questionId: editingId,
            questionText: text,
            type: type,
            isRequired: $('#qRequired').is(':checked') ? 1 : 0,
            orderIndex: 0,
            choices: choices,
            ratingMin: $('#qRatingMin').val(),
            ratingMax: $('#qRatingMax').val()
        };

        $.post('app/Controllers/surveyController.php', payload, null, 'json')
        .done(function (res) {
            if (res.success) {
                resetQuestionForm();
                openDetailModal(currentSurveyId);
            } else {
                $('#addQError').text(res.message);
            }
        });
    });
});
</script>
</body>
</html>
