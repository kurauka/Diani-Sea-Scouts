<?php
session_start();
require_once '../config/db.php';
require_once '../models/Maintenance.php';
require_once '../models/Equipment.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $maintenanceModel = new Maintenance($database);
    $equipmentModel = new Equipment($database);

    if ($action === 'report') {
        $maintenanceModel->equipment_id = $_POST['equipment_id'];
        $maintenanceModel->reported_by = $_SESSION['id'];
        $maintenanceModel->issue_description = $_POST['issue_description'];

        if ($maintenanceModel->create()) {
            // Mark equipment as Under Repair
            $equipmentModel->id = $_POST['equipment_id'];
            $equipmentModel->updateStatus('Under Repair');
            $_SESSION['success'] = "Issue reported successfully.";
        } else {
            $_SESSION['error'] = "Failed to report issue.";
        }
    } elseif ($action === 'update') {
        $maintenanceModel->id = $_POST['log_id'];
        $maintenanceModel->maintained_by = $_SESSION['id'];
        $maintenanceModel->status = $_POST['status'];
        $maintenanceModel->repair_details = $_POST['repair_details'];
        $maintenanceModel->cost = $_POST['cost'];

        if ($maintenanceModel->update()) {
            // If status is Repaired, change equipment status back to Available
            if ($_POST['status'] === 'Repaired') {
                // We need the equipment_id from the log
                $stmt = $database->prepare("SELECT equipment_id FROM maintenance_records WHERE id = ?");
                $stmt->execute([$_POST['log_id']]);
                $log = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($log) {
                    $equipmentModel->id = $log['equipment_id'];
                    $equipmentModel->updateStatus('Available');
                }
            } elseif ($_POST['status'] === 'Decommissioned') {
                $stmt = $database->prepare("SELECT equipment_id FROM maintenance_records WHERE id = ?");
                $stmt->execute([$_POST['log_id']]);
                $log = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($log) {
                    $equipmentModel->id = $log['equipment_id'];
                    $equipmentModel->updateStatus('Decommissioned');
                }
            }
            $_SESSION['success'] = "Repair status updated.";
        } else {
            $_SESSION['error'] = "Failed to update status.";
        }
    }

    header("Location: maintenance.php");
    exit;
}
