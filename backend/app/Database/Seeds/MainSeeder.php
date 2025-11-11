<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MainSeeder extends Seeder
{
    public function run()
    {
        // Call individual seeders in order
        $this->call('LayananSeeder');
        $this->call('UserSeeder');
        $this->call('TransaksiLayananSeeder');
        $this->call('RiwayatPengangkutanSeeder');
        $this->call('InvoiceSeeder');
        $this->call('DokumenKerjasamaSeeder');
        $this->call('ManifestElektronikSeeder');
    }
}