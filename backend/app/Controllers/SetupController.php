<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class SetupController extends Controller
{
    public function resetDatabase()
    {
        $db = \Config\Database::connect();
        
        // Get all tables
        $tables = $db->listTables();
        
        // Drop all tables
        foreach ($tables as $table) {
            if ($table !== 'migrations') {
                $db->query("DROP TABLE IF EXISTS {$table}");
            }
        }
        
        // Clear migrations table
        $db->table('migrations')->truncate();
        
        echo "Database reset completed.\n";
        echo "Now run: php spark migrate\n";
    }
}