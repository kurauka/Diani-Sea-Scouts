<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Fetch System Stats
$userStmt = $database->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$userStats = $userStmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['student' => 50, 'instructor' => 2]

$examStmt = $database->query("SELECT COUNT(*) as count FROM exams");
$totalExams = $examStmt->fetch(PDO::FETCH_ASSOC)['count'];

$eventStmt = $database->query("SELECT COUNT(*) as count FROM calendar_events");
$totalEvents = $eventStmt->fetch(PDO::FETCH_ASSOC)['count'];

$attendanceStmt = $database->query("SELECT COUNT(*) as count FROM attendance WHERE attendance_date = CURRENT_DATE");
$todayAttendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC)['count'];

$totalStudents = $userStats['student'] ?? 0;
$totalInstructors = $userStats['instructor'] ?? 0;
$totalAdmins = $userStats['admin'] ?? 0;

$recentUsers = $database->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <!-- Hero Section -->
        <div class="mb-10 flex justify-between items-end animate-fade-in-down">
            <div>
                <span class="text-xs font-bold text-brand-blue uppercase tracking-widest mb-2 block">System
                    Overview</span>
                <h1
                    class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-brand-blue">
                    Command Center
                </h1>
                <p class="text-gray-500 mt-2 text-lg">Real-time system monitoring and control.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="window.location.reload()"
                    class="bg-white hover:bg-gray-50 text-brand-blue border border-gray-200 p-3 rounded-xl transition-all duration-300 shadow-sm"
                    title="Refresh Data">
                    <i class="fas fa-sync-alt fa-spin-hover"></i>
                </button>
            </div>
        </div>

        <!-- 3D System Health Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <!-- Total Instructors -->
            <div
                class="stats-card group bg-white rounded-3xl p-6 border border-gray-100 relative overflow-hidden hover:shadow-xl transition-all duration-300 shadow-lg">
                <div
                    class="absolute -right-10 -bottom-10 text-9xl text-purple-100 opacity-50 group-hover:scale-110 transition-transform duration-500">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Instructors</h3>
                <p class="text-4xl font-extrabold text-gray-800 group-hover:text-purple-600 transition-colors">
                    <?php echo $totalInstructors; ?>
                </p>
                <div class="mt-4 flex items-center gap-2 text-xs text-green-600 bg-green-50 px-2 py-1 rounded-lg w-fit">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span> Online
                </div>
            </div>

            <!-- Total Students -->
            <div
                class="stats-card group bg-white rounded-3xl p-6 border border-gray-100 relative overflow-hidden hover:shadow-xl transition-all duration-300 shadow-lg">
                <div
                    class="absolute -right-10 -bottom-10 text-9xl text-blue-100 opacity-50 group-hover:scale-110 transition-transform duration-500">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Students</h3>
                <p class="text-4xl font-extrabold text-gray-800 group-hover:text-brand-blue transition-colors">
                    <?php echo $totalStudents; ?>
                </p>
            </div>

            <!-- Total Exams -->
            <div
                class="stats-card group bg-white rounded-3xl p-6 border border-gray-100 relative overflow-hidden hover:shadow-xl transition-all duration-300 shadow-lg">
                <div
                    class="absolute -right-10 -bottom-10 text-9xl text-cyan-100 opacity-50 group-hover:scale-110 transition-transform duration-500">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Exams Created</h3>
                <p class="text-4xl font-extrabold text-gray-800 group-hover:text-cyan-600 transition-colors">
                    <?php echo $totalExams; ?>
                </p>
            </div>

            <!-- System Events -->
            <div
                class="stats-card group bg-white rounded-3xl p-6 border border-gray-100 relative overflow-hidden hover:shadow-xl transition-all duration-300 shadow-lg">
                <div
                    class="absolute -right-10 -bottom-10 text-9xl text-orange-100 opacity-50 group-hover:scale-110 transition-transform duration-500">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Calendar Events</h3>
                <p class="text-4xl font-extrabold text-gray-800 group-hover:text-orange-500 transition-colors">
                    <?php echo $totalEvents; ?>
                </p>
            </div>

            <!-- Today's Attendance -->
            <div
                class="stats-card group bg-white rounded-3xl p-6 border border-gray-100 relative overflow-hidden hover:shadow-xl transition-all duration-300 shadow-lg">
                <div
                    class="absolute -right-10 -bottom-10 text-9xl text-green-100 opacity-50 group-hover:scale-110 transition-transform duration-500">
                    <i class="fas fa-user-check"></i></div>
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Today's Presence</h3>
                <p class="text-4xl font-extrabold text-gray-800 group-hover:text-green-600 transition-colors">
                    <?php echo $todayAttendance; ?></p>
                <div class="mt-4 flex items-center gap-2 text-xs text-green-600 bg-green-50 px-2 py-1 rounded-lg w-fit">
                    Verified ID Scans
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- User Management Table -->
            <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-lg">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-users text-brand-blue"></i> Newest Users
                    </h3>
                    <a href="users.php"
                        class="text-xs font-bold bg-blue-50 hover:bg-blue-100 text-brand-blue px-4 py-2 rounded-lg transition-all duration-300">Manage
                        All Users</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold tracking-wider">
                            <tr>
                                <th class="p-4 pl-6">User</th>
                                <th class="p-4">Role</th>
                                <th class="p-4">Joined</th>
                                <th class="p-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($recentUsers as $user): ?>
                                <tr class="hover:bg-blue-50/30 transition duration-150">
                                    <td class="p-4 pl-6">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center font-bold text-sm text-brand-blue">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-sm text-gray-800">
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </p>
                                                <p class="text-xs text-gray-500 font-mono">
                                                    <?php echo htmlspecialchars($user['email']); ?>
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
                                    <td class="p-4 text-sm text-gray-500 font-mono">
                                        <?php echo date('M d', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="w-3 h-3 mx-auto bg-green-500 rounded-full shadow-sm"></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Analytics & Charts -->
            <div class="space-y-6">
                <!-- User Distribution Chat -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-lg relative">
                    <h3 class="font-bold text-gray-800 mb-6">User Distribution</h3>
                    <div class="h-48 relative z-10">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>

                <!-- Quick Admin Actions -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-lg">
                    <h3 class="font-bold text-gray-800 mb-4">Admin Actions</h3>
                    <div class="space-y-3">
                        <a href="users.php"
                            class="group flex items-center justify-between w-full bg-blue-50 hover:bg-blue-100 text-brand-blue p-4 rounded-xl transition-all duration-300">
                            <span class="font-bold flex items-center gap-3"><i class="fas fa-users-cog"></i> User
                                Management</span>
                            <i
                                class="fas fa-arrow-right opacity-0 group-hover:opacity-100 transform -translate-x-2 group-hover:translate-x-0 transition-all"></i>
                        </a>
                        <a href="settings.php"
                            class="group flex items-center justify-between w-full bg-gray-50 hover:bg-gray-100 text-gray-600 hover:text-gray-800 p-4 rounded-xl transition-all duration-300">
                            <span class="font-bold flex items-center gap-3"><i class="fas fa-cogs"></i> System
                                Settings</span>
                            <i class="fas fa-lock text-xs opacity-50"></i>
                        </a>
                        <a href="export.php?type=users"
                            class="group flex items-center justify-between w-full bg-gray-50 hover:bg-gray-100 text-gray-600 hover:text-gray-800 p-4 rounded-xl transition-all duration-300">
                            <span class="font-bold flex items-center gap-3"><i class="fas fa-file-export"></i> Export
                                Data</span>
                            <i class="fas fa-download text-xs opacity-50"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Scripts -->
<script>
    // 3D Tilt Effect
    document.addEventListener('mousemove', function (e) {
        document.querySelectorAll('.stats-card').forEach(card => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // Interaction range
            if (x > -20 && x < rect.width + 20 && y > -20 && y < rect.height + 20) {
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                // Subtle rotation
                const rotateX = ((y - centerY) / centerY) * -8;
                const rotateY = ((x - centerX) / centerX) * 8;

                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
                card.style.zIndex = '10';
            } else {
                card.style.transform = `perspective(1000px) rotateX(0) rotateY(0) scale(1)`;
                card.style.zIndex = '1';
            }
        });
    });

    // Chart.js Configuration
    const ctx = document.getElementById('userChart').getContext('2d');

    // PHP Data to JS
    const studentCount = <?php echo $totalStudents; ?>;
    const instructorCount = <?php echo $totalInstructors; ?>;
    const adminCount = <?php echo $totalAdmins; ?>;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Students', 'Instructors', 'Admins'],
            datasets: [{
                data: [studentCount, instructorCount, adminCount],
                backgroundColor: [
                    '#3B82F6', // Blue (Student)
                    '#A855F7', // Purple (Instructor)
                    '#EF4444'  // Red (Admin)
                ],
                borderColor: 'rgba(255, 255, 255, 0)',
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#9CA3AF',
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 10 }
                    }
                }
            }
        }
    });
</script>

<?php include_once '../includes/footer.php'; ?>