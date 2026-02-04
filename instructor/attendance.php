<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Fetch Today's Attendance for this instructor's troop
$instructor_id = $_SESSION['user_id'];
$stmt = $database->prepare("SELECT troop_id FROM users WHERE id = ?");
$stmt->execute([$instructor_id]);
$troop_id = $stmt->fetchColumn();

$query = "SELECT a.*, u.name, u.email, e.title as event_title
          FROM attendance a 
          JOIN users u ON a.student_id = u.id 
          LEFT JOIN calendar_events e ON a.event_id = e.id
          WHERE a.instructor_id = :ins AND a.attendance_date = CURRENT_DATE
          ORDER BY a.created_at DESC";
$stmt = $database->prepare($query);
$stmt->execute([':ins' => $instructor_id]);
$today_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight italic">Troop Attendance</h1>
                    <p class="text-slate-500 font-medium">Troop ID: <span class="text-brand-blue font-bold">
                            <?php echo htmlspecialchars($troop_id ?? 'N/A'); ?>
                        </span> |
                        <?php echo date('F d, Y'); ?>
                    </p>
                </div>
                <div class="flex gap-3">
                    <button id="startScanBtn"
                        class="bg-brand-blue text-white px-6 py-2.5 rounded-xl font-bold shadow-lg flex items-center gap-2 hover:bg-brand-dark transition">
                        <i class="fas fa-qrcode"></i> Start Scanner
                    </button>
                    <button id="stopScanBtn"
                        class="hidden bg-red-500 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg flex items-center gap-2 hover:bg-red-600 transition">
                        <i class="fas fa-stop"></i> Stop Scanner
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Scanner Section -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xl overflow-hidden relative">
                    <div id="reader" style="width: 100%;" class="rounded-2xl overflow-hidden border-4 border-slate-50">
                    </div>
                    <div id="scanMsg" class="mt-4 p-4 rounded-xl text-center font-bold hidden"></div>

                    <div id="welcomeScreen"
                        class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm flex flex-col items-center justify-center text-white p-8 text-center">
                        <div class="w-20 h-20 bg-white/10 rounded-full flex items-center justify-center mb-6 text-4xl">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h2 class="text-2xl font-bold mb-2 text-cyan-400">Scanner Standby</h2>
                        <p class="text-slate-300 mb-8 max-w-xs">Ready to track your troop? Click start scanner and point
                            the camera at a student's ID card QR code.</p>
                        <i class="fas fa-chevron-circle-down animate-bounce text-2xl opacity-50"></i>
                    </div>
                </div>

                <!-- List Section -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-xl flex flex-col h-[500px]">
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-clipboard-check text-green-500"></i> Today's Rollout
                        </h3>
                        <span
                            class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest">
                            <span id="presentCount">
                                <?php echo count($today_attendance); ?>
                            </span> Present
                        </span>
                    </div>

                    <div class="overflow-y-auto flex-1 p-2" id="attendanceList">
                        <?php if (empty($today_attendance)): ?>
                            <div class="flex flex-col items-center justify-center h-full text-center text-slate-400 p-8">
                                <i class="fas fa-user-slash text-4xl mb-4 opacity-20"></i>
                                <p class="text-xs uppercase font-black tracking-widest opacity-50">No students logged yet
                                </p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($today_attendance as $row): ?>
                                <div
                                    class="p-4 hover:bg-slate-50 rounded-2xl flex items-center justify-between transition border border-transparent hover:border-slate-100 mb-2">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs">
                                            <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-800 text-sm">
                                                <?php echo htmlspecialchars($row['name']); ?>
                                            </h4>
                                            <div class="flex items-center gap-2">
                                                <p class="text-[10px] text-slate-400">
                                                    <?php echo date('H:i A', strtotime($row['created_at'])); ?>
                                                </p>
                                                <?php if ($row['event_title']): ?>
                                                    <span
                                                        class="text-[8px] bg-blue-50 text-brand-blue px-1.5 py-0.5 rounded font-bold uppercase tracking-widest">
                                                        <?php echo htmlspecialchars($row['event_title']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const html5QrCode = new Html5Qrcode("reader");
    const scanMsg = document.getElementById('scanMsg');
    const startBtn = document.getElementById('startScanBtn');
    const stopBtn = document.getElementById('stopScanBtn');
    const welcome = document.getElementById('welcomeScreen');

    function onScanSuccess(decodedText, decodedResult) {
        console.log(`Code scanned = ${decodedText}`, decodedResult);

        // Disable scanner temporarily to prevent double scans
        html5QrCode.pause();

        try {
            const data = JSON.parse(decodedText);
            const studentId = data.id;
            const eventId = data.event_id || null;

            if (!studentId) throw new Error("Invalid ID");

            markAttendance(studentId, eventId);
        } catch (e) {
            showMsg("Invalid QR Code Data", "bg-red-100 text-red-600");
            setTimeout(() => html5QrCode.resume(), 2000);
        }
    }

    function markAttendance(studentId, eventId) {
        let body = `student_id=${studentId}`;
        if (eventId) body += `&event_id=${eventId}`;

        fetch('mark_attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMsg(`Present: ${data.name}`, "bg-green-100 text-green-600");
                    updateList(data.name, data.time);
                } else {
                    showMsg(data.error || "Already marked", "bg-orange-100 text-orange-600");
                }
                setTimeout(() => {
                    scanMsg.classList.add('hidden');
                    html5QrCode.resume();
                }, 3000);
            });
    }

    function showMsg(text, classes) {
        scanMsg.innerText = text;
        scanMsg.className = `mt-4 p-4 rounded-xl text-center font-bold ${classes}`;
        scanMsg.classList.remove('hidden');
    }

    function updateList(name, time) {
        const list = document.getElementById('attendanceList');
        const count = document.getElementById('presentCount');

        // Remove empty state if exists
        if (list.querySelector('.text-center')) {
            list.innerHTML = '';
        }

        const html = `
            <div class="p-4 bg-green-50/50 border border-green-100 rounded-2xl flex items-center justify-between transition mb-2 animate-bounce">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs">
                        ${name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm">${name}</h4>
                        <p class="text-[10px] text-slate-400">${time}</p>
                    </div>
                </div>
                <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
            </div>
        `;
        list.insertAdjacentHTML('afterbegin', html);
        count.innerText = parseInt(count.innerText) + 1;

        setTimeout(() => {
            const newItem = list.querySelector('.animate-bounce');
            if (newItem) newItem.classList.remove('animate-bounce');
        }, 1000);
    }

    startBtn.addEventListener('click', () => {
        welcome.classList.add('hidden');
        startBtn.classList.add('hidden');
        stopBtn.classList.remove('hidden');

        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess
        ).catch(err => {
            console.error(err);
            showMsg("Camera access denied", "bg-red-100 text-red-600");
        });
    });

    stopBtn.addEventListener('click', () => {
        html5QrCode.stop().then(() => {
            stopBtn.classList.add('hidden');
            startBtn.classList.remove('hidden');
            welcome.classList.remove('hidden');
            scanMsg.classList.add('hidden');
        });
    });
</script>
<?php include_once '../includes/footer.php'; ?>