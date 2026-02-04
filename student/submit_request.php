<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    exit('Unauthorized');
}

require_once '../config/db.php';
require_once '../models/Borrowing.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id = $_POST['equipment_id'];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $notes = $_POST['notes'];

    $borrowingModel = new Borrowing($database);
    $borrowingModel->equipment_id = $equipment_id;
    $borrowingModel->user_id = $_SESSION['user_id'];
    $borrowingModel->instructor_id = null; // Pending approval
    $borrowingModel->due_date = $due_date;
    $borrowingModel->status = 'Pending';
    $borrowingModel->condition_on_borrow = 'Good'; // Default
    $borrowingModel->notes = $notes;

    if ($borrowingModel->create()) {
        header("Location: my_gear.php?msg=request_sent");
    } else {
        header("Location: my_gear.php?error=request_failed");
    }
} else {
    header("Location: my_gear.php");
}
exit;
