<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
// $database is already provided by config/db.php

// Fetch Today's Attendance for this instructor's troop
$instructor_id = $_SESSION['user_id'];
$stmt = $database->prepare("SELECT troop_id FROM users WHERE id = ?");
$stmt->execute([$instructor_id]);
$troop_id = $stmt->fetchColumn();

// Fetch all students in this troop (handle NULL troop_id for testing/fallback)
$student_query = "SELECT u.id, u.name, u.email, 
                  a.status as attendance_status, a.created_at as attendance_time,
                  e.title as event_title
                  FROM users u
                  LEFT JOIN attendance a ON u.id = a.student_id AND a.attendance_date = CURRENT_DATE
                  LEFT JOIN calendar_events e ON a.event_id = e.id
                  WHERE (u.troop_id = :troop OR (u.troop_id IS NULL AND :troop_null = 1)) 
                  AND u.role = 'student'
                  ORDER BY u.name ASC";
$stmt = $database->prepare($student_query);
$stmt->execute([':troop' => $troop_id, ':troop_null' => ($troop_id === null ? 1 : 0)]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$total_students = count($students);
$present_count = 0;
$late_count = 0;
$absent_count = 0;

foreach ($students as $s) {
    if ($s['attendance_status'] === 'present')
        $present_count++;
    elseif ($s['attendance_status'] === 'late')
        $late_count++;
    else
        $absent_count++;
}

// Fetch Today's & Future Events for setup selection
$eventsStmt = $database->query("SELECT id, title, type FROM calendar_events WHERE start_date >= CURRENT_DATE ORDER BY start_date ASC LIMIT 10");
$availableEvents = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out forwards;
    }
