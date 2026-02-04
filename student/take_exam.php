<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
include_once '../models/Exam.php';
include_once '../models/Question.php';
include_once '../models/Result.php';

$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : die('Error: Missing Exam ID');

// Load Exam Details
$examQuery = "SELECT * FROM exams WHERE id = :id";
$stmt = $database->prepare($examQuery);
$stmt->bindParam(':id', $exam_id);
$stmt->execute();
$examData = $stmt->fetch(PDO::FETCH_ASSOC);

// Load Questions
$questionModel = new Question($database);
$questions = $questionModel->read_by_exam($exam_id);
$all_questions = $questions->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resultModel = new Result($database);

    // Start Exam Record
    $student_exam_id = $resultModel->startExam($_SESSION['user_id'], $exam_id);

    $score = 0;
    $total_questions = count($all_questions);

    foreach ($all_questions as $q) {
        $qid = $q['id'];
        $selected = isset($_POST['q_' . $qid]) ? $_POST['q_' . $qid] : '';
        $correct = ($selected === $q['correct_option']) ? 1 : 0;

        if ($correct)
            $score++;

        $resultModel->saveAnswer($student_exam_id, $qid, $selected, $correct);
    }

    // Calculate Percentage
    $final_score = ($total_questions > 0) ? round(($score / $total_questions) * 100) : 0;
    $resultModel->submitExam($student_exam_id, $final_score);

    header("Location: dashboard.php?exam_submitted=true");
    exit;
}
?>
<?php include_once '../includes/header.php'; ?>

<div class="bg-brand-pale min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-xl overflow-hidden">
        <div class="bg-brand-dark text-white p-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">
                    <?php echo $examData['title']; ?>
                </h1>
                <p class="text-brand-light text-sm">Duration:
                    <?php echo $examData['duration_minutes']; ?> mins
                </p>
            </div>
            <div id="timer" class="text-xl font-mono font-bold bg-white text-brand-dark px-4 py-2 rounded">
                00:00
            </div>
        </div>

        <form action="take_exam.php?exam_id=<?php echo $exam_id; ?>" method="POST" id="examForm" class="p-8">
            <?php foreach ($all_questions as $index => $q): ?>
                <div class="mb-8 border-b pb-6 last:border-0">
                    <p class="text-lg font-semibold text-gray-800 mb-4">
                        <span class="text-brand-blue mr-2">Q
                            <?php echo $index + 1; ?>.
                        </span>
                        <?php echo $q['question_text']; ?>
                    </p>

                    <div class="space-y-2 ml-6">
                        <label class="flex items-center space-x-3 cursor-pointer p-2 rounded hover:bg-gray-50">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="A"
                                class="form-radio text-brand-blue h-5 w-5">
                            <span class="text-gray-700">A)
                                <?php echo $q['option_a']; ?>
                            </span>
                        </label>
                        <label class="flex items-center space-x-3 cursor-pointer p-2 rounded hover:bg-gray-50">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="B"
                                class="form-radio text-brand-blue h-5 w-5">
                            <span class="text-gray-700">B)
                                <?php echo $q['option_b']; ?>
                            </span>
                        </label>
                        <label class="flex items-center space-x-3 cursor-pointer p-2 rounded hover:bg-gray-50">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="C"
                                class="form-radio text-brand-blue h-5 w-5">
                            <span class="text-gray-700">C)
                                <?php echo $q['option_c']; ?>
                            </span>
                        </label>
                        <label class="flex items-center space-x-3 cursor-pointer p-2 rounded hover:bg-gray-50">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="D"
                                class="form-radio text-brand-blue h-5 w-5">
                            <span class="text-gray-700">D)
                                <?php echo $q['option_d']; ?>
                            </span>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="flex justify-end mt-6">
                <button type="submit"
                    class="bg-green-600 text-white text-lg px-8 py-3 rounded-lg hover:bg-green-700 transition shadow-lg">
                    Submit Exam
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Simple Timer Logic
    let minutes = <?php echo $examData['duration_minutes']; ?>;
    let seconds = 0;
    const timerDisplay = document.getElementById('timer');

    const interval = setInterval(() => {
        if (seconds === 0) {
            if (minutes === 0) {
                clearInterval(interval);
                alert("Time is up! Submitting exam...");
                document.getElementById('examForm').submit();
                return;
            }
            minutes--;
            seconds = 59;
        } else {
            seconds--;
        }

        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        if (minutes < 5) {
            timerDisplay.classList.add('text-red-600');
        }
    }, 1000);
</script>

</body> <!-- override footer for isolated exam view -->

</html>