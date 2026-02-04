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
$stmt = $database->prepare("SELECT * FROM users WHERE id = :id AND role = 'student'");
$stmt->execute([':id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "Student not found.";
    exit;
}

// Fetch Exam Results
$query = "SELECT se.id as result_id, se.score, se.completed_at, e.title, e.subject 
          FROM student_exams se 
          JOIN exams e ON se.exam_id = e.id 
          WHERE se.student_id = :uid AND se.status = 'completed' 
          ORDER BY se.completed_at DESC";
$stmt = $database->prepare($query);
$stmt->execute([':uid' => $student_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$totalExams = count($results);
$totalScore = 0;
$passed = 0;
$failed = 0;

$dates = [];
$scores = [];

foreach ($results as $game) {
    $totalScore += $game['score'];
    if ($game['score'] >= ($game['pass_mark'] ?? 50)) {
        $passed++;
    } else {
        $failed++;
    }
    // Prepare Chart Data (Reverse chronological for chart left-to-right)
    array_unshift($dates, date('M d', strtotime($game['completed_at'])));
    array_unshift($scores, $game['score']);
}

$avgScore = $totalExams > 0 ? round($totalScore / $totalExams) : 0;
?>
<?php include_once '../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <div class="max-w-6xl mx-auto">
            <a href="users.php" class="text-sm text-gray-500 hover:text-brand-blue mb-4 inline-block"><i
                    class="fas fa-arrow-left mr-1"></i> Back to Users</a>

            <!-- Profile Header -->
            <div
                class="bg-white rounded-3xl p-8 border border-gray-100 shadow-xl mb-8 flex flex-col md:flex-row items-center gap-8">
                <div
                    class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center text-5xl font-bold text-brand-blue shadow-inner">
                    <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                </div>
                <div class="text-center md:text-left flex-1">
                    <h1 class="text-3xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($student['name']); ?>
                    </h1>
                    <p class="text-gray-500 text-lg">
                        <?php echo htmlspecialchars($student['email']); ?>
                    </p>
                    <div class="mt-4 flex flex-wrap justify-center md:justify-start gap-4">
                        <span
                            class="px-4 py-2 bg-blue-50 text-brand-blue rounded-xl text-sm font-bold border border-blue-100">
                            <i class="fas fa-id-card mr-2"></i> ID: #<?php echo $student['id']; ?>
                        </span>
                        <a href="id_card.php?id=<?php echo $student['id']; ?>" target="_blank"
                            class="px-4 py-2 bg-gray-800 text-white rounded-xl text-sm font-bold shadow hover:bg-gray-900 transition flex items-center gap-2">
                            <i class="fas fa-print"></i> Generate ID Card
                        </a>
                        <span
                            class="px-4 py-2 bg-green-50 text-green-600 rounded-xl text-sm font-bold border border-green-100">
                            <i class="fas fa-check-circle mr-2"></i> Active Student
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-lg text-center">
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Total Exams</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">
                        <?php echo $totalExams; ?>
                    </p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-lg text-center">
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Average Score</p>
                    <p class="text-3xl font-bold text-brand-blue mt-2">
                        <?php echo $avgScore; ?>%
                    </p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-lg text-center">
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Passed</p>
                    <p class="text-3xl font-bold text-green-500 mt-2">
                        <?php echo $passed; ?>
                    </p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-lg text-center">
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Failed</p>
                    <p class="text-3xl font-bold text-red-500 mt-2">
                        <?php echo $failed; ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Progress Chart -->
                <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-gray-100 shadow-xl">
                    <h3 class="font-bold text-gray-800 mb-6">Performance Progress</h3>
                    <div class="h-64">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activities / Weaknesses could go here, for now just a placeholder or extra info -->
                <div
                    class="bg-gradient-to-br from-brand-blue to-blue-700 rounded-3xl p-8 shadow-xl text-white flex flex-col justify-center items-center text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mb-4 text-2xl">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Top Performer?</h3>
                    <p class="text-blue-100 text-sm mb-6">
                        <?php if ($avgScore > 80): ?>
                            This student is performing excellently! Review their results to see if they qualify for special
                            badges.
                        <?php elseif ($avgScore > 50): ?>
                            Good progress. Encourage them to focus on their weak subjects.
                        <?php else: ?>
                            Needs attention. scores are below average.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Detailed Exam History -->
            <div class="mt-8 bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-xl">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800">Exam History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="p-4 pl-6">Exam Title</th>
                                <th class="p-4">Subject</th>
                                <th class="p-4">Date Taken</th>
                                <th class="p-4">Score</th>
                                <th class="p-4 text-center">Certificate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($results as $res):
                                $isPass = $res['score'] >= ($res['pass_mark'] ?? 50);
                                ?>
                                <tr class="hover:bg-blue-50/30 transition">
                                    <td class="p-4 pl-6 font-bold text-gray-800">
                                        <?php echo htmlspecialchars($res['title']); ?>
                                    </td>
                                    <td class="p-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($res['subject']); ?>
                                    </td>
                                    <td class="p-4 text-sm text-gray-500 font-mono">
                                        <?php echo date('M d, Y', strtotime($res['completed_at'])); ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="font-bold <?php echo $isPass ? 'text-green-600' : 'text-red-500'; ?>">
                                            <?php echo $res['score']; ?>%
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <?php if ($isPass): ?>
                                            <a href="certificate.php?result_id=<?php echo $res['result_id']; ?>" target="_blank"
                                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 rounded-lg text-xs font-bold transition">
                                                <i class="fas fa-certificate"></i> Print
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">Not Eligible</span>
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