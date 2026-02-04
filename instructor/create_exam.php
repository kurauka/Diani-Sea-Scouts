<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
include_once '../models/Exam.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $exam = new Exam($database);

    $exam->title = $_POST['title'];
    $exam->description = $_POST['description'];
    $exam->duration_minutes = $_POST['duration'];
    $exam->created_by = $_SESSION['user_id'];

    if ($exam->create()) {
        header("Location: dashboard.php");
    } else {
        $error = "Something went wrong. Please try again.";
    }
}
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 p-8 bg-brand-pale">
        <h2 class="text-3xl font-bold text-brand-dark mb-6">Create New Exam</h2>

        <div class="bg-white p-8 rounded-lg shadow-md max-w-2xl">
            <form action="create_exam.php" method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Exam Title</label>
                    <input type="text" name="title" required
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue"></textarea>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Duration (Minutes)</label>
                    <input type="number" name="duration" value="60" required
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                </div>
                <div class="flex justify-end">
                    <a href="dashboard.php"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg mr-2 hover:bg-gray-400">Cancel</a>
                    <button type="submit" class="bg-brand-blue text-white px-4 py-2 rounded-lg hover:bg-blue-600">Create
                        Exam</button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>