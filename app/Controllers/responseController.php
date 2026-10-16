<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/responseOperations.php";
include __DIR__ . "/../Models/surveyOperations.php";
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeResident();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'getActiveSurveys':    getActiveSurveys(); break;
    case 'getCompletedSurveys': getCompletedSurveys(); break;
    case 'getSurveyToAnswer':   getSurveyToAnswer(); break;
    case 'submitResponse':      submitResponse(); break;
    case 'viewCompletedResponse': viewCompletedResponse(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function getActiveSurveys() {
    $surveys = getActiveSurveysForResident($_SESSION['ResidentID']);
    echo json_encode(['success' => true, 'surveys' => $surveys]);
    exit();
}

function getCompletedSurveys() {
    $surveys = getCompletedSurveysForResident($_SESSION['ResidentID']);
    echo json_encode(['success' => true, 'surveys' => $surveys]);
    exit();
}

function getSurveyToAnswer() {
    $surveyId = (int)($_POST['surveyId'] ?? $_GET['surveyId'] ?? 0);

    if (residentAlreadyResponded($surveyId, $_SESSION['ResidentID'])) {
        echo json_encode(['success' => false, 'message' => 'You have already answered this survey.']);
        exit();
    }
    if (!surveyIsWithinWindow($surveyId)) {
        echo json_encode(['success' => false, 'message' => 'This survey is not currently open.']);
        exit();
    }

    $detail = fetchSurveyDetail($surveyId);
    if (!$detail) {
        echo json_encode(['success' => false, 'message' => 'Survey not found.']);
        exit();
    }
    echo json_encode(['success' => true, 'survey' => $detail]);
    exit();
}

function submitResponse() {
    global $conn;
    $surveyId = (int)($_POST['surveyId'] ?? 0);
    $answers = $_POST['answers'] ?? [];
    $residentId = $_SESSION['ResidentID'];

    if (residentAlreadyResponded($surveyId, $residentId)) {
        echo json_encode(['success' => false, 'message' => 'You have already submitted a response for this survey.']);
        exit();
    }
    if (!surveyIsWithinWindow($surveyId)) {
        echo json_encode(['success' => false, 'message' => 'This survey is no longer accepting responses.']);
        exit();
    }
    $questions = fetchQuestionsForSurvey($surveyId);
    $answersByQid = [];
    foreach ($answers as $a) {
        $answersByQid[(int)$a['questionId']] = $a;
    }
    foreach ($questions as $q) {
        if ((int)$q['IsRequired'] === 1) {
            $a = $answersByQid[$q['QuestionID']] ?? null;
            $hasAnswer = $a && (trim($a['answerText'] ?? '') !== '' || !empty($a['choiceId']));
            if (!$hasAnswer) {
                echo json_encode(['success' => false, 'message' => 'Please answer all required questions.']);
                exit();
            }
        }
    }

    $conn->begin_transaction();
    try {
        $responseId = insertResponse($surveyId, $residentId);
        foreach ($answers as $a) {
            $qid = (int)$a['questionId'];
            $answerText = $a['answerText'] ?? null;
            $choiceId = !empty($a['choiceId']) ? (int)$a['choiceId'] : null;
            insertAnswer($responseId, $qid, $answerText, $choiceId);
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Could not submit — you may have already responded to this survey.']);
        exit();
    }

    insertLog('Resident', $residentId, 'Submitted survey response', 'Survey', $surveyId);
    echo json_encode(['success' => true, 'responseId' => $responseId]);
    exit();
}

function viewCompletedResponse() {
    $responseId = (int)($_POST['responseId'] ?? $_GET['responseId'] ?? 0);
    $detail = fetchResponseDetail($responseId, $_SESSION['ResidentID']);
    if (!$detail) {
        echo json_encode(['success' => false, 'message' => 'Response not found.']);
        exit();
    }
    echo json_encode(['success' => true, 'response' => $detail]);
    exit();
}
?>
