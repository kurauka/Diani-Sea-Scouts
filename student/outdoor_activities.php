<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
require_once '../models/ActivityReport.php';

$activityReport = new ActivityReport($database);
$activities = $activityReport->readByStudent($_SESSION['user_id'])->fetchAll(PDO::FETCH_ASSOC);

// Calculate some stats
$approved = 0;
$totalHours = 0;
foreach ($activities as $a) {
    if ($a['status'] == 'Approved') {
        $approved++;
        $totalHours += $a['hours'];
    }
}
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="mb-10 animate-fade-in-down">
            <span class="text-xs font-bold text-brand-blue uppercase tracking-widest mb-2 block">Student Portal</span>
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1
                        class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-brand-blue">
                        Outdoor Activities
                    </h1>
                    <p class="text-gray-500 mt-2 text-lg">Report your hikes, camps, and community service.</p>
                </div>
                <button onclick="document.getElementById('logActivityModal').classList.remove('hidden')"
                    class="bg-brand-blue text-white px-8 py-4 rounded-2xl font-black shadow-lg hover:shadow-blue-200 transition-all flex items-center gap-3">
                    <i class="fas fa-plus-circle text-xl"></i> LOG NEW ACTIVITY
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Logged Total</p>
                <h3 class="text-3xl font-black text-slate-800">
                    <?php echo count($activities); ?>
                </h3>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Approved</p>
                <h3 class="text-3xl font-black text-green-600">
                    <?php echo $approved; ?>
                </h3>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Official Hours</p>
                <h3 class="text-3xl font-black text-blue-600">
                    <?php echo $totalHours; ?>
                </h3>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pending</p>
                <h3 class="text-3xl font-black text-orange-400">
                    <?php echo count($activities) - $approved; ?>
                </h3>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'submitted'): ?>
            <div
                class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-2xl flex items-center gap-3 animate-fade-in">
                <i class="fas fa-check-circle text-xl"></i>
                <p class="font-bold text-sm">Activity report submitted successfully! Awaiting instructor review.</p>
            </div>
        <?php endif; ?>

        <!-- Activity History -->
        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-lg">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="font-bold text-gray-800 tracking-tight">Activity Log History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead
                        class="bg-gray-50 text-gray-400 text-[10px] uppercase font-black tracking-[0.2em] border-b border-gray-100">
                        <tr>
                            <th class="p-4 pl-8">Activity & Type</th>
                            <th class="p-4">Date</th>
                            <th class="p-4">Duration</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Instructor Feedback</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($activities)): ?>
                            <tr>
                                <td colspan="5" class="p-16 text-center">
                                    <div class="flex flex-col items-center gap-4 text-gray-300">
                                        <i class="fas fa-hiking text-5xl"></i>
                                        <p class="font-bold text-lg">No activities logged yet.</p>
                                        <p class="text-sm">Log your first outdoor activity to start building your record!
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($activities as $row): ?>
                            <tr class="hover:bg-blue-50/30 transition duration-150 group">
                                <td class="p-6 pl-8">
                                    <p class="font-black text-slate-800 group-hover:text-brand-blue transition-colors">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </p>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-2 py-0.5 bg-slate-50 rounded border border-slate-100">
                                        <?php echo htmlspecialchars($row['activity_type']); ?>
                                    </span>
                                </td>
                                <td class="p-6 font-medium text-slate-500 italic text-sm">
                                    <?php echo date('M d, Y', strtotime($row['activity_date'])); ?>
                                </td>
                                <td class="p-6 font-bold text-slate-700">
                                    <?php echo $row['hours']; ?> Hours
                                </td>
                                <td class="p-6">
                                    <?php
                                    $statusClass = 'bg-gray-100 text-gray-600';
                                    if ($row['status'] == 'Approved')
                                        $statusClass = 'bg-green-100 text-green-700';
                                    if ($row['status'] == 'Pending')
                                        $statusClass = 'bg-orange-100 text-orange-700';
                                    if ($row['status'] == 'Rejected')
                                        $statusClass = 'bg-red-100 text-red-700';
                                    ?>
                                    <span
                                        class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider <?php echo $statusClass; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="p-6">
                                    <?php if ($row['instructor_notes']): ?>
                                        <div class="text-xs text-slate-500 max-w-xs italic line-clamp-2"
                                            title="<?php echo htmlspecialchars($row['instructor_notes']); ?>">
                                            "
                                            <?php echo htmlspecialchars($row['instructor_notes']); ?>"
                                        </div>
                                        <p class="text-[9px] font-bold text-slate-300 mt-1 uppercase">Reviewed by:
                                            <?php echo htmlspecialchars($row['instructor_name']); ?>
                                        </p>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-300 italic">No feedback yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Log Activity Modal -->
<div id="logActivityModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div
        class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden animate-zoom-in border border-white/20">
        <div class="p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="text-xl font-black text-gray-800 tracking-tight">Log Outdoor Activity</h3>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Official Scout Record
                    Submission</p>
            </div>
            <button onclick="document.getElementById('logActivityModal').classList.add('hidden')"
                class="w-10 h-10 bg-white shadow-sm border border-gray-200 rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="submit_activity_logic.php" method="POST" class="p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Activity
                        Title</label>
                    <input type="text" name="title" required placeholder="e.g., Mount Kenya Expedition"
                        class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-brand-blue/10 outline-none transition font-medium">
                </div>
                <div>
                    <label
                        class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Type</label>
                    <select name="activity_type" required
                        class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-brand-blue/10 outline-none transition font-medium">
                        <option value="Hiking">Hiking</option>
                        <option value="Camping">Camping</option>
                        <option value="Scout Craft">Scout Craft</option>
                        <option value="Community Service">Community Service</option>
                        <option value="Water Activity">Water Activity</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Hours
                        Spent</label>
                    <input type="number" name="hours" required min="1" value="2"
                        class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-brand-blue/10 outline-none transition font-medium">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Completion
                        Date</label>
                    <input type="date" name="activity_date" required value="<?php echo date('Y-m-d'); ?>"
                        class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-brand-blue/10 outline-none transition font-medium">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Evidence
                        Link (Optional)</label>
                    <input type="url" name="evidence_link" placeholder="Drive link or photo URL"
                        class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-brand-blue/10 outline-none transition font-medium">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Detailed
                        Description</label>
                    <textarea name="description" rows="4" required placeholder="Describe what you did and learned..."
                        class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-brand-blue/10 outline-none transition font-medium"></textarea>
                </div>
            </div>

            <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                <p class="text-[10px] text-blue-700 font-bold leading-relaxed">
                    <i class="fas fa-info-circle mr-1"></i> Submission will be reviewed by an instructor. Ensure your
                    description is clear and accurate.
                </p>
            </div>

            <button type="submit"
                class="w-full bg-slate-900 text-white font-black py-5 rounded-2xl shadow-2xl hover:bg-black transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-3">
                <i class="fas fa-paper-plane"></i> SUBMIT ACTIVITY LOG
            </button>
        </form>
    </div>
</div>

<style>
    @keyframes zoom-in {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .animate-zoom-in {
        animation: zoom-in 0.2s ease-out;
    }

    .animate-fade-in-down {
        animation: fadeInDown 0.6s ease-out;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<?php include_once '../includes/footer.php'; ?>