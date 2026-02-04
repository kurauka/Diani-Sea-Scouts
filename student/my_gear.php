<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
require_once '../models/Borrowing.php';
require_once '../models/Equipment.php';

$borrowingModel = new Borrowing($database);
$historyStmt = $borrowingModel->readByUser($_SESSION['user_id']);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available equipment for requests
$equipmentModel = new Equipment($database);
$availStmt = $equipmentModel->readAll();
$availableItems = $availStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="mb-10 animate-fade-in-down">
            <span class="text-xs font-bold text-brand-blue uppercase tracking-widest mb-2 block">Student Portal</span>
            <h1
                class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-brand-blue">
                My Gear
            </h1>
            <p class="text-gray-500 mt-2 text-lg">Track your borrowed equipment and return deadlines.</p>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'request_sent'): ?>
                <div
                    class="mt-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-2xl flex items-center gap-3 animate-fade-in">
                    <i class="fas fa-check-circle text-xl"></i>
                    <p class="font-bold text-sm">Request submitted successfully! It is now pending instructor approval.</p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div
                    class="mt-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded-2xl flex items-center gap-3 animate-fade-in">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <p class="font-bold text-sm">Failed to submit request. Please try again.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Borrowing History -->
            <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-lg">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-bold text-gray-800">Borrowing History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold tracking-wider">
                            <tr>
                                <th class="p-4 pl-6">Equipment</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Due Date</th>
                                <th class="p-4">Return Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="4" class="p-10 text-center text-gray-400">You haven't borrowed any gear
                                        yet.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($history as $row): ?>
                                <tr class="hover:bg-blue-50/30 transition duration-150">
                                    <td class="p-4 pl-6">
                                        <p class="font-bold text-gray-800">
                                            <?php echo htmlspecialchars($row['equipment_name']); ?>
                                        </p>
                                        <p class="text-[10px] text-gray-400">Issued by:
                                            <?php echo htmlspecialchars($row['instructor_name']); ?>
                                        </p>
                                    </td>
                                    <td class="p-4">
                                        <?php
                                        $statusClass = 'bg-gray-100 text-gray-600';
                                        if ($row['status'] == 'Pending')
                                            $statusClass = 'bg-yellow-100 text-yellow-700';
                                        if ($row['status'] == 'Issued')
                                            $statusClass = 'bg-blue-100 text-blue-700';
                                        if ($row['status'] == 'Returned')
                                            $statusClass = 'bg-green-100 text-green-700';
                                        if ($row['status'] == 'Overdue')
                                            $statusClass = 'bg-red-100 text-red-700';
                                        ?>
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase <?php echo $statusClass; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($row['due_date'])); ?>
                                    </td>
                                    <td class="p-4 text-sm text-gray-500">
                                        <?php echo $row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Available Gear for Reference -->
            <div class="space-y-6">
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-lg">
                    <h3 class="font-bold text-gray-800 mb-4">Available Gear</h3>
                    <p class="text-xs text-gray-500 mb-4">Items available in the store for requesting.</p>
                    <div class="space-y-3">
                        <?php foreach (array_slice($availableItems, 0, 5) as $item): ?>
                            <div
                                class="flex items-center justify-between p-3 bg-blue-50/50 rounded-xl border border-blue-100/50">
                                <span class="text-xs font-bold text-gray-700">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </span>
                                <span class="text-[10px] bg-white px-2 py-0.5 rounded shadow-sm">
                                    <?php echo $item['available_quantity']; ?> left
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button onclick="document.getElementById('requestModal').classList.remove('hidden')"
                        class="w-full mt-6 bg-brand-blue text-white font-bold py-3 rounded-xl shadow-md hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-plus-circle"></i> Request Equipment
                    </button>
                </div>

                <div class="bg-orange-50 rounded-3xl p-6 border border-orange-100 shadow-sm">
                    <h4 class="text-orange-800 font-bold mb-2 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i> Scout Rule
                    </h4>
                    <p class="text-xs text-orange-700 leading-relaxed">
                        Always return gear in the same or better condition than when you borrowed it. Report any damages
                        immediately!
                    </p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Request Modal -->
<div id="requestModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-zoom-in">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-800">Request New Gear</h3>
            <button onclick="document.getElementById('requestModal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="submit_request.php" method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-black uppercase text-gray-400 tracking-widest mb-2">Select
                    Equipment</label>
                <select name="equipment_id" required
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-blue outline-none transition">
                    <?php foreach ($availableItems as $item): ?>
                        <option value="<?php echo $item['id']; ?>">
                            <?php echo htmlspecialchars($item['name']); ?> (<?php echo $item['available_quantity']; ?>
                            available)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-gray-400 tracking-widest mb-2">Return Deadline
                    (Optional)</label>
                <input type="date" name="due_date"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-blue outline-none transition">
            </div>
            <div>
                <label
                    class="block text-xs font-black uppercase text-gray-400 tracking-widest mb-2">Purpose/Notes</label>
                <textarea name="notes" rows="3" placeholder="Why do you need this?"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-blue outline-none transition"></textarea>
            </div>
            <button type="submit"
                class="w-full bg-brand-blue text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-blue-200 transition-all">
                Submit Request
            </button>
        </form>
    </div>
</div>

<style>
    @keyframes zoom-in {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .animate-zoom-in {
        animation: zoom-in 0.2s ease-out;
    }
</style>

<?php include_once '../includes/footer.php'; ?>