</style>

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
                    <button id="finalizeBtn"
                        class="hidden bg-slate-900 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg flex items-center gap-2 hover:bg-slate-800 transition">
                        <i class="fas fa-check-double"></i> Finalize Rollcall
                    </button>
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

            <!-- Stats Dashboard -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div
                    class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-4 group hover:shadow-md transition">
                    <div
                        class="w-12 h-12 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center text-xl group-hover:bg-slate-900 group-hover:text-white transition">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Troop</p>
                        <h4 class="text-2xl font-black text-slate-800"><?php echo $total_students; ?></h4>
                    </div>
                </div>
                <div
                    class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-4 group hover:shadow-md transition">
                    <div
                        class="w-12 h-12 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center text-xl group-hover:bg-green-500 group-hover:text-white transition shadow-sm shadow-green-200">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-green-500 tracking-wider">Checked In</p>
                        <h4 class="text-2xl font-black text-slate-800" id="presentCountMain">
                            <?php echo $present_count; ?>
                        </h4>
                    </div>
                </div>
                <div
                    class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-4 group hover:shadow-md transition">
                    <div
                        class="w-12 h-12 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center text-xl group-hover:bg-orange-500 group-hover:text-white transition shadow-sm shadow-orange-200">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-orange-500 tracking-wider">Late</p>
                        <h4 class="text-2xl font-black text-slate-800" id="lateCountMain"><?php echo $late_count; ?>
                        </h4>
                    </div>
                </div>
                <div
                    class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-4 group hover:shadow-md transition">
                    <div
                        class="w-12 h-12 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center text-xl group-hover:bg-red-500 group-hover:text-white transition shadow-sm shadow-red-200">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-red-500 tracking-wider">Absent</p>
                        <h4 class="text-2xl font-black text-slate-800" id="absentCountMain"><?php echo $absent_count; ?>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Scanner Section -->
                <div
                    class="lg:col-span-5 bg-white rounded-3xl p-6 border border-gray-100 shadow-xl overflow-hidden relative h-fit">
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

                <!-- Recent Activity Feed -->
                <div
                    class="lg:col-span-5 bg-white rounded-3xl p-6 border border-gray-100 shadow-xl mt-4 flex flex-col h-[300px]">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-slate-800 text-sm italic tracking-tight">Recent Activity</h3>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Live Flow</span>
                    </div>
                    <div id="recentLog" class="flex-1 overflow-y-auto space-y-3 pr-2">
                        <div class="text-center py-8 text-slate-300">
                            <i class="fas fa-stream block text-2xl mb-2 opacity-20"></i>
                            <p class="text-[10px] font-bold uppercase tracking-widest">Waiting for scans...</p>
                        </div>
                    </div>
                </div>

                <!-- List Section -->
                <div
                    class="lg:col-span-7 bg-white rounded-3xl border border-gray-100 shadow-xl flex flex-col h-[600px]">
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-users text-brand-blue"></i> Student Roster
                        </h3>
                        <div class="flex gap-2">
                            <span class="bg-green-100 text-green-600 px-2 py-1 rounded-lg text-[10px] font-bold">
                                <span id="presentCount"><?php echo $present_count; ?></span> Present
                            </span>
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-1 p-2" id="attendanceList">
                        <?php foreach ($students as $row):
                            $status = $row['attendance_status'] ?? 'absent';
                            $status_class = "text-red-500 bg-red-50";
                            $status_label = "Absent";
                            if ($status === 'present') {
                                $status_class = "text-green-500 bg-green-50";
                                $status_label = "Present";
                            } elseif ($status === 'late') {
                                $status_class = "text-orange-500 bg-orange-50";
                                $status_label = "Late";
                            }
                            ?>
                            <div id="student-row-<?php echo $row['id']; ?>"
                                class="p-4 hover:bg-slate-50 rounded-2xl flex items-center justify-between transition border border-transparent hover:border-slate-100 mb-2">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs relative">
                                        <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                        <div
                                            class="status-indicator absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full border-2 border-white <?php echo $status === 'absent' ? 'bg-red-500' : ($status === 'late' ? 'bg-orange-500' : 'bg-green-500'); ?>">
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </h4>
                                        <div class="flex items-center gap-2">
                                            <p class="text-[10px] text-slate-400 status-text">
                                                <?php echo $row['attendance_time'] ? date('H:i A', strtotime($row['attendance_time'])) : $status_label; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="action-buttons flex items-center gap-2">
                                    <button onclick="manualMark(<?php echo $row['id']; ?>, 'present')"
                                        class="mark-present-btn <?php echo $status === 'present' ? 'hidden' : ''; ?> bg-green-500 text-white text-[10px] px-3 py-1.5 rounded-lg font-bold hover:bg-green-600 transition">Mark
                                        Present</button>
                                    <button onclick="manualMark(<?php echo $row['id']; ?>, 'late')"
                                        class="mark-late-btn <?php echo $status === 'late' ? 'hidden' : ''; ?> bg-orange-500 text-white text-[10px] px-3 py-1.5 rounded-lg font-bold hover:bg-orange-600 transition">Mark
                                        Late</button>
                                    <span
                                        class="status-badge <?php echo $status === 'absent' ? 'hidden' : ''; ?> text-[10px] font-black uppercase tracking-widest <?php echo $status_class; ?> px-2 py-1 rounded-md">
                                        <?php echo $status; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Attendance Setup Modal -->
