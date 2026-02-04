<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}
include_once '../includes/header.php';
?>
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Academic Calendar</h1>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-full">
            <!-- Sidebar / Legend -->
            <div class="space-y-6">
                <!-- Upcoming Widget (simplified) -->
                <div class="bg-gradient-to-br from-brand-dark to-brand-blue p-6 rounded-2xl text-white shadow-lg">
                    <h2 class="font-bold text-lg mb-2">Upcoming</h2>
                    <p class="text-blue-100 text-sm">Check the calendar for pinned exams and important dates.</p>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4">Legend</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#DA292E]"></span>
                            Exam / Deadline</div>
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#2EC4B6]"></span>
                            Class / Seminar</div>
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#FF9F1C]"></span>
                            Meeting</div>
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-[#20A4F3]"></span>
                            Holiday / Event</div>
                    </div>
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="lg:col-span-3 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 min-h-[600px]">
                <div id='calendar'></div>
            </div>
        </div>
    </main>
</div>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            selectable: false, // Read only
            editable: false,   // Read only
            events: '../instructor/calendar_logic.php', // Reuse instructor logic to fetch events

            eventClick: function (info) {
                const eventDate = info.event.startStr.split('T')[0];
                const today = new Date().toISOString().split('T')[0];

                if (eventDate === today) {
                    showCheckinModal(info.event);
                } else {
                    alert(info.event.title + "\n" + (info.event.extendedProps.description || ""));
                }
            }
        });
        calendar.render();

        // Modal Logic
        const modal = document.getElementById('checkinModal');
        const modalContent = document.getElementById('modalContent');

        window.showCheckinModal = function (event) {
            document.getElementById('modalEventTitle').innerText = event.title;
            document.getElementById('qrcode').innerHTML = '';

            const qrData = JSON.stringify({
                id: <?php echo $_SESSION['user_id']; ?>,
                name: "<?php echo $_SESSION['name']; ?>",
                event_id: event.id,
                role: 'student'
            });

            new QRCode(document.getElementById("qrcode"), {
                text: qrData,
                width: 200,
                height: 200,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
        }

        window.closeModal = function () {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }
    });
</script>

<!-- QR Modal -->
<div id="checkinModal"
    class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-3xl p-8 w-full max-w-sm transform scale-95 transition-transform duration-300 shadow-2xl text-center"
        id="modalContent">
        <div class="w-16 h-1 bg-brand-blue mx-auto rounded-full mb-6"></div>
        <h2 class="text-2xl font-black text-slate-800 mb-2">Event Check-in</h2>
        <p id="modalEventTitle" class="text-sm font-bold text-brand-blue uppercase tracking-widest mb-6"></p>

        <div class="bg-slate-50 p-4 rounded-2xl inline-block border-2 border-slate-900 mb-6">
            <div id="qrcode"></div>
        </div>

        <p class="text-xs text-slate-400 mb-8 italic">Show this code to your instructor to verify your attendance.</p>

        <button onclick="closeModal()"
            class="w-full bg-slate-900 text-white font-black py-4 rounded-2xl hover:bg-slate-800 transition shadow-lg">
            Dismiss
        </button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<?php include_once '../includes/footer.php'; ?>