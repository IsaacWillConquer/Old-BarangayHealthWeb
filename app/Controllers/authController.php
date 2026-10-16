<?php
ob_start();
session_start();

include __DIR__ . "/../Models/authOperations.php";
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/appConfig.php";
require_once __DIR__ . "/../../config/dbConn.php";

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'loginDaUser':
        login();
        break;
    case 'changepass':
        changepass();
        break;
    case 'skipChangepass':
        skipChangepass();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function login() {
    global $conn;
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if ($identifier === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Please fill in both fields.']);
        exit();
    }

    $staff = staffByEmail($identifier);
    if ($staff) {
        if ($staff['Status'] !== 'Active') {
            echo json_encode(['success' => false, 'message' => 'This account has been deactivated.']);
            exit();
        }
        if (!password_verify($password, $staff['Password'])) {
            insertLog('StaffAdmin', $staff['StaffID'], 'Login failed (wrong password)', null, null, $ip);
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit();
        }
        $_SESSION['StaffID'] = $staff['StaffID'];
        $_SESSION['Role'] = $staff['Role'];
        $_SESSION['DefaultPassword'] = (int)$staff['DefaultPassword'];
        $_SESSION['FirstName'] = $staff['FirstName'];

        insertLog('StaffAdmin', $staff['StaffID'], 'Login success', null, null, $ip);

        $redirect = $staff['DefaultPassword'] == 1
            ? BASE_URL . 'app/Views/Auth/changePass.php'
            : ($staff['Role'] === 'Admin' ? BASE_URL . 'app/Views/Admin/adminDashboard.php' : BASE_URL . 'app/Views/Staff/staffDashboard.php');

        echo json_encode(['success' => true, 'redirect' => $redirect]);
        exit();
    }

    $resident = residentByNumber($identifier);
    if ($resident) {
        if ($resident['Status'] !== 'Active') {
            echo json_encode(['success' => false, 'message' => 'This account is not active.']);
            exit();
        }
        if (!password_verify($password, $resident['Password'])) {
            insertLog('Resident', $resident['ResidentID'], 'Login failed (wrong password)', null, null, $ip);
            echo json_encode(['success' => false, 'message' => 'Invalid resident number or password.']);
            exit();
        }
        $_SESSION['ResidentID'] = $resident['ResidentID'];
        $_SESSION['DefaultPassword'] = (int)$resident['DefaultPassword'];
        $_SESSION['FirstName'] = $resident['FirstName'];

        insertLog('Resident', $resident['ResidentID'], 'Login success', null, null, $ip);

        $redirect = $resident['DefaultPassword'] == 1
            ? BASE_URL . 'app/Views/Auth/changePass.php'
            : BASE_URL . 'app/Views/Resident/residentDashboard.php';

        echo json_encode(['success' => true, 'redirect' => $redirect]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    exit();
}

function changepass() {
    $newpass = trim($_POST['newpass'] ?? '');

    if (strlen($newpass) < 7) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 7 characters.']);
        exit();
    }

    $hashed = password_hash($newpass, PASSWORD_DEFAULT);

    if (isset($_SESSION['StaffID'])) {
        updStaffPass($_SESSION['StaffID'], $hashed);
        $_SESSION['DefaultPassword'] = 0;
        insertLog('StaffAdmin', $_SESSION['StaffID'], 'Changed password', null, null, $_SERVER['REMOTE_ADDR'] ?? '');
        echo json_encode(['success' => true, 'redirect' => dashboardUrlAfterAuth()]);
        exit();
    }

    if (isset($_SESSION['ResidentID'])) {
        updResidentPass($_SESSION['ResidentID'], $hashed);
        $_SESSION['DefaultPassword'] = 0;
        insertLog('Resident', $_SESSION['ResidentID'], 'Changed password', null, null, $_SERVER['REMOTE_ADDR'] ?? '');
        echo json_encode(['success' => true, 'redirect' => dashboardUrlAfterAuth()]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

function skipChangepass() {
    if (!isset($_SESSION['StaffID']) && !isset($_SESSION['ResidentID'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in.']);
        exit();
    }
    echo json_encode(['success' => true, 'redirect' => dashboardUrlAfterAuth()]);
    exit();
}

function dashboardUrlAfterAuth() {
    if (isset($_SESSION['StaffID'])) {
        return $_SESSION['Role'] === 'Admin'
            ? BASE_URL . 'app/Views/Admin/adminDashboard.php'
            : BASE_URL . 'app/Views/Staff/staffDashboard.php';
    }
    if (isset($_SESSION['ResidentID'])) {
        return BASE_URL . 'app/Views/Resident/residentDashboard.php';
    }
    return BASE_URL . 'index.php';
}
?>
