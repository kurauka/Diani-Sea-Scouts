<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Fetch previous announcements
$stmt = $database->prepare("SELECT a.*, u.name as author FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC");
$stmt->execute();
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Communications</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Post New Announcement -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
                <h2 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-bullhorn text-brand-blue"></i> Post Announcement
                </h2>

                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                        Announcement posted successfully!
                    </div>
                <?php endif; ?>

                <form action="save_announcement_logic.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-gray-600 text-sm font-bold mb-2">Subject / Title</label>
                        <input type="text" name="title" required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue"
                            placeholder="e.g. Exam Postponed">
                    </div>

                    <div>
                        <label class="block text-gray-600 text-sm font-bold mb-2">Priority Type</label>
                        <select name="type" required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            <option value="info">General Info ℹ️</option>
                            <option value="warning">Important / Warning ⚠️</option>
                            <option value="urgent">Urgent / Critical 🚨</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-600 text-sm font-bold mb-2">Message</label>
                        <textarea name="message" rows="4" required
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue"
                            placeholder="Type your message here..."></textarea>
                    </div>

                    <button type="submit"
                        class="w-full bg-brand-blue text-white py-2.5 rounded-lg font-bold hover:bg-blue-600 transition shadow-lg flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i> Post Update
                    </button>
                </form>
            </div>

            <!-- History -->
            <div class="lg:col-span-2 space-y-6">
                <h2 class="text-lg font-bold text-gray-700">Recent Announcements</h2>

                <?php if ($stmt->rowCount() > 0): ?>
                    <div class="space-y-4">
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                            $bg_class = 'bg-white border-l-4 border-blue-500';
                            $icon_class = 'text-blue-500 fa-info-circle';
                            $badge_class = 'bg-blue-100 text-blue-600';

                            if ($row['type'] == 'warning') {
                                $bg_class = 'bg-white border-l-4 border-orange-500';
                                $icon_class = 'text-orange-500 fa-exclamation-triangle';
                                $badge_class = 'bg-orange-100 text-orange-600';
                            } elseif ($row['type'] == 'urgent') {
                                $bg_class = 'bg-white border-l-4 border-red-500';
                                $icon_class = 'text-red-500 fa-exclamation-circle';
                                $badge_class = 'bg-red-100 text-red-600';
                            }
                            ?>
                            <div class="<?php echo $bg_class; ?> rounded-xl p-6 shadow-sm flex items-start gap-4">
                                <div class="flex-shrink-0 mt-1">
                                    <i class="fas <?php echo $icon_class; ?> text-2xl"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start mb-1">
                                        <h3 class="font-bold text-gray-800 text-lg">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </h3>
                                        <span class="text-xs text-gray-400 whitespace-nowrap">
                                            <?php echo date('M d, h:i A', strtotime($row['created_at'])); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 mb-3">
                                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-1 rounded text-xs font-bold uppercase <?php echo $badge_class; ?>">
                                            <?php echo $row['type']; ?>
                                        </span>
                                        <span class="text-xs text-gray-400">Posted by
                                            <?php echo $row['author']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center p-12 bg-white rounded-xl border border-dashed border-gray-300 text-gray-400">
                        <i class="fas fa-comment-slash text-4xl mb-4 opacity-30"></i>
                        <p>No announcements yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>