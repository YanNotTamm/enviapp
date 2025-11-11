<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ResetDatabase extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:reset';
    protected $description = 'Reset database by dropping all tables';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        
        CLI::write('Resetting database...', 'yellow');
        
        // Disable foreign key checks
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        
        // Get all tables
        $tables = $db->listTables();
        
        // Drop all tables except migrations
        foreach ($tables as $table) {
            if ($table !== 'migrations') {
                CLI::write("Dropping table: {$table}", 'red');
                $db->query("DROP TABLE IF EXISTS {$table}");
            }
        }
        
        // Clear migrations table
        $db->table('migrations')->truncate();
        
        // Re-enable foreign key checks
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
        
        CLI::write('Database reset completed!', 'green');
        CLI::write('Now run: php spark migrate', 'green');
    }
}