<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
        
        $data = [
            [
                'username' => 'superadmin',
                'email' => 'superadmin@envindo.com',
                'password' => $passwordHash,
                'role' => 'superadmin',
                'nama_lengkap' => 'Super Administrator',
                'nama_perusahaan' => 'PT Enviro Metrolestari',
                'alamat_perusahaan' => 'Jl. Lingkar Luar Barat No. 88, Jakarta Barat',
                'telepon' => '021-55551111',
                'email_verified' => true,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'envipoin' => 0,
                'masa_berlaku' => null,
                'layanan_aktif' => 'EnviReg',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'adminkeuangan',
                'email' => 'adminkeuangan@envindo.com',
                'password' => $passwordHash,
                'role' => 'admin_keuangan',
                'nama_lengkap' => 'Budi Santoso',
                'nama_perusahaan' => 'PT Enviro Metrolestari',
                'alamat_perusahaan' => 'Jl. Lingkar Luar Barat No. 88, Jakarta Barat',
                'telepon' => '021-55552222',
                'email_verified' => true,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'envipoin' => 0,
                'masa_berlaku' => null,
                'layanan_aktif' => 'EnviReg',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'user1',
                'email' => 'user1@ptmitra.com',
                'password' => $passwordHash,
                'role' => 'user',
                'nama_lengkap' => 'John Doe',
                'nama_perusahaan' => 'PT Mitra Sejahtera',
                'alamat_perusahaan' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'telepon' => '021-55553333',
                'email_verified' => true,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'envipoin' => 450,
                'masa_berlaku' => date('Y-m-d', strtotime('+6 months')),
                'layanan_aktif' => 'Envi+',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'user2',
                'email' => 'user2@ptindustri.com',
                'password' => $passwordHash,
                'role' => 'user',
                'nama_lengkap' => 'Jane Smith',
                'nama_perusahaan' => 'PT Industri Makmur',
                'alamat_perusahaan' => 'Jl. Gatot Subroto No. 456, Jakarta Selatan',
                'telepon' => '021-55554444',
                'email_verified' => true,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'envipoin' => 750,
                'masa_berlaku' => date('Y-m-d', strtotime('+12 months')),
                'layanan_aktif' => 'EnviPro',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}