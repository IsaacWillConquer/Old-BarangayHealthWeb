<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/staffAccountOperations.php";
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

adminOnly();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'createStaff':     createStaff(); break;
    case 'listStaff':       listStaff(); break;
    case 'listArchivedStaff': listArchivedStaff(); break;
    case 'getStaff':        getStaff(); break;
    case 'updateStaff':     updateStaff(); break;
    case 'deactivateStaff': deactivateStaff(); break;
    case 'reactivateStaff': reactivateStaff(); break;
    case 'archiveStaff':    archiveStaffAction(); break;
    case 'restoreStaff':    restoreStaffAction(); break;
    case 'resetStaffPassword': resetStaffPassword(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function fail($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function createStaff() {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'Staff';

    if ($firstName === '' || $lastName === '' || $email === '') {
        fail('First name, last name, and email are required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fail('A valid email address is required.');
    }
    if (!in_array($role, ['Admin', 'Staff'])) {
        fail('Invalid role.');
    }
    if (staffEmailExists($email)) {
        fail('That email is already registered.');
    }

    $id = insertStaff($firstName, $lastName, $email, $role);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Created staff/admin account', 'StaffAdmin', $id);
    echo json_encode(['success' => true, 'staffId' => $id, 'message' => 'Account created. Default password: StaffPassword']);
    exit();
}

function listStaff() {
    echo json_encode(['success' => true, 'staff' => fetchStaffList()]);
    exit();
}

function listArchivedStaff() {
    $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
    echo json_encode(['success' => true] + fetchArchivedStaff($page));
    exit();
}

function getStaff() {
    $id = (int)($_POST['staffId'] ?? $_GET['staffId'] ?? 0);
    $staff = fetchStaffById($id);
    if (!$staff) fail('Account not found.');
    echo json_encode(['success' => true, 'staff' => $staff]);
    exit();
}

function updateStaff() {
    $id = (int)($_POST['staffId'] ?? 0);
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'Staff';

    if ($firstName === '' || $lastName === '' || $email === '') {
        fail('First name, last name, and email are required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fail('A valid email address is required.');
    }
    if (staffEmailExists($email, $id)) {
        fail('That email is already registered.');
    }
    updateStaffRecord($id, $firstName, $lastName, $email, $role);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Updated staff/admin account', 'StaffAdmin', $id);
    echo json_encode(['success' => true]);
    exit();
}

function deactivateStaff() {
    $id = (int)($_POST['staffId'] ?? 0);
    if ($id === (int)$_SESSION['StaffID']) {
        fail('You cannot deactivate your own account.');
    }
    setStaffStatus($id, 'Deactivated');
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Deactivated staff/admin account', 'StaffAdmin', $id);
    echo json_encode(['success' => true]);
    exit();
}

function reactivateStaff() {
    $id = (int)($_POST['staffId'] ?? 0);
    setStaffStatus($id, 'Active');
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Reactivated staff/admin account', 'StaffAdmin', $id);
    echo json_encode(['success' => true]);
    exit();
}

function archiveStaffAction() {
    $id = (int)($_POST['staffId'] ?? 0);
    if ($id === (int)$_SESSION['StaffID']) {
        fail('You cannot archive your own account.');
    }
    if (!fetchStaffById($id)) fail('Account not found.');
    archiveStaff($id, $_SESSION['StaffID']);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Archived staff/admin account', 'StaffAdmin', $id);
    echo json_encode(['success' => true]);
    exit();
}

function restoreStaffAction() {
    $id = (int)($_POST['staffId'] ?? 0);
    restoreStaff($id);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Restored staff/admin account', 'StaffAdmin', $id);
    echo json_encode(['success' => true]);
    exit();
}

function resetStaffPassword() {
    $id = (int)($_POST['staffId'] ?? 0);
    $custom = trim($_POST['newPassword'] ?? '');

    if (!fetchStaffById($id)) fail('Account not found.');
    if ($custom !== '' && strlen($custom) < 7) fail('Password must be at least 7 characters.');

    $newPassword = $custom !== '' ? $custom : bin2hex(random_bytes(4));
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    resetStaffPasswordDb($id, $hashed);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Reset staff/admin password', 'StaffAdmin', $id);

    echo json_encode(['success' => true, 'newPassword' => $newPassword, 'message' => 'Password reset. The account must change it on next login.']);
    exit();
}
?>