<div id="setupModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-fadeIn">
        <div class="p-8 text-center">
            <div
                class="w-16 h-16 bg-brand-pale text-brand-blue rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Setup Today's Rollcall</h2>
            <p class="text-slate-500 text-sm mb-8">What activity are we tracking today? This helps organize your
                reports.</p>

            <div class="space-y-4 text-left">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Select
                        Activity</label>
                    <select id="activitySelect"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue font-medium">
                        <option value="">General Troop Meeting (No Event)</option>
                        <optgroup label="Upcoming Events">
                            <?php foreach ($availableEvents as $event): ?>
                                <option value="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['title']); ?>
                                    (<?php echo ucfirst($event['type']); ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="flex items-center gap-4">
                    <hr class="flex-1 border-slate-100">
                    <span class="text-[10px] font-bold text-slate-300 uppercase">Or</span>
                    <hr class="flex-1 border-slate-100">
                </div>

                <button onclick="openAddActivityModal()"
                    class="w-full py-3 border-2 border-dashed border-slate-200 rounded-xl text-slate-500 font-bold text-sm hover:border-brand-blue hover:text-brand-blue transition flex items-center justify-center gap-2">
                    <i class="fas fa-plus"></i> Create New Activity
                </button>
            </div>

            <button onclick="startSession()"
                class="w-full bg-brand-blue text-white py-4 rounded-2xl font-bold shadow-lg shadow-blue-200 mt-10 hover:bg-brand-dark transition">
                Start Taking Attendance
            </button>
        </div>
    </div>
</div>

<!-- Add Activity Modal -->
<div id="addActivityModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[110] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-slate-800 italic">New Activity</h3>
            <button onclick="toggleModal('addActivityModal')" class="text-slate-400 hover:text-slate-600"><i
                    class="fas fa-times"></i></button>
        </div>
        <form id="newActivityForm" class="p-8 space-y-6">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Activity
                    Name</label>
                <input type="text" id="newActivityTitle" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue"
                    placeholder="e.g. Swimming Proficiency">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Type</label>
                <select id="newActivityType"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                    <option value="class">Class / Seminar</option>
                    <option value="exam">Exam / Competition</option>
                    <option value="meeting">Meeting</option>
                    <option value="other">Other Event</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="toggleModal('addActivityModal')"
                    class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold">Cancel</button>
                <button type="submit"
                    class="flex-1 py-4 bg-brand-blue text-white rounded-2xl font-bold shadow-lg shadow-blue-200">Save &
                    Select</button>
            </div>
        </form>
    </div>
</div>

<script>
    const html5QrCode = new Html5Qrcode("reader");
    const scanMsg = document.getElementById('scanMsg');
    const startBtn = document.getElementById('startScanBtn');
    const stopBtn = document.getElementById('stopScanBtn');
    const welcome = document.getElementById('welcomeScreen');

    let currentEventId = null;

    function toggleModal(id) {
        document.getElementById(id).classList.toggle('hidden');
    }

    function openAddActivityModal() {
        toggleModal('addActivityModal');
    }

    function startSession() {
        const select = document.getElementById('activitySelect');
        currentEventId = select.value;
        const selectedText = select.options[select.selectedIndex].text;

        // Update dashboard title to show active activity
        if (currentEventId) {
            document.querySelector('h1').innerHTML = `<i class="fas fa-clipboard-check text-brand-blue"></i> ${selectedText}`;
        }

        // Show finalize button
        document.getElementById('finalizeBtn').classList.remove('hidden');

        const setupModal = document.getElementById('setupModal');
        setupModal.classList.add('opacity-0');
        setTimeout(() => setupModal.classList.add('hidden'), 300);
    }

    // Handle Finalize Click
    document.getElementById('finalizeBtn').addEventListener('click', function () {
        if (!confirm("This will mark all students who haven't scanned in as ABSENT. Are you sure?")) return;

        fetch('finalize_attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `event_id=${currentEventId || ''}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh to see all updated statuses
                } else {
                    alert("Error: " + data.error);
                }
            });
    });

    // Handle new activity form submission
    document.getElementById('newActivityForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const title = document.getElementById('newActivityTitle').value;
        const type = document.getElementById('newActivityType').value;
        const today = new Date().toISOString().split('T')[0];

        fetch('calendar_logic.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: title,
                type: type,
                start: today,
                end: today,
                description: 'Created during rollcall'
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // We'd need the ID of the new event to select it, but calendar_logic doesn't return it currently.
                    // Let's reload to refresh the list or just fetch again.
                    // For now, simpler: alert and reload
                    alert("Activity created! Please select it from the list.");
                    location.reload();
                }
            });
    });

    function onScanSuccess(decodedText, decodedResult) {
        console.log(`Code scanned = ${decodedText}`, decodedResult);

        // Disable scanner temporarily to prevent double scans
        html5QrCode.pause();

        try {
            const data = JSON.parse(decodedText);
            const studentId = data.id;
            // Use currentEventId if set, otherwise use what's in QR (fall back)
            const eventId = currentEventId || data.event_id || null;

            if (!studentId) throw new Error("Invalid ID");

            markAttendance(studentId, eventId);
        } catch (e) {
            showMsg("Invalid QR Code Data", "bg-red-100 text-red-600");
            setTimeout(() => html5QrCode.resume(), 2000);
        }
    }

    function manualMark(studentId, status) {
        markAttendance(studentId, currentEventId, status);
    }

    function markAttendance(studentId, eventId = null, status = 'present') {
        let body = `student_id=${studentId}&status=${status}`;
        if (eventId) body += `&event_id=${eventId}`;

        fetch('mark_attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMsg(`Marked ${status}: ${data.name}`, "bg-green-100 text-green-600");
                    updateUI(studentId, data.name, data.time, status);
                } else {
                    showMsg(data.error || "Error marking attendance", "bg-orange-100 text-orange-600");
                }
                setTimeout(() => {
                    scanMsg.classList.add('hidden');
                    // Safer check for resuming scanner
                    try {
                        if (html5QrCode.getState() === 3) { // 3 is PAUSED in html5-qrcode
                            html5QrCode.resume();
                        }
                    } catch (e) { }
                }, 3000);
            });
    }

    function showMsg(text, classes) {
        scanMsg.innerText = text;
        scanMsg.className = `mt-4 p-4 rounded-xl text-center font-bold ${classes}`;
        scanMsg.classList.remove('hidden');
    }

    function updateUI(studentId, name, time, status) {
        const row = document.getElementById(`student-row-${studentId}`);
        const presentCountMain = document.getElementById('presentCountMain');
        const lateCountMain = document.getElementById('lateCountMain');
        const absentCountMain = document.getElementById('absentCountMain');
        const presentCountSub = document.getElementById('presentCount');
        const recentLog = document.getElementById('recentLog');

        // Update Recent Log (feedback even if not in roster)
        const logHtml = `
            <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between animate-fadeIn">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-[10px]">
                        ${name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <h5 class="font-bold text-slate-800 text-xs">${name}</h5>
                        <p class="text-[9px] text-slate-400">${time} • <span class="uppercase font-black text-brand-blue">${status}</span></p>
                    </div>
                </div>
                <div class="w-2 h-2 rounded-full ${status === 'late' ? 'bg-orange-500' : 'bg-green-500'}"></div>
            </div>
        `;
        if (recentLog.querySelector('.text-center')) recentLog.innerHTML = '';
        recentLog.insertAdjacentHTML('afterbegin', logHtml);

        if (!row) {
            console.warn("Student not found in current roster view");
            return;
        }

        // Determine if we are changing from absent to something else
        const indicator = row.querySelector('.status-indicator');
        const wasAbsent = indicator.classList.contains('bg-red-500');
        const wasPresent = indicator.classList.contains('bg-green-500');
        const wasLate = indicator.classList.contains('bg-orange-500');

        // Update indicator and text
        indicator.className = `status-indicator absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full border-2 border-white ${status === 'late' ? 'bg-orange-500' : 'bg-green-500'}`;
        row.querySelector('.status-text').innerText = time;

        // Update button/status label
        const actionArea = row.querySelector('.action-buttons');
        const presentBtn = actionArea.querySelector('.mark-present-btn');
        const lateBtn = actionArea.querySelector('.mark-late-btn');
        const badge = actionArea.querySelector('.status-badge');

        if (badge) {
            badge.innerText = status;
            badge.className = `status-badge text-[10px] font-black uppercase tracking-widest ${status === 'late' ? 'text-orange-500 bg-orange-50' : 'text-green-500 bg-green-50'} px-2 py-1 rounded-md`;
            badge.classList.remove('hidden');
        }

        if (presentBtn) {
            if (status === 'present') {
                presentBtn.classList.add('hidden');
                lateBtn.classList.remove('hidden');
            } else if (status === 'late') {
                lateBtn.classList.add('hidden');
                presentBtn.classList.remove('hidden');
            }
        }

        // Update counts (safely)
        const safeInc = (el, val) => { if (el) el.innerText = parseInt(el.innerText || 0) + val; };

        if (wasAbsent) {
            safeInc(absentCountMain, -1);
        } else if (wasPresent && status === 'late') {
            safeInc(presentCountMain, -1);
            safeInc(presentCountSub, -1);
        } else if (wasLate && status === 'present') {
            safeInc(lateCountMain, -1);
        }

        if (status === 'present' && !wasPresent) {
            safeInc(presentCountMain, 1);
            safeInc(presentCountSub, 1);
        } else if (status === 'late' && !wasLate) {
            safeInc(lateCountMain, 1);
        }

        row.classList.add('bg-blue-50', 'animate-pulse');
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(() => row.classList.remove('bg-blue-50', 'animate-pulse'), 2000);
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