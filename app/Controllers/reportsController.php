<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/healthEventOperations.php";
include __DIR__ . "/../Models/healthHistoryOperations.php";
include __DIR__ . "/../Models/residentAccountOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeStaff();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'getEventsReport':       getEventsReport(); break;
    case 'getHealthHistoryReport': getHealthHistoryReport(); break;
    case 'getResidentPrintData':  getResidentPrintData(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function fail($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

/** Validates and normalizes a start/end date pair. Returns [start, end] or [null, null] if not provided. */
function resolveDateRange() {
    $start = trim($_POST['startDate'] ?? $_GET['startDate'] ?? '');
    $end   = trim($_POST['endDate'] ?? $_GET['endDate'] ?? '');

    if ($start === '' && $end === '') {
        // Default range: current month
        $start = date('Y-m-01');
        $end   = date('Y-m-t');
    }

    if ($start !== '' && $end !== '' && strtotime($start) > strtotime($end)) {
        fail('Start date must be before end date.');
    }

    return [$start ?: null, $end ?: null];
}

function getEventsReport() {
    global $conn;
    [$start, $end] = resolveDateRange();

    $sql = "SELECT e.EventID, e.Title, e.EventType, e.EventDate, e.Status,
                   (SELECT COUNT(*) FROM event_signups s WHERE s.EventID = e.EventID) AS SignupCount
            FROM health_events e WHERE e.IsArchived = 0";
    $params = []; $types = "";
    if ($start) { $sql .= " AND e.EventDate >= ?"; $params[] = $start; $types .= "s"; }
    if ($end)   { $sql .= " AND e.EventDate <= ?"; $params[] = $end; $types .= "s"; }
    $sql .= " ORDER BY e.EventDate ASC";

    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Breakdown by event type
    $byType = [];
    foreach ($events as $e) {
        $byType[$e['EventType']] = ($byType[$e['EventType']] ?? 0) + 1;
    }

    echo json_encode(['success' => true, 'events' => $events, 'byType' => $byType, 'startDate' => $start, 'endDate' => $end]);
    exit();
}

function getHealthHistoryReport() {
    [$start, $end] = resolveDateRange();
    $includeVoided = !empty($_POST['includeVoided']) || !empty($_GET['includeVoided']);

    $conditionSummary = fetchConditionSummary($start, $end);
    $statusSummary = fetchStatusSummary($start, $end);

    global $conn;
    $sql = "SELECT h.RecordID, h.ResidentID, h.ConditionType, h.OtherConditionText, h.RecordDate, h.Status, h.IsVoided,
                   CONCAT(r.FirstName, ' ', r.LastName) AS ResidentName,
                   CONCAT(s.FirstName, ' ', s.LastName) AS RecordedByName
            FROM resident_health_history h
            JOIN residents r ON r.ResidentID = h.ResidentID
            LEFT JOIN staffadmin s ON s.StaffID = h.RecordedByStaffID
            WHERE 1=1" . ($includeVoided ? "" : " AND h.IsVoided = 0");
    $params = []; $types = "";
    if ($start) { $sql .= " AND h.RecordDate >= ?"; $params[] = $start; $types .= "s"; }
    if ($end)   { $sql .= " AND h.RecordDate <= ?"; $params[] = $end; $types .= "s"; }
    $sql .= " ORDER BY h.RecordDate DESC LIMIT 100";

    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true, 'conditionSummary' => $conditionSummary, 'statusSummary' => $statusSummary,
        'recent' => $recent, 'startDate' => $start, 'endDate' => $end
    ]);
    exit();
}

function getResidentPrintData() {
    $id = (int)($_POST['residentId'] ?? $_GET['residentId'] ?? 0);
    $resident = fetchResidentById($id);
    if (!$resident) fail('Resident not found.');

    $resident['spouse'] = $resident['CivilStatus'] === 'Married' ? fetchResidentSpouse($id) : null;
    $resident['children'] = fetchResidentChildren($id);

    echo json_encode(['success' => true, 'resident' => $resident]);
    exit();
}
?>
