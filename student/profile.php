<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Fetch User Data
$stmt = $database->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Registered/Completed Exams count
$examStmt = $database->prepare("SELECT COUNT(*) as count FROM student_exams WHERE student_id = :id");
$examStmt->execute([':id' => $_SESSION['user_id']]);
$examCount = $examStmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">My Profile</h1>

        <?php if (isset($_GET['msg'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php
                if ($_GET['msg'] == 'updated')
                    echo "Profile updated successfully!";
                if ($_GET['msg'] == 'password_changed')
                    echo "Password changed successfully!";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php
                if ($_GET['error'] == 'password_mismatch')
                    echo "New passwords do not match.";
                if ($_GET['error'] == 'current_password_incorrect')
                    echo "Current password is incorrect.";
                ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center h-fit">
                <div class="relative inline-block mb-6">
                    <img src="../<?php echo !empty($user['profile_image']) ? $user['profile_image'] : 'assets/images/default_avatar.png'; ?>"
                        alt="Profile" class="w-32 h-32 rounded-full object-cover border-4 border-brand-pale shadow-lg">
                    <div
                        class="absolute bottom-0 right-0 bg-brand-blue text-white w-8 h-8 rounded-full flex items-center justify-center border-2 border-white">
                        <i class="fas fa-camera text-xs"></i>
                    </div>
                </div>

                <h2 class="text-xl font-bold text-gray-800 mb-1">
                    <?php echo htmlspecialchars($user['name']); ?>
                </h2>
                <p class="text-gray-500 text-sm mb-4">
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>

                <div class="flex justify-center gap-4 mb-6">
                    <div class="bg-blue-50 px-4 py-2 rounded-lg text-center">
                        <span class="block text-xl font-bold text-brand-blue">
                            <?php echo $examCount; ?>
                        </span>
                        <span class="text-xs text-gray-500 uppercase tracking-wide">Exams</span>
                    </div>
                    <div class="bg-purple-50 px-4 py-2 rounded-lg text-center">
                        <span class="block text-xl font-bold text-purple-600">
                            <?php echo !empty($user['troop_id']) ? htmlspecialchars($user['troop_id']) : '-'; ?>
                        </span>
                        <span class="text-xs text-gray-500 uppercase tracking-wide">Troop ID</span>
                    </div>
                </div>

                <a href="../admin/id_card.php" target="_blank"
                    class="w-full flex items-center justify-center gap-2 bg-gray-800 text-white py-3 rounded-xl font-bold hover:bg-gray-900 transition shadow-lg">
                    <i class="fas fa-id-card"></i> View My ID Card
                </a>
            </div>

            <!-- Edit Forms -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Personal Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-user-edit text-brand-blue"></i> Edit Details
                    </h3>

                    <form action="update_profile_logic.php" method="POST" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-gray-600 text-sm font-bold mb-2">Full Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['name']); ?>" disabled
                                    class="w-full px-4 py-2 border rounded-lg bg-gray-50 cursor-not-allowed">
                                <p class="text-xs text-gray-400 mt-1">Contact admin to change name.</p>
                            </div>
                            <div>
                                <label class="block text-gray-600 text-sm font-bold mb-2">Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                                    class="w-full px-4 py-2 border rounded-lg bg-gray-50 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-gray-600 text-sm font-bold mb-2">School</label>
                                <input type="text" name="school"
                                    value="<?php echo htmlspecialchars($user['school'] ?? ''); ?>"
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue"
                                    placeholder="Enter your school name">
                            </div>
                            <div>
                                <label class="block text-gray-600 text-sm font-bold mb-2">Troop ID</label>
                                <input type="text" name="troop_id"
                                    value="<?php echo htmlspecialchars($user['troop_id'] ?? ''); ?>"
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue"
                                    placeholder="e.g. TR-101">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-600 text-sm font-bold mb-2">Profile Picture</label>
                                <input type="file" name="profile_image"
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            </div>
                        </div>
                        <button type="submit" name="update_info"
                            class="bg-brand-blue text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-600 transition">
                            Save Changes
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-lock text-brand-blue"></i> Security
                    </h3>

                    <form action="update_profile_logic.php" method="POST">
                        <div class="space-y-4 mb-6">
                            <div>
                                <label class="block text-gray-600 text-sm font-bold mb-2">Current Password</label>
                                <input type="password" name="current_password" required
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            </div>
                            <div>
                                <label class="block text-gray-600 text-sm font-bold mb-2">New Password</label>
                                <input type="password" name="new_password" required
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            </div>
                            <div>
                                <label class="block text-gray-600 text-sm font-bold mb-2">Confirm New Password</label>
                                <input type="password" name="confirm_password" required
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            </div>
                        </div>
                        <button type="submit" name="change_password"
                            class="bg-red-500 text-white px-6 py-2 rounded-lg font-bold hover:bg-red-600 transition">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>