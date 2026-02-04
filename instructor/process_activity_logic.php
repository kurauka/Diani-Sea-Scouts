<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'instructor' && $_SESSION['role'] !== 'admin')) {
    exit('Unauthorized');
}

require_once '../config/db.php';
require_once '../models/ActivityReport.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activityReport = new ActivityReport($database);

    $activity_id = $_POST['activity_id'];
    $status = ($_POST['action'] === 'Approve') ? 'Approved' : 'Rejected';
    $notes = $_POST['instructor_notes'];
    $instructor_id = $_SESSION['user_id'];

    $activityReport->id = $activity_id;

    if ($activityReport->updateStatus($status, $instructor_id, $notes)) {
        header("Location: review_activities.php?msg=processed");
    } else {
        header("Location: review_activities.php?error=failed");
    }
} else {
    header("Location: review_activities.php");
}
exit;
