<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    exit('Unauthorized');
}

require_once '../config/db.php';
require_once '../models/ActivityReport.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activityReport = new ActivityReport($database);

    $activityReport->student_id = $_SESSION['user_id'];
    $activityReport->title = $_POST['title'];
    $activityReport->activity_type = $_POST['activity_type'];
    $activityReport->description = $_POST['description'];
    $activityReport->activity_date = $_POST['activity_date'];
    $activityReport->hours = $_POST['hours'];
    $activityReport->evidence_link = $_POST['evidence_link'];

    if ($activityReport->create()) {
        header("Location: outdoor_activities.php?msg=submitted");
    } else {
        header("Location: outdoor_activities.php?error=failed");
    }
} else {
    header("Location: outdoor_activities.php");
}
exit;
