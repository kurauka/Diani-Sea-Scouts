<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    die("Unauthorized");
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $uploaded_by = $_SESSION['user_id'];

    $target_dir = "../assets/uploads/";
    $error = $_FILES["file"]["error"];

    if ($error === 0) {
        $file_name = time() . '_' . basename($_FILES["file"]["name"]);
        $target_file = $target_dir . $file_name;
        $db_path = "assets/uploads/" . $file_name;

        // Simple validation (can be expanded)
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $stmt = $database->prepare("INSERT INTO materials (title, description, file_path, type, uploaded_by) VALUES (:title, :desc, :path, :type, :uid)");
            $stmt->execute([
                ':title' => $title,
                ':desc' => $description,
                ':path' => $db_path,
                ':type' => $type,
                ':uid' => $uploaded_by
            ]);

            header("Location: manage_materials.php?msg=success");
            exit;
        } else {
            echo "Error moving file.";
        }
    } else {
        echo "Upload error code: " . $error;
    }
} else {
    header("Location: manage_materials.php");
}
?>