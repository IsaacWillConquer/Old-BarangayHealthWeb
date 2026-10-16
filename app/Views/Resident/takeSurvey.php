<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeResident();
$surveyId = (int)($_GET['surveyId'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/generalize.css">
    <title>Answer Survey</title>
</head>
<body>
<div class="topbar">
    <h2>Barangay Health Portal</h2>
    <nav>
        <a href="app/Views/Resident/residentDashboard.php">Back to Dashboard</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
</div>

<div class="page-wrap">
    <div id="formView" class="card">
        <h3 id="surveyTitle">Loading...</h3>
        <p id="surveyDesc" style="color:#24322C;"></p>
        <div id="questionsArea"></div>
        <button class="btn primary" id="reviewBtn">Review Answers</button>
        <div id="formError" class="error-text"></div>
    </div>

    <div id="reviewView" class="card" style="display:none;">
        <h3>Review Your Answers</h3>
        <p style="color:#b8b8d0;">Please check everything before submitting — you can only submit once.</p>
        <div id="reviewArea"></div>
        <button class="btn secondary" id="backBtn">Back to Edit</button>
        <button class="btn primary" id="submitBtn">Submit Response</button>
        <div id="reviewError" class="error-text"></div>
    </div>

    <div id="doneView" class="card" style="display:none;">
        <h3>Thank you!</h3>
        <p>Your response has been recorded.</p>
        <a class="btn" href="app/Views/Resident/residentDashboard.php">Back to Dashboard</a>
    </div>
</div>

<script>
var surveyId = <?php echo $surveyId; ?>;
var surveyData = null;

function renderQuestion(q) {
    var html = '<div class="card" style="background:#faf9f5;">';
    html += '<label><strong>' + $('<div>').text(q.QuestionText).html() + '</strong>' + (q.IsRequired == 1 ? ' *' : ' (optional)') + '</label>';

    if (q.Type === 'MultipleChoice') {
        q.choices.forEach(function (c) {
            html += '<label class="option-label"><input type="radio" name="q' + q.QuestionID + '" value="' + c.ChoiceID + '"> ' + $('<div>').text(c.ChoiceText).html() + '</label>';
        });
    } else if (q.Type === 'YesNo') {
        html += '<label class="option-label"><input type="radio" name="q' + q.QuestionID + '" value="Yes"> Yes</label>';
        html += '<label class="option-label"><input type="radio" name="q' + q.QuestionID + '" value="No"> No</label>';
    } else if (q.Type === 'Rating') {
        var min = q.RatingMin || 1, max = q.RatingMax || 5, step = q.RatingStep || 1;
        html += '<div class="rating-scale">';
        for (var v = min; v <= max; v += step) {
            html += '<label><input type="radio" name="q' + q.QuestionID + '" value="' + v + '">' + v + '</label>';
        }
        html += '</div>';
    } else { // ShortAnswer
        html += '<textarea name="q' + q.QuestionID + '" rows="2"></textarea>';
    }
    html += '</div>';
    return html;
}

function collectAnswers() {
    var answers = [];
    var valid = true;
    surveyData.questions.forEach(function (q) {
        var val, choiceId = null, answerText = null;
        if (q.Type === 'MultipleChoice' || q.Type === 'Rating') {
            val = $('input[name="q' + q.QuestionID + '"]:checked').val();
            if (q.Type === 'MultipleChoice') choiceId = val || null;
            else answerText = val || null;
        } else if (q.Type === 'YesNo') {
            val = $('input[name="q' + q.QuestionID + '"]:checked').val();
            answerText = val || null;
        } else {
            answerText = $('textarea[name="q' + q.QuestionID + '"]').val().trim();
        }
        if (q.IsRequired == 1 && !choiceId && !answerText) valid = false;
        answers.push({ questionId: q.QuestionID, type: q.Type, choiceId: choiceId, answerText: answerText });
    });
    return valid ? answers : null;
}

function answerDisplayText(q, a) {
    if (!a.choiceId && !a.answerText) return '(no answer)';
    if (q.Type === 'MultipleChoice') {
        var c = q.choices.find(function (c) { return String(c.ChoiceID) === String(a.choiceId); });
        return c ? c.ChoiceText : '(no answer)';
    }
    return a.answerText || '(no answer)';
}

$(function () {
    $.post('app/Controllers/responseController.php', { action: 'getSurveyToAnswer', surveyId: surveyId }, null, 'json')
    .done(function (res) {
        if (!res.success) {
            $('#surveyTitle').text('Unavailable');
            $('#formError').text(res.message);
            $('#reviewBtn').hide();
            return;
        }
        surveyData = res.survey;
        $('#surveyTitle').text(surveyData.Title);
        $('#surveyDesc').text(surveyData.Description || '');
        var qHtml = '';
        surveyData.questions.forEach(function (q) { qHtml += renderQuestion(q); });
        $('#questionsArea').html(qHtml);
    });

    $('#reviewBtn').on('click', function () {
        var answers = collectAnswers();
        $('#formError').text('');
        if (!answers) { $('#formError').text('Please answer all required questions.'); return; }
        window.__answers = answers;

        var html = '';
        surveyData.questions.forEach(function (q, i) {
            html += '<p><strong>' + $('<div>').text(q.QuestionText).html() + '</strong><br>' + $('<div>').text(answerDisplayText(q, answers[i])).html() + '</p>';
        });
        $('#reviewArea').html(html);
        $('#formView').hide();
        $('#reviewView').show();
    });

    $('#backBtn').on('click', function () {
        $('#reviewView').hide();
        $('#formView').show();
    });

    $('#submitBtn').on('click', function () {
        $('#submitBtn').prop('disabled', true).text('Submitting...');
        $.post('app/Controllers/responseController.php', {
            action: 'submitResponse',
            surveyId: surveyId,
            answers: window.__answers
        }, null, 'json')
        .done(function (res) {
            if (res.success) {
                $('#reviewView').hide();
                $('#doneView').show();
            } else {
                $('#reviewError').text(res.message || 'Submission failed.');
                $('#submitBtn').prop('disabled', false).text('Submit Response');
            }
        });
    });
});
</script>
</body>
</html>
