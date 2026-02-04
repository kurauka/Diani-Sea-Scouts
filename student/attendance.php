<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

$student_id = $_SESSION['user_id'];

// Fetch Summary Stats
$stmt = $database->prepare("SELECT COUNT(*) as total_present FROM attendance WHERE student_id = :sid AND status = 'present'");
$stmt->execute([':sid' => $student_id]);
$total_present = $stmt->fetch(PDO::FETCH_ASSOC)['total_present'];

// Fetch Recent Logs
$stmt = $database->prepare("SELECT a.*, u.name as instructor_name FROM attendance a JOIN users u ON a.instructor_id = u.id WHERE a.student_id = :sid ORDER BY a.attendance_date DESC LIMIT 10");
$stmt->execute([':sid' => $student_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// QR Data
$qrData = json_encode([
    'id' => $student_id,
    'name' => $_SESSION['name'],
    'role' => 'student',
    'valid' => true
]);

?>
<?php include_once '../includes/header.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="max-w-4xl mx-auto">

            <div class="mb-10 text-center md:text-left">
                <h1 class="text-3xl font-black text-slate-900 tracking-tight italic">My Attendance</h1>
                <p class="text-slate-500 font-medium">Present your QR code to the instructor for verification.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">

                <!-- Large Scannable QR -->
                <div
                    class="bg-white rounded-3xl p-8 border border-gray-100 shadow-2xl flex flex-col items-center justify-center relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-2 bg-brand-blue"></div>
                    <div
                        class="absolute -top-10 -right-10 w-32 h-32 bg-brand-pale rounded-full opacity-20 blur-2xl group-hover:scale-150 transition-transform">
                    </div>

                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400 mb-8 italic">Scanner-Ready
                        ID</h3>

                    <div class="p-4 bg-white rounded-3xl shadow-inner border-2 border-slate-900 mb-6">
                        <div id="qrcode"></div>
                    </div>

                    <div class="text-center">
                        <p class="text-lg font-black text-slate-800 uppercase tracking-tight">
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </p>
                        <p class="text-xs font-mono text-slate-400">ID:
                            <?php echo str_pad($student_id, 6, '0', STR_PAD_LEFT); ?>
                        </p>
                    </div>

                    <div
                        class="mt-8 flex items-center gap-2 text-[10px] font-bold text-green-500 bg-green-50 px-3 py-1.5 rounded-full uppercase tracking-widest border border-green-100">
                        <i class="fas fa-signal animate-pulse"></i> Live Verification Ready
                    </div>
                </div>

                <!-- Stats & Summary -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div
                        class="bg-gradient-to-br from-brand-dark to-brand-blue rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-8 opacity-10 text-8xl">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-xs font-bold uppercase tracking-widest opacity-60 mb-1">Attendance Record</p>
                            <h2 class="text-5xl font-black mb-4">
                                <?php echo $total_present; ?> <span class="text-lg font-medium opacity-80">Days
                                    Present</span>
                            </h2>
                            <div class="h-1.5 bg-white/20 rounded-full w-full overflow-hidden">
                                <div class="h-full bg-cyan-400"
                                    style="width: <?php echo min(100, $total_present * 5); ?>%"></div>
                            </div>
                            <p class="text-[10px] mt-4 opacity-60 uppercase font-black italic tracking-widest">
                                Calculated for 2026 Participation</p>
                        </div>
                    </div>

                    <!-- Recent Logs Card -->
                    <div
                        class="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden flex flex-col h-[280px]">
                        <div class="p-6 border-b border-gray-50">
                            <h3 class="font-bold text-slate-800 text-sm">Recent Sign-ins</h3>
                        </div>
                        <div class="overflow-y-auto flex-1 p-2">
                            <?php if (empty($logs)): ?>
                                <div
                                    class="h-full flex flex-col items-center justify-center text-slate-300 opacity-50 p-8 text-center">
                                    <i class="fas fa-history text-3xl mb-2"></i>
                                    <p class="text-[10px] uppercase font-black tracking-widest leading-tight">No attendance
                                        records<br>found for this year</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <div
                                        class="flex items-center justify-between p-3 hover:bg-slate-50 rounded-2xl transition border border-transparent hover:border-slate-50">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-blue-50 text-brand-blue flex items-center justify-center text-[10px] font-black">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-slate-800">
                                                    <?php echo date('F d, Y', strtotime($log['attendance_date'])); ?>
                                                </p>
                                                <p class="text-[9px] text-slate-400">Verified by
                                                    <?php echo htmlspecialchars($log['instructor_name']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-mono text-slate-300">
                                            <?php echo date('H:i', strtotime($log['created_at'])); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<script type="text/javascript">
    new QRCode(document.getElementById("qrcode"), {
        text: '<?php echo $qrData; ?>',
        width: 280,
        height: 280,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
</script>
<?php include_once '../includes/footer.php'; ?>