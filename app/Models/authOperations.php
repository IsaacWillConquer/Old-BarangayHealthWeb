<?php

function staffByEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM staffadmin WHERE BINARY Email = BINARY ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function residentByNumber($residentNumber) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM residents WHERE BINARY ResidentNumber = BINARY ? LIMIT 1");
    $stmt->bind_param("s", $residentNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function updStaffPass($staffId, $hashedPassword) {
    global $conn;
    $stmt = $conn->prepare("UPDATE staffadmin SET Password = ?, DefaultPassword = 0 WHERE StaffID = ?");
    $stmt->bind_param("si", $hashedPassword, $staffId);
    return $stmt->execute();
}

function updResidentPass($residentId, $hashedPassword) {
    global $conn;
    $stmt = $conn->prepare("UPDATE residents SET Password = ?, DefaultPassword = 0 WHERE ResidentID = ?");
    $stmt->bind_param("si", $hashedPassword, $residentId);
    return $stmt->execute();
}
?>
