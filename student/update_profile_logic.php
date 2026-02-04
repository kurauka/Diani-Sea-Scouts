<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// 1. Update Personal Info
if (isset($_POST['update_info'])) {
    $school = trim($_POST['school']);
    $troop_id = trim($_POST['troop_id']);
    $uid = $_SESSION['user_id'];

    $query = "UPDATE users SET school = :school, troop_id = :troop_id WHERE id = :id";
    $stmt = $database->prepare($query);
    if ($stmt->execute([':school' => $school, ':troop_id' => $troop_id, ':id' => $uid])) {
        // Handle Image Upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $filetmp = $_FILES['profile_image']['tmp_name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                // Ensure directory exists
                $target_dir = "../assets/uploads/profile_images/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $new_filename = "profile_" . $uid . "_" . time() . "." . $ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($filetmp, $target_file)) {
                    // Update DB with new image path (remove ../ for storage if preferred, but usually keep relative to root or consistent)
                    // Storing as 'assets/uploads/...'
                    $db_path = "assets/uploads/profile_images/" . $new_filename;
                    $updateImg = $database->prepare("UPDATE users SET profile_image = :img WHERE id = :id");
                    $updateImg->execute([':img' => $db_path, ':id' => $uid]);
                }
            }
        }
        header("Location: profile.php?msg=updated");
    } else {
        header("Location: profile.php?error=update_failed");
    }
}

// 2. Change Password
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $uid = $_SESSION['user_id'];

    if ($new_pass !== $confirm_pass) {
        header("Location: profile.php?error=password_mismatch");
        exit;
    }

    // Verify old password
    $stmt = $database->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($current_pass, $user['password'])) {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $database->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $update->execute([':pass' => $new_hash, ':id' => $uid]);
        header("Location: profile.php?msg=password_changed");
    } else {
        header("Location: profile.php?error=current_password_incorrect");
    }
}
?>