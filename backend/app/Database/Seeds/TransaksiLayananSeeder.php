<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TransaksiLayananSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user_id' => 3,
                'layanan_id' => 1,
                'kode_transaksi' => 'TRX/2024/001',
                'tanggal_pesan' => date('Y-m-d H:i:s', strtotime('-2 months')),
                'tanggal_mulai' => date('Y-m-d', strtotime('-2 months')),
                'tanggal_selesai' => date('Y-m-d', strtotime('+10 months')),
                'jumlah' => 1,
                'total_harga' => 2500000.00,
                'status' => 'aktif',
                'catatan' => 'Pemesanan layanan EnviReg untuk PT Mitra Sejahtera',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 months')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 3,
                'layanan_id' => 2,
                'kode_transaksi' => 'TRX/2024/002',
                'tanggal_pesan' => date('Y-m-d H:i:s', strtotime('-1 month')),
                'tanggal_mulai' => date('Y-m-d', strtotime('-1 month')),
                'tanggal_selesai' => date('Y-m-d', strtotime('+11 months')),
                'jumlah' => 1,
                'total_harga' => 4500000.00,
                'status' => 'aktif',
                'catatan' => 'Upgrade ke layanan Envi+ untuk manajemen limbah yang lebih baik',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 4,
                'layanan_id' => 3,
                'kode_transaksi' => 'TRX/2024/003',
                'tanggal_pesan' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
                'tanggal_mulai' => date('Y-m-d', strtotime('-3 weeks')),
                'tanggal_selesai' => date('Y-m-d', strtotime('+11 months +1 week')),
                'jumlah' => 1,
                'total_harga' => 8500000.00,
                'status' => 'aktif',
                'catatan' => 'Layanan EnviPro untuk industri dengan volume limbah besar',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 4,
                'layanan_id' => 5,
                'kode_transaksi' => 'TRX/2024/004',
                'tanggal_pesan' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'tanggal_mulai' => null,
                'tanggal_selesai' => null,
                'jumlah' => 5,
                'total_harga' => 6000000.00,
                'status' => 'diproses',
                'catatan' => 'Pengujian 5 sampel limbah untuk analisis karakteristik',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('transaksi_layanan')->insertBatch($data);
    }
}