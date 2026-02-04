<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
require_once '../models/Equipment.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipmentModel = new Equipment($database);
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $equipmentModel->name = $_POST['name'];
        $equipmentModel->category_id = $_POST['category_id'];
        $equipmentModel->description = $_POST['description'];
        $equipmentModel->serial_number = $_POST['serial_number'];
        $equipmentModel->total_quantity = $_POST['total_quantity'];
        $equipmentModel->available_quantity = $_POST['total_quantity'];
        $equipmentModel->status = 'Available';
        $equipmentModel->condition = $_POST['condition'];

        // QR Code generation (placeholder/link to Google Charts)
        // We'll update the QR code field after we get the ID
        if ($equipmentModel->create()) {
            $newId = $equipmentModel->id;
            $qrUrl = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=EQUIP-" . $newId . "&choe=UTF-8";

            // Update the QR code URL with the actual ID
            $updateQuery = "UPDATE equipment SET qr_code = :qr_code WHERE id = :id";
            $stmt = $database->prepare($updateQuery);
            $stmt->bindParam(':qr_code', $qrUrl);
            $stmt->bindParam(':id', $newId);
            $stmt->execute();

            $_SESSION['success'] = "Equipment added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add equipment.";
        }
    } elseif ($action === 'delete') {
        $equipmentModel->id = $_POST['id'];
        if ($equipmentModel->delete()) {
            $_SESSION['success'] = "Equipment deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete equipment.";
        }
    }

    header("Location: inventory.php");
    exit;
}
