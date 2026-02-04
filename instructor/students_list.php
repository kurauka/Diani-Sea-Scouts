<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Fetch All Students with Exam Counts
$query = "SELECT u.*, COUNT(se.id) as exams_taken 
          FROM users u 
          LEFT JOIN student_exams se ON u.id = se.student_id 
          WHERE u.role = 'student' 
          GROUP BY u.id 
          ORDER BY u.name ASC";
$stmt = $database->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts
$total_students = count($students);
// $new_this_month = ... (Optional: if logic requires created_at timestamp check)
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <!-- Hero / Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Students</h1>
                <p class="text-gray-500 mt-1">Manage and track registered students.</p>
            </div>

            <div class="flex items-center gap-4">
                <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3">
                    <div class="bg-blue-50 w-10 h-10 rounded-lg flex items-center justify-center text-brand-blue">
                        <i class="fas fa-users text-lg"></i>
                    </div>
                    <div>
                        <span
                            class="block text-xl font-bold text-gray-800 leading-none"><?php echo $total_students; ?></span>
                        <span class="text-xs text-gray-500 font-medium uppercase">Total</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-8 flex items-center gap-4">
            <i class="fas fa-search text-gray-400 text-lg ml-2"></i>
            <input type="text" id="studentSearch" placeholder="Search by name, email, school, or troop ID..."
                class="w-full text-gray-700 focus:outline-none text-lg placeholder-gray-300">
        </div>

        <!-- Student Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="studentsGrid">
            <?php foreach ($students as $student): ?>
                <div
                    class="student-card bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:translate-y-[-5px] transition-all duration-300 overflow-hidden group">
                    <div class="h-24 bg-gradient-to-r from-blue-500 to-cyan-400 relative">
                        <div class="absolute -bottom-10 left-1/2 transform -translate-x-1/2">
                            <img src="../<?php echo !empty($student['profile_image']) ? htmlspecialchars($student['profile_image']) : 'assets/images/default_avatar.png'; ?>"
                                class="w-20 h-20 rounded-full border-4 border-white shadow-md object-cover bg-white"
                                alt="Avatar">
                        </div>
                    </div>

                    <div class="pt-12 pb-6 px-6 text-center">
                        <h3 class="font-bold text-gray-800 text-lg mb-1 student-name">
                            <?php echo htmlspecialchars($student['name']); ?></h3>
                        <p class="text-xs text-gray-500 mb-4 student-email">
                            <?php echo htmlspecialchars($student['email']); ?></p>

                        <div class="flex flex-wrap justify-center gap-2 mb-4">
                            <?php if (!empty($student['school'])): ?>
                                <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold student-school">
                                    <i class="fas fa-school mr-1 opacity-50"></i>
                                    <?php echo htmlspecialchars($student['school']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($student['troop_id'])): ?>
                                <span
                                    class="bg-purple-50 text-purple-600 px-3 py-1 rounded-full text-xs font-bold student-troop">
                                    <i class="fas fa-id-card mr-1 opacity-50"></i>
                                    <?php echo htmlspecialchars($student['troop_id']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4">
                            <div class="text-center">
                                <span
                                    class="block font-bold text-gray-800 text-lg"><?php echo $student['exams_taken']; ?></span>
                                <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Exams</span>
                            </div>
                            <div class="text-center border-l border-gray-100">
                                <a href="view_results.php?student_id=<?php echo $student['id']; ?>"
                                    class="text-brand-blue hover:text-blue-700 text-sm font-bold flex items-center justify-center h-full gap-1">
                                    Results <i class="fas fa-chevron-right text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty State (Hidden by default) -->
        <div id="noResults" class="hidden text-center py-20 text-gray-400">
            <i class="fas fa-search text-4xl mb-4 opacity-20"></i>
            <p class="text-lg">No students found matching your search.</p>
        </div>

    </main>
</div>

<script>
    // Real-time Search Logic
    const searchInput = document.getElementById('studentSearch');
    const cards = document.querySelectorAll('.student-card');
    const noResults = document.getElementById('noResults');

    searchInput.addEventListener('keyup', function (e) {
        const term = e.target.value.toLowerCase();
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.querySelector('.student-name').innerText.toLowerCase();
            const email = card.querySelector('.student-email').innerText.toLowerCase();
            const school = card.querySelector('.student-school')?.innerText.toLowerCase() || '';
            const troop = card.querySelector('.student-troop')?.innerText.toLowerCase() || '';

            if (name.includes(term) || email.includes(term) || school.includes(term) || troop.includes(term)) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleCount === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    });
</script>

<?php include_once '../includes/footer.php'; ?>