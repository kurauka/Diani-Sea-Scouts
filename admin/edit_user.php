<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: users.php");
    exit;
}

// Fetch User
$stmt = $database->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Password Update Logic
    $passwordSql = "";
    $params = [':name' => $name, ':email' => $email, ':role' => $role, ':status' => $status, ':id' => $id];

    if (!empty($_POST['password'])) {
        $passwordSql = ", password = :password";
        $params[':password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    $sql = "UPDATE users SET name = :name, email = :email, role = :role, status = :status $passwordSql WHERE id = :id";
    $stmt = $database->prepare($sql);

    if ($stmt->execute($params)) {
        header("Location: users.php?msg=updated");
        exit;
    } else {
        $error = "Failed to update user.";
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
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Edit User</h1>
                    <span class="text-xs text-gray-400 font-mono">ID: #
                        <?php echo $user['id']; ?>
                    </span>
                </div>

                <?php if (isset($error)): ?>
                    <div
                        class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 border border-red-100 text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-500 text-sm font-bold mb-2">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>"
                                    required
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                            </div>
                            <div>
                                <label class="block text-gray-500 text-sm font-bold mb-2">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                                    required
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-500 text-sm font-bold mb-2">Role</label>
                                <select name="role"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                                    <option value="student" <?php echo ($user['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                    <option value="instructor" <?php echo ($user['role'] == 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>
                                        >Admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-500 text-sm font-bold mb-2">Account Status</label>
                                <select name="status"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                                    <option value="active" <?php echo ($user['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($user['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive (Locked)</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100">
                            <h3 class="font-bold text-gray-800 mb-4">Password Reset</h3>
                            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                                <label class="block text-gray-600 text-xs font-bold mb-2 uppercase tracking-wide">New
                                    Password</label>
                                <input type="password" name="password"
                                    placeholder="Leave blank to keep current password"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-8">
                        <button type="submit"
                            class="bg-brand-blue hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg transform hover:scale-105">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>