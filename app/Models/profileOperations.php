<?php

function updateResidentProfile($residentId, $data) {
    global $conn;
    $stmt = $conn->prepare(
        "UPDATE residents SET
            MiddleName = ?, ExtensionName = ?, CivilStatus = ?, Gender = ?,
            Address = ?, Address2 = ?, Email = ?, ContactNumber = ?, HomePhone = ?,
            EmergencyContactName = ?, EmergencyContactRelationship = ?, EmergencyContactNumber = ?,
            Birthday = ?, Bio = ?, Occupation = ?, OccupationLocation = ?
         WHERE ResidentID = ?"
    );
    $stmt->bind_param(
        "ssssssssssssssssi",
        $data['middleName'], $data['extensionName'], $data['civilStatus'], $data['gender'],
        $data['address'], $data['address2'], $data['email'], $data['contactNumber'], $data['homePhone'],
        $data['emergencyContactName'], $data['emergencyContactRelationship'], $data['emergencyContactNumber'],
        $data['birthday'], $data['bio'], $data['occupation'], $data['occupationLocation'], $residentId
    );
    return $stmt->execute();
}

function updateResidentProfileImage($residentId, $imagePath) {
    global $conn;
    $stmt = $conn->prepare("UPDATE residents SET ProfileImage = ? WHERE ResidentID = ?");
    $stmt->bind_param("si", $imagePath, $residentId);
    return $stmt->execute();
}

function fetchOwnProfile($residentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM residents WHERE ResidentID = ?");
    $stmt->bind_param("i", $residentId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
