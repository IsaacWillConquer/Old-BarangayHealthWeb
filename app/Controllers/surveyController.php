<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/surveyOperations.php";
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeStaff();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'createSurvey':       createSurvey(); break;
    case 'addQuestion':        addQuestion(); break;
    case 'updateQuestion':     updateQuestionAction(); break;
    case 'deleteQuestion':     deleteQuestionAction(); break;
    case 'activateSurvey':     activateSurvey(); break;
    case 'closeSurvey':        closeSurvey(); break;
    case 'archiveSurvey':      archiveSurvey(); break;
    case 'restoreSurvey':      restoreSurvey(); break;
    case 'listArchivedSurveys': listArchivedSurveysAction(); break;
    case 'deleteSurvey':       deleteSurvey(); break;
    case 'updateSurveyMeta':   updateSurveyMeta(); break;
    case 'listSurveys':        listSurveys(); break;
    case 'getSurveyDetail':    getSurveyDetail(); break;
    case 'getSurveyResults':   getSurveyResults(); break;
    case 'getRespondents':     getRespondents(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function createSurvey() {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $openAt = !empty($_POST['openAt']) ? $_POST['openAt'] : null;
    $closeAt = !empty($_POST['closeAt']) ? $_POST['closeAt'] : null;

    if ($title === '') {
        echo json_encode(['success' => false, 'message' => 'Title is required.']);
        exit();
    }
    if ($openAt && $closeAt && strtotime($closeAt) <= strtotime($openAt)) {
        echo json_encode(['success' => false, 'message' => 'Close date must be after open date.']);
        exit();
    }

    $surveyId = insertSurvey($title, $description, $openAt, $closeAt, $_SESSION['StaffID']);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Created survey', 'Survey', $surveyId);

    echo json_encode(['success' => true, 'surveyId' => $surveyId]);
    exit();
}

function addQuestion() {
    $surveyId = (int)($_POST['surveyId'] ?? 0);
    $questionText = trim($_POST['questionText'] ?? '');
    $type = $_POST['type'] ?? '';
    $isRequired = isset($_POST['isRequired']) && $_POST['isRequired'] == '1' ? 1 : 0;
    $orderIndex = (int)($_POST['orderIndex'] ?? 0);
    $choices = $_POST['choices'] ?? [];
    $ratingMin = $_POST['ratingMin'] ?? null;
    $ratingMax = $_POST['ratingMax'] ?? null;
    $ratingStep = $_POST['ratingStep'] ?? 1;

    $validTypes = ['MultipleChoice', 'YesNo', 'Rating', 'ShortAnswer'];
    if (!in_array($type, $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid question type.']);
        exit();
    }
    if ($questionText === '') {
        echo json_encode(['success' => false, 'message' => 'Question text is required.']);
        exit();
    }
    if (surveyHasResponses($surveyId)) {
        echo json_encode(['success' => false, 'message' => 'Cannot add questions once a survey has responses.']);
        exit();
    }
    if ($type === 'MultipleChoice' && count(array_filter($choices, fn($c) => trim($c) !== '')) < 2) {
        echo json_encode(['success' => false, 'message' => 'Multiple choice questions need at least 2 choices.']);
        exit();
    }

    $qId = insertQuestion(
        $surveyId, $questionText, $type, $isRequired, $orderIndex,
        $type === 'Rating' ? (int)$ratingMin : null,
        $type === 'Rating' ? (int)$ratingMax : null,
        $type === 'Rating' ? (int)$ratingStep : 1
    );

    if ($type === 'MultipleChoice') {
        $i = 0;
        foreach ($choices as $choiceText) {
            $choiceText = trim($choiceText);
            if ($choiceText === '') continue;
            insertChoice($qId, $choiceText, $i);
            $i++;
        }
    }

    echo json_encode(['success' => true, 'questionId' => $qId]);
    exit();
}

function updateQuestionAction() {
    $questionId = (int)($_POST['questionId'] ?? 0);
    $questionText = trim($_POST['questionText'] ?? '');
    $type = $_POST['type'] ?? '';
    $isRequired = isset($_POST['isRequired']) && $_POST['isRequired'] == '1' ? 1 : 0;
    $orderIndex = (int)($_POST['orderIndex'] ?? 0);
    $choices = $_POST['choices'] ?? []; 
    $ratingMin = $_POST['ratingMin'] ?? null;
    $ratingMax = $_POST['ratingMax'] ?? null;
    $ratingStep = $_POST['ratingStep'] ?? 1;

    $validTypes = ['MultipleChoice', 'YesNo', 'Rating', 'ShortAnswer'];
    if (!$questionId) {
        echo json_encode(['success' => false, 'message' => 'Question ID is required.']);
        exit();
    }
    if (!in_array($type, $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid question type.']);
        exit();
    }
    if ($questionText === '') {
        echo json_encode(['success' => false, 'message' => 'Question text is required.']);
        exit();
    }

    $question = fetchQuestionById($questionId);
    if (!$question) {
        echo json_encode(['success' => false, 'message' => 'Question not found.']);
        exit();
    }
    if (surveyHasResponses($question['SurveyID'])) {
        echo json_encode(['success' => false, 'message' => 'This survey already has responses, so its questions can no longer be edited. Close it and create a new survey instead.']);
        exit();
    }
    if ($type === 'MultipleChoice' && count(array_filter($choices, fn($c) => trim($c) !== '')) < 2) {
        echo json_encode(['success' => false, 'message' => 'Multiple choice questions need at least 2 choices.']);
        exit();
    }

    updateQuestion(
        $questionId, $questionText, $type, $isRequired, $orderIndex,
        $type === 'Rating' ? (int)$ratingMin : null,
        $type === 'Rating' ? (int)$ratingMax : null,
        $type === 'Rating' ? (int)$ratingStep : 1
    );

    if ($type === 'MultipleChoice') {
        replaceChoicesForQuestion($questionId, $choices);
    } else {
        replaceChoicesForQuestion($questionId, []); 
    }

    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Edited survey question', 'SurveyQuestion', $questionId);
    echo json_encode(['success' => true]);
    exit();
}

function deleteQuestionAction() {
    $questionId = (int)($_POST['questionId'] ?? 0);
    if (!$questionId) {
        echo json_encode(['success' => false, 'message' => 'Question ID is required.']);
        exit();
    }

    $question = fetchQuestionById($questionId);
    if (!$question) {
        echo json_encode(['success' => false, 'message' => 'Question not found.']);
        exit();
    }
    if (surveyHasResponses($question['SurveyID'])) {
        echo json_encode(['success' => false, 'message' => 'This survey already has responses, so its questions can no longer be deleted. Close it and create a new survey instead.']);
        exit();
    }

    deleteQuestion($questionId);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Deleted survey question', 'SurveyQuestion', $questionId);
    echo json_encode(['success' => true]);
    exit();
}

function activateSurvey() {
    $surveyId = (int)($_POST['surveyId'] ?? 0);
    $survey = fetchSurveyById($surveyId);
    if (!$survey) {
        echo json_encode(['success' => false, 'message' => 'Survey not found.']);
        exit();
    }
    $questions = fetchQuestionsForSurvey($surveyId);
    if (count($questions) === 0) {
        echo json_encode(['success' => false, 'message' => 'Add at least one question before activating.']);
        exit();
    }
    updateSurveyStatus($surveyId, 'Active');
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Activated survey', 'Survey', $surveyId);
    echo json_encode(['success' => true]);
    exit();
}

function closeSurvey() {
    $surveyId = (int)($_POST['surveyId'] ?? 0);
    updateSurveyStatus($surveyId, 'Closed');
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Closed survey', 'Survey', $surveyId);
    echo json_encode(['success' => true]);
    exit();
}

function archiveSurvey() {
    $surveyId = (int)($_POST['surveyId'] ?? 0);
    updateSurveyStatus($surveyId, 'Archived');
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Archived survey', 'Survey', $surveyId);
    echo json_encode(['success' => true]);
    exit();
}

function restoreSurvey() {
    $surveyId = (int)($_POST['surveyId'] ?? 0);
    updateSurveyStatus($surveyId, 'Closed'); // Archived surveys always came from Closed
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Restored survey from archive', 'Survey', $surveyId);
    echo json_encode(['success' => true]);
    exit();
}

function listArchivedSurveysAction() {
    $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
    $perPage = 10;
    $all = fetchSurveys('Archived');
    foreach ($all as &$s) { $s['participantCount'] = countParticipants($s['SurveyID']); }
    $total = count($all);
    $totalPages = max(1, ceil($total / $perPage));
    $rows = array_slice($all, ($page - 1) * $perPage, $perPage);
    echo json_encode(['success' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'totalPages' => $totalPages]);
    exit();
}

function deleteSurvey() {
    $surveyId = (int)($_POST['surveyId'] ?? 0);
    if (surveyHasResponses($surveyId)) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete a survey that already has responses — archive it instead.']);
        exit();
    }
    deleteDraftSurvey($surveyId);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Deleted draft survey', 'Survey', $surveyId);
    echo json_encode(['success' => true]);
    exit();
}

function updateSurveyMeta() {
    $surveyId = (int)($_POST['surveyId'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $openAt = !empty($_POST['openAt']) ? $_POST['openAt'] : null;
    $closeAt = !empty($_POST['closeAt']) ? $_POST['closeAt'] : null;

    if ($title === '') {
        echo json_encode(['success' => false, 'message' => 'Title is required.']);
        exit();
    }
    updateSurveyMetaFields($surveyId, $title, $description, $openAt, $closeAt);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Updated survey details', 'Survey', $surveyId);
    echo json_encode(['success' => true]);
    exit();
}

function listSurveys() {
    $statusFilter = $_POST['status'] ?? $_GET['status'] ?? null;
    $surveys = fetchSurveys($statusFilter ?: null);
    foreach ($surveys as &$s) {
        $s['participantCount'] = countParticipants($s['SurveyID']);
    }
    echo json_encode(['success' => true, 'surveys' => $surveys]);
    exit();
}

function getSurveyDetail() {
    $surveyId = (int)($_POST['surveyId'] ?? $_GET['surveyId'] ?? 0);
    $detail = fetchSurveyDetail($surveyId);
    if (!$detail) {
        echo json_encode(['success' => false, 'message' => 'Survey not found.']);
        exit();
    }
    $detail['participantCount'] = countParticipants($surveyId);
    echo json_encode(['success' => true, 'survey' => $detail]);
    exit();
}

function getSurveyResults() {
    $surveyId = (int)($_POST['surveyId'] ?? $_GET['surveyId'] ?? 0);
    $results = fetchSurveyResults($surveyId);
    echo json_encode(['success' => true, 'results' => $results]);
    exit();
}

function getRespondents() {
    $surveyId = (int)($_POST['surveyId'] ?? $_GET['surveyId'] ?? 0);
    $respondents = fetchRespondentsForSurvey($surveyId);
    echo json_encode(['success' => true, 'respondents' => $respondents]);
    exit();
}
?>
