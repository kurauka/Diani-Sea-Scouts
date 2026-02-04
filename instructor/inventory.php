<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'instructor' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
require_once '../models/Equipment.php';
require_once '../models/Borrowing.php';

$equipmentModel = new Equipment($database);
$borrowingModel = new Borrowing($database);

$allEquipmentStmt = $equipmentModel->readAll();
$allEquipment = $allEquipmentStmt->fetchAll(PDO::FETCH_ASSOC);

$activeBorrowingsStmt = $borrowingModel->readAllActive();
$activeBorrowings = $activeBorrowingsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students for the issue form
$studentsStmt = $database->query("SELECT id, name FROM users WHERE role = 'student' ORDER BY name ASC");
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="mb-10 flex justify-between items-end">
            <div>
                <span class="text-xs font-bold text-purple-600 uppercase tracking-widest mb-2 block">Instructor
                    Tools</span>
                <h1
                    class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-purple-600">
                    Gear Logistics
                </h1>
                <p class="text-gray-500 mt-2 text-lg">Manage borrowing requests and equipment issuance.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="toggleModal('issueEquipmentModal')"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 shadow-lg flex items-center gap-2">
                    <i class="fas fa-handshake"></i> Issue Equipment
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Active Borrowings Table -->
            <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-lg">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-exchange-alt text-purple-600"></i> Active Borrowings
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold tracking-wider">
                            <tr>
                                <th class="p-4 pl-6">Equipment</th>
                                <th class="p-4">Student</th>
                                <th class="p-4">Due Date</th>
                                <th class="p-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($activeBorrowings)): ?>
                                <tr>
                                    <td colspan="4" class="p-10 text-center text-gray-400">No active borrowings found.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($activeBorrowings as $row): ?>
                                <tr class="hover:bg-purple-50/30 transition duration-150">
                                    <td class="p-4 pl-6">
                                        <p class="font-bold text-gray-800">
                                            <?php echo htmlspecialchars($row['equipment_name']); ?>
                                        </p>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-sm font-semibold text-gray-700">
                                            <?php echo htmlspecialchars($row['borrower_name']); ?>
                                        </p>
                                    </td>
                                    <td class="p-4">
                                        <?php
                                        $dueDate = strtotime($row['due_date']);
                                        $isOverdue = $dueDate < time();
                                        ?>
                                        <p
                                            class="text-sm <?php echo $isOverdue ? 'text-red-600 font-bold' : 'text-gray-500'; ?>">
                                            <?php echo date('M d, Y', $dueDate); ?>
                                            <?php if ($isOverdue): ?> (Overdue)
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                    <td class="p-4 text-center">
                                        <button
                                            onclick="prepareReturn(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['equipment_name']); ?>')"
                                            class="bg-green-50 hover:bg-green-100 text-green-700 font-bold py-2 px-4 rounded-lg text-xs transition-all">
                                            Process Return
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stock Glance -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-lg">
                <h3 class="font-bold text-gray-800 mb-6">Equipment Status</h3>
                <div class="space-y-4">
                    <?php foreach (array_slice($allEquipment, 0, 8) as $item): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-2xl">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-tools text-brand-blue"></i>
                                <div>
                                    <p class="text-xs font-bold text-gray-800">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </p>
                                    <p class="text-[10px] text-gray-500">
                                        <?php echo $item['available_quantity']; ?> available
                                    </p>
                                </div>
                            </div>
                            <span
                                class="w-2 h-2 rounded-full <?php echo $item['available_quantity'] > 0 ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Issue Equipment Modal -->
<div id="issueEquipmentModal"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-xl">Issue Equipment</h3>
            <button onclick="toggleModal('issueEquipmentModal')"
                class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form action="inventory_actions.php" method="POST" class="p-8 space-y-6">
            <input type="hidden" name="action" value="issue">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Select Student</label>
                <select name="user_id" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">-- Choose Student --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Select Equipment</label>
                <select name="equipment_id" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">-- Choose Item --</option>
                    <?php foreach ($allEquipment as $item): ?>
                        <?php if ($item['available_quantity'] > 0): ?>
                            <option value="<?php echo $item['id']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?> (
                                <?php echo $item['available_quantity']; ?> left)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Due Date</label>
                <input type="date" name="due_date" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-600"
                    value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="toggleModal('issueEquipmentModal')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition-all">Cancel</button>
                <button type="submit"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg">Issue
                    Gear</button>
            </div>
        </form>
    </div>
</div>

<!-- Return Equipment Modal -->
<div id="returnEquipmentModal"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-xl">Return Equipment</h3>
        </div>
        <form action="inventory_actions.php" method="POST" class="p-8 space-y-6">
            <input type="hidden" name="action" value="return">
            <input type="hidden" name="borrow_id" id="return_borrow_id">
            <p class="text-gray-600">Returning: <span id="return_item_name" class="font-bold text-gray-800"></span></p>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Condition on Return</label>
                <select name="condition_on_return"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600">
                    <option value="New">New</option>
                    <option value="Good" selected>Good</option>
                    <option value="Worn">Worn</option>
                    <option value="Damaged">Damaged</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Additional Notes</label>
                <textarea name="notes" rows="2"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="toggleModal('returnEquipmentModal')"
                    class="bg-gray-100 text-gray-600 font-bold py-3 px-6 rounded-xl">Cancel</button>
                <button type="submit" class="bg-green-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg">Complete
                    Return</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.toggle('hidden');
    }

    function prepareReturn(id, name) {
        document.getElementById('return_borrow_id').value = id;
        document.getElementById('return_item_name').innerText = name;
        toggleModal('returnEquipmentModal');
    }
</script>

<?php include_once '../includes/footer.php'; ?>