<!-- Mobile Header -->
<div
    class="md:hidden bg-white border-b border-gray-200 p-4 flex justify-between items-center fixed top-0 left-0 right-0 z-50 shadow-sm">
    <div class="flex items-center gap-2">
        <div
            class="w-8 h-8 bg-gradient-to-tr from-brand-dark to-brand-blue rounded-lg flex items-center justify-center transform rotate-3">
            <i class="fas fa-anchor text-white text-xs"></i>
        </div>
        <span class="font-bold text-gray-800 text-lg">Diani Scouts</span>
    </div>
    <button id="mobileMenuBtn" class="text-gray-600 hover:text-brand-blue focus:outline-none">
        <i class="fas fa-bars text-2xl"></i>
    </button>
</div>

<!-- Spacer for Mobile Header -->
<div class="md:hidden h-16 w-full"></div>

<!-- Overlay -->
<div id="sidebarOverlay"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden transition-opacity duration-300 opacity-0"></div>

<!-- Sidebar Container -->
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-200 min-h-screen flex flex-col flex-shrink-0 transform -translate-x-full md:translate-x-0 md:static md:flex transition-transform duration-300 ease-in-out shadow-2xl md:shadow-none">
    <!-- Logo Area (Hidden on Mobile as it's in header) -->
    <div class="p-8 flex items-center gap-3 hidden md:flex">
        <div
            class="w-10 h-10 bg-gradient-to-tr from-brand-dark to-brand-blue rounded-xl flex items-center justify-center shadow-lg transform rotate-3">
            <i class="fas fa-anchor text-white text-lg"></i>
        </div>
        <div>
            <h1 class="font-bold text-brand-dark text-lg tracking-tight">Diani Scouts</h1>
            <p class="text-xs text-brand-light font-medium tracking-wide">EXAM PORTAL</p>
        </div>
    </div>

    <!-- Mobile Close Button -->
    <div class="md:hidden p-4 flex justify-end">
        <button id="closeSidebarBtn" class="text-gray-500 hover:text-red-500">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 space-y-1 mt-4">
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Main Menu</p>

        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        function isActive($page, $current)
        {
            return $page === $current ? 'bg-brand-pale text-brand-dark font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-brand-blue';
        }
        ?>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'instructor'): ?>
            <a href="../instructor/dashboard.php"
                class="<?php echo isActive('dashboard.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-th-large w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Dashboard</span>
            </a>
            <a href="../instructor/exam_builder.php"
                class="<?php echo isActive('exam_builder.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-plus-circle w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Create Exam</span>
            </a>
            <a href="../instructor/manage_materials.php"
                class="<?php echo isActive('manage_materials.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-book w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Study Materials</span>
            </a>
            <a href="../instructor/communications.php"
                class="<?php echo isActive('communications.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-bullhorn w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Communications</span>
            </a>
            <a href="../instructor/calendar.php"
                class="<?php echo isActive('calendar.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-calendar-alt w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Calendar</span>
            </a>
            <a href="../instructor/students_list.php"
                class="<?php echo isActive('students_list.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-user-graduate w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Students</span>
            </a>
            <a href="../instructor/attendance.php"
                class="<?php echo isActive('attendance.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-calendar-check w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Attendance</span>
            </a>
            <a href="../instructor/review_activities.php"
                class="<?php echo isActive('review_activities.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-hiking w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Activity Approvals</span>
            </a>
            <a href="../instructor/inventory.php"
                class="<?php echo isActive('inventory.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-warehouse w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Equipment Store</span>
            </a>

        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <a href="../student/dashboard.php"
                class="<?php echo isActive('dashboard.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-home w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Dashboard</span>
            </a>
            <a href="../student/calendar.php"
                class="<?php echo isActive('calendar.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-calendar-alt w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Calendar</span>
            </a>
            <a href="../student/history.php"
                class="<?php echo isActive('history.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-history w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>History</span>
            </a>
            <a href="../student/analytics.php"
                class="<?php echo isActive('analytics.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-chart-pie w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Analytics</span>
            </a>
            <a href="../student/attendance.php"
                class="<?php echo isActive('attendance.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-user-check w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>My Attendance</span>
            </a>
            <a href="../student/materials.php"
                class="<?php echo isActive('materials.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-book-reader w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Materials</span>
            </a>
            <a href="../student/profile.php"
                class="<?php echo isActive('profile.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-user-circle w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>My Profile</span>
            </a>
            <a href="../student/my_gear.php"
                class="<?php echo isActive('my_gear.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-tools w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>My Gear</span>
            </a>
            <a href="../student/outdoor_activities.php"
                class="<?php echo isActive('outdoor_activities.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-mountain w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Outdoor Activities</span>
            </a>

        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <style>
                .sidebar-admin {
                    background-color: #1a202c;
                    color: #fff;
                }

                .sidebar-admin a:hover {
                    background-color: #2d3748;
                }
            </style>
            <!-- Admin Menu -->
            <a href="../admin/dashboard.php"
                class="<?php echo isActive('dashboard.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-tachometer-alt w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>Command Center</span>
            </a>
            <a href="../admin/users.php"
                class="<?php echo isActive('users.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-users-cog w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>User Management</span>
            </a>
            <a href="../admin/exams_list.php"
                class="<?php echo isActive('exams_list.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-poll w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>Results & Grading</span>
            </a>
            <a href="../admin/settings.php"
                class="<?php echo isActive('settings.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-cogs w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>System Settings</span>
            </a>
            <a href="../admin/communications.php"
                class="<?php echo isActive('communications.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-bullhorn w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>Communications</span>
            </a>
            <a href="../admin/attendance_logs.php"
                class="<?php echo isActive('attendance_logs.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-calendar-check w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>Attendance Logs</span>
            </a>
            <a href="../admin/event_intel.php"
                class="<?php echo isActive('event_intel.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-layer-group w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>Event Intel</span>
            </a>
            <a href="../instructor/review_activities.php"
                class="<?php echo isActive('review_activities.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-hiking w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>Activity Approvals</span>
            </a>
            <div class="pt-4 pb-1">
                <p class="px-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Inventory Systems</p>
            </div>
            <a href="../admin/inventory.php"
                class="<?php echo isActive('inventory.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-boxes w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>Equipment Store</span>
            </a>
            <a href="../admin/maintenance.php"
                class="<?php echo isActive('maintenance.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-tools w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>Maintenance & Repairs</span>
            </a>
            <a href="../admin/inventory_reports.php"
                class="<?php echo isActive('inventory_reports.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-chart-line w-6 group-hover:text-cyan-400 transition-colors"></i>
                <span>Logistics Analytics</span>
            </a>

        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'maintenance_officer'): ?>
            <a href="../admin/maintenance.php"
                class="<?php echo isActive('maintenance.php', $current_page); ?> flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
                <i class="fas fa-tools w-6 group-hover:text-brand-blue transition-colors"></i>
                <span>Maintenance Dashboard</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-4 border-t border-gray-100">
        <a href="../auth/logout.php"
            class="flex items-center px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200">
            <i class="fas fa-sign-out-alt w-6"></i>
            <span class="font-medium">Logout</span>
        </a>
    </div>
</aside>

<script>
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        const isClosed = sidebar.classList.contains('-translate-x-full');
        if (isClosed) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10);
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }
    }

    if (mobileBtn) mobileBtn.addEventListener('click', toggleSidebar);
    if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);
</script>