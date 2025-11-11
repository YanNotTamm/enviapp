<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FreshDatabase extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:fresh';
    protected $description = 'Drop all tables, run migrations and seeders (WARNING: This will delete all data!)';

    public function run(array $params)
    {
        CLI::write('WARNING: This will delete all existing data!', 'red');
        $confirm = CLI::prompt('Are you sure you want to continue?', ['y', 'n']);
        
        if ($confirm !== 'y') {
            CLI::write('Operation cancelled.', 'yellow');
            return;
        }

        CLI::newLine();
        CLI::write('Starting fresh database setup...', 'yellow');
        CLI::newLine();

        // Rollback all migrations
        CLI::write('Rolling back all migrations...', 'cyan');
        $migrate = \Config\Services::migrations();
        
        try {
            $migrate->setNamespace(null)->regress(0);
            CLI::write('Rollback completed successfully!', 'green');
        } catch (\Exception $e) {
            CLI::error('Rollback failed: ' . $e->getMessage());
            return;
        }

        CLI::newLine();

        // Run migrations
        CLI::write('Running migrations...', 'cyan');
        
        try {
            $migrate->latest();
            CLI::write('Migrations completed successfully!', 'green');
        } catch (\Exception $e) {
            CLI::error('Migration failed: ' . $e->getMessage());
            return;
        }

        CLI::newLine();

        // Run seeders
        CLI::write('Running seeders...', 'cyan');
        $seeder = \Config\Database::seeder();
        
        try {
            $seeder->call('MainSeeder');
            CLI::write('Seeders completed successfully!', 'green');
        } catch (\Exception $e) {
            CLI::error('Seeding failed: ' . $e->getMessage());
            return;
        }

        CLI::newLine();
        CLI::write('Fresh database setup completed!', 'green');
        CLI::newLine();
        CLI::write('Default users created:', 'yellow');
        CLI::write('  - superadmin@envindo.com (password: password123)', 'white');
        CLI::write('  - adminkeuangan@envindo.com (password: password123)', 'white');
        CLI::write('  - user1@ptmitra.com (password: password123)', 'white');
        CLI::write('  - user2@ptindustri.com (password: password123)', 'white');
        CLI::newLine();
    }
}
