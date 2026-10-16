<?php

function countTotalResidents() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS cnt FROM residents WHERE Status = 'Active'");
    return (int)$result->fetch_assoc()['cnt'];
}

function countSurveysByStatus($status) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM surveys WHERE Status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['cnt'];
}

function countTotalResponses() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS cnt FROM responses");
    return (int)$result->fetch_assoc()['cnt'];
}

function computeParticipationRate() {
    $residents = countTotalResidents();
    $responses = countTotalResponses();
    if ($residents === 0) return 0;
    $rate = ($responses / $residents) * 100;
    return (int)min(100, round($rate));
}

function getParticipationTrend() {
    global $conn;
    $residents = max(1, countTotalResidents());
    $sql = "SELECT DATE_FORMAT(SubmittedAt, '%Y-%m') AS ym, COUNT(*) AS cnt
            FROM responses
            WHERE SubmittedAt >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY ym ORDER BY ym ASC";
    $result = $conn->query($sql);
    $byMonth = [];
    while ($row = $result->fetch_assoc()) {
        $byMonth[$row['ym']] = (int)$row['cnt'];
    }

    $labels = []; $data = [];
    for ($i = 5; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-$i months"));
        $labels[] = date('M Y', strtotime("-$i months"));
        $count = $byMonth[$ym] ?? 0;
        $data[] = (int)min(100, round(($count / $residents) * 100));
    }
    return ['labels' => $labels, 'data' => $data];
}

function getNeedsAttention() {
    global $conn;

    $drafts = $conn->query(
        "SELECT SurveyID, Title FROM surveys WHERE Status = 'Draft' ORDER BY CreatedAt DESC LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC);

    $closingSoon = $conn->query(
        "SELECT SurveyID, Title, CloseAt FROM surveys
         WHERE Status = 'Active' AND CloseAt IS NOT NULL
         AND CloseAt BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
         ORDER BY CloseAt ASC LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC);

    $zeroResponse = $conn->query(
        "SELECT s.SurveyID, s.Title FROM surveys s
         WHERE s.Status = 'Active'
         AND NOT EXISTS (SELECT 1 FROM responses r WHERE r.SurveyID = s.SurveyID)
         ORDER BY s.OpenAt ASC LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC);

    return ['drafts' => $drafts, 'closingSoon' => $closingSoon, 'zeroResponse' => $zeroResponse];
}

function getRecentActivity($limit = 8) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM logs ORDER BY Timestamp DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getDashboardStats() {
    return [
        'totalResidents' => countTotalResidents(),
        'activeSurveys' => countSurveysByStatus('Active'),
        'draftSurveys' => countSurveysByStatus('Draft'),
        'closedSurveys' => countSurveysByStatus('Closed'),
        'totalResponses' => countTotalResponses(),
        'participationRate' => computeParticipationRate(),
        'upcomingEvents' => countUpcomingEvents(),
    ];
}

function countUpcomingEvents() {
    global $conn;
    $result = $conn->query(
        "SELECT COUNT(*) AS c FROM health_events WHERE IsArchived = 0 AND Status IN ('Upcoming','Active') AND EventDate >= CURDATE()"
    );
    return (int)$result->fetch_assoc()['c'];
}
?>
