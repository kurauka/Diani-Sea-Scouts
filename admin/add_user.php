<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Check if email exists
    $stmt = $database->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $error = "Email already exists.";
    } else {
        $sql = "INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)";
        $stmt = $database->prepare($sql);
        if ($stmt->execute([':name' => $name, ':email' => $email, ':password' => $password, ':role' => $role, ':status' => $status])) {
            header("Location: users.php?msg=added");
            exit;
        } else {
            $error = "Failed to create user.";
        }
    }
}
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <div class="max-w-2xl mx-auto">
            <a href="users.php" class="text-sm text-gray-500 hover:text-brand-blue mb-4 inline-block"><i
                    class="fas fa-arrow-left mr-1"></i> Back to Users</a>

            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-xl relative">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Add New User</h1>

                <?php if (isset($error)): ?>
                    <div
                        class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100 text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-500 text-sm font-bold mb-2">Full Name</label>
                            <input type="text" name="name" required
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                        </div>
                        <div>
                            <label class="block text-gray-500 text-sm font-bold mb-2">Email Address</label>
                            <input type="email" name="email" required
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                        </div>
                        <div>
                            <label class="block text-gray-500 text-sm font-bold mb-2">Password</label>
                            <input type="password" name="password" required
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                        </div>
                        <div>
                            <label class="block text-gray-500 text-sm font-bold mb-2">Role</label>
                            <select name="role"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                                <option value="student">Student</option>
                                <option value="instructor">Instructor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-500 text-sm font-bold mb-2">Status</label>
                            <select name="status"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive (Locked)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="bg-brand-blue hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg transform hover:scale-105">
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>