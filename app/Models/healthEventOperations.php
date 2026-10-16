<?php

// ---------------------------------------------------------
// HEALTH EVENTS — core record
// ---------------------------------------------------------

function insertHealthEvent($data, $staffId) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO health_events
            (Title, EventType, Description, Location, EventDate, StartTime, EndTime, Status, ImagePath, CreatedBy)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "sssssssssi",
        $data['title'], $data['eventType'], $data['description'], $data['location'],
        $data['eventDate'], $data['startTime'], $data['endTime'], $data['status'], $data['imagePath'], $staffId
    );
    $stmt->execute();
    return $conn->insert_id;
}

function fetchHealthEvents() {
    global $conn;
    $result = $conn->query(
        "SELECT e.*, (SELECT COUNT(*) FROM event_signups s WHERE s.EventID = e.EventID) AS SignupCount
         FROM health_events e WHERE e.IsArchived = 0 ORDER BY e.EventDate ASC"
    );
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchArchivedHealthEvents($page = 1, $perPage = 10) {
    global $conn;
    $offset = ($page - 1) * $perPage;
    $stmt = $conn->prepare(
        "SELECT e.EventID, e.Title, e.EventDate, e.ArchivedAt,
                (SELECT CONCAT(FirstName, ' ', LastName) FROM staffadmin WHERE StaffID = e.ArchivedBy) AS ArchivedByName
         FROM health_events e WHERE e.IsArchived = 1 ORDER BY e.ArchivedAt DESC LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $total = $conn->query("SELECT COUNT(*) AS c FROM health_events WHERE IsArchived = 1")->fetch_assoc()['c'];
    return ['rows' => $rows, 'total' => (int)$total, 'page' => $page, 'totalPages' => max(1, ceil($total / $perPage))];
}

function fetchHealthEventById($eventId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT e.*, (SELECT COUNT(*) FROM event_signups s WHERE s.EventID = e.EventID) AS SignupCount
         FROM health_events e WHERE e.EventID = ?"
    );
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function updateHealthEvent($eventId, $data) {
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE health_events SET
            Title = ?, EventType = ?, Description = ?, Location = ?, EventDate = ?,
            StartTime = ?, EndTime = ?, Status = ?
         WHERE EventID = ?"
    );
    $stmt->bind_param(
        "ssssssssi",
        $data['title'], $data['eventType'], $data['description'], $data['location'],
        $data['eventDate'], $data['startTime'], $data['endTime'], $data['status'], $eventId
    );
    return $stmt->execute();
}

function updateHealthEventImage($eventId, $imagePath) {
    global $conn;
    $stmt = $conn->prepare("UPDATE health_events SET ImagePath = ? WHERE EventID = ?");
    $stmt->bind_param("si", $imagePath, $eventId);
    return $stmt->execute();
}

function archiveHealthEvent($eventId, $staffId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE health_events SET IsArchived = 1, ArchivedAt = NOW(), ArchivedBy = ? WHERE EventID = ?");
    $stmt->bind_param("ii", $staffId, $eventId);
    return $stmt->execute();
}

function restoreHealthEvent($eventId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE health_events SET IsArchived = 0, ArchivedAt = NULL, ArchivedBy = NULL WHERE EventID = ?");
    $stmt->bind_param("i", $eventId);
    return $stmt->execute();
}

// ---------------------------------------------------------
// RESIDENT-FACING: active/upcoming events + signups
// ---------------------------------------------------------

function fetchVisibleEventsForResident() {
    global $conn;
    $result = $conn->query(
        "SELECT * FROM health_events
         WHERE IsArchived = 0 AND Status IN ('Upcoming','Active')
         ORDER BY EventDate ASC"
    );
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchResidentSignedUpEventIds($residentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT EventID FROM event_signups WHERE ResidentID = ?");
    $stmt->bind_param("i", $residentId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    return array_column($rows, 'EventID');
}

function signUpForEvent($eventId, $residentId) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO event_signups (EventID, ResidentID) VALUES (?, ?)");
    $stmt->bind_param("ii", $eventId, $residentId);
    return $stmt->execute();
}

function cancelEventSignup($eventId, $residentId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM event_signups WHERE EventID = ? AND ResidentID = ?");
    $stmt->bind_param("ii", $eventId, $residentId);
    return $stmt->execute();
}

function fetchEventSignups($eventId) {
    global $conn;
    $stmt = $conn->prepare(
        "SELECT s.SignupID, s.SignedUpAt, r.ResidentID, r.ResidentNumber, r.FirstName, r.LastName, r.ContactNumber
         FROM event_signups s JOIN residents r ON r.ResidentID = s.ResidentID
         WHERE s.EventID = ? ORDER BY s.SignedUpAt ASC"
    );
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
