<?php
include_once '../config/db.php';
include_once '../models/User.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $user = new User($database);

    // Check if registration is allowed
    $stmt = $database->query("SELECT setting_value FROM settings WHERE setting_key = 'allow_registration'");
    $allow_registration = $stmt->fetchColumn();

    if ($allow_registration == '0') {
        $message = "Registration is currently closed. Please contact the administrator.";
    } else {
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        $user->password = $_POST['password'];
        $user->role = $_POST['role'];

        if ($user->register()) {
            header("Location: login.php?registered=true");
            exit;
        } else {
            $message = "Registration failed. Email might already exist.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Diani Sea Scouts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-cyan-500 to-blue-600 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-3xl p-8 shadow-2xl w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Join the Scouts</h1>
            <p class="text-gray-500">Create your account</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Full Name</label>
                <input type="text" name="name" required
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
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
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                <select name="role"
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="student">Student</option>
                    <option value="instructor">Instructor</option>
                </select>
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-300">Sign
                Up</button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-600">Already have an account? <a href="login.php"
                class="text-blue-500 hover:underline">Login</a></p>
    </div>
</body>

</html>