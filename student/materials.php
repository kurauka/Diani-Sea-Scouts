<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Fetch Materials
$query = "SELECT m.*, u.name as instructor_name FROM materials m JOIN users u ON m.uploaded_by = u.id ORDER BY created_at DESC";
$stmt = $database->query($query);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Learning Materials</h1>
        <p class="text-gray-500 mb-8">Access study guides, videos, and handouts provided by your instructors.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <?php
                $icon = 'file';
                $color = 'gray';
                if ($row['type'] == 'pdf') {
                    $icon = 'file-pdf';
                    $color = 'red';
                } elseif ($row['type'] == 'video') {
                    $icon = 'play-circle';
                    $color = 'blue';
                } elseif ($row['type'] == 'link') {
                    $icon = 'link';
                    $color = 'green';
                }
                ?>
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-lg transition group flex flex-col h-full">
                    <div class="flex items-start justify-between mb-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-500 flex items-center justify-center text-2xl">
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">
                            <?php echo $row['type']; ?>
                        </span>
                    </div>

                    <h3 class="font-bold text-gray-800 mb-2 line-clamp-2" title="<?php echo $row['title']; ?>">
                        <?php echo $row['title']; ?>
                    </h3>
                    <p class="text-xs text-gray-500 mb-4 flex-1">
                        <?php echo $row['description']; ?>
                    </p>

                    <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                        <span class="text-xs text-gray-400">By
                            <?php echo explode(' ', $row['instructor_name'])[0]; ?>
                        </span>
                        <a href="../<?php echo $row['file_path']; ?>" target="_blank"
                            class="bg-gray-100 text-gray-700 px-3 py-1 rounded-lg text-xs font-bold hover:bg-brand-blue hover:text-white transition">
                            <i class="fas fa-download mr-1"></i> Access
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if ($stmt->rowCount() == 0): ?>
                <div class="col-span-full text-center py-12 text-gray-400">
                    <i class="fas fa-books text-4xl mb-4 opacity-30"></i>
                    <p>No materials available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>