<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
include_once '../models/Exam.php';
include_once '../models/Result.php';

$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
$exam = new Exam($database);
$resultModel = new Result($database);

// 1. Get all exams
$all_exams = $exam->read_all();

// 2. Get student history
$history = $resultModel->getStudentResults($_SESSION['user_id']);
$completed_exams = [];
$scores = [];
$total_score = 0;
while ($row = $history->fetch(PDO::FETCH_ASSOC)) {
    $completed_exams[$row['exam_id']] = $row;
    $scores[] = $row['score'];
    $total_score += $row['score'];
}

$exams_taken = count($completed_exams);
$avg_score = $exams_taken > 0 ? round($total_score / $exams_taken) : 0;

// 3. Get Recent Materials (Limit 3)
$matQuery = "SELECT title, type, file_path, created_at FROM materials ORDER BY created_at DESC LIMIT 3";
$matStmt = $database->query($matQuery);

// 4. Analytics Snapshot (Simplified logic from analytics.php)
// In a real app, refactor this into a Service/Class
$best_subject = "N/A";
if ($exams_taken > 0) {
    // Quick calc (this is a simplified version of analytics.php logic)
    $subjQuery = "SELECT e.subject, AVG(se.score) as avg_score FROM student_exams se JOIN exams e ON se.exam_id = e.id WHERE se.student_id = :uid GROUP BY e.subject ORDER BY avg_score DESC LIMIT 1";
    $sStmt = $database->prepare($subjQuery);
    $sStmt->execute([':uid' => $_SESSION['user_id']]);
    $best = $sStmt->fetch(PDO::FETCH_ASSOC);
    if ($best)
        $best_subject = $best['subject'];
}

// Time Aware Greeting
$hour = date('H');
$greeting = "Hello";
if ($hour < 12)
    $greeting = "Good Morning";
elseif ($hour < 18)
    $greeting = "Good Afternoon";
else
    $greeting = "Good Evening";
?>
<?php include_once '../includes/header.php'; ?>

