<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// 1. Fetch distinct activity titles and events for selection
$activities = $database->query("SELECT DISTINCT title, 'manual' as source FROM calendar_events 
                                UNION 
                                SELECT DISTINCT event_id, 'log' as source FROM attendance WHERE event_id IS NOT NULL 
                                ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

$selectedTitle = $_GET['activity'] ?? '';

// 2. Fetch attendance grouped by date for this activity title or event ID
$logs = [];
if ($selectedTitle) {
    // We treat the selected title as either a literal title or an event ID
    $query = "SELECT a.*, s.name as student_name, s.email as student_email, e.title as event_title, i.name as instructor_name
              FROM attendance a 
              JOIN users s ON a.student_id = s.id 
              JOIN users i ON a.instructor_id = i.id
              LEFT JOIN calendar_events e ON a.event_id = e.id
              WHERE (e.title = :title OR a.event_id = :title)
              ORDER BY a.attendance_date DESC, s.name ASC";
    $stmt = $database->prepare($query);
    $stmt->execute([':title' => $selectedTitle]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Group logs by date for the report view
$groupedLogs = [];
foreach ($logs as $log) {
    $groupedLogs[$log['attendance_date']][] = $log;
}

?>
<?php include_once '../includes/header.php'; ?>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        .flex-1 {
            overflow: visible !important;
        }

        main {
            padding: 0 !important;
        }

        .shadow-xl,
        .shadow-lg {
            shadow: none !important;
            border: 1px solid #eee !important;
        }

        .bg-gray-50 {
            background: white !important;
        }

        .bg-slate-900 {
            background: #333 !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
        }
    }
</style>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <div class="no-print">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="max-w-6xl mx-auto">

            <div class="flex justify-between items-center mb-10 no-print">
                <div>
                    <h1 class="text-4xl font-black text-slate-800 tracking-tighter italic">Event Intel & Tracking</h1>
                    <p class="text-slate-500 font-medium">Consolidated attendance history for regular sessions.</p>
                </div>
                <button onclick="window.print()"
                    class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 hover:bg-slate-800 transition">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>

            <!-- Activity Selection -->
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-lg mb-8 no-print">
                <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Select
                            Regular Activity</label>
                        <select name="activity"
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 focus:ring-2 focus:ring-brand-blue outline-none font-bold text-slate-700">
                            <option value="">-- Choose Activity --</option>
                            <?php foreach ($activities as $act): ?>
                                <option value="<?php echo htmlspecialchars($act['title']); ?>" <?php echo $selectedTitle == $act['title'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($act['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit"
                        class="bg-brand-blue text-white px-10 py-3 rounded-2xl font-bold shadow-lg shadow-blue-100 hover:bg-brand-dark transition">
                        View History
                    </button>
                </form>
            </div>

            <?php if ($selectedTitle): ?>
                <div class="bg-white rounded-[2.5rem] p-10 shadow-xl border border-gray-100 relative overflow-hidden">
                    <!-- Print Header -->
                    <div class="hidden print:flex justify-between items-center mb-10 border-b-2 border-slate-900 pb-6">
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 uppercase tracking-tighter">Attendance Report</h2>
                            <p class="text-slate-500 font-bold uppercase tracking-widest text-xs mt-1">Activity:
                                <?php echo htmlspecialchars($selectedTitle); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Diani Sea Scouts</p>
                            <p class="text-[10px] text-slate-300">
                                <?php echo date('F d, Y'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="space-y-12">
                        <?php foreach ($groupedLogs as $date => $dayLogs): ?>
                            <div class="break-inside-avoid">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="h-px flex-1 bg-slate-100"></div>
                                    <h3 class="font-black text-slate-900 text-sm uppercase tracking-[0.2em] whitespace-nowrap">
                                        <?php echo date('l, F d, Y', strtotime($date)); ?>
                                    </h3>
                                    <div class="h-px flex-1 bg-slate-100"></div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <?php foreach ($dayLogs as $row):
                                        $isAbsent = $row['status'] === 'absent';
                                        $isLate = $row['status'] === 'late';
                                        $statusClass = $isAbsent ? 'bg-red-50 text-red-500 border-red-100' : ($isLate ? 'bg-orange-50 text-orange-500 border-orange-100' : 'bg-green-50 text-green-500 border-green-100');
                                        ?>
                                        <div
                                            class="p-4 rounded-2xl border border-slate-50 bg-slate-50/30 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-[10px]">
                                                    <?php echo strtoupper(substr($row['student_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-800 text-xs truncate w-32">
                                                        <?php echo htmlspecialchars($row['student_name']); ?>
                                                    </p>
                                                    <p class="text-[9px] text-slate-400">
                                                        <?php echo $isAbsent ? 'No scan' : date('H:i A', strtotime($row['created_at'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <span
                                                class="px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest border <?php echo $statusClass; ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-20 text-center bg-white rounded-3xl border border-dashed border-slate-200">
                    <div
                        class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl text-slate-300">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-xl">Event Selection Required</h3>
                    <p class="text-slate-400 mt-2 max-w-xs mx-auto">Please select a recurring activity from the menu above
                        to visualize consolidated attendance history.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>