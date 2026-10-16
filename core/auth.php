<?php

session_start();
require_once __DIR__ . '/../config/appConfig.php';

function mustBeStaff($role = null) {
    if (!isset($_SESSION['StaffID'])) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
    if ($role && ($_SESSION['Role'] ?? '') !== $role) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

function mustBeResident() {
    if (!isset($_SESSION['ResidentID'])) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

function adminOnly() {
    if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'Admin') {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

function checkStaffAccess($role = null) {
    if (!isset($_SESSION['StaffID'])) {
        echo json_encode(['success' => false, 'message' => 'You shall not pass.']);
        exit();
    }
    if ($role && ($_SESSION['Role'] ?? '') !== $role) {
        echo json_encode(['success' => false, 'message' => 'You shall not pass.']);
        exit();
    }
}

function checkResidentAccess() {
    if (!isset($_SESSION['ResidentID'])) {
        echo json_encode(['success' => false, 'message' => 'You shall not pass.']);
        exit();
    }
}

function isDefaultPassword(): bool {
    return isset($_SESSION['DefaultPassword']) && $_SESSION['DefaultPassword'] == 1;
}

function dashboardUrlForSession(): string {
    if (isset($_SESSION['StaffID'])) {
        if ($_SESSION['Role'] === 'Admin') {
            return BASE_URL . 'app/Views/Admin/adminDashboard.php';
        }
        return BASE_URL . 'app/Views/Staff/staffDashboard.php';
    }
    if (isset($_SESSION['ResidentID'])) {
        return BASE_URL . 'app/Views/Resident/residentDashboard.php';
    }
    return BASE_URL . 'index.php';
}
?>
