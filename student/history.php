<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Fetch all completed exams
$query = "SELECT se.*, e.title, e.subject, e.duration_minutes 
          FROM student_exams se 
          JOIN exams e ON se.exam_id = e.id 
          WHERE se.student_id = :uid AND se.status = 'completed' 
          ORDER BY se.completed_at DESC";
$stmt = $database->prepare($query);
$stmt->execute([':uid' => $_SESSION['user_id']]);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Exam History</h1>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <?php if ($stmt->rowCount() > 0): ?>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="p-6 font-semibold">Exam Title</th>
                            <th class="p-6 font-semibold">Subject</th>
                            <th class="p-6 font-semibold">Date Taken</th>
                            <th class="p-6 font-semibold text-center">Score</th>
                            <th class="p-6 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50 transition group">
                                <td class="p-6 font-bold text-gray-800 group-hover:text-brand-blue transition">
                                    <?php echo $row['title']; ?>
                                </td>
                                <td class="p-6">
                                    <span class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded text-xs font-bold uppercase">
                                        <?php echo $row['subject'] ?: 'General'; ?>
                                    </span>
                                </td>
                                <td class="p-6 text-sm text-gray-500">
                                    <?php echo date('M d, Y h:i A', strtotime($row['completed_at'])); ?>
                                </td>
                                <td class="p-6 text-center">
                                    <div
                                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold 
                                        <?php echo $row['score'] >= 70 ? 'bg-green-100 text-green-700' : ($row['score'] >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                                        <?php echo $row['score']; ?>%
                                    </div>
                                </td>
                                <td class="p-6 text-right">
                                    <a href="result.php?id=<?php echo $row['id']; ?>"
                                        class="inline-flex items-center gap-2 text-brand-blue hover:underline font-bold text-sm">
                                        Review <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-history text-4xl mb-4 opacity-30"></i>
                    <p class="text-lg">No history found.</p>
                    <p class="text-sm">You haven't completed any exams yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>