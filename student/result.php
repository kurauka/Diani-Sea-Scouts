<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';

$student_exam_id = isset($_GET['id']) ? $_GET['id'] : die('Error: Missing Result ID');
$student_id = $_SESSION['user_id'];

$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Verify ownership
$checkQuery = "SELECT se.*, e.title, e.description, e.subject, e.duration_minutes 
               FROM student_exams se 
               JOIN exams e ON se.exam_id = e.id 
               WHERE se.id = :id AND se.student_id = :student_id";
$stmt = $database->prepare($checkQuery);
$stmt->bindParam(':id', $student_exam_id);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    die("Access Denied or Exam Not Found.");
}

$examData = $stmt->fetch(PDO::FETCH_ASSOC);

// Get Answers
$ansQuery = "SELECT a.*, q.question_text, q.correct_option as correct_val, q.option_a, q.option_b, q.option_c, q.option_d 
             FROM answers a 
             JOIN questions q ON a.question_id = q.id 
             WHERE a.student_exam_id = :id";
$stmt2 = $database->prepare($ansQuery);
$stmt2->bindParam(':id', $student_exam_id);
$stmt2->execute();
$answers = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="dashboard.php"
                    class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-500 hover:text-brand-blue transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo $examData['title']; ?></h1>
                    <div class="flex items-center gap-3 text-sm text-gray-500">
                        <span
                            class="bg-blue-100 text-brand-blue px-2 py-0.5 rounded text-xs font-bold uppercase"><?php echo $examData['subject'] ?: 'General'; ?></span>
                        <span><i class="far fa-calendar-alt"></i>
                            <?php echo date('M d, Y', strtotime($examData['completed_at'])); ?></span>
                    </div>
                </div>
            </div>
            <div class="bg-white px-6 py-2 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3">
                <span class="text-gray-500 font-medium">Final Score</span>
                <span
                    class="text-2xl font-bold <?php echo $examData['score'] >= 70 ? 'text-green-500' : 'text-brand-blue'; ?>"><?php echo $examData['score']; ?>%</span>
            </div>
        </div>

        <div class="max-w-4xl mx-auto space-y-6 pb-20">
            <!-- Summary Info -->
            <div
                class="bg-gradient-to-r from-brand-dark to-brand-blue rounded-2xl p-8 text-white relative overflow-hidden shadow-lg">
                <div class="relative z-10 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Performance Review</h2>
                        <p class="text-blue-100">Review your answers below to identify areas for improvement.</p>
                    </div>
                    <?php if ($examData['score'] == 100): ?>
                        <div class="text-yellow-300 text-5xl"><i class="fas fa-medal"></i></div>
                    <?php endif; ?>
                </div>
                <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-white opacity-10 rounded-full blur-3xl"></div>
            </div>

            <!-- Questions Review -->
            <?php foreach ($answers as $index => $ans): ?>
                <?php
                $is_correct = $ans['is_correct'];
                $user_choice = $ans['selected_option'];
                $correct_ans = $ans['correct_val'];

                $border_class = $is_correct ? 'border-green-200 bg-green-50/30' : 'border-red-200 bg-red-50/30';
                $icon = $is_correct ? 'fa-check text-green-500' : 'fa-times text-red-500';
                ?>
                <div class="bg-white rounded-2xl p-6 shadow-sm border <?php echo $border_class; ?>">
                    <!-- Question Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex gap-4">
                            <span
                                class="flex-shrink-0 w-8 h-8 rounded-full <?php echo $is_correct ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?> flex items-center justify-center font-bold text-sm">
                                <?php echo $index + 1; ?>
                            </span>
                            <h3 class="text-lg font-bold text-gray-800 pt-1"><?php echo $ans['question_text']; ?></h3>
                        </div>
                        <i class="fas <?php echo $icon; ?> text-2xl"></i>
                    </div>

                    <!-- Options Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 ml-12">
                        <?php
                        $options = ['A', 'B', 'C', 'D'];
                        foreach ($options as $opt):
                            $opt_text = $ans['option_' . strtolower($opt)];
                            // Determine styling for this option
                            $opt_class = "border-gray-200 text-gray-600";
                            $icon_status = "";

                            // Logic for highlighting
                            if ($opt === $correct_ans) {
                                $opt_class = "border-green-500 bg-green-50 text-green-800 font-bold ring-1 ring-green-500";
                                $icon_status = '<i class="fas fa-check text-green-600 ml-auto"></i>';
                            } elseif ($opt === $user_choice && !$is_correct) {
                                $opt_class = "border-red-400 bg-red-50 text-red-800 font-bold ring-1 ring-red-400";
                                $icon_status = '<i class="fas fa-times text-red-500 ml-auto"></i>';
                            } elseif ($opt === $user_choice && $is_correct) {
                                // Already handled by correct_ans check above usually, but just in case
                                $opt_class = "border-green-500 bg-green-50 text-green-800 font-bold";
                            }
                            ?>
                            <div class="p-3 rounded-xl border <?php echo $opt_class; ?> flex items-center transition-all">
                                <span class="w-6 font-bold opacity-50"><?php echo $opt; ?>.</span>
                                <span><?php echo $opt_text; ?></span>
                                <?php echo $icon_status; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="flex justify-center pt-8">
                <a href="analytics.php" class="text-brand-blue font-bold hover:underline flex items-center gap-2">
                    View Comprehensive Analytics <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>