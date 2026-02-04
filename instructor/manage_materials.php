<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Get file path to delete file
    $stmt = $database->prepare("SELECT file_path FROM materials WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file && file_exists("../" . $file['file_path'])) {
        unlink("../" . $file['file_path']);
    }

    $del = $database->prepare("DELETE FROM materials WHERE id = :id");
    $del->execute([':id' => $id]);
    header("Location: manage_materials.php?msg=deleted");
    exit;
}

// Fetch Materials
$stmt = $database->prepare("SELECT * FROM materials WHERE uploaded_by = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $_SESSION['user_id']]);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Learning Materials</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Upload Form -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
                <h2 class="text-lg font-bold text-gray-700 mb-4">Upload New Material</h2>
                <form action="upload_material_logic.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-gray-600 text-sm font-bold mb-2">Title</label>
                        <input type="text" name="title" required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue"
                            placeholder="e.g. Navigation Guide">
                    </div>

                    <div>
                        <label class="block text-gray-600 text-sm font-bold mb-2">Type</label>
                        <select name="type"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            <option value="pdf">PDF Document</option>
                            <option value="video">Video</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-600 text-sm font-bold mb-2">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue"
                            placeholder="Short description..."></textarea>
                    </div>

                    <div
                        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition cursor-pointer relative">
                        <input type="file" name="file" required
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-500">Click to upload file</p>
                    </div>

                    <button type="submit"
                        class="w-full bg-brand-blue text-white py-2 rounded-lg font-bold hover:bg-blue-600 transition shadow-lg">Upload
                        Material</button>
                </form>
            </div>

            <!-- List -->
            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-lg font-bold text-gray-700">Uploaded Resources</h2>

                <?php if ($stmt->rowCount() > 0): ?>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl
                                <?php echo $row['type'] == 'pdf' ? 'bg-red-50 text-red-500' : ($row['type'] == 'video' ? 'bg-blue-50 text-blue-500' : 'bg-gray-50 text-gray-500'); ?>">
                                <i
                                    class="fas fa-<?php echo $row['type'] == 'pdf' ? 'file-pdf' : ($row['type'] == 'video' ? 'video' : 'file'); ?>"></i>
                            </div>

                            <div class="flex-1">
                                <h3 class="font-bold text-gray-800">
                                    <?php echo $row['title']; ?>
                                </h3>
                                <p class="text-xs text-gray-400 mb-2">
                                    <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                </p>
                                <p class="text-sm text-gray-600 line-clamp-2">
                                    <?php echo $row['description']; ?>
                                </p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <a href="../<?php echo $row['file_path']; ?>" target="_blank"
                                    class="text-brand-blue hover:underline text-sm"><i class="fas fa-external-link-alt"></i>
                                    View</a>
                                <a href="manage_materials.php?delete=<?php echo $row['id']; ?>"
                                    class="text-red-400 hover:text-red-600 text-sm"
                                    onclick="return confirm('Delete this file?')"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center p-8 bg-white rounded-xl border border-dashed border-gray-300 text-gray-400">
                        <i class="fas fa-folder-open text-4xl mb-2"></i>
                        <p>No materials uploaded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>