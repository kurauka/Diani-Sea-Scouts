<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    header("Location: users.php");
    exit;
}

// Fetch Student Info
$stmt = $database->prepare("SELECT u.* FROM users u WHERE u.id = :id AND u.role = 'student'");
$stmt->execute([':id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Assign troop_id to a troop_name variable for consistent UI display
if ($student) {
    $student['troop_name'] = $student['troop_id'] ?? 'Unassigned Troop';
}

if (!$student) {
    echo "Student not found.";
    exit;
}

// Fetch Exam Results
$query = "SELECT se.id as result_id, se.score, se.completed_at, e.title, e.subject, 'exam' as type
          FROM student_exams se 
          JOIN exams e ON se.exam_id = e.id 
          WHERE se.student_id = :uid AND se.status = 'completed' 
          ORDER BY se.completed_at DESC";
$stmt = $database->prepare($query);
$stmt->execute([':uid' => $student_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Attendance Stats
$attStmt = $database->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = :uid GROUP BY status");
$attStmt->execute([':uid' => $student_id]);
$attRaw = $attStmt->fetchAll(PDO::FETCH_ASSOC);
$attStats = ['present' => 0, 'late' => 0, 'absent' => 0];
foreach ($attRaw as $row) {
    $attStats[$row['status']] = $row['count'];
}
$totalSessions = array_sum($attStats);
$attendanceRate = $totalSessions > 0 ? round(($attStats['present'] / $totalSessions) * 100) : 0;

// Fetch Active Borrowing Records
$gearStmt = $database->prepare("SELECT b.*, e.name as item_name, c.name as category 
                                FROM borrowing_records b 
                                JOIN equipment e ON b.equipment_id = e.id 
                                LEFT JOIN equipment_categories c ON e.category_id = c.id
                                WHERE b.user_id = :uid AND b.status = 'Issued'");
$gearStmt->execute([':uid' => $student_id]);
$activeGear = $gearStmt->fetchAll(PDO::FETCH_ASSOC);

// Unified Timeline (Exams + Attendance)
$timelineQuery = "(SELECT 'exam' as act_type, e.title as act_title, se.score as act_meta, se.completed_at as act_date 
                   FROM student_exams se JOIN exams e ON se.exam_id = e.id WHERE se.student_id = :uid AND se.status = 'completed')
                  UNION 
                  (SELECT 'attendance' as act_type, COALESCE(ce.title, 'General Activity') as act_title, a.status as act_meta, a.created_at as act_date 
                   FROM attendance a LEFT JOIN calendar_events ce ON a.event_id = ce.id WHERE a.student_id = :uid)
                  ORDER BY act_date DESC LIMIT 10";
$timelineStmt = $database->prepare($timelineQuery);
$timelineStmt->execute([':uid' => $student_id]);
$timeline = $timelineStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Exam Stats
$totalExams = count($results);
$totalScore = 0;
$passed = 0;
$failed = 0;

$dates = [];
$scores = [];

foreach ($results as $res) {
    $totalScore += $res['score'];
    if ($res['score'] >= 50) { // Assuming 50 as default pass mark
        $passed++;
    } else {
        $failed++;
    }
    array_unshift($dates, date('M d', strtotime($res['completed_at'])));
    array_unshift($scores, $res['score']);
}

$avgScore = $totalExams > 0 ? round($totalScore / $totalExams) : 0;
?>
<?php include_once '../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    @media print {
        .no-print {
            display: none !important;
        }

        .flex-1 {
            overflow: visible !important;
        }

        main {
            padding: 0 !important;
        }

        .shadow-xl,
        .shadow-lg,
        .shadow-sm {
            box-shadow: none !important;
            border: 1px solid #eee !important;
        }

        .bg-gray-50 {
            background: white !important;
        }

        .h-screen {
            height: auto !important;
        }
    }
</style>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <div class="no-print">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-4 no-print">
                <a href="users.php" class="text-sm text-gray-500 hover:text-brand-blue inline-block"><i
                        class="fas fa-arrow-left mr-1"></i> Back to Users</a>
                <button onclick="window.print()"
                    class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 hover:bg-gray-50 transition">
                    <i class="fas fa-print"></i> Print Profile
                </button>
            </div>

            <!-- Profile Header -->
            <div
                class="bg-white rounded-3xl p-8 border border-gray-100 shadow-xl mb-8 flex flex-col md:flex-row items-center gap-8">
                <div
                    class="w-32 h-32 rounded-full overflow-hidden bg-blue-100 flex items-center justify-center shadow-inner border-4 border-white">
                    <?php if (!empty($student['profile_image']) && $student['profile_image'] !== 'assets/images/default_avatar.png'): ?>
                        <img src="../<?php echo htmlspecialchars($student['profile_image']); ?>"
                            class="w-full h-full object-cover">
                    <?php else: ?>
                        <span
                            class="text-5xl font-bold text-brand-blue"><?php echo strtoupper(substr($student['name'], 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-center md:text-left flex-1">
                    <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                        <h1 class="text-4xl font-black text-slate-800 tracking-tighter italic">
                            <?php echo htmlspecialchars($student['name']); ?>
                        </h1>
                        <span
                            class="bg-indigo-100 text-indigo-700 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest border border-indigo-200 shadow-sm">
                            <?php echo htmlspecialchars($student['troop_name'] ?? 'Unassigned Troop'); ?>
                        </span>
                    </div>
                    <p class="text-slate-400 font-medium text-lg">
                        <?php echo htmlspecialchars($student['email']); ?>
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center md:justify-start gap-3">
                        <span
                            class="px-5 py-2.5 bg-slate-50 text-slate-600 rounded-2xl text-xs font-black uppercase tracking-widest border border-slate-100 shadow-sm">
                            <i class="fas fa-fingerprint text-brand-blue"></i> ID: #<?php echo $student['id']; ?>
                        </span>
                        <a href="id_card.php?id=<?php echo $student['id']; ?>" target="_blank"
                            class="px-5 py-2.5 bg-slate-900 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-slate-200 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                            <i class="fas fa-print text-teal-400"></i> Print ID Card
                        </a>
                        <span
                            class="px-5 py-2.5 bg-green-50 text-green-600 rounded-2xl text-xs font-black uppercase tracking-widest border border-green-100 shadow-sm">
                            <i class="fas fa-shield-alt"></i> Verified Scout
                        </span>
                    </div>
                </div>
            </div>

            <!-- High Octane Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10 no-print">
                <div
                    class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-blue-50 text-brand-blue rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-brand-blue group-hover:text-white transition-colors">
                        <i class="fas fa-pen-nib"></i>
                    </div>
                    <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest">Exams Taken</p>
                    <h4 class="text-3xl font-black text-slate-800"><?php echo $totalExams; ?></h4>
                </div>
                <div
                    class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <p class="text-[10px] uppercase font-black text-purple-400 tracking-widest">Avg Performance</p>
                    <h4 class="text-3xl font-black text-slate-800"><?php echo $avgScore; ?>%</h4>
                </div>
                <div
                    class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-green-500 group-hover:text-white transition-colors">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <p class="text-[10px] uppercase font-black text-green-400 tracking-widest">Attendance Rate</p>
                    <h4 class="text-3xl font-black text-slate-800"><?php echo $attendanceRate; ?>%</h4>
                </div>
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 cursor-pointer"
                    onclick="document.getElementById('gearSection').scrollIntoView({behavior: 'smooth'})">
                    <div
                        class="w-12 h-12 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-orange-500 group-hover:text-white transition-colors">
                        <i class="fas fa-tools"></i>
                    </div>
                    <p class="text-[10px] uppercase font-black text-orange-400 tracking-widest">Gear Loaned</p>
                    <h4 class="text-3xl font-black text-slate-800"><?php echo count($activeGear); ?></h4>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Progress Chart -->
                <div
                    class="lg:col-span-2 bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-xl break-inside-avoid">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="font-black text-slate-800 italic flex items-center gap-2">
                            <i class="fas fa-chart-area text-brand-blue"></i> Performance Pulse
                        </h3>
                        <div class="flex gap-2 no-print">
                            <span class="w-3 h-3 rounded-full bg-brand-blue"></span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Score
                                History</span>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>

                <!-- Unified Unified Activity Feed -->
                <div
                    class="lg:col-span-1 bg-white rounded-3xl p-8 border border-gray-100 shadow-xl overflow-hidden relative">
                    <h3 class="font-black text-slate-800 mb-8 italic flex items-center gap-2">
                        <i class="fas fa-bolt text-teal-400"></i> Operation Feed
                    </h3>

                    <div class="space-y-8 relative">
                        <div class="absolute left-4 top-2 bottom-2 w-0.5 bg-slate-100"></div>
                        <?php foreach ($timeline as $entry):
                            $icon = $entry['act_type'] === 'exam' ? 'fa-file-alt' : 'fa-user-check';
                            $iconColor = $entry['act_type'] === 'exam' ? 'bg-blue-500' : 'bg-teal-500';
                            ?>
                            <div class="relative pl-12">
                                <div
                                    class="absolute left-0 top-1 w-8 h-8 rounded-xl <?php echo $iconColor; ?> shadow-lg flex items-center justify-center text-white text-xs">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1"><?php echo date('M d, H:i', strtotime($entry['act_date'])); ?></span>
                                    <h5 class="text-xs font-black text-slate-800 mb-1">
                                        <?php echo htmlspecialchars($entry['act_title']); ?>
                                    </h5>
                                    <span class="text-[9px] font-bold text-slate-400 italic">Action:
                                        <?php echo $entry['act_meta']; ?>
                                        <?php echo $entry['act_type'] === 'exam' ? '%' : ''; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($timeline)): ?>
                            <p class="text-center text-slate-400 font-bold py-10">Silence in the fleet. No recent activity.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Logistics & Equipment Tab -->
            <div id="gearSection"
                class="mt-12 bg-white rounded-[2.5rem] border border-gray-100 shadow-xl overflow-hidden animate-fade-in-up">
                <div class="p-8 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="font-black text-slate-800 italic">Quartermaster Records: Gear Loans</h3>
                    <span
                        class="text-[10px] font-black uppercase bg-orange-50 text-orange-600 px-3 py-1 rounded-full border border-orange-100">Scout
                        Equipment Tracking</span>
                </div>
                <div class="p-0">
                    <table class="w-full text-left">
                        <thead
                            class="bg-gray-50/50 text-[10px] uppercase font-black tracking-[0.2em] text-slate-400 border-b border-gray-100">
                            <tr>
                                <th class="p-6">Equipment Item</th>
                                <th class="p-6">Category</th>
                                <th class="p-6">Loaned On</th>
                                <th class="p-6">Due Back</th>
                                <th class="p-6 text-right">Condition</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($activeGear as $item): ?>
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="p-6 font-black text-slate-800">
                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                    </td>
                                    <td class="p-6 font-bold text-slate-400 uppercase text-[10px]">
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </td>
                                    <td class="p-6 font-medium text-slate-500 italic">
                                        <?php echo date('M d, Y', strtotime($item['borrow_date'])); ?>
                                    </td>
                                    <td class="p-6 font-medium text-brand-blue italic">
                                        <?php echo date('M d, Y', strtotime($item['due_date'])); ?>
                                    </td>
                                    <td class="p-6 text-right">
                                        <span
                                            class="text-[9px] font-black uppercase bg-slate-900 text-white px-3 py-1 rounded-lg">Issued</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($activeGear)): ?>
                                <tr>
                                    <td colspan="5" class="p-20 text-center">
                                        <div
                                            class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                            <i class="fas fa-boxes"></i>
                                        </div>
                                        <p class="text-slate-400 font-bold">No active gear loans recorded for this scout.
                                        </p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detailed Exam History -->
            <div
                class="mt-12 bg-white rounded-[2.5rem] border border-gray-100 overflow-hidden shadow-xl animate-fade-in-up">
                <div class="p-8 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="font-black text-slate-800 italic">Academic Records: Exam History</h3>
                    <div class="flex gap-2">
                        <span
                            class="text-[9px] font-black uppercase bg-green-50 text-green-600 px-3 py-1 rounded-full border border-green-100">Pass:
                            50%+</span>
                        <span
                            class="text-[9px] font-black uppercase bg-red-50 text-red-600 px-3 py-1 rounded-full border border-red-100">Fail</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead
                            class="bg-gray-50/50 text-[10px] uppercase font-black tracking-[0.2em] text-slate-400 border-b border-gray-100">
                            <tr>
                                <th class="p-6 pl-8">Exam Module</th>
                                <th class="p-6">Subject Cluster</th>
                                <th class="p-6">Completion Date</th>
                                <th class="p-6">Final Score</th>
                                <th class="p-6 text-center">Credentials</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($results as $res):
                                $isPass = $res['score'] >= 50;
                                ?>
                                <tr class="hover:bg-indigo-50/30 transition group">
                                    <td class="p-6 pl-8">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-2 h-2 rounded-full <?php echo $isPass ? 'bg-green-500' : 'bg-red-500'; ?>">
                                            </div>
                                            <span
                                                class="font-black text-slate-800 group-hover:text-brand-blue transition-colors"><?php echo htmlspecialchars($res['title']); ?></span>
                                        </div>
                                    </td>
                                    <td class="p-6">
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-2 py-0.5 bg-slate-50 rounded border border-slate-100">
                                            <?php echo htmlspecialchars($res['subject']); ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-xs text-slate-500 font-medium italic">
                                        <?php echo date('M d, Y', strtotime($res['completed_at'])); ?>
                                    </td>
                                    <td class="p-6">
                                        <div class="flex flex-col">
                                            <span
                                                class="font-black text-lg <?php echo $isPass ? 'text-green-600' : 'text-red-500'; ?>">
                                                <?php echo $res['score']; ?>%
                                            </span>
                                            <div class="w-16 h-1 bg-slate-100 rounded-full mt-1 overflow-hidden">
                                                <div class="h-full <?php echo $isPass ? 'bg-green-500' : 'bg-red-500'; ?>"
                                                    style="width: <?php echo $res['score']; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <?php if ($isPass): ?>
                                            <a href="certificate.php?result_id=<?php echo $res['result_id']; ?>" target="_blank"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white hover:bg-slate-800 rounded-xl text-[10px] font-black uppercase tracking-widest transition shadow-lg shadow-slate-100">
                                                <i class="fas fa-award text-teal-400"></i> View Certificate
                                            </a>
                                        <?php else: ?>
                                            <span
                                                class="text-[10px] text-slate-300 font-black uppercase tracking-widest">Ineligible</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    const ctx = document.getElementById('progressChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Exam Score',
                data: <?php echo json_encode($scores); ?>,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#2563EB',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: '#F3F4F6' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>

<?php include_once '../includes/footer.php'; ?>