<?php
// migrate.php - Run this once to setup your Aiven database
require_once 'config/db.php';

echo "<h2>Diani Sea Scouts - Database Migration Tool</h2>";

try {
    $sql = file_get_contents('aiven_setup.sql');
    if (!$sql) {
        die("Error: Could not find aiven_setup.sql");
    }

    // Execute the SQL
    $database->exec($sql);

    echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS: Database tables created and Admin account initialized!</p>";
    echo "<p>You can now go to your <a href='auth/login.php'>Login Page</a> and sign in.</p>";
    echo "<p><strong>Important:</strong> After you are logged in, please delete this file (migrate.php) from your GitHub for security.</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: migration failed.</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>