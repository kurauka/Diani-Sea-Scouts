<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
// $database is already provided by config/db.php

// History Filters
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$params = [':start' => $startDate, ':end' => $endDate];
$whereClauses = ["a.attendance_date BETWEEN :start AND :end"];

if ($statusFilter) {
    $whereClauses[] = "a.status = :status";
    $params[':status'] = $statusFilter;
}

if ($searchQuery) {
    $whereClauses[] = "(s.name LIKE :search OR s.email LIKE :search)";
    $params[':search'] = "%$searchQuery%";
}

$whereString = implode(" AND ", $whereClauses);

$query = "SELECT a.*, s.name as student_name, s.email as student_email, i.name as instructor_name, e.title as event_title
          FROM attendance a 
          JOIN users s ON a.student_id = s.id 
          JOIN users i ON a.instructor_id = i.id 
          LEFT JOIN calendar_events e ON a.event_id = e.id
          WHERE $whereString
          ORDER BY a.created_at DESC";
$stmt = $database->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats for the filtered period
$totalScans = count($logs);
$onTimeCount = 0;
$lateCount = 0;
foreach ($logs as $log) {
    if ($log['status'] === 'present')
        $onTimeCount++;
    if ($log['status'] === 'late')
        $lateCount++;
}

// Get student coverage (unique students scanned / total students)
$totalStudentsStmt = $database->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$totalStudentsCount = $totalStudentsStmt->fetchColumn();
$uniqueStudentsScanned = count(array_unique(array_column($logs, 'student_id')));
$coverage = $totalStudentsCount > 0 ? round(($uniqueStudentsScanned / $totalStudentsCount) * 100) : 0;

include_once '../includes/header.php';
?>
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
        .shadow-lg,
        .shadow-sm {
            box-shadow: none !important;
            border: 1px solid #eee !important;
        }

        .bg-gray-50 {
            background: white !important;
        }

        .h-screen {
            height: auto !important;
        }
    }
