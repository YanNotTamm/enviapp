<?php
// Migrate script for dev.envirometrolestari.com
// Path: /devapp/migrate.php

// Change to backend directory
chdir(__DIR__ . '/backend');

require 'vendor/autoload.php';

$app = \Config\Services::codeigniter();
$app->initialize();

$migrate = \Config\Services::migrations();

echo "<h1>Database Migration</h1>";
echo "<p>Starting migration...</p>";

try {
    $migrate->latest();
    echo "<p style='color: green;'><strong>✓ Migrations completed successfully!</strong></p>";
    echo "<p>Database tables have been created.</p>";
    echo "<hr>";
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li>Delete this file (migrate.php) for security</li>";
    echo "<li>Run the SQL to create admin user in phpMyAdmin</li>";
    echo "<li>Test the application at <a href='https://dev.envirometrolestari.com/'>https://dev.envirometrolestari.com/</a></li>";
    echo "</ol>";
} catch (\Exception $e) {
    echo "<p style='color: red;'><strong>✗ Migration failed!</strong></p>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr>";
    echo "<h2>Troubleshooting:</h2>";
    echo "<ul>";
    echo "<li>Check database credentials in backend/.env</li>";
    echo "<li>Make sure database 'envirome_devdb' exists</li>";
    echo "<li>Check database user 'envirome_dev' has proper permissions</li>";
    echo "</ul>";
}
 