<!-- Custom Styles for Animations -->
<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-enter {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    .delay-100 {
        animation-delay: 0.1s;
    }

    .delay-200 {
        animation-delay: 0.2s;
    }

    .delay-300 {
        animation-delay: 0.3s;
    }

    /* 3D Tilt Wrapper */
    .tilt-card {
        transition: transform 0.1s;
        will-change: transform;
        transform-style: preserve-3d;
    }
</style>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto">
        <!-- Hero Section -->
        <div class="relative bg-brand-dark overflow-hidden animate-enter">
            <div class="absolute inset-0 bg-gradient-to-r from-brand-dark to-brand-blue opacity-90"></div>
            <!-- Decorative Circles -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white opacity-10 rounded-full blur-3xl"></div>
            <div
                class="absolute bottom-0 left-0 w-64 h-64 bg-teal-400 opacity-20 rounded-full blur-3xl transform translate-y-1/2 -translate-x-1/2">
            </div>

            <div class="relative z-10 px-8 py-12 md:py-16 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="text-white space-y-2">
                    <span
                        class="bg-white/20 text-teal-200 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider backdrop-blur-sm">Student
                        Portal</span>
                    <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight">
                        <?php echo $greeting; ?>, <?php echo explode(' ', $_SESSION['name'])[0]; ?>! 👋
                    </h1>
                    <p class="text-blue-100 text-lg max-w-xl">Ready to challenge yourself? You have new exams waiting
                        for you.</p>
                </div>

                <!-- Quick Stats in Hero -->
                <div class="flex gap-4">
                    <div
                        class="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-2xl text-center min-w-[120px] hover:bg-white/20 transition transform hover:-translate-y-1">
                        <p class="text-3xl font-bold text-white"><?php echo $exams_taken; ?></p>
                        <p class="text-xs text-blue-200 uppercase font-semibold">Exams Taken</p>
                    </div>
                    <div
                        class="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-2xl text-center min-w-[120px] hover:bg-white/20 transition transform hover:-translate-y-1">
                        <p class="text-3xl font-bold text-teal-300"><?php echo $avg_score; ?>%</p>
                        <p class="text-xs text-teal-100 uppercase font-semibold">Avg Score</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-8 -mt-8 relative z-20 pb-12">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content: Exams Grid -->
                <div class="lg:col-span-2 space-y-8 animate-enter delay-100">

                    <!-- Integrated Analytics Snapshot Widget -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div
                            class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between group hover:shadow-md transition">
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase">Best Subject</p>
                                <p class="font-bold text-gray-800 text-lg"><?php echo $best_subject; ?></p>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-green-50 text-green-500 flex items-center justify-center text-xl group-hover:scale-110 transition">
                                <i class="fas fa-crown"></i>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between group hover:shadow-md transition cursor-pointer"
                            onclick="window.location='analytics.php'">
                            <div>
                                <p class="text-xs text-gray-400 font-bold uppercase">Analytics</p>
                                <p class="font-bold text-brand-blue text-sm">View Full Report</p>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-blue-50 text-brand-blue flex items-center justify-center text-xl group-hover:scale-110 transition">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Announcements Widget -->
                    <?php
                    $announceQuery = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3";
                    $announceStmt = $database->query($announceQuery);
                    if ($announceStmt->rowCount() > 0):
                    ?>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-brand-pale">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-bullhorn text-brand-blue"></i> Recent Announcements
                        </h2>
                        <div class="space-y-3">
                            <?php while ($ann = $announceStmt->fetch(PDO::FETCH_ASSOC)): 
                                 $bg = 'bg-blue-50 border-blue-100 text-blue-700';
                                 $icon = 'fa-info-circle';
                                 if($ann['type'] == 'warning') { $bg = 'bg-orange-50 border-orange-100 text-orange-700'; $icon = 'fa-exclamation-triangle'; }
                                 if($ann['type'] == 'urgent') { $bg = 'bg-red-50 border-red-100 text-red-700'; $icon = 'fa-exclamation-circle'; }
                            ?>
                            <div class="p-4 rounded-xl border <?php echo $bg; ?> flex gap-3 items-start">
                                <i class="fas <?php echo $icon; ?> mt-1"></i>
                                <div>
                                    <h3 class="font-bold text-sm mb-1"><?php echo htmlspecialchars($ann['title']); ?></h3>
                                    <p class="text-xs opacity-80"><?php echo htmlspecialchars($ann['message']); ?></p>
                                    <span class="text-[10px] uppercase font-bold tracking-wider mt-2 block opacity-60">
                                        <?php echo date('M d, H:i', strtotime($ann['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-brand-blue"></i> Available to Take
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php while ($row = $all_exams->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                            $is_taken = array_key_exists($row['id'], $completed_exams);
                            if ($is_taken)
                                continue;
                            ?>
                            <!-- Tilt Card -->
                            <div
                                class="tilt-card group bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-2xl hover:border-blue-200 flex flex-col justify-between h-full relative overflow-hidden transform transition-all duration-300">
                                <div
                                    class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-blue-50 to-transparent rounded-bl-full opacity-50 group-hover:scale-110 transition-transform duration-500">
                                </div>

                                <div class="relative z-10 pointer-events-none">
                                    <!-- Pointer events none for inner elements to let tilt work smoothly -->
                                    <div class="flex justify-between items-start mb-4">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-blue-50 text-brand-blue flex items-center justify-center group-hover:bg-brand-blue group-hover:text-white transition-colors duration-300">
                                            <i class="fas fa-edit text-xl"></i>
                                        </div>
                                        <span
                                            class="bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded-full flex items-center gap-1">
                                            <i class="far fa-clock"></i> <?php echo $row['duration_minutes']; ?>m
                                        </span>
                                    </div>

                                    <h3
                                        class="text-lg font-bold text-gray-800 mb-2 group-hover:text-brand-blue transition-colors">
                                        <?php echo $row['title']; ?>
                                    </h3>
                                    <p class="text-gray-500 text-sm line-clamp-2 mb-4"><?php echo $row['description']; ?>
                                    </p>

                                    <div class="flex items-center gap-2 mb-4">
                                        <span
                                            class="text-xs font-semibold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded"><?php echo isset($row['subject']) ? $row['subject'] : 'General'; ?></span>
                                    </div>
                                </div>

                                <a href="take_exam.php?exam_id=<?php echo $row['id']; ?>"
                                    class="mt-auto w-full py-3 rounded-xl bg-gray-50 text-brand-dark font-semibold text-center hover:bg-brand-blue hover:text-white transition-all shadow-sm border border-gray-100 relative z-20">
                                    Start Exam <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Recent Results Section -->
                    <div class="pt-8">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-history text-purple-500"></i> Recent History
                            </h2>
                            <a href="result.php" class="text-xs font-bold text-brand-blue hover:underline">View All</a>
                        </div>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <?php if (count($completed_exams) > 0): ?>
                                <table class="w-full text-left">
                                    <tbody class="divide-y divide-gray-50">
                                        <?php
                                        $count = 0;
                                        foreach (array_reverse($completed_exams) as $exam_id => $data):
                                            if ($count++ >= 3)
                                                break; // Limit to 3
                                            ?>
                                            <tr class="hover:bg-gray-50 transition group">
                                                <td class="p-4 pl-6">
                                                    <div class="font-bold text-gray-800 group-hover:text-brand-blue transition">
                                                        <?php echo $data['title']; ?>
                                                    </div>
                                                    <div class="text-xs text-gray-400">
                                                        <?php echo date('M d, Y', strtotime($data['completed_at'])); ?>
                                                    </div>
                                                </td>
                                                <td class="p-4">
                                                    <div
                                                        class="text-xs text-gray-500 font-medium bg-gray-100 inline-block px-2 rounded">
                                                        <?php echo isset($data['subject']) ? $data['subject'] : 'General'; ?>
                                                    </div>
                                                </td>
                                                <td class="p-4 text-center">
                                                    <div
                                                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold 
                                                        <?php echo $data['score'] >= 70 ? 'bg-green-100 text-green-700' : ($data['score'] >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                                                        <?php echo $data['score']; ?>%
                                                    </div>
                                                </td>
                                                <td class="p-4 pr-6 text-right">
                                                    <a href="result.php?id=<?php echo $data['id']; ?>"
                                                        class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-brand-blue hover:text-white transition">
                                                        <i class="far fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="p-8 text-center text-gray-400">No exams taken yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Content -->
                <div class="space-y-6 animate-enter delay-200">

                    <!-- Progress Chart -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-gray-800 font-bold mb-4">Your Progress</h3>
                        <div class="h-40 relative">
                            <canvas id="studentChart"></canvas>
                        </div>
                    </div>

                    <!-- Materials Widget -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-gray-800 font-bold">New Materials</h3>
                            <a href="materials.php" class="text-xs font-bold text-brand-blue hover:underline">View
                                All</a>
                        </div>

                        <div class="space-y-3">
                            <?php while ($mat = $matStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <a href="../<?php echo $mat['file_path']; ?>" target="_blank"
                                    class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition group block">
                                    <?php
                                    $icon = 'file';
                                    $bg = 'gray';
                                    if ($mat['type'] == 'pdf') {
                                        $icon = 'file-pdf';
                                        $bg = 'red';
                                    } elseif ($mat['type'] == 'video') {
                                        $icon = 'play-circle';
                                        $bg = 'blue';
                                    }
                                    ?>
                                    <div
                                        class="w-10 h-10 rounded-lg bg-<?php echo $bg; ?>-50 text-<?php echo $bg; ?>-500 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition">
                                        <i class="fas fa-<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <p
                                            class="text-sm font-bold text-gray-700 truncate group-hover:text-brand-blue transition">
                                            <?php echo $mat['title']; ?>
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            <?php echo date('M d', strtotime($mat['created_at'])); ?>
                                        </p>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                            <?php if ($matStmt->rowCount() == 0): ?>
                                <p class="text-xs text-gray-400 text-center py-4">No materials uploaded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pro Tip (Gradient Card) -->
                    <div
                        class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden transform hover:scale-[1.02] transition duration-300">
                        <div class="relative z-10">
                            <h3 class="font-bold text-lg mb-2">Pro Tip</h3>
                            <p class="text-indigo-100 text-sm mb-4">You score 15% higher in morning exams. Try
                                scheduling your next one before noon!</p>
                            <button
                                class="bg-white/20 hover:bg-white/30 backdrop-blur rounded-lg px-4 py-2 text-xs font-bold transition">View
                                Tips</button>
                        </div>
                        <div class="absolute -bottom-4 -right-4 text-9xl text-white opacity-10">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script>
    // Config Chart
    const ctx = document.getElementById('studentChart').getContext('2d');
    const scores = <?php echo json_encode(array_reverse(array_slice($scores, 0, 5))); ?>;
    const labels = scores.map((_, i) => `Exam ${i + 1}`);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Score',
                data: scores,
                borderColor: '#00B4D8',
                backgroundColor: 'rgba(0, 180, 216, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0077B6',
                pointRadius: 4
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, display: false },
                x: { display: false }
            },
            maintainAspectRatio: false
        }
    });

    // 3D Tilt Effect Logic
    document.addEventListener('mousemove', (e) => {
        document.querySelectorAll('.tilt-card').forEach(card => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            if (x >= 0 && x <= rect.width && y >= 0 && y <= rect.height) {
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const rotateX = ((y - centerY) / centerY) * -5; // Max -5deg to 5deg
                const rotateY = ((x - centerX) / centerX) * 5;

                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
            } else {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
            }
        });
    });
</script>

<?php include_once '../includes/footer.php'; ?>