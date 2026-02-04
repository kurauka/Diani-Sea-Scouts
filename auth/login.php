<?php
session_start();
include_once '../config/db.php';
include_once '../models/User.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // $database is already provided by config/db.php
    $user = new User($database);

    // Check Maintenance Mode
    $stmt = $database->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $maintenance_mode = $stmt->fetchColumn();

    $user->email = $_POST['email'];
    $user->password = $_POST['password'];

    if ($user->login()) {
        if ($maintenance_mode == '1' && $user->role !== 'admin') {
            $message = "System is currently under maintenance. Please try again later.";
        } elseif (isset($user->status) && $user->status === 'inactive') {
            $message = "Your account has been deactivated. Please contact the administrator.";
        } else {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['name'] = $user->name;
            $_SESSION['role'] = $user->role;

            if ($user->role == 'instructor') {
                header("Location: ../instructor/dashboard.php");
            } elseif ($user->role == 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../student/dashboard.php");
            }
        }
    } else {
        $message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Diani Sea Scouts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-cyan-500 to-blue-600 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-3xl p-8 shadow-2xl w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Welcome Back</h1>
            <p class="text-gray-500">Login to continue</p>
        </div>

        <?php if (isset($_GET['registered'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Registration successful!
                Please login.</div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" required
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-300">Login</button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-600">Don't have an account? <a href="register.php"
                class="text-blue-500 hover:underline">Register</a></p>
    </div>
</body>

</html>