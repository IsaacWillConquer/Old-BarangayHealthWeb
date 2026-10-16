<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/healthEventOperations.php";
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeResident();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'listActiveEvents': listActiveEvents(); break;
    case 'signUp':           signUp(); break;
    case 'cancelSignup':     cancelSignup(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function fail($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function listActiveEvents() {
    $residentId = $_SESSION['ResidentID'];
    $events = fetchVisibleEventsForResident();
    $signedUpIds = fetchResidentSignedUpEventIds($residentId);

    foreach ($events as &$e) {
        $e['isSignedUp'] = in_array($e['EventID'], $signedUpIds);
        $e['signupCount'] = count(fetchEventSignups($e['EventID']));
    }

    echo json_encode(['success' => true, 'events' => $events]);
    exit();
}

function signUp() {
    $residentId = $_SESSION['ResidentID'];
    $eventId = (int)($_POST['eventId'] ?? 0);
    $event = fetchHealthEventById($eventId);

    if (!$event || $event['IsArchived']) fail('Event not found.');
    if (!in_array($event['Status'], ['Upcoming', 'Active'])) {
        fail('Sign-up is closed for this event.');
    }
    if (strtotime($event['EventDate']) < strtotime('today')) {
        fail('This event has already passed.');
    }

    if (in_array($eventId, fetchResidentSignedUpEventIds($residentId))) {
        fail('You are already signed up for this event.');
    }

    signUpForEvent($eventId, $residentId);
    insertLog('Resident', $residentId, 'Signed up for health event: ' . $event['Title']);
    echo json_encode(['success' => true]);
    exit();
}

function cancelSignup() {
    $residentId = $_SESSION['ResidentID'];
    $eventId = (int)($_POST['eventId'] ?? 0);
    cancelEventSignup($eventId, $residentId);
    insertLog('Resident', $residentId, 'Cancelled event sign-up');
    echo json_encode(['success' => true]);
    exit();
}
?>
