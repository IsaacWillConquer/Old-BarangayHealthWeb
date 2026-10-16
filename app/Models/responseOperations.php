<?php

function getActiveSurveysForResident($residentId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT s.* FROM surveys s
         WHERE s.Status = 'Active'
         AND (s.OpenAt IS NULL OR NOW() >= s.OpenAt)
         AND (s.CloseAt IS NULL OR NOW() <= s.CloseAt)
         AND NOT EXISTS (
             SELECT 1 FROM responses r WHERE r.SurveyID = s.SurveyID AND r.ResidentID = ?
         )
         ORDER BY s.OpenAt ASC"
    );
    $stmt->bind_param("i", $residentId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getCompletedSurveysForResident($residentId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT s.*, r.ResponseID, r.SubmittedAt FROM surveys s
         JOIN responses r ON r.SurveyID = s.SurveyID
         WHERE r.ResidentID = ?
         ORDER BY r.SubmittedAt DESC"
    );
    $stmt->bind_param("i", $residentId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function residentAlreadyResponded($surveyId, $residentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT 1 FROM responses WHERE SurveyID = ? AND ResidentID = ?");
    $stmt->bind_param("ii", $surveyId, $residentId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function surveyIsWithinWindow($surveyId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT 1 FROM surveys WHERE SurveyID = ? AND Status = 'Active'
         AND (OpenAt IS NULL OR NOW() >= OpenAt)
         AND (CloseAt IS NULL OR NOW() <= CloseAt)"
    );
    $stmt->bind_param("i", $surveyId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function insertResponse($surveyId, $residentId) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO responses (SurveyID, ResidentID) VALUES (?, ?)");
    $stmt->bind_param("ii", $surveyId, $residentId);
    $stmt->execute();
    return $conn->insert_id;
}

function insertAnswer($responseId, $questionId, $answerText = null, $selectedChoiceId = null) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO response_answers (ResponseID, QuestionID, AnswerText, SelectedChoiceID)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("iisi", $responseId, $questionId, $answerText, $selectedChoiceId);
    return $stmt->execute();
}

function fetchResponseDetail($responseId, $residentId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT r.*, s.Title, s.Description FROM responses r
         JOIN surveys s ON s.SurveyID = r.SurveyID
         WHERE r.ResponseID = ? AND r.ResidentID = ?"
    );
    $stmt->bind_param("ii", $responseId, $residentId);
    $stmt->execute();
    $response = $stmt->get_result()->fetch_assoc();
    if (!$response) return null;

    $astmt = $conn->prepare(
        "SELECT ra.*, sq.QuestionText, sq.Type, sc.ChoiceText FROM response_answers ra
         JOIN survey_questions sq ON sq.QuestionID = ra.QuestionID
         LEFT JOIN survey_choices sc ON sc.ChoiceID = ra.SelectedChoiceID
         WHERE ra.ResponseID = ?"
    );
    $astmt->bind_param("i", $responseId);
    $astmt->execute();
    $response['answers'] = $astmt->get_result()->fetch_all(MYSQLI_ASSOC);
    return $response;
}
?>
