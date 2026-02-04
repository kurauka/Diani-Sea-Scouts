<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Fetch Exams with Stats
$query = "SELECT e.id, e.title, e.subject, u.name as instructor,
            (SELECT COUNT(*) FROM student_exams se WHERE se.exam_id = e.id AND se.status = 'completed') as attempts,
            (SELECT AVG(score) FROM student_exams se WHERE se.exam_id = e.id AND se.status = 'completed') as avg_score
          FROM exams e
          JOIN users u ON e.created_by = u.id
          ORDER BY e.created_at DESC";
$exams = $database->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Results & Grading</h1>
                <p class="text-gray-500 text-sm mt-1">Select an exam to view detailed analytics and student rankings.
                </p>
            </div>
            <div class="hidden md:block">
                <div class="bg-blue-100 text-brand-blue px-4 py-2 rounded-xl font-bold text-sm">
                    <i class="fas fa-info-circle mr-2"></i> Auto-Grading Enabled
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($exams as $exam): ?>
                <div
                    class="bg-white rounded-3xl border border-gray-100 shadow-lg hover:shadow-2xl transition-all duration-300 group overflow-hidden relative">
                    <!-- Decorative Top Border -->
                    <div class="h-2 w-full bg-gradient-to-r from-brand-dark to-brand-blue"></div>

                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <span
                                class="px-3 py-1 bg-blue-50 text-brand-blue rounded-full text-xs font-bold uppercase tracking-wide border border-blue-100">
                                <?php echo htmlspecialchars($exam['subject']); ?>
                            </span>
                            <span class="text-gray-400 text-xs">ID: #
                                <?php echo $exam['id']; ?>
                            </span>
                        </div>

                        <h3
                            class="text-xl font-bold text-gray-800 mb-2 line-clamp-2 min-h-[3.5rem] group-hover:text-brand-blue transition-colors">
                            <?php echo htmlspecialchars($exam['title']); ?>
                        </h3>

                        <p class="text-xs text-gray-500 mb-6 flex items-center gap-1">
                            <i class="fas fa-chalkboard-teacher text-blue-300"></i>
                            By
                            <?php echo htmlspecialchars($exam['instructor']); ?>
                        </p>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-gray-50 p-3 rounded-xl text-center">
                                <p class="text-xs text-gray-400 font-bold uppercase">Attempts</p>
                                <p class="text-xl font-black text-gray-800">
                                    <?php echo $exam['attempts']; ?>
                                </p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl text-center">
                                <p class="text-xs text-gray-400 font-bold uppercase">Avg Score</p>
                                <p
                                    class="text-xl font-black <?php echo ($exam['avg_score'] >= 50 ? 'text-green-500' : 'text-red-500'); ?>">
                                    <?php echo $exam['avg_score'] ? round($exam['avg_score']) : 0; ?>%
                                </p>
                            </div>
                        </div>

                        <a href="exam_results.php?exam_id=<?php echo $exam['id']; ?>"
                            class="block w-full text-center bg-gray-50 hover:bg-brand-blue text-gray-600 hover:text-white font-bold py-3 rounded-xl transition duration-300 group-hover:shadow-md">
                            View Analytics <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>
</div>