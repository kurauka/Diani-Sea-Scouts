<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
include_once '../models/Exam.php';

$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
$exam = new Exam($database);
$result = $exam->read_by_instructor($_SESSION['user_id']);
$active_exams_count = $result->rowCount();

// Fetch Total Students
$stuStmt = $database->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$total_students = $stuStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Fetch Recent Announcements (Limit 2)
$annStmt = $database->prepare("SELECT * FROM announcements WHERE created_by = :uid ORDER BY created_at DESC LIMIT 2");
$annStmt->execute([':uid' => $_SESSION['user_id']]);

// Fetch Upcoming Calendar Events (Limit 2)
$calStmt = $database->prepare("SELECT * FROM calendar_events ORDER BY start_date ASC LIMIT 2"); // Simply fetching next 2 events
$calStmt->execute();

$time_hour = date('H');
$greeting = "Good Morning";
if ($time_hour >= 12 && $time_hour < 17)
    $greeting = "Good Afternoon";
if ($time_hour >= 17)
    $greeting = "Good Evening";

// Gear stats
$loanStmt = $database->query("SELECT COUNT(*) FROM borrowing_records WHERE status = 'Issued'");
$activeLoans = $loanStmt->fetchColumn();

// Pending Activities
$actStmt = $database->query("SELECT COUNT(*) FROM outdoor_activities WHERE status = 'Pending'");
$pendingActivities = $actStmt->fetchColumn();
?>
<?php include_once '../includes/header.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <!-- Hero Section -->
        <div class="mb-8 animate-fade-in-down">
            <h1
                class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-brand-blue mb-2">
                <?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['name']); ?> 👋
            </h1>
            <p class="text-gray-500 text-lg">Here's your command center overview for today.</p>
        </div>

        <!-- 3D Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- Active Exams Card -->
            <div
                class="stats-card group bg-white rounded-3xl p-6 shadow-lg border border-gray-100 relative overflow-hidden transition-all duration-300 hover:shadow-2xl">
                <div
                    class="absolute -right-10 -top-10 w-40 h-40 bg-gradient-to-br from-blue-50 to-blue-100 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700">
                </div>

                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div class="flex justify-between items-start mb-4">
                        <div
                            class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-brand-blue shadow-sm group-hover:bg-brand-blue group-hover:text-white transition-colors duration-300">
                            <i class="fas fa-file-alt text-xl"></i>
                        </div>
                        <span
                            class="bg-green-100 text-green-600 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                            <i class="fas fa-check-circle"></i> Active
                        </span>
                    </div>
                    <div>
                        <h3 class="text-gray-400 font-medium text-sm uppercase tracking-wide">Created Exams</h3>
                        <p class="text-4xl font-bold text-gray-800 mt-1"><?php echo $active_exams_count; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Students Card -->
            <div
                class="stats-card group bg-white rounded-3xl p-6 shadow-lg border border-gray-100 relative overflow-hidden transition-all duration-300 hover:shadow-2xl">
                <div
                    class="absolute -right-10 -top-10 w-40 h-40 bg-gradient-to-br from-purple-50 to-purple-100 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700">
                </div>

                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div class="flex justify-between items-start mb-4">
                        <div
                            class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <span
                            class="bg-purple-100 text-purple-600 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                            <i class="fas fa-arrow-up"></i> Total
                        </span>
                    </div>
                    <div>
                        <h3 class="text-gray-400 font-medium text-sm uppercase tracking-wide">Registered Students</h3>
                        <p class="text-4xl font-bold text-gray-800 mt-1"><?php echo $total_students; ?></p>
                    </div>
                </div>
            </div>

            <!-- Active Gear Loans -->
            <div
                class="stats-card group bg-white rounded-3xl p-6 shadow-lg border border-gray-100 relative overflow-hidden transition-all duration-300 hover:shadow-2xl">
                <div
                    class="absolute -right-10 -top-10 w-40 h-40 bg-gradient-to-br from-teal-50 to-teal-100 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700">
                </div>

                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div class="flex justify-between items-start mb-4">
                        <div
                            class="w-12 h-12 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600 shadow-sm group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300">
                            <i class="fas fa-hand-holding-heart text-xl"></i>
                        </div>
                        <span
                            class="bg-teal-100 text-teal-600 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                            <i class="fas fa-exchange-alt"></i> On Loan
                        </span>
                    </div>
                    <div>
                        <h3 class="text-gray-400 font-medium text-sm uppercase tracking-wide">Active Gear Loans</h3>
                        <p class="text-4xl font-bold text-gray-800 mt-1"><?php echo $activeLoans; ?></p>
                    </div>
                </div>
            </div>

            <!-- Activity Approvals Card -->
            <div onclick="window.location='review_activities.php'"
                class="stats-card group bg-white rounded-3xl p-6 shadow-lg border border-gray-100 relative overflow-hidden transition-all duration-300 hover:shadow-2xl cursor-pointer">
                <div
                    class="absolute -right-10 -top-10 w-40 h-40 bg-gradient-to-br from-orange-50 to-orange-100 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700">
                </div>

                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div class="flex justify-between items-start mb-4">
                        <div
                            class="w-12 h-12 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600 shadow-sm group-hover:bg-orange-600 group-hover:text-white transition-colors duration-300">
                            <i class="fas fa-hiking text-xl"></i>
                        </div>
                        <?php if ($pendingActivities > 0): ?>
                            <span
                                class="bg-red-100 text-red-600 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> Action Required
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 class="text-gray-400 font-medium text-sm uppercase tracking-wide">Pending Approvals</h3>
                        <p class="text-4xl font-bold text-gray-800 mt-1"><?php echo $pendingActivities; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Grid -->
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-bolt text-yellow-400"></i> Quick Actions
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <a href="exam_builder.php"
                class="group bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-blue hover:shadow-md transition-all text-center">
                <div
                    class="w-10 h-10 mx-auto bg-blue-50 rounded-full flex items-center justify-center text-brand-blue mb-2 group-hover:scale-110 transition-transform">
                    <i class="fas fa-plus"></i>
                </div>
                <span class="font-bold text-gray-700 text-sm">Create Exam</span>
            </a>
            <a href="communications.php"
                class="group bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:border-purple-500 hover:shadow-md transition-all text-center">
                <div
                    class="w-10 h-10 mx-auto bg-purple-50 rounded-full flex items-center justify-center text-purple-600 mb-2 group-hover:scale-110 transition-transform">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <span class="font-bold text-gray-700 text-sm">Announcement</span>
            </a>
            <a href="calendar.php"
                class="group bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:border-green-500 hover:shadow-md transition-all text-center">
                <div
                    class="w-10 h-10 mx-auto bg-green-50 rounded-full flex items-center justify-center text-green-600 mb-2 group-hover:scale-110 transition-transform">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <span class="font-bold text-gray-700 text-sm">Pin Event</span>
            </a>
            <a href="inventory.php"
                class="group bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:border-teal-500 hover:shadow-md transition-all text-center">
                <div
                    class="w-10 h-10 mx-auto bg-teal-50 rounded-full flex items-center justify-center text-teal-600 mb-2 group-hover:scale-110 transition-transform">
                    <i class="fas fa-boxes"></i>
                </div>
                <span class="font-bold text-gray-700 text-sm">Gear Logistics</span>
            </a>
            <a href="students_list.php"
                class="group bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:border-orange-500 hover:shadow-md transition-all text-center">
                <div
                    class="w-10 h-10 mx-auto bg-orange-50 rounded-full flex items-center justify-center text-orange-500 mb-2 group-hover:scale-110 transition-transform">
                    <i class="fas fa-user-plus"></i>
                </div>
                <span class="font-bold text-gray-700 text-sm">Manage Students</span>
            </a>
        </div>

        <!-- Widgets Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">

            <!-- Recent Announcements -->
            <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-bullhorn text-brand-blue"></i> Your Updates
                    </h3>
                    <a href="communications.php"
                        class="text-xs font-bold text-brand-blue bg-blue-50 px-3 py-1 rounded-full hover:bg-blue-100 transition">View
                        All</a>
                </div>

                <div class="space-y-4">
                    <?php if ($annStmt->rowCount() > 0): ?>
                        <?php while ($ann = $annStmt->fetch(PDO::FETCH_ASSOC)):
                            $bg = 'bg-blue-50 text-blue-600';
                            if ($ann['type'] == 'warning')
                                $bg = 'bg-orange-50 text-orange-600';
                            if ($ann['type'] == 'urgent')
                                $bg = 'bg-red-50 text-red-600';
                            ?>
                            <div class="flex gap-4 items-start p-3 rounded-xl hover:bg-gray-50 transition">
                                <div
                                    class="w-10 h-10 rounded-full <?php echo $bg; ?> flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($ann['title']); ?>
                                    </h4>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <?php echo date('M d, H:i', strtotime($ann['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-gray-400 text-sm text-center py-4">No recent announcements.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar Widget -->
            <div class="bg-gradient-to-br from-brand-dark to-brand-blue rounded-3xl p-6 shadow-lg text-white">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-white flex items-center gap-2">
                        <i class="fas fa-calendar-alt"></i> Schedule
                    </h3>
                    <a href="calendar.php"
                        class="text-xs font-bold text-white bg-white/20 px-3 py-1 rounded-full hover:bg-white/30 transition">Open
                        Calendar</a>
                </div>

                <div class="space-y-4">
                    <?php if ($calStmt->rowCount() > 0): ?>
                        <?php while ($ev = $calStmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="bg-white/10 backdrop-blur-sm p-4 rounded-xl border border-white/10">
                                <div class="flex justify-between items-start">
                                    <h4 class="font-bold text-sm"><?php echo htmlspecialchars($ev['title']); ?></h4>
                                    <span
                                        class="text-[10px] bg-white/20 px-2 py-0.5 rounded text-white/80 uppercase"><?php echo $ev['type']; ?></span>
                                </div>
                                <div class="flex items-center gap-2 mt-2 text-xs text-cyan-200">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('M d, H:i A', strtotime($ev['start_date'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-white/50 text-sm text-center py-4">No upcoming events.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity Chart (Simplified) -->
            <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4">Exam Completion</h3>
                <div class="relative h-48">
                    <canvas id="miniChart"></canvas>
                </div>
            </div>

        </div>

        <!-- Recent Exams Table -->
        <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-800 text-lg">Recent Exams</h3>
                <button class="text-sm text-brand-blue font-bold hover:underline">View All</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50/50 text-gray-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="p-4 font-semibold">Title</th>
                            <th class="p-4 font-semibold">Created</th>
                            <th class="p-4 font-semibold">Duration</th>
                            <th class="p-4 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-blue-50/20 transition-colors group">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-blue-100 text-brand-blue flex items-center justify-center font-bold text-xs">
                                            <?php echo strtoupper(substr($row['title'], 0, 1)); ?>
                                        </div>
                                        <span
                                            class="font-bold text-gray-700 group-hover:text-brand-blue transition"><?php echo $row['title']; ?></span>
                                    </div>
                                </td>
                                <td class="p-4 text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="p-4 text-sm text-gray-500">
                                    <span
                                        class="bg-gray-100 px-2 py-1 rounded text-xs font-bold text-gray-600"><?php echo $row['duration_minutes']; ?>
                                        min</span>
                                </td>
                                <td
                                    class="p-4 flex justify-center gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                    <a href="add_question.php?exam_id=<?php echo $row['id']; ?>"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition"
                                        title="Add Questions">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                    <a href="view_results.php?exam_id=<?php echo $row['id']; ?>"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-brand-blue hover:bg-blue-100 transition"
                                        title="View Results">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- 3D Tilt Script (Vanilla) -->
<script>
    document.addEventListener('mousemove', function (e) {
        document.querySelectorAll('.stats-card').forEach(card => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // Only apply if mouse is somewhat near/over (simplified for performance)
            if (x > -50 && x < rect.width + 50 && y > -50 && y < rect.height + 50) {
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = ((y - centerY) / centerY) * -5; // Max 5deg
                const rotateY = ((x - centerX) / centerX) * 5;

                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            } else {
                card.style.transform = `perspective(1000px) rotateX(0) rotateY(0)`;
            }
        });
    });

    // Mini Chart
    const ctx = document.getElementById('miniChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending'],
            datasets: [{
                data: [85, 15],
                backgroundColor: ['#2EC4B6', '#F3F4F6'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
</script>

<?php include_once '../includes/footer.php'; ?>