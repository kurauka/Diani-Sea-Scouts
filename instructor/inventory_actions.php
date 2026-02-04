<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'instructor' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
require_once '../models/Equipment.php';
require_once '../models/Borrowing.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $borrowingModel = new Borrowing($database);
    $equipmentModel = new Equipment($database);

    if ($action === 'issue') {
        $equipmentId = $_POST['equipment_id'];
        $userId = $_POST['user_id'];
        $dueDate = $_POST['due_date'];

        $borrowingModel->equipment_id = $equipmentId;
        $borrowingModel->user_id = $userId;
        $borrowingModel->instructor_id = $_SESSION['id'];
        $borrowingModel->due_date = $dueDate;
        $borrowingModel->status = 'Issued';
        $borrowingModel->condition_on_borrow = 'Good'; // Default

        $database->beginTransaction();
        try {
            if ($borrowingModel->create()) {
                // Reduce available quantity
                $equipmentModel->id = $equipmentId;
                if ($equipmentModel->updateQuantity(-1)) {
                    $database->commit();
                    $_SESSION['success'] = "Equipment issued successfully!";
                } else {
                    $database->rollBack();
                    $_SESSION['error'] = "Failed to update stock quantity.";
                }
            } else {
                $database->rollBack();
                $_SESSION['error'] = "Failed to create borrowing record.";
            }
        } catch (Exception $e) {
            $database->rollBack();
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'return') {
        $borrowId = $_POST['borrow_id'];
        $returnCondition = $_POST['condition_on_return'];

        // Find the record to get equipment_id
        $stmt = $database->prepare("SELECT equipment_id FROM borrowing_records WHERE id = ?");
        $stmt->execute([$borrowId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $database->beginTransaction();
            try {
                $borrowingModel->id = $borrowId;
                if ($borrowingModel->returnEquipment($returnCondition)) {
                    // Increase available quantity
                    $equipmentModel->id = $record['equipment_id'];
                    $equipmentModel->updateQuantity(1);

                    // Update equipment condition if changed
                    $equipmentModel->updateStatus('Available', $returnCondition);

                    $database->commit();
                    $_SESSION['success'] = "Equipment returned successfully!";
                } else {
                    $database->rollBack();
                    $_SESSION['error'] = "Failed to process return.";
                }
            } catch (Exception $e) {
                $database->rollBack();
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        }
    }

    header("Location: inventory.php");
    exit;
}
