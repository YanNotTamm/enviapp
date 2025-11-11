<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LayananSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'kode_layanan' => 'ENVIREG001',
                'nama_layanan' => 'EnviReg - Registrasi Limbah B3',
                'deskripsi' => 'Layanan registrasi dan pendataan limbah B3 secara komprehensif untuk kepatuhan regulasi',
                'harga' => 2500000.00,
                'satuan' => 'perusahaan/tahun',
                'tipe_layanan' => 'EnviReg',
                'durasi_hari' => 365,
                'envipoin_reward' => 100,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'kode_layanan' => 'ENVIPLUS002',
                'nama_layanan' => 'Envi+ - Pengelolaan Limbah Plus',
                'deskripsi' => 'Layanan pengelolaan limbah B3 dengan sistem manajemen terintegrasi dan pelaporan digital',
                'harga' => 4500000.00,
                'satuan' => 'perusahaan/tahun',
                'tipe_layanan' => 'Envi+',
                'durasi_hari' => 365,
                'envipoin_reward' => 200,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'kode_layanan' => 'ENVIPRO003',
                'nama_layanan' => 'EnviPro - Solusi Profesional Limbah',
                'deskripsi' => 'Layanan komprehensif untuk industri dengan volume limbah besar dan kebutuhan khusus',
                'harga' => 8500000.00,
                'satuan' => 'perusahaan/tahun',
                'tipe_layanan' => 'EnviPro',
                'durasi_hari' => 365,
                'envipoin_reward' => 350,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'kode_layanan' => 'LEGAL004',
                'nama_layanan' => 'Legal+ - Konsultasi Legal & Compliance',
                'deskripsi' => 'Layanan konsultasi hukum dan kepatuhan regulasi di bidang pengelolaan limbah B3',
                'harga' => 3500000.00,
                'satuan' => 'perusahaan/bulan',
                'tipe_layanan' => 'Legal+',
                'durasi_hari' => 30,
                'envipoin_reward' => 150,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'kode_layanan' => 'UJIPRO005',
                'nama_layanan' => 'UjiPro - Analisis & Pengujian Limbah',
                'deskripsi' => 'Layanan pengujian dan analisis karakteristik limbah B3 di laboratorium berstandar',
                'harga' => 1200000.00,
                'satuan' => 'per sampel',
                'tipe_layanan' => 'UjiPro',
                'durasi_hari' => 14,
                'envipoin_reward' => 75,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('layanan')->insertBatch($data);
    }
}