<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Handle Delete
if (isset($_GET['delete'])) {
    $del = $database->prepare("DELETE FROM announcements WHERE id = ?");
    $del->execute([$_GET['delete']]);
    header("Location: communications.php?msg=deleted");
    exit;
}

// Fetch Announcements
$stmt = $database->prepare("SELECT a.*, u.name as author FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC");
$stmt->execute();
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Communications Hub</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Post New Announcement -->
            <div class="bg-white rounded-3xl shadow-lg border border-gray-100 p-6 h-fit relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-brand-dark to-brand-blue"></div>

                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <span class="bg-blue-100 text-brand-blue p-2 rounded-lg"><i class="fas fa-bullhorn"></i></span> Post
                    New Alert
                </h2>

                <?php if (isset($_GET['msg'])): ?>
                    <div
                        class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 text-sm flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> Action successful.
                    </div>
                <?php endif; ?>

                <form action="save_announcement_logic.php" method="POST" class="space-y-5">
                    <div>
                        <label class="block text-gray-500 text-xs font-bold uppercase tracking-wide mb-2">Subject /
                            Title</label>
                        <input type="text" name="title" required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition"
                            placeholder="e.g. System Maintenance">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-500 text-xs font-bold uppercase tracking-wide mb-2">Priority
                                Type</label>
                            <select name="type" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                                <option value="info">General Info ℹ️</option>
                                <option value="warning">Warning ⚠️</option>
                                <option value="urgent">Urgent 🚨</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-500 text-xs font-bold uppercase tracking-wide mb-2">Target
                                Audience</label>
                            <select name="target_role" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                                <option value="all">All Users 🌍</option>
                                <option value="student">Students Only 🎓</option>
                                <option value="instructor">Instructors Only 👨‍🏫</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-500 text-xs font-bold uppercase tracking-wide mb-2">Message
                            Content</label>
                        <textarea name="message" rows="4" required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition"
                            placeholder="Type your official announcement here..."></textarea>
                    </div>

                    <div class="flex items-center gap-3 bg-blue-50 p-3 rounded-xl border border-blue-100">
                        <input type="checkbox" name="add_to_calendar" id="calendarCheck"
                            class="w-5 h-5 text-brand-blue rounded border-gray-300 focus:ring-brand-blue">
                        <label for="calendarCheck" class="text-sm text-gray-600 font-medium">Add as Event to System
                            Calendar</label>
                    </div>
                    <div id="dateField" class="hidden">
                        <label class="block text-gray-500 text-xs font-bold uppercase tracking-wide mb-2">Event
                            Date</label>
                        <input type="date" name="event_date"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 transition">
                    </div>

                    <button type="submit"
                        class="w-full bg-gray-800 hover:bg-gray-900 text-white py-3.5 rounded-xl font-bold transition shadow-lg flex items-center justify-center gap-2 transform hover:scale-[1.02]">
                        <i class="fas fa-paper-plane"></i> Publish Announcement
                    </button>
                </form>
            </div>

            <!-- Thread / History -->
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-800">Recent Broadcasts</h2>
                    <span class="text-sm text-gray-400">Manage all system alerts</span>
                </div>

                <?php if ($stmt->rowCount() > 0): ?>
                    <div class="space-y-4">
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                            $bg_class = 'border-l-4 border-blue-500 bg-white';
                            $badge_color = 'bg-blue-100 text-blue-600';

                            if ($row['type'] == 'warning') {
                                $bg_class = 'border-l-4 border-orange-500 bg-white';
                                $badge_color = 'bg-orange-100 text-orange-600';
                            } elseif ($row['type'] == 'urgent') {
                                $bg_class = 'border-l-4 border-red-500 bg-red-50/50';
                                $badge_color = 'bg-red-100 text-red-600';
                            }
                            ?>
                            <div
                                class="<?php echo $bg_class; ?> rounded-2xl p-6 shadow-sm hover:shadow-md transition relative group border border-gray-100">
                                <a href="communications.php?delete=<?php echo $row['id']; ?>"
                                    onclick="return confirm('Delete this announcement?');"
                                    class="absolute top-4 right-4 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition p-2">
                                    <i class="fas fa-trash-alt"></i>
                                </a>

                                <div class="flex justify-between items-start mb-2 pr-8">
                                    <div class="flex items-center gap-3">
                                        <h3 class="font-bold text-gray-800 text-lg">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </h3>
                                        <span
                                            class="px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide <?php echo $badge_color; ?>">
                                            <?php echo $row['type']; ?>
                                        </span>
                                    </div>
                                    <span class="text-xs text-gray-400 font-mono">
                                        <?php echo date('M d, H:i', strtotime($row['created_at'])); ?>
                                    </span>
                                </div>

                                <p class="text-gray-600 mb-4 bg-gray-50 p-4 rounded-xl text-sm border border-gray-100">
                                    <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                </p>

                                <div class="flex items-center gap-4 text-xs">
                                    <span class="text-gray-400 flex items-center gap-1">
                                        <i class="fas fa-bullseye"></i> Target: <strong class="text-gray-600 uppercase">
                                            <?php echo $row['target_role']; ?>
                                        </strong>
                                    </span>
                                    <span class="text-gray-400 flex items-center gap-1">
                                        <i class="fas fa-user-edit"></i> By:
                                        <?php echo htmlspecialchars($row['author']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div
                        class="flex flex-col items-center justify-center p-16 bg-white rounded-3xl border border-dashed border-gray-200 text-gray-400">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-inbox text-2xl opacity-50"></i>
                        </div>
                        <p>No active announcements found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    const calendarCheck = document.getElementById('calendarCheck');
    const dateField = document.getElementById('dateField');
    calendarCheck.addEventListener('change', function () {
        if (this.checked) {
            dateField.classList.remove('hidden');
            dateField.querySelector('input').setAttribute('required', 'true');
        } else {
            dateField.classList.add('hidden');
            dateField.querySelector('input').removeAttribute('required');
        }
    });
</script>

<?php include_once '../includes/footer.php'; ?>