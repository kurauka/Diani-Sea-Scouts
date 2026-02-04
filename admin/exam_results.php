<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) {
    header("Location: exams_list.php");
    exit;
}

// Fetch Exam Info
$stmt = $database->prepare("SELECT title, subject FROM exams WHERE id = :id");
$stmt->execute([':id' => $exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Results for Leaderboard
$query = "SELECT se.id as result_id, se.score, se.completed_at, u.name, u.email, u.id as student_id
          FROM student_exams se 
          JOIN users u ON se.student_id = u.id 
          WHERE se.exam_id = :eid AND se.status = 'completed'
          ORDER BY se.score DESC";
$stmt = $database->prepare($query);
$stmt->execute([':eid' => $exam_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Analytics
$totalAttempts = count($results);
$scores = array_column($results, 'score');
$minScore = $totalAttempts ? min($scores) : 0;
$maxScore = $totalAttempts ? max($scores) : 0;
$avgScore = $totalAttempts ? round(array_sum($scores) / $totalAttempts) : 0;

?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                 <a href="exams_list.php" class="text-sm text-gray-500 hover:text-brand-blue inline-block"><i class="fas fa-arrow-left mr-1"></i> Back to All Exams</a>
                 
                 <a href="export.php?type=results&exam_id=<?php echo $exam_id; ?>" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg transition flex items-center gap-2">
                     <i class="fas fa-file-csv"></i> Export CSV
                 </a>
            </div>

            <!-- Header -->
            <div class="mb-8">
                 <span class="px-3 py-1 bg-blue-100 text-brand-blue rounded-full text-xs font-bold uppercase tracking-wide border border-blue-200"><?php echo htmlspecialchars($exam['subject']); ?></span>
                <h1 class="text-3xl font-bold text-gray-800 mt-2"><?php echo htmlspecialchars($exam['title']); ?> - Analytics</h1>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-lg flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-brand-blue text-xl"><i class="fas fa-users"></i></div>
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wide">Total Attempts</p>
                        <p class="text-2xl font-black text-gray-800"><?php echo $totalAttempts; ?></p>
                    </div>
                </div>
                 <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-lg flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-50 flex items-center justify-center text-yellow-500 text-xl"><i class="fas fa-trophy"></i></div>
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wide">Highest Score</p>
                        <p class="text-2xl font-black text-gray-800"><?php echo $maxScore; ?>%</p>
                    </div>
                </div>
                 <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-lg flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 text-xl"><i class="fas fa-exclamation-triangle"></i></div>
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wide">Lowest Score</p>
                        <p class="text-2xl font-black text-gray-800"><?php echo $minScore; ?>%</p>
                    </div>
                </div>
                 <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-lg flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-500 text-xl"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wide">Average Score</p>
                        <p class="text-2xl font-black text-gray-800"><?php echo $avgScore; ?>%</p>
                    </div>
                </div>
            </div>

            <!-- Leaderboard Logic -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Top 3 Podium (Visual) -->
                <div class="lg:col-span-1 bg-gradient-to-br from-brand-dark to-brand-blue rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
                    
                    <h3 class="font-bold text-lg mb-6 flex items-center gap-2"><i class="fas fa-crown text-yellow-400"></i> Top Performers</h3>
                    
                    <div class="space-y-6">
                        <?php for ($i = 0; $i < min(3, count($results)); $i++): 
                                $top = $results[$i];
                                $color = $i === 0 ? 'text-yellow-300' : ($i === 1 ? 'text-gray-300' : 'text-orange-300');
                                $rank = $i + 1;
                        ?>
                            <div class="flex items-center gap-4 bg-white/10 p-4 rounded-2xl border border-white/10">
                                <div class="text-2xl font-black <?php echo $color; ?>">#<?php echo $rank; ?></div>
                                <div>
                                    <p class="font-bold"><?php echo htmlspecialchars($top['name']); ?></p>
                                    <p class="text-2xl font-black"><?php echo $top['score']; ?>%</p>
                                </div>
                            </div>
                        <?php endfor; ?>
                        
                        <?php if (count($results) == 0): ?>
                             <p class="text-blue-200 italic text-sm">No results to display yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Full Leaderboard Table -->
                <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800">All Results (Ranked)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="p-4 pl-6">Rank</th>
                                    <th class="p-4">Student</th>
                                    <th class="p-4">Date Taken</th>
                                    <th class="p-4">Score</th>
                                    <th class="p-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($results as $index => $res): ?>
                                    <tr class="hover:bg-blue-50/50 transition">
                                        <td class="p-4 pl-6 font-mono text-gray-400">#<?php echo $index + 1; ?></td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                                                    <?php echo strtoupper(substr($res['name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($res['name']); ?></p>
                                                    <p class="text-xs text-gray-400"><?php echo htmlspecialchars($res['email']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 text-sm text-gray-500 font-mono"><?php echo date('M d, H:i', strtotime($res['completed_at'])); ?></td>
                                        <td class="p-4">
                                            <span class="font-bold <?php echo ($res['score'] >= 50 ? 'text-green-600' : 'text-red-500'); ?>">
                                                <?php echo $res['score']; ?>%
                                            </span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <a href="student_profile.php?id=<?php echo $res['student_id']; ?>" class="p-2 rounded-lg bg-gray-50 hover:bg-brand-blue text-gray-400 hover:text-white transition" title="View Profile">
                                                <i class="fas fa-user"></i>
                                            </a>
                                            <?php if($res['score'] >= 50): ?>
                                                <a href="certificate.php?result_id=<?php echo $res['result_id']; ?>" target="_blank" class="p-2 rounded-lg bg-gray-50 hover:bg-yellow-500 text-gray-400 hover:text-white transition" title="Print Certificate">
                                                    <i class="fas fa-certificate"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
