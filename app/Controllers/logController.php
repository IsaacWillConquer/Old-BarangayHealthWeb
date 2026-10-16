<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeStaff();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'listLogs':
        listLogs();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function listLogs() {
    $actorType = $_POST['actorType'] ?? $_GET['actorType'] ?? null;
    $dateFrom = $_POST['dateFrom'] ?? $_GET['dateFrom'] ?? null;
    $dateTo = $_POST['dateTo'] ?? $_GET['dateTo'] ?? null;
    $page = (int)($_POST['page'] ?? $_GET['page'] ?? 1);
    $pageSize = 15;

    $logs = fetchLogs($actorType ?: null, $dateFrom ?: null, $dateTo ?: null, $page, $pageSize);
    $total = countLogs($actorType ?: null, $dateFrom ?: null, $dateTo ?: null);
    $totalPages = max(1, (int)ceil($total / $pageSize));

    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'page' => $page,
        'totalPages' => $totalPages,
        'total' => $total
    ]);
    exit();
}
?>
