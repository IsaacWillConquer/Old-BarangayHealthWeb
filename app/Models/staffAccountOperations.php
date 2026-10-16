<?php

function insertStaff($firstName, $lastName, $email, $role) {
    global $conn;
    $defaultPass = 'StaffPassword';
    $hashed = password_hash($defaultPass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO staffadmin (FirstName, LastName, Email, Password, Role, DefaultPassword)
         VALUES (?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashed, $role);
    $stmt->execute();
    return $conn->insert_id;
}

function staffEmailExists($email, $excludeId = null) {
    global $conn;
    if ($excludeId) {
        $stmt = $conn->prepare("SELECT 1 FROM staffadmin WHERE Email = ? AND StaffID != ?");
        $stmt->bind_param("si", $email, $excludeId);
    } else {
        $stmt = $conn->prepare("SELECT 1 FROM staffadmin WHERE Email = ?");
        $stmt->bind_param("s", $email);
    }
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function fetchStaffList() {
    global $conn;
    $result = $conn->query(
        "SELECT StaffID, FirstName, LastName, Email, Role, Status, ProfileImage
         FROM staffadmin WHERE IsArchived = 0 ORDER BY LastName, FirstName"
    );
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchArchivedStaff($page = 1, $perPage = 10) {
    global $conn;
    $offset = ($page - 1) * $perPage;
    $stmt = $conn->prepare(
        "SELECT s.StaffID, s.FirstName, s.LastName, s.Role, s.ArchivedAt,
                (SELECT CONCAT(a.FirstName, ' ', a.LastName) FROM staffadmin a WHERE a.StaffID = s.ArchivedBy) AS ArchivedByName
         FROM staffadmin s WHERE s.IsArchived = 1 ORDER BY s.ArchivedAt DESC LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $total = $conn->query("SELECT COUNT(*) AS c FROM staffadmin WHERE IsArchived = 1")->fetch_assoc()['c'];
    return ['rows' => $rows, 'total' => (int)$total, 'page' => $page, 'totalPages' => max(1, ceil($total / $perPage))];
}

function fetchStaffById($staffId) {
    global $conn;
    $stmt = $conn->prepare("SELECT StaffID, FirstName, LastName, Email, Role, Status, ProfileImage FROM staffadmin WHERE StaffID = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function updateStaffRecord($staffId, $firstName, $lastName, $email, $role) {
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE staffadmin SET FirstName = ?, LastName = ?, Email = ?, Role = ? WHERE StaffID = ?"
    );
    $stmt->bind_param("ssssi", $firstName, $lastName, $email, $role, $staffId);
    return $stmt->execute();
}

function setStaffStatus($staffId, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE staffadmin SET Status = ? WHERE StaffID = ?");
    $stmt->bind_param("si", $status, $staffId);
    return $stmt->execute();
}

function archiveStaff($staffId, $archivedByStaffId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE staffadmin SET IsArchived = 1, ArchivedAt = NOW(), ArchivedBy = ? WHERE StaffID = ?");
    $stmt->bind_param("ii", $archivedByStaffId, $staffId);
    return $stmt->execute();
}

function restoreStaff($staffId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE staffadmin SET IsArchived = 0, ArchivedAt = NULL, ArchivedBy = NULL WHERE StaffID = ?");
    $stmt->bind_param("i", $staffId);
    return $stmt->execute();
}

function resetStaffPasswordDb($staffId, $hashedPassword) {
    global $conn;
    $stmt = $conn->prepare("UPDATE staffadmin SET Password = ?, DefaultPassword = 1 WHERE StaffID = ?");
    $stmt->bind_param("si", $hashedPassword, $staffId);
    return $stmt->execute();
}
?>
