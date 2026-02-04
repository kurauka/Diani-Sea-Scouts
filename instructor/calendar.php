<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
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
        padding: 4px 8px;
        font-weight: 600;
        font-size: 0.75rem;
        border: none !important;
        margin: 1px 2px !important;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
</style>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Course Calendar & Schedule</h1>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-full">
            <!-- Sidebar / Legend / Tools -->
            <div class="space-y-6">
                <div
                    class="bg-gradient-to-br from-brand-blue to-blue-700 p-6 rounded-3xl text-white shadow-xl relative overflow-hidden group">
                    <div
                        class="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700">
                    </div>
                    <h2 class="font-black text-xl mb-2 relative z-10 flex items-center gap-2">
                        <i class="fas fa-calendar-plus text-blue-200"></i>
                        Manage
                    </h2>
                    <p class="text-blue-100 text-xs mb-6 relative z-10 leading-relaxed font-medium">Click on any date to
                        schedule an event or drag existing ones to reschedule.</p>

                    <button onclick="openModalWithToday()"
                        class="w-full bg-white text-brand-blue py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-50 transition shadow-lg flex items-center justify-center gap-2 mb-2 relative z-10">
                        <i class="fas fa-plus"></i> New Event
                    </button>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3
                        class="font-black text-slate-800 text-xs uppercase tracking-widest mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-brand-blue"></i>
                        Color Legend
                    </h3>
                    <div class="space-y-3 font-bold text-slate-600">
                        <div
                            class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition cursor-default text-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#DA292E] ring-4 ring-red-50"></span>
                            Exam / Deadline
                        </div>
                        <div
                            class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition cursor-default text-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#2EC4B6] ring-4 ring-teal-50"></span>
                            Class / Seminar
                        </div>
                        <div
                            class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition cursor-default text-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#FF9F1C] ring-4 ring-orange-50"></span>
                            Meeting
                        </div>
                        <div
                            class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition cursor-default text-xs">
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

<!-- Add Event Modal -->
<div id="eventModal"
    class="fixed inset-0 bg-slate-900/60 z-50 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-3xl p-8 w-full max-w-md transform scale-95 transition-transform duration-300 shadow-2xl relative overflow-hidden"
        id="modalContent">
        <div class="w-16 h-1 bg-brand-blue rounded-full mb-6 mx-auto"></div>
        <h2 class="text-2xl font-black text-slate-800 mb-6 text-center">Schedule New Event</h2>

        <form id="addEventForm" class="space-y-5">
            <input type="hidden" id="eventStart">
            <input type="hidden" id="eventEnd">

            <div class="space-y-1">
                <label class="block text-slate-500 text-[10px] font-black uppercase tracking-widest ml-1">Event
                    Title</label>
                <input type="text" id="eventTitle" required placeholder="e.g. Advanced Navigation Exam"
                    class="w-full px-5 py-3 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-brand-blue font-bold text-slate-700">
            </div>

            <div class="space-y-1">
                <label class="block text-slate-500 text-[10px] font-black uppercase tracking-widest ml-1">Event
                    Type</label>
                <div class="relative">
                    <select id="eventType"
                        class="w-full px-5 py-3 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-brand-blue font-bold text-slate-700 appearance-none">
                        <option value="exam">Exam 🔴</option>
                        <option value="class">Class 🟢</option>
                        <option value="meeting">Meeting 🟠</option>
                        <option value="holiday">Holiday 🔵</option>
                        <option value="other">Other ⚪</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <label
                    class="block text-slate-500 text-[10px] font-black uppercase tracking-widest ml-1">Description</label>
                <textarea id="eventDesc" rows="3" placeholder="Tell students what to expect..."
                    class="w-full px-5 py-3 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-brand-blue font-bold text-slate-700"></textarea>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" onclick="closeModal()"
                    class="flex-1 py-4 text-slate-400 font-bold hover:text-slate-600 transition">Discard</button>
                <button type="submit"
                    class="flex-[2] bg-brand-blue text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-600 shadow-lg transition">Create
                    Event</button>
            </div>
        </form>
    </div>
</div>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var modal = document.getElementById('eventModal');
        var modalContent = document.getElementById('modalContent');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            selectable: true,
            editable: true, // Drag and drop enabled
            events: 'calendar_logic.php', // Fetch events from backend

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

            // Handle Date Click / Selection
            select: function (info) {
                document.getElementById('eventStart').value = info.startStr;
                document.getElementById('eventEnd').value = info.endStr;
                openModal();
            },

            // Handle Event Click (Delete)
            eventClick: function (info) {
                Swal.fire({
                    title: 'Manage Event',
                    text: `What would you like to do with "${info.event.title}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: 'Keep it',
                    denyButtonText: 'Delete Event',
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#10b981',
                    denyButtonColor: '#ef4444',
                    customClass: {
                        popup: 'rounded-3xl',
                        confirmButton: 'rounded-xl px-6',
                        denyButton: 'rounded-xl px-6'
                    }
                }).then((result) => {
                    if (result.isDenied) {
                        fetch('calendar_logic.php?action=delete&id=' + info.event.id)
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'deleted') {
                                    info.event.remove();
                                    Swal.fire({
                                        title: 'Deleted!',
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false,
                                        customClass: { popup: 'rounded-3xl' }
                                    });
                                }
                            });
                    }
                });
            }
        });

        calendar.render();

        // Helper to open modal with today's date
        window.openModalWithToday = function () {
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('eventStart').value = today;
            document.getElementById('eventEnd').value = today;
            openModal();
        }

        // Modal Logic
        window.openModal = function () {
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

        // Form Submit
        document.getElementById('addEventForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const eventData = {
                title: document.getElementById('eventTitle').value,
                type: document.getElementById('eventType').value,
                description: document.getElementById('eventDesc').value,
                start: document.getElementById('eventStart').value,
                end: document.getElementById('eventEnd').value
            };

            fetch('calendar_logic.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(eventData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        calendar.refetchEvents();
                        closeModal();
                        // Reset form
                        document.getElementById('addEventForm').reset();
                    } else {
                        alert('Error saving event');
                    }
                });
        });
    });
</script>

<?php include_once '../includes/footer.php'; ?>