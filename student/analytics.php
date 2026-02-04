<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
include_once '../models/Result.php';

$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// 1. Get All Results for Student
$query = "SELECT se.score, e.title, e.subject 
          FROM student_exams se 
          JOIN exams e ON se.exam_id = e.id 
          WHERE se.student_id = :uid AND se.status = 'completed'";
$stmt = $database->prepare($query);
$stmt->execute([':uid' => $_SESSION['user_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Process Data for Charts
$subject_scores = [];
$subject_counts = [];

if (count($results) > 0) {
    foreach ($results as $r) {
        $subj = $r['subject'] ?: 'General';
        if (!isset($subject_scores[$subj])) {
            $subject_scores[$subj] = 0;
            $subject_counts[$subj] = 0;
        }
        $subject_scores[$subj] += $r['score'];
        $subject_counts[$subj]++;
    }
}

$avg_subject_scores = [];
foreach ($subject_scores as $subj => $total) {
    $avg_subject_scores[$subj] = round($total / $subject_counts[$subj]);
}

// Sort for Strengths/Weaknesses
arsort($avg_subject_scores);
$strengths = array_slice($avg_subject_scores, 0, 3);
$weaknesses = array_slice($avg_subject_scores, -3); // Logic might need refinement if fewer items
asort($weaknesses); // Sort weaknesses ascending (worst first)

?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Performance Analytics</h1>

        <?php if (count($results) == 0): ?>
            <div class="bg-white p-8 rounded-2xl shadow-sm text-center">
                <p class="text-gray-500">No layout data available yet. Complete some exams to see your analytics!</p>
                <a href="dashboard.php" class="inline-block mt-4 bg-brand-blue text-white px-6 py-2 rounded-lg">Go to
                    Dashboard</a>
            </div>
        <?php else: ?>

            <!-- Strengths & Weaknesses -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Strengths -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-green-100 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <i class="fas fa-trophy text-6xl text-green-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-fire text-orange-500"></i> Top Strengths
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($strengths as $subj => $score): ?>
                            <div>
                                <div class="flex justify-between text-sm font-medium mb-1">
                                    <span class="text-gray-600">
                                        <?php echo $subj; ?>
                                    </span>
                                    <span class="text-green-600">
                                        <?php echo $score; ?>%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $score; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Weaknesses -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-red-100 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <i class="fas fa-book-reader text-6xl text-red-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-red-500"></i> Areas to Improve
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($weaknesses as $subj => $score): ?>
                            <div>
                                <div class="flex justify-between text-sm font-medium mb-1">
                                    <span class="text-gray-600">
                                        <?php echo $subj; ?>
                                    </span>
                                    <span class="text-red-500">
                                        <?php echo $score; ?>%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-red-400 h-2 rounded-full" style="width: <?php echo $score; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-6">Subject Proficiency</h3>
                    <div class="h-64">
                        <canvas id="radarChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-6">Learning Journey</h3>
                    <div class="h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </main>
</div>

<?php if (count($results) > 0): ?>
    <script>
        // Radar Chart Data (Subjects)
        const subjectLabels = <?php echo json_encode(array_keys($avg_subject_scores)); ?>;
        const subjectData = <?php echo json_encode(array_values($avg_subject_scores)); ?>;

        new Chart(document.getElementById('radarChart'), {
            type: 'radar',
            data: {
                labels: subjectLabels,
                datasets: [{
                    label: 'Proficiency',
                    data: subjectData,
                    fill: true,
                    backgroundColor: 'rgba(0, 180, 216, 0.2)',
                    borderColor: '#00B4D8',
                    pointBackgroundColor: '#0077B6',
                }]
            },
            options: {
                scales: {
                    r: {
                        angleLines: { display: false },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                }
            }
        });

        // Trend Line Chart
        // (Simplification: Just creating a generic trend based on raw history order for now)
        const rawScores = <?php echo json_encode(array_column($results, 'score')); ?>;
        const rawLabels = rawScores.map((_, i) => `Exam ${i + 1}`);

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: rawLabels,
                datasets: [{
                    label: 'Score History',
                    data: rawScores,
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    </script>
<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>