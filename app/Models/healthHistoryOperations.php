<?php

function insertHealthHistory($residentId, $conditionType, $otherText, $recordDate, $status, $notes, $staffId) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO resident_health_history
            (ResidentID, ConditionType, OtherConditionText, RecordDate, Status, Notes, RecordedByStaffID)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssssi", $residentId, $conditionType, $otherText, $recordDate, $status, $notes, $staffId);
    $stmt->execute();
    return $conn->insert_id;
}

function fetchHealthHistory($residentId, $includeVoided = false) {
    global $conn;
    $sql = "SELECT h.*, CONCAT(s.FirstName, ' ', s.LastName) AS RecordedByName,
                   CONCAT(v.FirstName, ' ', v.LastName) AS VoidedByName
            FROM resident_health_history h
            LEFT JOIN staffadmin s ON s.StaffID = h.RecordedByStaffID
            LEFT JOIN staffadmin v ON v.StaffID = h.VoidedBy
            WHERE h.ResidentID = ?" . ($includeVoided ? "" : " AND h.IsVoided = 0") . "
            ORDER BY h.RecordDate DESC, h.CreatedAt DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $residentId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetchHealthHistoryById($recordId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM resident_health_history WHERE RecordID = ?");
    $stmt->bind_param("i", $recordId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function updateHealthHistory($recordId, $conditionType, $otherText, $recordDate, $status, $notes) {
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE resident_health_history SET ConditionType = ?, OtherConditionText = ?, RecordDate = ?, Status = ?, Notes = ?
         WHERE RecordID = ? AND IsVoided = 0"
    );
    $stmt->bind_param("sssssi", $conditionType, $otherText, $recordDate, $status, $notes, $recordId);
    return $stmt->execute();
}

function voidHealthHistory($recordId, $staffId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE resident_health_history SET IsVoided = 1, VoidedAt = NOW(), VoidedBy = ? WHERE RecordID = ?");
    $stmt->bind_param("ii", $staffId, $recordId);
    return $stmt->execute();
}

function restoreHealthHistory($recordId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE resident_health_history SET IsVoided = 0, VoidedAt = NULL, VoidedBy = NULL WHERE RecordID = ?");
    $stmt->bind_param("i", $recordId);
    return $stmt->execute();
}

function fetchConditionSummary($startDate = null, $endDate = null) {
    global $conn;
    $sql = "SELECT ConditionType, COUNT(*) AS Total FROM resident_health_history WHERE IsVoided = 0";
    $params = []; $types = "";
    if ($startDate) { $sql .= " AND RecordDate >= ?"; $params[] = $startDate; $types .= "s"; }
    if ($endDate)   { $sql .= " AND RecordDate <= ?"; $params[] = $endDate; $types .= "s"; }
    $sql .= " GROUP BY ConditionType ORDER BY Total DESC";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetchStatusSummary($startDate = null, $endDate = null) {
    global $conn;
    $sql = "SELECT Status, COUNT(*) AS Total FROM resident_health_history WHERE IsVoided = 0";
    $params = []; $types = "";
    if ($startDate) { $sql .= " AND RecordDate >= ?"; $params[] = $startDate; $types .= "s"; }
    if ($endDate)   { $sql .= " AND RecordDate <= ?"; $params[] = $endDate; $types .= "s"; }
    $sql .= " GROUP BY Status";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
