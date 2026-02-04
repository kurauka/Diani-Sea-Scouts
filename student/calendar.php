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
<style>
    :root {
        --fc-border-color: #f1f5f9;
        --fc-daygrid-event-dot-width: 8px;
        --fc-today-bg-color: #f8fafc;
    }

    .fc {
        font-family: 'Inter', sans-serif;
        background: white;
        border-radius: 1.5rem;
        padding: 1.5rem;
    }

    .fc .fc-toolbar-title {
        font-size: 1.25rem !important;
        font-weight: 800;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .fc .fc-button-primary {
        background-color: #f8fafc;
        border-color: #f1f5f9;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 0.5rem 1rem;
        border-radius: 0.75rem !important;
        transition: all 0.2s;
    }

    .fc .fc-button-primary:hover {
        background-color: #f1f5f9;
        color: #1e293b;
    }

    .fc .fc-button-active {
        background-color: #0f172a !important;
        border-color: #0f172a !important;
        color: white !important;
    }

    .fc-theme-standard td,
    .fc-theme-standard th {
        border-color: #f1f5f9 !important;
    }

    .fc-day-today {
        background-color: var(--fc-today-bg-color) !important;
    }

    .fc-event {
        border-radius: 6px;
        padding: 2px 4px;
        font-weight: 600;
        font-size: 0.75rem;
        border: none !important;
        margin: 1px 2px !important;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .fc-event:hover {
        transform: scale(1.02);
        filter: brightness(1.1);
    }

    .fc-daygrid-event-dot {
        border-width: 4px !important;
    }

    .upcoming-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    @keyframes glow {

        0%,
        100% {
            box-shadow: 0 0 5px rgba(32, 164, 243, 0.2);
        }

        50% {
            box-shadow: 0 0 20px rgba(32, 164, 243, 0.4);
        }
    }

    .check-in-btn-animate {
        animation: glow 2s infinite;
    }
</style>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Academic Calendar</h1>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-full">
            <!-- Sidebar / Legend -->
            <div class="space-y-6">
                <!-- Upcoming Widget -->
                <div
                    class="bg-gradient-to-br from-slate-900 to-slate-800 p-6 rounded-3xl text-white shadow-xl relative overflow-hidden group">
                    <div
                        class="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700">
                    </div>
                    <h2 class="font-black text-xl mb-4 flex items-center gap-2 relative z-10">
                        <i class="fas fa-bolt text-yellow-400"></i>
                        Upcoming
                    </h2>
                    <div id="upcomingEventsList" class="space-y-4 relative z-10">
                        <p class="text-slate-400 text-xs italic">Loading agenda...</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3
                        class="font-black text-slate-800 text-sm uppercase tracking-widest mb-4 flex items-center gap-2">
                        <i class="fas fa-layer-group text-brand-blue"></i>
                        Event Types
                    </h3>
                    <div class="space-y-3 text-sm font-bold text-slate-600">
                        <div class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition cursor-default">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#DA292E] ring-4 ring-red-50"></span>
                            Exam / Deadline
                        </div>
                        <div class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition cursor-default">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#2EC4B6] ring-4 ring-teal-50"></span>
                            Class / Seminar
                        </div>
                        <div class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition cursor-default">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#FF9F1C] ring-4 ring-orange-50"></span>
                            Meeting
                        </div>
                        <div class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition cursor-default">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#20A4F3] ring-4 ring-blue-50"></span>
                            Holiday / Event
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="lg:col-span-3 bg-white p-2 rounded-3xl shadow-sm border border-gray-100 min-h-[650px]">
                <div id='calendar' class="h-full"></div>
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

            eventDidMount: function (info) {
                // Color mapping logic
                const type = info.event.extendedProps.type;
                let color = '#20A4F3'; // Default
                if (type === 'exam') color = '#DA292E';
                if (type === 'class') color = '#2EC4B6';
                if (type === 'meeting') color = '#FF9F1C';

                info.el.style.backgroundColor = color + '22'; // Transparent bg
                info.el.style.color = color;
                info.el.style.borderLeft = `4px solid ${color}`;
            },

            loading: function (isLoading) {
                if (!isLoading) {
                    updateUpcomingWidget(calendar.getEvents());
                }
            },

            eventClick: function (info) {
                const eventDate = info.event.startStr.split('T')[0];
                const today = new Date().toISOString().split('T')[0];

                if (eventDate === today) {
                    showCheckinModal(info.event);
                } else {
                    // Modern alert
                    Swal.fire({
                        title: info.event.title,
                        text: info.event.extendedProps.description || "No description provided.",
                        icon: 'info',
                        confirmButtonText: 'Got it',
                        confirmButtonColor: '#0f172a',
                        customClass: {
                            popup: 'rounded-3xl',
                            confirmButton: 'rounded-xl px-8 py-3'
                        }
                    });
                }
            }
        });
        calendar.render();

        function updateUpcomingWidget(events) {
            const list = document.getElementById('upcomingEventsList');
            list.innerHTML = '';

            const today = new Date();
            const futureEvents = events
                .filter(e => new Date(e.start) >= today)
                .sort((a, b) => new Date(a.start) - new Date(b.start))
                .slice(0, 3);

            if (futureEvents.length === 0) {
                list.innerHTML = '<p class="text-slate-500 text-xs italic">No upcoming events.</p>';
                return;
            }

            futureEvents.forEach(e => {
                const date = new Date(e.start).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const type = e.extendedProps.type;
                let colorClass = 'bg-blue-500';
                if (type === 'exam') colorClass = 'bg-red-500';
                if (type === 'class') colorClass = 'bg-teal-500';

                list.innerHTML += `
                    <div class="flex items-center gap-3 p-3 bg-white/5 rounded-2xl border border-white/10 hover:bg-white/10 transition cursor-pointer" onclick="calendar.gotoDate('${e.startStr}')">
                        <div class="w-10 h-10 ${colorClass} rounded-xl flex flex-col items-center justify-center text-[10px] font-black">
                            <span>${date.split(' ')[0]}</span>
                            <span class="text-sm mt-[-4px]">${date.split(' ')[1]}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold truncate">${e.title}</p>
                            <p class="text-[10px] text-slate-400 capitalize">${type}</p>
                        </div>
                    </div>
                `;
            });
        }

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