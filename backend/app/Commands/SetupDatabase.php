<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SetupDatabase extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:setup';
    protected $description = 'Setup database by running migrations and seeders';

    public function run(array $params)
    {
        CLI::write('Starting database setup...', 'yellow');
        CLI::newLine();

        // Run migrations
        CLI::write('Running migrations...', 'cyan');
        $migrate = \Config\Services::migrations();
        
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
        CLI::write('Database setup completed!', 'green');
        CLI::newLine();
        CLI::write('Default users created:', 'yellow');
        CLI::write('  - superadmin@envindo.com (password: password123)', 'white');
        CLI::write('  - adminkeuangan@envindo.com (password: password123)', 'white');
        CLI::write('  - user1@ptmitra.com (password: password123)', 'white');
        CLI::write('  - user2@ptindustri.com (password: password123)', 'white');
        CLI::newLine();
    }
}
