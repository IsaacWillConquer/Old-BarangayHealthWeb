<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/profileOperations.php";
include __DIR__ . "/../Models/residentAccountOperations.php"; // reuse spouse/children helpers
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeResident();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'getProfile':          getProfile(); break;
    case 'updateProfile':       updateProfile(); break;
    case 'uploadProfileImage':  uploadProfileImage(); break;
    case 'addChild':            addChild(); break;
    case 'updateChild':         updateChild(); break;
    case 'deleteChild':         deleteChild(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function fail($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function calcAge($birthdate) {
    if (!$birthdate) return null;
    $bd = new DateTime($birthdate);
    return $bd->diff(new DateTime('today'))->y;
}
function isFutureDate($date) { return strtotime($date) > strtotime('today'); }
function isTooOldDate($date, $maxYears = 120) { return strtotime($date) < strtotime("-{$maxYears} years"); }

function getProfile() {
    $residentId = $_SESSION['ResidentID'];
    $profile = fetchOwnProfile($residentId);
    $profile['age'] = calcAge($profile['Birthday']);
    $profile['spouse'] = $profile['CivilStatus'] === 'Married' ? fetchResidentSpouse($residentId) : null;
    $children = fetchResidentChildren($residentId);
    foreach ($children as &$c) { $c['Age'] = calcAge($c['Birthdate']); }
    $profile['children'] = $children;
    echo json_encode(['success' => true, 'profile' => $profile]);
    exit();
}

function updateProfile() {
    $residentId = $_SESSION['ResidentID'];

    $data = [
        'middleName'     => trim($_POST['middleName'] ?? '') ?: null,
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

    if ($data['civilStatus'] === '' || !in_array($data['civilStatus'], ['Single','Married','Widowed','Separated','Divorced'])) {
        fail('A valid civil status is required.');
    }
    if ($data['gender'] === '' || !in_array($data['gender'], ['Male','Female','Other'])) {
        fail('A valid gender is required.');
    }
    if ($data['address'] === '') fail('Address is required.');

    if ($data['contactNumber'] === '' || !preg_match('/^\d{11}$/', $data['contactNumber'])) {
        fail('Contact number must be exactly 11 digits, numbers only.');
    }
    foreach (['homePhone' => 'Home phone', 'emergencyContactNumber' => 'Emergency contact number'] as $key => $label) {
        $val = $data[$key];
        if ($val !== null && $val !== '' && !preg_match('/^\d{11}$/', $val)) {
            fail("$label must be exactly 11 digits, numbers only.");
        }
    }
    if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        fail('Email address is not valid.');
    }
    if ($data['emergencyContactName'] === '' || $data['emergencyContactRelationship'] === '' || $data['emergencyContactNumber'] === '') {
        fail('Emergency contact name, relationship, and number are all required.');
    }
    if ($data['birthday'] === '') fail('Birthday is required.');
    if (isFutureDate($data['birthday'])) fail('Birthday cannot be in the future.');
    if (isTooOldDate($data['birthday'])) fail('Birthday is too far in the past — please check the year.');

    $spouseName = null;
    if ($data['civilStatus'] === 'Married') {
        $spouseName = trim($_POST['spouseName'] ?? '');
        $spouseBirthdate = trim($_POST['spouseBirthdate'] ?? '');
        $spouseCivilStatus = trim($_POST['spouseCivilStatus'] ?? '') ?: null;
        $spouseStatus = trim($_POST['spouseStatus'] ?? 'Alive');

        if ($spouseName === '') fail('Spouse name is required when civil status is Married.');
        if ($spouseBirthdate !== '') {
            if (isFutureDate($spouseBirthdate)) fail('Spouse birthdate cannot be in the future.');
            if (isTooOldDate($spouseBirthdate)) fail('Spouse birthdate is too far in the past — please check the year.');
            if (calcAge($spouseBirthdate) < 18) fail('Spouse must be at least 18 years old.');
        }
        if (!in_array($spouseStatus, ['Alive','Deceased'])) fail('Spouse status must be Alive or Deceased.');
    }

    updateResidentProfile($residentId, $data);
    if ($data['civilStatus'] === 'Married') {
        upsertResidentSpouse($residentId, $spouseName, $spouseBirthdate ?: null, $spouseCivilStatus, $spouseStatus);
    } else {
        deleteResidentSpouse($residentId);
    }

    insertLog('Resident', $residentId, 'Updated profile');
    echo json_encode(['success' => true]);
    exit();
}

function uploadProfileImage() {
    $residentId = $_SESSION['ResidentID'];

    if (!isset($_FILES['profileImage']) || $_FILES['profileImage']['error'] !== UPLOAD_ERR_OK) {
        fail('No image uploaded.');
    }

    $file = $_FILES['profileImage'];
    $allowedTypes = ['image/jpeg', 'image/png']; // GIF removed
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        fail('Only JPG or PNG images are allowed.');
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        fail('Image must be under 3MB.');
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'resident_' . $residentId . '_' . time() . '.' . $ext;
    $destPath = __DIR__ . '/../../uploads/profile_images/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        fail('Failed to save image.');
    }

    $relativePath = 'uploads/profile_images/' . $filename;
    updateResidentProfileImage($residentId, $relativePath);
    insertLog('Resident', $residentId, 'Updated profile image');

    echo json_encode(['success' => true, 'imagePath' => BASE_URL . $relativePath]);
    exit();
}

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
            fail('Child age does not look consistent with your age — please double check the birthdate.');
        }
    }
    return [$lastName, $firstName, $birthdate, $gender, $status];
}

function addChild() {
    $residentId = $_SESSION['ResidentID'];
    $resident = fetchOwnProfile($residentId);
    [$lastName, $firstName, $birthdate, $gender, $status] = validateChildInput($resident['Birthday']);
    $childId = insertResidentChild($residentId, $lastName, $firstName, $birthdate, $gender, $status);
    insertLog('Resident', $residentId, 'Added child record');
    echo json_encode(['success' => true, 'childId' => $childId]);
    exit();
}

function updateChild() {
    $residentId = $_SESSION['ResidentID'];
    $childId = (int)($_POST['childId'] ?? 0);
    $resident = fetchOwnProfile($residentId);
    [$lastName, $firstName, $birthdate, $gender, $status] = validateChildInput($resident['Birthday']);
    updateResidentChild($childId, $residentId, $lastName, $firstName, $birthdate, $gender, $status);
    insertLog('Resident', $residentId, 'Updated child record');
    echo json_encode(['success' => true]);
    exit();
}

function deleteChild() {
    $residentId = $_SESSION['ResidentID'];
    $childId = (int)($_POST['childId'] ?? 0);
    deleteResidentChild($childId, $residentId);
    insertLog('Resident', $residentId, 'Deleted child record');
    echo json_encode(['success' => true]);
    exit();
}
?>
