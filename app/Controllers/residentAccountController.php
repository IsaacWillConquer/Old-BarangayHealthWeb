<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/residentAccountOperations.php";
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeStaff();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'createResident':     createResident(); break;
    case 'listResidents':      listResidents(); break;
    case 'listArchivedResidents': listArchivedResidents(); break;
    case 'getResident':        getResident(); break;
    case 'updateResident':     updateResident(); break;
    case 'deactivateResident': deactivateResident(); break;
    case 'reactivateResident': reactivateResident(); break;
    case 'archiveResident':    archiveResidentAction(); break;
    case 'restoreResident':    restoreResidentAction(); break;
    case 'resetResidentPassword': resetResidentPassword(); break;
    case 'addChild':           addChild(); break;
    case 'updateChild':        updateChild(); break;
    case 'deleteChild':        deleteChild(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

// ---------------------------------------------------------
// Shared validation helpers
// ---------------------------------------------------------

function calcAge($birthdate) {
    if (!$birthdate) return null;
    $bd = new DateTime($birthdate);
    $today = new DateTime('today');
    return $bd->diff($today)->y;
}

function isFutureDate($date) {
    return strtotime($date) > strtotime('today');
}

function isTooOldDate($date, $maxYears = 120) {
    return strtotime($date) < strtotime("-{$maxYears} years");
}

function fail($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

/**
 * Validates the full resident field set (shared by create + update).
 * Returns the cleaned $data array, or calls fail() and exits on error.
 */
function validateResidentInput($excludeId = null) {
    $data = [
        'residentNumber' => trim($_POST['residentNumber'] ?? ''),
        'firstName'      => trim($_POST['firstName'] ?? ''),
        'middleName'     => trim($_POST['middleName'] ?? '') ?: null,
        'lastName'       => trim($_POST['lastName'] ?? ''),
        'extensionName'  => trim($_POST['extensionName'] ?? '') ?: null,
        'civilStatus'    => trim($_POST['civilStatus'] ?? ''),
        'gender'         => trim($_POST['gender'] ?? ''),
        'address'        => trim($_POST['address'] ?? ''),
        'address2'       => trim($_POST['address2'] ?? '') ?: null,
        'email'          => trim($_POST['email'] ?? '') ?: null,
        'contactNumber'  => trim($_POST['contactNumber'] ?? ''),
        'homePhone'      => trim($_POST['homePhone'] ?? '') ?: null,
        'emergencyContactName'         => trim($_POST['emergencyContactName'] ?? ''),
        'emergencyContactRelationship' => trim($_POST['emergencyContactRelationship'] ?? ''),
        'emergencyContactNumber'       => trim($_POST['emergencyContactNumber'] ?? ''),
        'birthday'       => trim($_POST['birthday'] ?? ''),
        'bio'            => trim($_POST['bio'] ?? '') ?: null,
        'occupation'     => trim($_POST['occupation'] ?? '') ?: null,
        'occupationLocation' => trim($_POST['occupationLocation'] ?? '') ?: null,
    ];

    // Required core fields
    if ($data['residentNumber'] === '' || $data['firstName'] === '' || $data['lastName'] === '') {
        fail('Resident number, first name, and last name are required.');
    }
    if ($data['civilStatus'] === '' || !in_array($data['civilStatus'], ['Single','Married','Widowed','Separated','Divorced'])) {
        fail('A valid civil status is required.');
    }
    if ($data['gender'] === '' || !in_array($data['gender'], ['Male','Female','Other'])) {
        fail('A valid gender is required.');
    }
    if ($data['address'] === '') {
        fail('Address is required.');
    }

    // Phone fields — 11 digits, numeric only
    $phoneFields = ['contactNumber' => 'Contact number', 'homePhone' => 'Home phone', 'emergencyContactNumber' => 'Emergency contact number'];
    foreach ($phoneFields as $key => $label) {
        $val = $data[$key];
        if ($key === 'contactNumber' && $val === '') fail('Contact number is required.');
        if ($val !== null && $val !== '' && !preg_match('/^\d{11}$/', $val)) {
            fail("$label must be exactly 11 digits, numbers only.");
        }
    }

    // Email format, only if provided
    if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        fail('Email address is not valid.');
    }

    // Emergency contact — required trio (all or nothing)
    if ($data['emergencyContactName'] === '' || $data['emergencyContactRelationship'] === '' || $data['emergencyContactNumber'] === '') {
        fail('Emergency contact name, relationship, and number are all required.');
    }

    // Birthday — required, not future, not older than 120 years
    if ($data['birthday'] === '') {
        fail('Birthday is required.');
    }
    if (isFutureDate($data['birthday'])) {
        fail('Birthday cannot be in the future.');
    }
    if (isTooOldDate($data['birthday'])) {
        fail('Birthday is too far in the past — please check the year.');
    }

    // Resident number uniqueness
    if (residentNumberExists($data['residentNumber'], $excludeId)) {
        fail('That resident number is already registered.');
    }

    return $data;
}

/**
 * Validates spouse fields if Civil Status = Married. Returns spouse array or null.
 */
function validateSpouseInput($civilStatus, $residentBirthday) {
    if ($civilStatus !== 'Married') return null;

    $name = trim($_POST['spouseName'] ?? '');
    $birthdate = trim($_POST['spouseBirthdate'] ?? '');
    $spouseCivilStatus = trim($_POST['spouseCivilStatus'] ?? '') ?: null;
    $status = trim($_POST['spouseStatus'] ?? 'Alive');

    if ($name === '') {
        fail('Spouse name is required when civil status is Married.');
    }
    if ($birthdate !== '') {
        if (isFutureDate($birthdate)) fail('Spouse birthdate cannot be in the future.');
        if (isTooOldDate($birthdate)) fail('Spouse birthdate is too far in the past — please check the year.');
        if (calcAge($birthdate) < 18) fail('Spouse must be at least 18 years old.');
    }
    if (!in_array($status, ['Alive','Deceased'])) {
        fail('Spouse status must be Alive or Deceased.');
    }

    return [$name, $birthdate ?: null, $spouseCivilStatus, $status];
}

// ---------------------------------------------------------
// Resident actions
// ---------------------------------------------------------

function createResident() {
    $data = validateResidentInput();
    $spouse = validateSpouseInput($data['civilStatus'], $data['birthday']);

    $id = insertResident($data);
    if ($spouse) {
        upsertResidentSpouse($id, $spouse[0], $spouse[1], $spouse[2], $spouse[3]);
    }

    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Created resident account', 'Resident', $id);
    echo json_encode(['success' => true, 'residentId' => $id, 'message' => 'Account created. Default password is the Resident Number: ' . $data['residentNumber']]);
    exit();
}

function listResidents() {
    echo json_encode(['success' => true, 'residents' => fetchResidents()]);
    exit();
}

function listArchivedResidents() {
    $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
    echo json_encode(['success' => true] + fetchArchivedResidents($page));
    exit();
}

function getResident() {
    $id = (int)($_POST['residentId'] ?? $_GET['residentId'] ?? 0);
    $resident = fetchResidentById($id);
    if (!$resident) fail('Resident not found.');

    $resident['age'] = calcAge($resident['Birthday']);
    $resident['spouse'] = $resident['CivilStatus'] === 'Married' ? fetchResidentSpouse($id) : null;
    $children = fetchResidentChildren($id);
    foreach ($children as &$c) { $c['Age'] = calcAge($c['Birthdate']); }
    $resident['children'] = $children;

    echo json_encode(['success' => true, 'resident' => $resident]);
    exit();
}

function updateResident() {
    $id = (int)($_POST['residentId'] ?? 0);
    if (!$id || !fetchResidentById($id)) fail('Resident not found.');

    $data = validateResidentInput($id);
    $spouse = validateSpouseInput($data['civilStatus'], $data['birthday']);

    updateResidentRecord($id, $data);
    if ($spouse) {
        upsertResidentSpouse($id, $spouse[0], $spouse[1], $spouse[2], $spouse[3]);
    } else {
        deleteResidentSpouse($id); // no longer married — remove spouse record
    }

    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Updated resident account', 'Resident', $id);
    echo json_encode(['success' => true]);
    exit();
}

function deactivateResident() {
    $id = (int)($_POST['residentId'] ?? 0);
    setResidentStatus($id, 'Suspended');
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Deactivated resident account', 'Resident', $id);
    echo json_encode(['success' => true]);
    exit();
}

function reactivateResident() {
    $id = (int)($_POST['residentId'] ?? 0);
    setResidentStatus($id, 'Active');
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Reactivated resident account', 'Resident', $id);
    echo json_encode(['success' => true]);
    exit();
}

function archiveResidentAction() {
    $id = (int)($_POST['residentId'] ?? 0);
    if (!fetchResidentById($id)) fail('Resident not found.');
    archiveResident($id, $_SESSION['StaffID']);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Archived resident account', 'Resident', $id);
    echo json_encode(['success' => true]);
    exit();
}

function restoreResidentAction() {
    $id = (int)($_POST['residentId'] ?? 0);
    restoreResident($id);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Restored resident account', 'Resident', $id);
    echo json_encode(['success' => true]);
    exit();
}

function resetResidentPassword() {
    $id = (int)($_POST['residentId'] ?? 0);
    $custom = trim($_POST['newPassword'] ?? '');

    if (!fetchResidentById($id)) fail('Resident not found.');
    if ($custom !== '' && strlen($custom) < 7) fail('Password must be at least 7 characters.');

    $newPassword = $custom !== '' ? $custom : bin2hex(random_bytes(4));
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    resetResidentPasswordDb($id, $hashed);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Reset resident password', 'Resident', $id);

    echo json_encode(['success' => true, 'newPassword' => $newPassword, 'message' => 'Password reset. The resident must change it on next login.']);
    exit();
}

// ---------------------------------------------------------
// Children actions
// ---------------------------------------------------------

function validateChildInput($residentBirthday) {
    $lastName = trim($_POST['lastName'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $gender = trim($_POST['gender'] ?? '') ?: null;
    $status = trim($_POST['status'] ?? 'Living');

    if ($lastName === '' || $firstName === '') fail('Child last name and first name are required.');
    if ($birthdate === '') fail('Child birthdate is required.');
    if (isFutureDate($birthdate)) fail('Child birthdate cannot be in the future.');
    if (isTooOldDate($birthdate)) fail('Child birthdate is too far in the past — please check the year.');
    if (!in_array($status, ['Living','Deceased'])) fail('Child status must be Living or Deceased.');

    if ($residentBirthday) {
        $parentAge = calcAge($residentBirthday);
        $childAge = calcAge($birthdate);
        if (($parentAge - $childAge) < 12) {
            fail('Child age does not look consistent with the resident\'s age — please double check the birthdate.');
        }
    }

    return [$lastName, $firstName, $birthdate, $gender, $status];
}

function addChild() {
    $residentId = (int)($_POST['residentId'] ?? 0);
    $resident = fetchResidentById($residentId);
    if (!$resident) fail('Resident not found.');

    [$lastName, $firstName, $birthdate, $gender, $status] = validateChildInput($resident['Birthday']);
    $childId = insertResidentChild($residentId, $lastName, $firstName, $birthdate, $gender, $status);

    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Added child record', 'Resident', $residentId);
    echo json_encode(['success' => true, 'childId' => $childId]);
    exit();
}

function updateChild() {
    $childId = (int)($_POST['childId'] ?? 0);
    $residentId = (int)($_POST['residentId'] ?? 0);
    $resident = fetchResidentById($residentId);
    if (!$resident) fail('Resident not found.');

    [$lastName, $firstName, $birthdate, $gender, $status] = validateChildInput($resident['Birthday']);
    updateResidentChild($childId, $residentId, $lastName, $firstName, $birthdate, $gender, $status);

    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Updated child record', 'Resident', $residentId);
    echo json_encode(['success' => true]);
    exit();
}

function deleteChild() {
    $childId = (int)($_POST['childId'] ?? 0);
    $residentId = (int)($_POST['residentId'] ?? 0);
    deleteResidentChild($childId, $residentId);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Deleted child record', 'Resident', $residentId);
    echo json_encode(['success' => true]);
    exit();
}
?>
