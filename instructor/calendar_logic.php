<?php
session_start();
include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Set header for JSON response
header('Content-Type: application/json');

// 1. Fetch Events (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['mode'] ?? 'all';

    // In a real app, perhaps filter by range ?start=...&end=...
    $query = "SELECT id, title, start_date as start, end_date as end, type, description FROM calendar_events";
    $stmt = $database->query($query);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map types to colors
    foreach ($events as &$event) {
        switch ($event['type']) {
            case 'exam':
                $event['color'] = '#DA292E';
                break; // Red
            case 'meeting':
                $event['color'] = '#FF9F1C';
                break; // Orange
            case 'class':
                $event['color'] = '#2EC4B6';
                break; // Teal
            case 'holiday':
                $event['color'] = '#20A4F3';
                break; // Blue
            default:
                $event['color'] = '#6B7280';
                break; // Gray
        }
    }

    echo json_encode($events);
    exit;
}

// 2. Create Event (POST) - Instructor Only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    // Decode JSON input if sent as raw body
    $input = json_decode(file_get_contents('php://input'), true);

    $title = $input['title'];
    $start = $input['start'];
    $end = isset($input['end']) ? $input['end'] : null;
    $type = $input['type'];
    $desc = isset($input['description']) ? $input['description'] : '';
    $uid = $_SESSION['user_id'];

    $query = "INSERT INTO calendar_events (title, start_date, end_date, type, description, created_by) VALUES (:title, :start, :end, :type, :desc, :uid)";
    $stmt = $database->prepare($query);

    if ($stmt->execute([':title' => $title, ':start' => $start, ':end' => $end, ':type' => $type, ':desc' => $desc, ':uid' => $uid])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// 3. Delete Event (DELETE via GET or proper DELETE)
// Simplified: using a GET param ?action=delete&id=... for ease
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
        exit; // handle error
    }
    $id = $_GET['id'];
    $del = $database->prepare("DELETE FROM calendar_events WHERE id = :id");
    $del->execute([':id' => $id]);
    echo json_encode(['status' => 'deleted']);
    exit;
}
?>