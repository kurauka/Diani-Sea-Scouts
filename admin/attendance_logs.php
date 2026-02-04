<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// History Filter
$dateFilter = $_GET['date'] ?? date('Y-m-d');

$query = "SELECT a.*, s.name as student_name, s.email as student_email, i.name as instructor_name, e.title as event_title
          FROM attendance a 
          JOIN users s ON a.student_id = s.id 
          JOIN users i ON a.instructor_id = i.id 
          LEFT JOIN calendar_events e ON a.event_id = e.id
          WHERE a.attendance_date = :date
          ORDER BY a.created_at DESC";
$stmt = $database->prepare($query);
$stmt->execute([':date' => $dateFilter]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="max-w-6xl mx-auto">

            <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Attendance Intelligence</h1>
                    <p class="text-gray-500 text-sm mt-1">Reviewing presence and scan logs across the fleet.</p>
                </div>

                <div class="flex items-center gap-3">
                    <form method="GET" class="flex items-center gap-2">
                        <input type="date" name="date" value="<?php echo $dateFilter; ?>"
                            class="bg-white border border-gray-200 rounded-xl px-4 py-2 focus:ring-2 focus:ring-brand-blue/20 outline-none"
                            onchange="this.form.submit()">
                    </form>
                    <a href="export.php?type=attendance&date=<?php echo $dateFilter; ?>"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl font-bold transition flex items-center gap-2">
                        <i class="fas fa-file-download"></i> Export
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase font-black tracking-widest">
                            <tr>
                                <th class="p-4 pl-8">Student / Cadet</th>
                                <th class="p-4">Verified By</th>
                                <th class="p-4">Time Scanned</th>
                                <th class="p-4 text-center">Identity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="p-20 text-center">
                                        <div class="opacity-20 mb-4 text-5xl"><i class="fas fa-search"></i></div>
                                        <p class="text-gray-400 font-medium">No attendance recorded for this date.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-blue-50/50 transition duration-150">
                                        <td class="p-4 pl-8">
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs">
                                                    <?php echo strtoupper(substr($log['student_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-800">
                                                        <?php echo htmlspecialchars($log['student_name']); ?>
                                                    </p>
                                                    <p class="text-[10px] text-slate-400">
                                                        <?php echo htmlspecialchars($log['student_email']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-slate-600"><?php echo htmlspecialchars($log['instructor_name']); ?></span>
                                                <?php if($log['event_title']): ?>
                                                    <span class="text-[9px] text-brand-blue font-bold uppercase tracking-tighter">Event: <?php echo htmlspecialchars($log['event_title']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="p-4 font-mono text-slate-400 text-xs">
                                            <?php echo date('H:i:s A', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span
                                                class="px-3 py-1 bg-green-50 text-green-600 border border-green-100 rounded-full text-[10px] font-black uppercase tracking-widest">
                                                Verified
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