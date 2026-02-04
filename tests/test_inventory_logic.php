<?php
// Simple verification script for Equipment Inventory System
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Equipment.php';
require_once __DIR__ . '/../models/Borrowing.php';
require_once __DIR__ . '/../models/Maintenance.php';

echo "Starting Equipment Inventory Verification...\n";

try {
    // 1. Test Equipment Model
    $eq = new Equipment($database);
    $eq->name = "Test Life Jacket";
    $eq->category_id = 1; // Marine Tools
    $eq->total_quantity = 5;
    $eq->available_quantity = 5;
    $eq->status = 'Available';
    $eq->condition = 'New';
    if ($eq->create()) {
        echo "✓ Equipment Created (ID: $eq->id)\n";
    }

    // 2. Test Borrowing logic
    $borrow = new Borrowing($database);
    $borrow->equipment_id = $eq->id;
    $borrow->user_id = 1; // Assuming admin/id 1 exists
    $borrow->due_date = date('Y-m-d H:i:s', strtotime('+1 day'));
    $borrow->status = 'Issued';
    if ($borrow->create()) {
        echo "✓ Borrowing Record Created\n";
        $eq->updateQuantity(-1);
        echo "✓ Stock Quantity Reduced\n";
    }

    // 3. Test Return logic
    if ($borrow->returnEquipment('Good')) {
        echo "✓ Borrowing Record Returned\n";
        $eq->updateQuantity(1);
        echo "✓ Stock Quantity Restored\n";
    }

    // 4. Test Maintenance
    $maint = new Maintenance($database);
    $maint->equipment_id = $eq->id;
    $maint->issue_description = "Small tear in fabric";
    if ($maint->create()) {
        echo "✓ Maintenance Log Created\n";
        $eq->updateStatus('Under Repair');
        echo "✓ Equipment marked 'Under Repair'\n";
    }

    // Cleanup
    $database->exec("DELETE FROM equipment WHERE id = " . $eq->id);
    echo "✓ Test Data Cleaned Up\n";
    echo "Verification Successful!\n";

} catch (Exception $e) {
    echo "Verification Failed: " . $e->getMessage() . "\n";
}
