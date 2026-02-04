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

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Course Calendar & Schedule</h1>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-full">
            <!-- Sidebar / Legend / Tools -->
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="font-bold text-gray-700 mb-4">Quick Add Event</h2>
                    <p class="text-xs text-gray-500 mb-4">Click on any date in the calendar to add an event, or drag to
                        select a range.</p>

                    <button onclick="openModalWithToday()"
                        class="w-full bg-brand-blue text-white py-2 rounded-lg font-bold hover:bg-blue-600 transition shadow-md flex items-center justify-center gap-2 mb-4">
                        <i class="fas fa-plus"></i> Add Event
                    </button>

                    <h3 class="font-bold text-gray-700 mb-2 mt-6">Legend</h3>
                    <div class="space-y-2 text-sm">
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

<!-- Add Event Modal -->
<div id="eventModal"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl p-8 w-full max-w-md transform scale-95 transition-transform duration-300"
        id="modalContent">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Event</h2>

        <form id="addEventForm" class="space-y-4">
            <input type="hidden" id="eventStart">
            <input type="hidden" id="eventEnd">

            <div>
                <label class="block text-gray-600 text-sm font-bold mb-2">Event Title</label>
                <input type="text" id="eventTitle" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
            </div>

            <div>
                <label class="block text-gray-600 text-sm font-bold mb-2">Event Type</label>
                <select id="eventType"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue">
                    <option value="exam">Exam 🔴</option>
                    <option value="class">Class 🟢</option>
                    <option value="meeting">Meeting 🟠</option>
                    <option value="holiday">Holiday 🔵</option>
                    <option value="other">Other ⚪</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-600 text-sm font-bold mb-2">Description (Optional)</label>
                <textarea id="eventDesc" rows="2"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue"></textarea>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()"
                    class="px-4 py-2 text-gray-500 hover:text-gray-700">Cancel</button>
                <button type="submit"
                    class="bg-brand-blue text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-600">Save
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

            // Handle Date Click / Selection
            select: function (info) {
                document.getElementById('eventStart').value = info.startStr;
                document.getElementById('eventEnd').value = info.endStr;
                openModal();
            },

            // Handle Event Click (Delete)
            eventClick: function (info) {
                if (confirm("Delete '" + info.event.title + "'?")) {
                    fetch('calendar_logic.php?action=delete&id=' + info.event.id)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'deleted') {
                                info.event.remove();
                            }
                        });
                }
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