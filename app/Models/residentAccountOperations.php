<?php

// ---------------------------------------------------------
// RESIDENT — core record
// ---------------------------------------------------------

function insertResident($data) {
    global $conn;
    $hashed = password_hash($data['residentNumber'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO residents
            (ResidentNumber, Password, DefaultPassword, FirstName, MiddleName, LastName, ExtensionName,
             CivilStatus, Gender, Address, Address2, Email, ContactNumber, HomePhone,
             EmergencyContactName, EmergencyContactRelationship, EmergencyContactNumber,
             Birthday, Bio, Occupation, OccupationLocation)
         VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssssssssssssssssssss",
        $data['residentNumber'], $hashed, $data['firstName'], $data['middleName'], $data['lastName'], $data['extensionName'],
        $data['civilStatus'], $data['gender'], $data['address'], $data['address2'], $data['email'], $data['contactNumber'], $data['homePhone'],
        $data['emergencyContactName'], $data['emergencyContactRelationship'], $data['emergencyContactNumber'],
        $data['birthday'], $data['bio'], $data['occupation'], $data['occupationLocation']
    );
    $stmt->execute();
    return $conn->insert_id;
}

function fetchResidents() {
    global $conn;
    $result = $conn->query(
        "SELECT ResidentID, ResidentNumber, FirstName, LastName, ContactNumber, Address, Status, ProfileImage
         FROM residents WHERE IsArchived = 0 ORDER BY LastName, FirstName"
    );
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchArchivedResidents($page = 1, $perPage = 10) {
    global $conn;
    $offset = ($page - 1) * $perPage;
    $stmt = $conn->prepare(
        "SELECT ResidentID, ResidentNumber, FirstName, LastName, ArchivedAt,
                (SELECT CONCAT(FirstName, ' ', LastName) FROM staffadmin WHERE StaffID = residents.ArchivedBy) AS ArchivedByName
         FROM residents WHERE IsArchived = 1 ORDER BY ArchivedAt DESC LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $total = $conn->query("SELECT COUNT(*) AS c FROM residents WHERE IsArchived = 1")->fetch_assoc()['c'];
    return ['rows' => $rows, 'total' => (int)$total, 'page' => $page, 'totalPages' => max(1, ceil($total / $perPage))];
}

function fetchResidentById($residentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM residents WHERE ResidentID = ?");
    $stmt->bind_param("i", $residentId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function updateResidentRecord($residentId, $data) {
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE residents SET
            ResidentNumber = ?, FirstName = ?, MiddleName = ?, LastName = ?, ExtensionName = ?,
            CivilStatus = ?, Gender = ?, Address = ?, Address2 = ?, Email = ?, ContactNumber = ?, HomePhone = ?,
            EmergencyContactName = ?, EmergencyContactRelationship = ?, EmergencyContactNumber = ?,
            Birthday = ?, Bio = ?, Occupation = ?, OccupationLocation = ?
         WHERE ResidentID = ?"
    );
    $stmt->bind_param(
        "sssssssssssssssssssi",
        $data['residentNumber'], $data['firstName'], $data['middleName'], $data['lastName'], $data['extensionName'],
        $data['civilStatus'], $data['gender'], $data['address'], $data['address2'], $data['email'], $data['contactNumber'], $data['homePhone'],
        $data['emergencyContactName'], $data['emergencyContactRelationship'], $data['emergencyContactNumber'],
        $data['birthday'], $data['bio'], $data['occupation'], $data['occupationLocation'], $residentId
    );
    return $stmt->execute();
}

function setResidentStatus($residentId, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE residents SET Status = ? WHERE ResidentID = ?");
    $stmt->bind_param("si", $status, $residentId);
    return $stmt->execute();
}

function archiveResident($residentId, $staffId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE residents SET IsArchived = 1, ArchivedAt = NOW(), ArchivedBy = ? WHERE ResidentID = ?");
    $stmt->bind_param("ii", $staffId, $residentId);
    return $stmt->execute();
}

function restoreResident($residentId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE residents SET IsArchived = 0, ArchivedAt = NULL, ArchivedBy = NULL WHERE ResidentID = ?");
    $stmt->bind_param("i", $residentId);
    return $stmt->execute();
}

function residentNumberExists($residentNumber, $excludeId = null) {
    global $conn;
    if ($excludeId) {
        $stmt = $conn->prepare("SELECT 1 FROM residents WHERE ResidentNumber = ? AND ResidentID != ?");
        $stmt->bind_param("si", $residentNumber, $excludeId);
    } else {
        $stmt = $conn->prepare("SELECT 1 FROM residents WHERE ResidentNumber = ?");
        $stmt->bind_param("s", $residentNumber);
    }
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function resetResidentPasswordDb($residentId, $hashedPassword) {
    global $conn;
    $stmt = $conn->prepare("UPDATE residents SET Password = ?, DefaultPassword = 1 WHERE ResidentID = ?");
    $stmt->bind_param("si", $hashedPassword, $residentId);
    return $stmt->execute();
}

// ---------------------------------------------------------
// SPOUSE — 1-to-1, hard delete when unmarried (per scope: not archived)
// ---------------------------------------------------------

function fetchResidentSpouse($residentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM resident_spouse WHERE ResidentID = ?");
    $stmt->bind_param("i", $residentId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function upsertResidentSpouse($residentId, $name, $birthdate, $civilStatus, $status) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO resident_spouse (ResidentID, SpouseName, SpouseBirthdate, SpouseCivilStatus, SpouseStatus)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE SpouseName = VALUES(SpouseName), SpouseBirthdate = VALUES(SpouseBirthdate),
                                  SpouseCivilStatus = VALUES(SpouseCivilStatus), SpouseStatus = VALUES(SpouseStatus)"
    );
    $stmt->bind_param("issss", $residentId, $name, $birthdate, $civilStatus, $status);
    return $stmt->execute();
}

function deleteResidentSpouse($residentId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM resident_spouse WHERE ResidentID = ?");
    $stmt->bind_param("i", $residentId);
    return $stmt->execute();
}

// ---------------------------------------------------------
// CHILDREN — 1-to-many, hard delete per row (per scope: not archived)
// ---------------------------------------------------------

function fetchResidentChildren($residentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM resident_children WHERE ResidentID = ? ORDER BY Birthdate DESC");
    $stmt->bind_param("i", $residentId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function insertResidentChild($residentId, $lastName, $firstName, $birthdate, $gender, $status) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO resident_children (ResidentID, LastName, FirstName, Birthdate, Gender, Status)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssss", $residentId, $lastName, $firstName, $birthdate, $gender, $status);
    $stmt->execute();
    return $conn->insert_id;
}

function updateResidentChild($childId, $residentId, $lastName, $firstName, $birthdate, $gender, $status) {
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE resident_children SET LastName = ?, FirstName = ?, Birthdate = ?, Gender = ?, Status = ?
         WHERE ChildID = ? AND ResidentID = ?"
    );
    $stmt->bind_param("sssssii", $lastName, $firstName, $birthdate, $gender, $status, $childId, $residentId);
    return $stmt->execute();
}

function deleteResidentChild($childId, $residentId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM resident_children WHERE ChildID = ? AND ResidentID = ?");
    $stmt->bind_param("ii", $childId, $residentId);
    return $stmt->execute();
}
?>
