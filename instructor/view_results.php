<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;

$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Helper to get exam title if ID exists
$exam_title = "";
if ($exam_id) {
    $stmt = $database->prepare("SELECT title FROM exams WHERE id = :id");
    $stmt->bindParam(':id', $exam_id);
    $stmt->execute();
    $ex = $stmt->fetch(PDO::FETCH_ASSOC);
    $exam_title = $ex ? $ex['title'] : "";
}

// Get Results
$query = "SELECT se.*, u.name as student_name, e.title 
          FROM student_exams se 
          JOIN users u ON se.student_id = u.id 
          JOIN exams e ON se.exam_id = e.id 
          WHERE 1=1";

if ($exam_id) {
    $query .= " AND se.exam_id = :exam_id";
} else {
    // Show results for exams created by this instructor
    $query .= " AND e.created_by = :instructor_id";
}
$query .= " ORDER BY se.completed_at DESC";

$stmt = $database->prepare($query);
if ($exam_id) {
    $stmt->bindParam(':exam_id', $exam_id);
} else {
    $stmt->bindParam(':instructor_id', $_SESSION['user_id']);
}
$stmt->execute();

?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 p-8 bg-brand-pale">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-3xl font-bold text-brand-dark">Student Results
                <?php echo $exam_title ? "- " . $exam_title : ""; ?>
            </h2>
            <?php if ($exam_id): ?>
                <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">Back to
                    Dashboard</a>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">Student Name</th>
                            <th class="py-3 px-6 text-left">Exam Title</th>
                            <th class="py-3 px-6 text-left">Date Taken</th>
                            <th class="py-3 px-6 text-center">Score</th>
                            <th class="py-3 px-6 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6 text-left font-medium">
                                    <?php echo $row['student_name']; ?>
                                </td>
                                <td class="py-3 px-6 text-left">
                                    <?php echo $row['title']; ?>
                                </td>
                                <td class="py-3 px-6 text-left">
                                    <?php echo date('M d, Y H:i', strtotime($row['completed_at'])); ?>
                                </td>
                                <td class="py-3 px-6 text-center font-bold text-brand-blue">
                                    <?php echo $row['score']; ?>%
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <span class="bg-green-200 text-green-600 py-1 px-3 rounded-full text-xs">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>