</style>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <div class="no-print">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="max-w-6xl mx-auto">

            <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-6 animate-fade-in-down">
                <div class="space-y-2">
                    <span
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-[10px] font-black text-brand-blue uppercase tracking-widest border border-blue-100 shadow-sm">
                        <i class="fas fa-satellite-dish"></i> Live Intelligence
                    </span>
                    <h1 class="text-6xl font-black text-slate-900 tracking-tighter leading-none italic">Attendance <span
                            class="text-brand-blue">Intel</span></h1>
                    <p class="text-slate-500 text-lg font-bold opacity-70">Troop presence monitoring & efficiency
                        analytics.</p>
                </div>

                <div class="flex items-center gap-3 no-print">
                    <button onclick="window.print()"
                        class="bg-white border border-gray-200 text-gray-600 px-6 py-3 rounded-2xl font-bold flex items-center gap-2 hover:bg-gray-50 transition border border-gray-100 shadow-sm">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="event_intel.php"
                        class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 hover:bg-slate-800 transition shadow-lg">
                        <i class="fas fa-layer-group"></i> Event Intel
                    </a>
                    <a href="export.php?type=attendance&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>"
                        class="bg-white hover:bg-gray-50 text-gray-700 px-6 py-3 rounded-2xl font-bold transition flex items-center gap-2 border border-gray-100 shadow-sm">
                        <i class="fas fa-file-export text-brand-blue"></i> Export Bundle
                    </a>
                </div>
            </div>

            <!-- Modern Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div
                    class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-blue-50 text-brand-blue rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-brand-blue group-hover:text-white transition-colors">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest">Total Scans</p>
                    <h4 class="text-3xl font-black text-slate-800"><?php echo $totalScans; ?></h4>
                </div>
                <div
                    class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-green-500 group-hover:text-white transition-colors">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <p class="text-[10px] uppercase font-bold text-green-400 tracking-widest">On-Time</p>
                    <h4 class="text-3xl font-black text-slate-800"><?php echo $onTimeCount; ?></h4>
                </div>
                <div
                    class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-orange-500 group-hover:text-white transition-colors">
                        <i class="fas fa-clock"></i>
                    </div>
                    <p class="text-[10px] uppercase font-bold text-orange-400 tracking-widest">Late Scans</p>
                    <h4 class="text-3xl font-black text-slate-800"><?php echo $lateCount; ?></h4>
                </div>
                <div
                    class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-xl mb-4 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <i class="fas fa-users"></i>
                    </div>
                    <p class="text-[10px] uppercase font-bold text-purple-400 tracking-widest">Coverage</p>
                    <h4 class="text-3xl font-black text-slate-800"><?php echo $coverage; ?>%</h4>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-lg mb-8">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Search
                            Students</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>"
                                placeholder="Name or email..."
                                class="w-full bg-gray-50 border-none rounded-2xl pl-12 pr-4 py-3 focus:ring-2 focus:ring-brand-blue transition-all outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Start
                            Date</label>
                        <input type="date" name="start_date" value="<?php echo $startDate; ?>"
                            class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 focus:ring-2 focus:ring-brand-blue transition-all outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">End
                            Date</label>
                        <input type="date" name="end_date" value="<?php echo $endDate; ?>"
                            class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 focus:ring-2 focus:ring-brand-blue transition-all outline-none">
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full bg-brand-blue text-white font-bold py-3 rounded-2xl shadow-lg shadow-blue-100 hover:bg-brand-dark transition transform active:scale-95">
                            Filter Logs
                        </button>
                    </div>
                </form>
            </div>

            <div
                class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] border border-white shadow-2xl shadow-blue-900/5 overflow-hidden ring-1 ring-slate-200/50">
                <div class="p-8 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm italic tracking-tight flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-brand-blue animate-pulse"></span>
                            Verified Activity Feed
                        </h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Real-time scan
                            logs</p>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Present</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Late</span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead
                            class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest border-b border-gray-100">
                            <tr>
                                <th class="p-6">Student / Cadet</th>
                                <th class="p-6">Verified By</th>
                                <th class="p-6">Time Scanned</th>
                                <th class="p-6 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="p-20 text-center">
                                        <div
                                            class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl text-gray-300">
                                            <i class="fas fa-ghost"></i>
                                        </div>
                                        <p class="text-gray-400 font-bold">No presence logs found for this period.</p>
                                        <p class="text-xs text-gray-300 mt-1">Try adjusting your filters or search terms.
                                        </p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log):
                                    $statusClass = $log['status'] === 'late' ? 'bg-orange-50 text-orange-600 border-orange-100' : 'bg-green-50 text-green-600 border-green-100';
                                    $statusLabel = ucfirst($log['status']);
                                    ?>
                                    <tr class="hover:bg-blue-50/30 transition duration-150">
                                        <td class="p-6">
                                            <a href="student_profile.php?id=<?php echo $log['student_id']; ?>"
                                                class="flex items-center gap-4 group">
                                                <div
                                                    class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-bold text-xs shadow-lg group-hover:scale-110 transition-transform">
                                                    <?php echo strtoupper(substr($log['student_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <p
                                                        class="font-black text-slate-800 group-hover:text-brand-blue transition-colors">
                                                        <?php echo htmlspecialchars($log['student_name']); ?>
                                                    </p>
                                                    <p class="text-[10px] text-slate-400 font-medium">
                                                        <?php echo htmlspecialchars($log['student_email']); ?>
                                                    </p>
                                                </div>
                                            </a>
                                        </td>
                                        <td class="p-6">
                                            <div class="flex flex-col">
                                                <span
                                                    class="font-bold text-slate-700"><?php echo htmlspecialchars($log['instructor_name']); ?></span>
                                                <?php if ($log['event_title']): ?>
                                                    <span
                                                        class="text-[10px] text-brand-blue font-black uppercase tracking-widest mt-1 opacity-60"><?php echo htmlspecialchars($log['event_title']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="p-6">
                                            <div class="flex items-center gap-2">
                                                <i class="far fa-clock text-slate-300"></i>
                                                <span
                                                    class="font-bold text-slate-500"><?php echo date('H:i:s A', strtotime($log['created_at'])); ?></span>
                                            </div>
                                            <p class="text-[10px] text-slate-300 mt-1">
                                                <?php echo date('M d, Y', strtotime($log['attendance_date'])); ?>
                                            </p>
                                        </td>
                                        <td class="p-6 text-center">
                                            <span
                                                class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border <?php echo $statusClass; ?>">
                                                <?php echo $statusLabel; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>