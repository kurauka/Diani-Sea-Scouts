<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';

// 1. Stock Summary Stats
$statsStmt = $database->query("
    SELECT 
        COUNT(*) as total_items,
        SUM(total_quantity) as total_stock,
        SUM(available_quantity) as total_available,
        SUM(CASE WHEN status = 'Under Repair' THEN 1 ELSE 0 END) as items_under_repair,
        SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as items_lost
    FROM equipment
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// 2. Category Distribution
$catStmt = $database->query("
    SELECT c.name, COUNT(e.id) as count
    FROM equipment_categories c
    LEFT JOIN equipment e ON c.id = e.category_id
    GROUP BY c.id
");
$catDist = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Low Stock Alerts (Available < 20% of Total or Available == 0)
$lowStockStmt = $database->query("
    SELECT name, available_quantity, total_quantity 
    FROM equipment 
    WHERE (available_quantity / total_quantity) < 0.2 OR available_quantity = 0
    ORDER BY available_quantity ASC
");
$lowStock = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Usage Analytics (Top 5 most borrowed items)
$usageStmt = $database->query("
    SELECT e.name, COUNT(b.id) as borrow_count
    FROM equipment e
    JOIN borrowing_records b ON e.id = b.equipment_id
    GROUP BY e.id
    ORDER BY borrow_count DESC
    LIMIT 5
");
$usage = $usageStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once '../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="mb-10 animate-fade-in-down">
            <span class="text-xs font-bold text-brand-blue uppercase tracking-widest mb-2 block">System
                Intelligence</span>
            <h1
                class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-brand-blue">
                Inventory Analytics
            </h1>
            <p class="text-gray-500 mt-2 text-lg">Inventory levels, trends, and logistics performance.</p>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-lg">
                <h3 class="text-gray-500 text-xs font-bold uppercase mb-2">Total Items</h3>
                <p class="text-3xl font-extrabold text-brand-blue">
                    <?php echo $stats['total_items']; ?>
                </p>
            </div>
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-lg">
                <h3 class="text-gray-500 text-xs font-bold uppercase mb-2">Total Stock</h3>
                <p class="text-3xl font-extrabold text-gray-700">
                    <?php echo $stats['total_stock'] ?: 0; ?>
                </p>
            </div>
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-lg">
                <h3 class="text-gray-500 text-xs font-bold uppercase mb-2">Available Now</h3>
                <p class="text-3xl font-extrabold text-green-600">
                    <?php echo $stats['total_available'] ?: 0; ?>
                </p>
            </div>
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-lg">
                <h3 class="text-gray-500 text-xs font-bold uppercase mb-2">Under Repair</h3>
                <p class="text-3xl font-extrabold text-orange-500">
                    <?php echo $stats['items_under_repair'] ?: 0; ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            <!-- Low Stock Alerts -->
            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-lg">
                <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-red-500"></i> Low Stock Alerts
                </h3>
                <div class="space-y-4">
                    <?php if (empty($lowStock)): ?>
                        <p class="text-gray-400 text-sm">All items are well stocked.</p>
                    <?php endif; ?>
                    <?php foreach ($lowStock as $item): ?>
                        <div class="flex justify-between items-center p-4 bg-red-50 rounded-2xl border border-red-100">
                            <div>
                                <p class="text-sm font-bold text-red-800">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </p>
                                <p class="text-xs text-red-600">
                                    <?php echo $item['available_quantity']; ?> left in stock
                                </p>
                            </div>
                            <span
                                class="text-xs font-bold bg-white text-red-600 px-3 py-1 rounded-full shadow-sm">CRITICAL</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Used Equipment -->
            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-lg">
                <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-chart-line text-brand-blue"></i> Most Used Gear
                </h3>
                <div class="space-y-4">
                    <?php foreach ($usage as $item): ?>
                        <div>
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-gray-700">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </span>
                                <span class="text-brand-blue">
                                    <?php echo $item['borrow_count']; ?> times
                                </span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-brand-blue"
                                    style="width: <?php echo ($usage[0]['borrow_count'] > 0) ? ($item['borrow_count'] / $usage[0]['borrow_count'] * 100) : 0; ?>%">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 bg-white rounded-3xl p-8 border border-gray-100 shadow-lg h-80">
                <h3 class="font-bold text-gray-800 mb-4">Category Distribution</h3>
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="lg:col-span-2 bg-white rounded-3xl p-8 border border-gray-100 shadow-lg h-80">
                <h3 class="font-bold text-gray-800 mb-4">Maintenance Cost Trends (Simulated)</h3>
                <canvas id="costChart"></canvas>
            </div>
        </div>
    </main>
</div>

<script>
    // Category Distribution Chart
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($catDist, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($catDist, 'count')); ?>,
                    backgroundColor: ['#3B82F6', '#A855F7', '#EF4444', '#F59E0B'],
                    borderWidth: 0
            }]
        },
    options: {
        responsive: true,
            maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
    }
    });

    // Simulated Cost Chart
    const ctxCost = document.getElementById('costChart').getContext('2d');
    new Chart(ctxCost, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Repair Costs (KES)',
                data: [500, 1200, 800, 2500, 1500, 3000],
                borderColor: '#3B82F6',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.05)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?php include_once '../includes/footer.php'; ?>