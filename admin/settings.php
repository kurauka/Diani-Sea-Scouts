<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maintenance = isset($_POST['maintenance_mode']) ? '1' : '0';
    $registration = isset($_POST['allow_registration']) ? '1' : '0';

    $stmt = $database->prepare("UPDATE settings SET setting_value = :val WHERE setting_key = :key");
    $stmt->execute([':val' => $maintenance, ':key' => 'maintenance_mode']);
    $stmt->execute([':val' => $registration, ':key' => 'allow_registration']);

    $success_msg = "Settings updated successfully.";
}

// Fetch Current Settings
$stmt = $database->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['maintenance_mode' => '0', ...]
?>
<?php include_once '../includes/header.php'; ?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden text-gray-800 font-sans">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">

        <div class="mb-8">
            <h1
                class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-brand-blue">
                System Configuration</h1>
            <p class="text-gray-500 mt-1">Manage global system behavior.</p>
        </div>

        <?php if (isset($success_msg)): ?>
            <div
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-xl relative">

                <form method="POST">
                    <!-- Maintenance Mode -->
                    <div class="flex items-center justify-between mb-8 pb-8 border-b border-gray-100">
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                                <i class="fas fa-tools text-orange-500"></i> Maintenance Mode
                            </h3>
                            <p class="text-gray-500 text-sm mt-1 max-w-sm">
                                Prevent all users (except Admins) from logging in. Use this when performing system
                                updates.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="maintenance_mode" class="sr-only peer" <?php echo ($settings['maintenance_mode'] == '1') ? 'checked' : ''; ?>>
                            <div
                                class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-orange-500">
                            </div>
                        </label>
                    </div>

                    <!-- Registration Control -->
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                                <i class="fas fa-user-plus text-brand-blue"></i> Allow Registration
                            </h3>
                            <p class="text-gray-500 text-sm mt-1 max-w-sm">
                                Allow new students via the registration page. Turn off to close enrollment.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_registration" class="sr-only peer" <?php echo ($settings['allow_registration'] == '1') ? 'checked' : ''; ?>>
                            <div
                                class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-brand-blue">
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="bg-gradient-to-r from-brand-dark to-brand-blue hover:from-blue-800 hover:to-blue-600 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg transform hover:scale-105">
                            Save Changes
                        </button>
                    </div>
                </form>

            </div>

            <!-- Info Panel -->
            <div
                class="bg-gradient-to-br from-brand-pale to-blue-50 rounded-3xl p-8 border border-blue-100 shadow-xl flex flex-col justify-center items-center text-center">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-6 shadow-sm">
                    <i class="fas fa-shield-alt text-4xl text-brand-blue"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Secure Command</h3>
                <p class="text-gray-500 text-sm">
                    Changes made here take effect immediately across the entire platform. Proceed with caution.
                </p>
            </div>
        </div>

    </main>
</div>

<?php include_once '../includes/footer.php'; ?>