<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
require_once '../models/Equipment.php';

$equipmentModel = new Equipment($database);
$stmt = $equipmentModel->readAll();
$allEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for the dropdown
$categoriesStmt = $database->query("SELECT * FROM equipment_categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <!-- Hero Section -->
        <div class="mb-10 flex justify-between items-end animate-fade-in-down">
            <div>
                <span class="text-xs font-bold text-brand-blue uppercase tracking-widest mb-2 block">Inventory
                    Management</span>
                <h1
                    class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-brand-blue">
                    Equipment Store
                </h1>
                <p class="text-gray-500 mt-2 text-lg">Manage all scout gear, marine tools, and uniforms.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="toggleModal('addEquipmentModal')"
                    class="bg-brand-blue hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 shadow-lg flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Equipment
                </button>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-lg">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-boxes text-brand-blue"></i> Stock Inventory
                </h3>
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search equipment..."
                        class="bg-white border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue w-64">
                    <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left" id="equipmentTable">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold tracking-wider">
                        <tr>
                            <th class="p-4 pl-6">Equipment</th>
                            <th class="p-4">Category</th>
                            <th class="p-4">Stock</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 text-center">Condition</th>
                            <th class="p-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($allEquipment as $item): ?>
                            <tr class="hover:bg-blue-50/30 transition duration-150">
                                <td class="p-4 pl-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center font-bold text-lg text-brand-blue">
                                            <i class="fas fa-tools"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 font-mono">
                                                <?php echo htmlspecialchars($item['serial_number'] ?: 'No S/N'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-semibold">
                                        <?php echo htmlspecialchars($item['category_name']); ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="text-sm">
                                        <span class="font-bold text-gray-800">
                                            <?php echo $item['available_quantity']; ?>
                                        </span>
                                        <span class="text-gray-400">/
                                            <?php echo $item['total_quantity']; ?>
                                        </span>
                                    </div>
                                    <div class="w-24 h-1.5 bg-gray-100 rounded-full mt-1">
                                        <div class="h-full bg-brand-blue rounded-full"
                                            style="width: <?php echo ($item['total_quantity'] > 0) ? ($item['available_quantity'] / $item['total_quantity'] * 100) : 0; ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <?php
                                    $statusClass = 'bg-gray-100 text-gray-600';
                                    if ($item['status'] == 'Available')
                                        $statusClass = 'bg-green-100 text-green-700';
                                    if ($item['status'] == 'In Use')
                                        $statusClass = 'bg-blue-100 text-blue-700';
                                    if ($item['status'] == 'Under Repair')
                                        $statusClass = 'bg-yellow-100 text-yellow-700';
                                    if ($item['status'] == 'Lost')
                                        $statusClass = 'bg-red-100 text-red-700';
                                    ?>
                                    <span
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide <?php echo $statusClass; ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php
                                    $condClass = 'text-gray-400';
                                    if ($item['condition'] == 'New')
                                        $condClass = 'text-green-500';
                                    if ($item['condition'] == 'Good')
                                        $condClass = 'text-blue-500';
                                    if ($item['condition'] == 'Worn')
                                        $condClass = 'text-yellow-500';
                                    if ($item['condition'] == 'Damaged')
                                        $condClass = 'text-red-500';
                                    ?>
                                    <div class="flex items-center justify-center gap-1">
                                        <i class="fas fa-circle text-[8px] <?php echo $condClass; ?>"></i>
                                        <span class="text-xs font-medium text-gray-700">
                                            <?php echo $item['condition']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                            title="View QR">
                                            <i class="fas fa-qrcode"></i>
                                        </button>
                                        <button class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Add Equipment Modal -->
<div id="addEquipmentModal"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl animate-fade-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-xl">Register New Equipment</h3>
            <button onclick="toggleModal('addEquipmentModal')"
                class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form action="save_equipment_logic.php" method="POST" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="action" value="create">
            <div class="col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Item Name</label>
                <input type="text" name="name" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue"
                    placeholder="e.g. Kayak Paddle">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Category</label>
                <select name="category_id" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Serial Number / Tag</label>
                <input type="text" name="serial_number"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue"
                    placeholder="e.g. SN-12345">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Total Quantity</label>
                <input type="number" name="total_quantity" value="1" min="1" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Initial Condition</label>
                <select name="condition"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                    <option value="New">New</option>
                    <option value="Good" selected>Good</option>
                    <option value="Worn">Worn</option>
                    <option value="Damaged">Damaged</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-blue"
                    placeholder="Additional details about the item..."></textarea>
            </div>
            <div class="col-span-2 flex justify-end gap-3 mt-4">
                <button type="button" onclick="toggleModal('addEquipmentModal')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition-all">Cancel</button>
                <button type="submit"
                    class="bg-brand-blue hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg">Save
                    Equipment</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.toggle('hidden');
    }

    // Simple search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll('#equipmentTable tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });
</script>

<?php include_once '../includes/footer.php'; ?>