<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
include_once '../models/Question.php';

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : die('ERROR: Missing Exam ID.');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $question = new Question($database);

    $question->exam_id = $_POST['exam_id'];
    $question->question_text = $_POST['question_text'];
    $question->option_a = $_POST['option_a'];
    $question->option_b = $_POST['option_b'];
    $question->option_c = $_POST['option_c'];
    $question->option_d = $_POST['option_d'];
    $question->correct_option = $_POST['correct_option'];

    if ($question->create()) {
        $message = "Question added successfully!";
    } else {
        $error = "Unable to add question.";
    }
    // Re-assign exam_id for the form
    $exam_id = $_POST['exam_id'];
}
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 p-8 bg-brand-pale">
        <h2 class="text-3xl font-bold text-brand-dark mb-6">Add Questions</h2>

        <div class="flex gap-6">
            <!-- Form Section -->
            <div class="w-full md:w-2/3">
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <?php if (isset($message)): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="add_question.php?exam_id=<?php echo $exam_id; ?>" method="POST">
                        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Question Text</label>
                            <textarea name="question_text" required
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Option A</label>
                                <input type="text" name="option_a" required
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Option B</label>
                                <input type="text" name="option_b" required
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Option C</label>
                                <input type="text" name="option_c" required
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Option D</label>
                                <input type="text" name="option_d" required
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Correct Option</label>
                            <select name="correct_option"
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                                <option value="A">Option A</option>
                                <option value="B">Option B</option>
                                <option value="C">Option C</option>
                                <option value="D">Option D</option>
                            </select>
                        </div>

                        <div class="flex justify-between">
                            <a href="dashboard.php"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">Done / Back</a>
                            <button type="submit"
                                class="bg-brand-blue text-white px-4 py-2 rounded-lg hover:bg-blue-600">Add
                                Question</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Questions Preview (Optional Enhancement) -->
            <div class="w-full md:w-1/3">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-bold text-gray-700 mb-4">Exam Info</h3>
                    <p class="mb-4">Adding questions to Exam ID: <?php echo $exam_id; ?></p>
                    <!-- Would list existing questions here in a full app -->
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>