<?php
session_start();
// Allowed roles: Admin, Instructor, Maintenance Officer
$allowedRoles = ['admin', 'instructor', 'maintenance_officer'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
require_once '../models/Maintenance.php';
require_once '../models/Equipment.php';

$maintenanceModel = new Maintenance($database);
$logsStmt = $maintenanceModel->readAll();
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

$equipmentModel = new Equipment($database);
$eqStmt = $equipmentModel->readAll();
$allEquipment = $eqStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="mb-10 flex justify-between items-end animate-fade-in-down">
            <div>
                <span class="text-xs font-bold text-orange-600 uppercase tracking-widest mb-2 block">Maintenance &
                    Repairs</span>
                <h1
                    class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-orange-600">
                    Repair Logbook
                </h1>
                <p class="text-gray-500 mt-2 text-lg">Track gear health, repair costs, and maintenance status.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="toggleModal('reportIssueModal')"
                    class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 shadow-lg flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> Report Issue
                </button>
            </div>
        </div>

        <!-- Maintenance Logs -->
        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-lg">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-tools text-orange-600"></i> Active & Past Repairs
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold tracking-wider">
                        <tr>
                            <th class="p-4 pl-6">Equipment</th>
                            <th class="p-4">Reported By</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Cost</th>
                            <th class="p-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-orange-50/30 transition duration-150">
                                <td class="p-4 pl-6">
                                    <p class="font-bold text-gray-800">
                                        <?php echo htmlspecialchars($log['equipment_name']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 italic">
                                        <?php echo htmlspecialchars(substr($log['issue_description'], 0, 50)); ?>...
                                    </p>
                                </td>
                                <td class="p-4">
                                    <p class="text-sm text-gray-700">
                                        <?php echo htmlspecialchars($log['reporter_name']); ?>
                                    </p>
                                    <p class="text-[10px] text-gray-400">
                                        <?php echo date('M d, Y', strtotime($log['reported_at'])); ?>
                                    </p>
                                </td>
                                <td class="p-4">
                                    <?php
                                    $statusClass = 'bg-gray-100 text-gray-600';
                                    if ($log['status'] == 'Reported')
                                        $statusClass = 'bg-red-100 text-red-700';
                                    if ($log['status'] == 'In Progress')
                                        $statusClass = 'bg-yellow-100 text-yellow-700';
                                    if ($log['status'] == 'Repaired')
                                        $statusClass = 'bg-green-100 text-green-700';
                                    ?>
                                    <span
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase <?php echo $statusClass; ?>">
                                        <?php echo $log['status']; ?>
                                    </span>
                                </td>
                                <td class="p-4 font-mono text-sm text-gray-600">
                                    KES
                                    <?php echo number_format($log['cost'], 2); ?>
                                </td>
                                <td class="p-4 text-center">
                                    <button
                                        onclick="openRepairUpdate(<?php echo $log['id']; ?>, '<?php echo $log['status']; ?>')"
                                        class="p-2 text-brand-blue hover:bg-blue-50 rounded-lg transition-colors">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Report Issue Modal -->
<div id="reportIssueModal"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-xl">Report Damage/Issue</h3>
        </div>
        <form action="maintenance_logic.php" method="POST" class="p-8 space-y-6">
            <input type="hidden" name="action" value="report">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Select Equipment</label>
                <select name="equipment_id" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-orange-600">
                    <?php foreach ($allEquipment as $eq): ?>
                        <option value="<?php echo $eq['id']; ?>">
                            <?php echo htmlspecialchars($eq['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Issue Description</label>
                <textarea name="issue_description" required rows="4"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-orange-600"
                    placeholder="What is wrong with the equipment?"></textarea>
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="toggleModal('reportIssueModal')"
                    class="bg-gray-100 text-gray-600 font-bold py-3 px-8 rounded-xl">Cancel</button>
                <button type="submit" class="bg-orange-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg">Submit
                    Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Repair Modal -->
<div id="updateRepairModal"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-xl">Update Repair Status</h3>
        </div>
        <form action="maintenance_logic.php" method="POST" class="p-8 space-y-6">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="log_id" id="update_log_id">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Status</label>
                <select name="status" id="update_status"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                    <option value="Reported">Reported</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Repaired">Repaired</option>
                    <option value="Decommissioned">Decommissioned</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Repair Details</label>
                <textarea name="repair_details" rows="3"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue"></textarea>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Cost (KES)</label>
                <input type="number" step="0.01" name="cost"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue"
                    value="0.00">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="toggleModal('updateRepairModal')"
                    class="bg-gray-100 text-gray-600 font-bold py-3 px-6 rounded-xl">Cancel</button>
                <button type="submit" class="bg-brand-blue text-white font-bold py-3 px-6 rounded-xl shadow-lg">Save
                    Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.toggle('hidden');
    }

    function openRepairUpdate(id, status) {
        document.getElementById('update_log_id').value = id;
        document.getElementById('update_status').value = status;
        toggleModal('updateRepairModal');
    }
</script>

<?php include_once '../includes/footer.php'; ?>