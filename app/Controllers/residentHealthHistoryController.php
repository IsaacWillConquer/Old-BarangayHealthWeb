<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/healthHistoryOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeResident();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'listMyHistory') {
    $residentId = $_SESSION['ResidentID'];
    // Voided records are excluded — a voided entry means staff retracted it, so it shouldn't show as active history.
    echo json_encode(['success' => true, 'records' => fetchHealthHistory($residentId, false)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
?>
