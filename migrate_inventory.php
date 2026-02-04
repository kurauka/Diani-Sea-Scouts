<?php
require_once __DIR__ . '/config/db.php';

echo "Applying Migration: inventory_setup.sql...\n";

try {
    $sql = file_get_contents(__DIR__ . '/inventory_setup.sql');

    // Split the SQL into individual statements
    // This is a simple split, might not handle complex cases but should be fine for this script
    $statements = explode(';', $sql);

    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if (!empty($trimmed)) {
            $database->exec($trimmed);
        }
    }

    echo "Migration Applied Successfully!\n";
} catch (Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
