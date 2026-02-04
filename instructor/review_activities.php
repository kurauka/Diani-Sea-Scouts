<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'instructor' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
require_once '../models/ActivityReport.php';

$activityReport = new ActivityReport($database);
$pendingActivities = $activityReport->readPending()->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="mb-10 animate-fade-in-down">
            <span class="text-xs font-bold text-orange-500 uppercase tracking-widest mb-2 block">Instructor
                Portal</span>
            <h1
                class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-700">
                Activity Approvals
            </h1>
            <p class="text-gray-500 mt-2 text-lg">Review and validate official outdoor scout activities.</p>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'processed'): ?>
            <div
                class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-2xl flex items-center gap-3 animate-fade-in">
                <i class="fas fa-check-circle text-xl"></i>
                <p class="font-bold text-sm">Activity report has been successfully processed.</p>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-lg">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 tracking-tight">Pending Approval Requests</h3>
                <span
                    class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-[10px] font-black uppercase"><?php echo count($pendingActivities); ?>
                    PENDING</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead
                        class="bg-gray-50 text-gray-400 text-[10px] uppercase font-black tracking-[0.2em] border-b border-gray-100">
                        <tr>
                            <th class="p-4 pl-8">Scout</th>
                            <th class="p-4">Activity Title</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Hours</th>
                            <th class="p-4">Date Logged</th>
                            <th class="p-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($pendingActivities)): ?>
                            <tr>
                                <td colspan="6" class="p-20 text-center">
                                    <div class="flex flex-col items-center gap-4 text-gray-300">
                                        <div
                                            class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center border-2 border-dashed border-gray-200">
                                            <i class="fas fa-clipboard-check text-4xl"></i>
                                        </div>
                                        <p class="font-bold text-lg">All caught up!</p>
                                        <p class="text-sm">No outdoor activities awaiting your review.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($pendingActivities as $activity): ?>
                            <tr class="hover:bg-orange-50/30 transition duration-150 group">
                                <td class="p-6 pl-8">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 bg-brand-pale rounded-full flex items-center justify-center font-bold text-brand-blue border-2 border-white shadow-sm">
                                            <?php echo strtoupper(substr($activity['student_name'], 0, 1)); ?>
                                        </div>
                                        <p class="font-black text-slate-800">
                                            <?php echo htmlspecialchars($activity['student_name']); ?></p>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <p class="font-bold text-slate-700"><?php echo htmlspecialchars($activity['title']); ?>
                                    </p>
                                </td>
                                <td class="p-6">
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-2 py-0.5 bg-slate-50 rounded border border-slate-100">
                                        <?php echo htmlspecialchars($activity['activity_type']); ?>
                                    </span>
                                </td>
                                <td class="p-6 font-bold text-brand-blue">
                                    <?php echo $activity['hours']; ?> Hrs
                                </td>
                                <td class="p-6 text-xs text-slate-400 font-medium italic">
                                    <?php echo date('M d, Y', strtotime($activity['created_at'])); ?>
                                </td>
                                <td class="p-6 text-right">
                                    <button onclick='openReviewModal(<?php echo json_encode($activity); ?>)'
                                        class="bg-slate-900 text-white text-[10px] font-black px-4 py-2.5 rounded-xl hover:bg-black transition-all shadow-md active:scale-95">
                                        REVIEW & PROCESS
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Review Modal -->
<div id="reviewModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div
        class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-xl overflow-hidden animate-zoom-in border border-white/20">
        <div class="p-10 border-b border-gray-100 flex justify-between items-start bg-gray-50/50">
            <div>
                <h3 class="text-2xl font-black text-gray-800 tracking-tight" id="modalTitle">Review Activity</h3>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Pending Scout Verification</p>
            </div>
            <button onclick="document.getElementById('reviewModal').classList.add('hidden')"
                class="w-10 h-10 bg-white shadow-sm border border-gray-200 rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-10">
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Scout
                        Name</label>
                    <p class="font-bold text-slate-800" id="modalScoutName">-</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Completion
                        Date</label>
                    <p class="font-bold text-slate-800 italic" id="modalDate">-</p>
                </div>
                <div class="col-span-2 bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Activity
                        Description</label>
                    <p class="text-sm text-slate-600 leading-relaxed font-medium" id="modalDescription">-</p>
                </div>
                <div id="evidenceContainer" class="col-span-2 hidden">
                    <label
                        class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Evidence/Photos
                        Link</label>
                    <a href="#" target="_blank" id="modalEvidence"
                        class="text-brand-blue font-bold text-xs hover:underline flex items-center gap-2">
                        <i class="fas fa-external-link-alt"></i> View Provided Evidence
                    </a>
                </div>
            </div>

            <form action="process_activity_logic.php" method="POST" class="space-y-6">
                <input type="hidden" name="activity_id" id="modalActivityId">
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-2">Instructor
                        Feedback/Notes</label>
                    <textarea name="instructor_notes" required rows="3" placeholder="Provide feedback to the scout..."
                        class="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-brand-blue/10 outline-none transition font-medium text-sm"></textarea>
                </div>

                <div class="flex gap-4">
                    <button type="submit" name="action" value="Reject"
                        class="flex-1 bg-white border-2 border-red-500 text-red-600 font-black py-4 rounded-2xl hover:bg-red-50 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-times-circle"></i> REJECT
                    </button>
                    <button type="submit" name="action" value="Approve"
                        class="flex-[2] bg-brand-blue text-white font-black py-4 rounded-2xl shadow-xl hover:shadow-blue-200 transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i> APPROVE RECORD
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openReviewModal(activity) {
        document.getElementById('modalActivityId').value = activity.id;
        document.getElementById('modalTitle').textContent = activity.title;
        document.getElementById('modalScoutName').textContent = activity.student_name;
        document.getElementById('modalDate').textContent = new Date(activity.activity_date).toLocaleDateString();
        document.getElementById('modalDescription').textContent = activity.description;

        const evidence = document.getElementById('evidenceContainer');
        if (activity.evidence_link) {
            evidence.classList.remove('hidden');
            document.getElementById('modalEvidence').href = activity.evidence_link;
        } else {
            evidence.classList.add('hidden');
        }

        document.getElementById('reviewModal').classList.remove('hidden');
    }
</script>

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