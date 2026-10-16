<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/dashboardOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeStaff(); 

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'getStats':
        echo json_encode(['success' => true, 'stats' => getDashboardStats()]);
        break;
    case 'getParticipationTrend':
        echo json_encode(['success' => true, 'trend' => getParticipationTrend()]);
        break;
    case 'getNeedsAttention':
        echo json_encode(['success' => true, 'attention' => getNeedsAttention()]);
        break;
    case 'getRecentActivity':
        echo json_encode(['success' => true, 'activity' => getRecentActivity()]);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
exit();
?>
