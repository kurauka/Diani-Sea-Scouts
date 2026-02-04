<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Handle Delete Action
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $del = $database->prepare("DELETE FROM users WHERE id = :id");
    $del->execute([':id' => $id]);
    header("Location: users.php?msg=deleted");
    exit;
}

// Handle Search
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

$sql = "SELECT * FROM users WHERE (name LIKE :search OR email LIKE :search)";
$params = [':search' => "%$search%"];

if ($roleFilter) {
    $sql .= " AND role = :role";
    $params[':role'] = $roleFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $database->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
                <p class="text-gray-500 text-sm mt-1">Manage student and instructor accounts.</p>
            </div>

            <div class="flex gap-2">
                <a href="add_user.php"
                    class="bg-brand-blue hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded mb-6 flex items-center gap-2">
                <i class="fas fa-check-circle"></i> User created successfully.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded mb-6 flex items-center gap-2">
                <i class="fas fa-check-circle"></i> User updated successfully.
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search by name or email..."
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20">
                </div>
                <div class="w-full md:w-48">
                    <select name="role"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20"
                        onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <option value="student" <?php if ($roleFilter == 'student')
                            echo 'selected'; ?>>Students</option>
                        <option value="instructor" <?php if ($roleFilter == 'instructor')
                            echo 'selected'; ?>>Instructors
                        </option>
                        <option value="admin" <?php if ($roleFilter == 'admin')
                            echo 'selected'; ?>>Admins</option>
                    </select>
                </div>
                <button type="submit"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl font-bold transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-lg">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="p-4 pl-6">User</th>
                            <th class="p-4">Role</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($users as $user): ?>
                            <tr class="group hover:bg-blue-50/30 transition duration-150">
                                <td class="p-4 pl-6">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center font-bold text-sm text-brand-blue group-hover:bg-brand-blue group-hover:text-white transition">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <?php
                                    $roleColor = 'bg-gray-100 text-gray-600';
                                    if ($user['role'] == 'admin')
                                        $roleColor = 'bg-red-50 text-red-600 border border-red-100';
                                    if ($user['role'] == 'instructor')
                                        $roleColor = 'bg-purple-50 text-purple-600 border border-purple-100';
                                    if ($user['role'] == 'student')
                                        $roleColor = 'bg-blue-50 text-brand-blue border border-blue-100';
                                    ?>
                                    <span
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide <?php echo $roleColor; ?>"><?php echo $user['role']; ?></span>
                                </td>
                                <td class="p-4">
                                    <?php if ($user['status'] == 'active'): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-green-50 text-green-600 border border-green-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-gray-100 text-gray-500 border border-gray-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <?php if ($user['role'] == 'student'): ?>
                                            <a href="student_profile.php?id=<?php echo $user['id']; ?>"
                                                class="p-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-500 hover:text-blue-700 border border-transparent hover:border-blue-200 transition"
                                                title="View Profile">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                            <a href="id_card.php?id=<?php echo $user['id']; ?>" target="_blank"
                                                class="p-2 rounded-lg bg-cyan-50 hover:bg-cyan-100 text-cyan-600 hover:text-cyan-800 border border-transparent hover:border-cyan-200 transition"
                                                title="Print ID Card">
                                                <i class="fas fa-id-card"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>"
                                            class="p-2 rounded-lg bg-gray-50 hover:bg-blue-50 text-gray-400 hover:text-brand-blue border border-transparent hover:border-blue-100 transition"
                                            title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['role'] !== 'admin' || $_SESSION['user_id'] != $user['id']): // Prevent deleting self or other admins easily ?>
                                            <a href="users.php?delete=<?php echo $user['id']; ?>"
                                                onclick="return confirm('Are you sure you want to delete this user?');"
                                                class="p-2 rounded-lg bg-gray-50 hover:bg-red-50 text-gray-400 hover:text-red-500 border border-transparent hover:border-red-100 transition"
                                                title="Delete User">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-500">
                                    <i class="fas fa-search mb-2 text-2xl text-gray-300"></i>
                                    <p>No users found matching your search.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php include_once '../includes/footer.php'; ?>