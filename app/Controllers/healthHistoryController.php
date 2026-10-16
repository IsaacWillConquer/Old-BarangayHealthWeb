<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/healthHistoryOperations.php";
include __DIR__ . "/../Models/residentAccountOperations.php"; // for fetchResidents (search)
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeStaff();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'addRecord':      addRecord(); break;
    case 'listRecords':    listRecords(); break;
    case 'updateRecord':   updateRecord(); break;
    case 'voidRecord':     voidRecordAction(); break;
    case 'restoreRecord':  restoreRecordAction(); break;
    case 'searchResidents': searchResidentsAction(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function fail($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

$validConditions = ['Hypertension','Diabetes','Asthma','Tuberculosis','Dengue','HeartDisease',
                     'Allergy','PregnancyRelated','COVID19','Malnutrition','Disability',
                     'SeniorCareConcern','Other'];
$validStatuses = ['Ongoing','Resolved','Chronic','Monitoring'];

function validateHistoryInput() {
    global $validConditions, $validStatuses;

    $conditionType = trim($_POST['conditionType'] ?? '');
    $otherText = trim($_POST['otherConditionText'] ?? '') ?: null;
    $recordDate = trim($_POST['recordDate'] ?? '');
    $status = trim($_POST['status'] ?? 'Ongoing');
    $notes = trim($_POST['notes'] ?? '') ?: null;

    if (!in_array($conditionType, $validConditions)) fail('A valid condition is required.');
    if ($conditionType === 'Other' && !$otherText) fail('Please specify the condition when selecting "Other".');
    if ($recordDate === '') fail('Record date is required.');
    if (strtotime($recordDate) > strtotime('today')) fail('Record date cannot be in the future.');
    if (!in_array($status, $validStatuses)) fail('A valid status is required.');

    return [$conditionType, $otherText, $recordDate, $status, $notes];
}

function addRecord() {
    $residentId = (int)($_POST['residentId'] ?? 0);
    if (!$residentId) fail('Resident is required.');

    [$conditionType, $otherText, $recordDate, $status, $notes] = validateHistoryInput();
    $recordId = insertHealthHistory($residentId, $conditionType, $otherText, $recordDate, $status, $notes, $_SESSION['StaffID']);

    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Added health history record', 'Resident', $residentId);
    echo json_encode(['success' => true, 'recordId' => $recordId]);
    exit();
}

function listRecords() {
    $residentId = (int)($_POST['residentId'] ?? $_GET['residentId'] ?? 0);
    $includeVoided = !empty($_POST['includeVoided']) || !empty($_GET['includeVoided']);
    if (!$residentId) fail('Resident is required.');
    echo json_encode(['success' => true, 'records' => fetchHealthHistory($residentId, $includeVoided)]);
    exit();
}

function updateRecord() {
    $recordId = (int)($_POST['recordId'] ?? 0);
    $record = fetchHealthHistoryById($recordId);
    if (!$record) fail('Record not found.');
    if ($record['IsVoided']) fail('This record is voided and cannot be edited. Restore it first.');

    [$conditionType, $otherText, $recordDate, $status, $notes] = validateHistoryInput();
    updateHealthHistory($recordId, $conditionType, $otherText, $recordDate, $status, $notes);

    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Updated health history record', 'Resident', $record['ResidentID']);
    echo json_encode(['success' => true]);
    exit();
}

function voidRecordAction() {
    $recordId = (int)($_POST['recordId'] ?? 0);
    $record = fetchHealthHistoryById($recordId);
    if (!$record) fail('Record not found.');

    voidHealthHistory($recordId, $_SESSION['StaffID']);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Voided health history record', 'Resident', $record['ResidentID']);
    echo json_encode(['success' => true]);
    exit();
}

function restoreRecordAction() {
    $recordId = (int)($_POST['recordId'] ?? 0);
    $record = fetchHealthHistoryById($recordId);
    if (!$record) fail('Record not found.');

    restoreHealthHistory($recordId);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Restored health history record', 'Resident', $record['ResidentID']);
    echo json_encode(['success' => true]);
    exit();
}

function searchResidentsAction() {
    // Reuses the same resident list used in Manage Residents, filtered client-side by the search box
    echo json_encode(['success' => true, 'residents' => fetchResidents()]);
    exit();
}
?>
