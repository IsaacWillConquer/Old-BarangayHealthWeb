<?php

function insertSurvey($title, $description, $openAt, $closeAt, $createdBy) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO surveys (Title, Description, Status, OpenAt, CloseAt, CreatedBy)
         VALUES (?, ?, 'Draft', ?, ?, ?)"
    );
    $stmt->bind_param("ssssi", $title, $description, $openAt, $closeAt, $createdBy);
    $stmt->execute();
    return $conn->insert_id;
}

function insertQuestion($surveyId, $questionText, $type, $isRequired, $orderIndex, $ratingMin = null, $ratingMax = null, $ratingStep = 1) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO survey_questions (SurveyID, QuestionText, Type, IsRequired, OrderIndex, RatingMin, RatingMax, RatingStep)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issiiiii", $surveyId, $questionText, $type, $isRequired, $orderIndex, $ratingMin, $ratingMax, $ratingStep);
    $stmt->execute();
    return $conn->insert_id;
}

function insertChoice($questionId, $choiceText, $orderIndex) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO survey_choices (QuestionID, ChoiceText, OrderIndex) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("isi", $questionId, $choiceText, $orderIndex);
    $stmt->execute();
    return $conn->insert_id;
}

function updateQuestion($questionId, $questionText, $type, $isRequired, $orderIndex, $ratingMin = null, $ratingMax = null, $ratingStep = 1) {
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE survey_questions
         SET QuestionText = ?, Type = ?, IsRequired = ?, OrderIndex = ?, RatingMin = ?, RatingMax = ?, RatingStep = ?
         WHERE QuestionID = ?"
    );
    $stmt->bind_param("ssiiiiii", $questionText, $type, $isRequired, $orderIndex, $ratingMin, $ratingMax, $ratingStep, $questionId);
    return $stmt->execute();
}

function deleteQuestion($questionId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM survey_questions WHERE QuestionID = ?");
    $stmt->bind_param("i", $questionId);
    return $stmt->execute();
}

function fetchQuestionById($questionId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM survey_questions WHERE QuestionID = ?");
    $stmt->bind_param("i", $questionId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function replaceChoicesForQuestion($questionId, $choiceTexts) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM survey_choices WHERE QuestionID = ?");
    $stmt->bind_param("i", $questionId);
    $stmt->execute();

    $i = 0;
    foreach ($choiceTexts as $text) {
        $text = trim($text);
        if ($text === '') continue;
        insertChoice($questionId, $text, $i);
        $i++;
    }
}

function surveyHasResponses($surveyId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM responses WHERE SurveyID = ?");
    $stmt->bind_param("i", $surveyId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)$row['cnt'] > 0;
}

function updateSurveyStatus($surveyId, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE surveys SET Status = ? WHERE SurveyID = ?");
    $stmt->bind_param("si", $status, $surveyId);
    return $stmt->execute();
}

function updateSurveyMetaFields($surveyId, $title, $description, $openAt, $closeAt) {
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE surveys SET Title = ?, Description = ?, OpenAt = ?, CloseAt = ? WHERE SurveyID = ?"
    );
    $stmt->bind_param("ssssi", $title, $description, $openAt, $closeAt, $surveyId);
    return $stmt->execute();
}

function deleteDraftSurvey($surveyId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM surveys WHERE SurveyID = ? AND Status = 'Draft'");
    $stmt->bind_param("i", $surveyId);
    return $stmt->execute();
}

function fetchSurveys($statusFilter = null) {
    global $conn;
    if ($statusFilter) {
        $stmt = $conn->prepare("SELECT * FROM surveys WHERE Status = ? ORDER BY CreatedAt DESC");
        $stmt->bind_param("s", $statusFilter);
    } else {
        $stmt = $conn->prepare("SELECT * FROM surveys ORDER BY CreatedAt DESC");
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetchSurveyById($surveyId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM surveys WHERE SurveyID = ?");
    $stmt->bind_param("i", $surveyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function fetchQuestionsForSurvey($surveyId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM survey_questions WHERE SurveyID = ? ORDER BY OrderIndex ASC");
    $stmt->bind_param("i", $surveyId);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($questions as &$q) {
        if ($q['Type'] === 'MultipleChoice') {
            $cstmt = $conn->prepare("SELECT * FROM survey_choices WHERE QuestionID = ? ORDER BY OrderIndex ASC");
            $cstmt->bind_param("i", $q['QuestionID']);
            $cstmt->execute();
            $q['choices'] = $cstmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $q['choices'] = [];
        }
    }
    return $questions;
}

function fetchSurveyDetail($surveyId) {
    $survey = fetchSurveyById($surveyId);
    if (!$survey) return null;
    $survey['questions'] = fetchQuestionsForSurvey($surveyId);
    return $survey;
}

function countParticipants($surveyId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM responses WHERE SurveyID = ?");
    $stmt->bind_param("i", $surveyId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)$row['cnt'];
}

function fetchSurveyResults($surveyId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM survey_results WHERE SurveyID = ? ORDER BY QuestionID, AnswerValue");
    $stmt->bind_param("i", $surveyId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $grouped = [];
    foreach ($rows as $r) {
        $qid = $r['QuestionID'];
        if (!isset($grouped[$qid])) {
            $grouped[$qid] = ['QuestionID' => $qid, 'Type' => $r['Type'], 'tally' => [], 'totalResponses' => 0];
        }
        $grouped[$qid]['tally'][] = ['value' => $r['AnswerValue'], 'count' => (int)$r['ResponseCount']];
        $grouped[$qid]['totalResponses'] += (int)$r['ResponseCount'];
    }

    foreach ($grouped as &$g) {
        if ($g['Type'] === 'Rating') {
            $sum = 0; $n = 0;
            foreach ($g['tally'] as $t) {
                if (is_numeric($t['value'])) {
                    $sum += $t['value'] * $t['count'];
                    $n += $t['count'];
                }
            }
            $g['average'] = $n > 0 ? round($sum / $n, 2) : null;
        }
        if ($g['Type'] === 'ShortAnswer') {
            $g['rawAnswers'] = fetchRawShortAnswers($surveyId, $g['QuestionID']);
        }
    }
    return array_values($grouped);
}

function fetchRawShortAnswers($surveyId, $questionId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT ra.AnswerText FROM response_answers ra
         JOIN responses r ON r.ResponseID = ra.ResponseID
         WHERE r.SurveyID = ? AND ra.QuestionID = ?"
    );
    $stmt->bind_param("ii", $surveyId, $questionId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    return array_column($rows, 'AnswerText');
}

function fetchRespondentsForSurvey($surveyId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT r.ResponseID, r.SubmittedAt, res.ResidentID, res.FirstName, res.LastName
         FROM responses r
         JOIN residents res ON res.ResidentID = r.ResidentID
         WHERE r.SurveyID = ? ORDER BY r.SubmittedAt DESC"
    );
    $stmt->bind_param("i", $surveyId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
