<?php

function insertLog($actorType, $actorId, $action, $targetType = null, $targetId = null, $ip = null) {
    global $conn;
    $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '');
    $stmt = $conn->prepare(
        "INSERT INTO logs (ActorType, ActorID, Action, TargetType, TargetID, IPAddress)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sissis", $actorType, $actorId, $action, $targetType, $targetId, $ip);
    return $stmt->execute();
}

function fetchLogs($actorTypeFilter = null, $dateFrom = null, $dateTo = null, $page = 1, $pageSize = 15) {
    global $conn;
    $sql = "SELECT * FROM logs WHERE 1=1";
    $types = "";
    $params = [];

    if ($actorTypeFilter) {
        $sql .= " AND ActorType = ?";
        $types .= "s";
        $params[] = $actorTypeFilter;
    }
    if ($dateFrom) {
        $sql .= " AND Timestamp >= ?";
        $types .= "s";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $sql .= " AND Timestamp <= ?";
        $types .= "s";
        $params[] = $dateTo;
    }

    $page = max(1, (int)$page);
    $pageSize = max(1, (int)$pageSize);
    $offset = ($page - 1) * $pageSize;

    $sql .= " ORDER BY Timestamp DESC LIMIT ? OFFSET ?";
    $types .= "ii";
    $params[] = $pageSize;
    $params[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function countLogs($actorTypeFilter = null, $dateFrom = null, $dateTo = null) {
    global $conn;
    $sql = "SELECT COUNT(*) AS cnt FROM logs WHERE 1=1";
    $types = "";
    $params = [];

    if ($actorTypeFilter) { $sql .= " AND ActorType = ?"; $types .= "s"; $params[] = $actorTypeFilter; }
    if ($dateFrom) { $sql .= " AND Timestamp >= ?"; $types .= "s"; $params[] = $dateFrom; }
    if ($dateTo) { $sql .= " AND Timestamp <= ?"; $types .= "s"; $params[] = $dateTo; }

    $stmt = $conn->prepare($sql);
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['cnt'];
}
?>
