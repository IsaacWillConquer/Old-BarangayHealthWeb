<?php
ob_start();
require_once __DIR__ . "/../../core/auth.php";
include __DIR__ . "/../Models/healthEventOperations.php";
include __DIR__ . "/../Models/logOperations.php";
require_once __DIR__ . "/../../config/dbConn.php";

mustBeStaff();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'createEvent':        createEvent(); break;
    case 'listEvents':         listEvents(); break;
    case 'listArchivedEvents': listArchivedEvents(); break;
    case 'getEvent':           getEvent(); break;
    case 'updateEvent':        updateEvent(); break;
    case 'archiveEvent':       archiveEventAction(); break;
    case 'restoreEvent':       restoreEventAction(); break;
    case 'uploadEventImage':   uploadEventImage(); break;
    case 'listSignups':        listSignups(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

function fail($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

$validEventTypes = ['Checkup','VaccinationHuman','VaccinationDog','Cleanup','Wellness',
                     'BloodDonation','EyeConsult','Campaign','FreeVitamins','Other'];
$validStatuses = ['Draft','Upcoming','Active','Closed','Cancelled'];

function validateEventInput() {
    global $validEventTypes, $validStatuses;

    $data = [
        'title'       => trim($_POST['title'] ?? ''),
        'eventType'   => trim($_POST['eventType'] ?? ''),
        'description' => trim($_POST['description'] ?? '') ?: null,
        'location'    => trim($_POST['location'] ?? ''),
        'eventDate'   => trim($_POST['eventDate'] ?? ''),
        'startTime'   => trim($_POST['startTime'] ?? '') ?: null,
        'endTime'     => trim($_POST['endTime'] ?? '') ?: null,
        'status'      => trim($_POST['status'] ?? 'Draft'),
    ];

    if ($data['title'] === '') fail('Event title is required.');
    if (!in_array($data['eventType'], $validEventTypes)) fail('A valid event type is required.');
    if ($data['location'] === '') fail('Event location is required.');
    if ($data['eventDate'] === '') fail('Event date is required.');
    if (!in_array($data['status'], $validStatuses)) fail('A valid status is required.');

    if ($data['startTime'] && $data['endTime'] && strtotime($data['endTime']) <= strtotime($data['startTime'])) {
        fail('End time must be after start time.');
    }

    return $data;
}

function createEvent() {
    $data = validateEventInput();
    $data['imagePath'] = null; // uploaded separately via uploadEventImage
    $id = insertHealthEvent($data, $_SESSION['StaffID']);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Created health event: ' . $data['title'], 'HealthEvent', $id);
    echo json_encode(['success' => true, 'eventId' => $id]);
    exit();
}

function listEvents() {
    echo json_encode(['success' => true, 'events' => fetchHealthEvents()]);
    exit();
}

function listArchivedEvents() {
    $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
    echo json_encode(['success' => true] + fetchArchivedHealthEvents($page));
    exit();
}

function getEvent() {
    $id = (int)($_POST['eventId'] ?? $_GET['eventId'] ?? 0);
    $event = fetchHealthEventById($id);
    if (!$event) fail('Event not found.');
    echo json_encode(['success' => true, 'event' => $event]);
    exit();
}

function updateEvent() {
    $id = (int)($_POST['eventId'] ?? 0);
    if (!$id || !fetchHealthEventById($id)) fail('Event not found.');
    $data = validateEventInput();
    updateHealthEvent($id, $data);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Updated health event: ' . $data['title'], 'HealthEvent', $id);
    echo json_encode(['success' => true]);
    exit();
}

function archiveEventAction() {
    $id = (int)($_POST['eventId'] ?? 0);
    if (!fetchHealthEventById($id)) fail('Event not found.');
    archiveHealthEvent($id, $_SESSION['StaffID']);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Archived health event', 'HealthEvent', $id);
    echo json_encode(['success' => true]);
    exit();
}

function restoreEventAction() {
    $id = (int)($_POST['eventId'] ?? 0);
    restoreHealthEvent($id);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Restored health event', 'HealthEvent', $id);
    echo json_encode(['success' => true]);
    exit();
}

function uploadEventImage() {
    $id = (int)($_POST['eventId'] ?? 0);
    if (!fetchHealthEventById($id)) fail('Event not found.');

    if (!isset($_FILES['eventImage']) || $_FILES['eventImage']['error'] !== UPLOAD_ERR_OK) {
        fail('No image uploaded.');
    }

    $file = $_FILES['eventImage'];
    $allowedTypes = ['image/jpeg', 'image/png']; // GIF not allowed, consistent with profile photos
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) fail('Only JPG or PNG images are allowed.');
    if ($file['size'] > 3 * 1024 * 1024) fail('Image must be under 3MB.');

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'event_' . $id . '_' . time() . '.' . $ext;
    $destPath = __DIR__ . '/../../uploads/event_images/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) fail('Failed to save image.');

    $relativePath = 'uploads/event_images/' . $filename;
    updateHealthEventImage($id, $relativePath);
    insertLog('StaffAdmin', $_SESSION['StaffID'], 'Updated health event image', 'HealthEvent', $id);

    echo json_encode(['success' => true, 'imagePath' => BASE_URL . $relativePath]);
    exit();
}

function listSignups() {
    $id = (int)($_POST['eventId'] ?? $_GET['eventId'] ?? 0);
    if (!fetchHealthEventById($id)) fail('Event not found.');
    echo json_encode(['success' => true, 'signups' => fetchEventSignups($id)]);
    exit();
}
?>
