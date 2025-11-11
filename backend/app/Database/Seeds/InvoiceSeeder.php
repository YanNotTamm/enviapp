<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user_id' => 3,
                'transaksi_id' => 1,
                'nomor_invoice' => 'INV/2024/001',
                'tanggal_invoice' => date('Y-m-d', strtotime('-1 month')),
                'tanggal_jatuh_tempo' => date('Y-m-d', strtotime('-1 week')),
                'subtotal' => 2500000.00,
                'ppn' => 250000.00,
                'total_tagihan' => 2750000.00,
                'status_pembayaran' => 'lunas',
                'metode_pembayaran' => 'Transfer Bank',
                'tanggal_pembayaran' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
                'catatan' => 'Pembayaran lunas untuk layanan EnviReg',
                'file_invoice' => 'invoice_001.pdf',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 3,
                'transaksi_id' => 2,
                'nomor_invoice' => 'INV/2024/002',
                'tanggal_invoice' => date('Y-m-d', strtotime('-3 weeks')),
                'tanggal_jatuh_tempo' => date('Y-m-d', strtotime('+1 week')),
                'subtotal' => 4500000.00,
                'ppn' => 450000.00,
                'total_tagihan' => 4950000.00,
                'status_pembayaran' => 'belum_bayar',
                'metode_pembayaran' => null,
                'tanggal_pembayaran' => null,
                'catatan' => 'Invoice untuk layanan Envi+',
                'file_invoice' => 'invoice_002.pdf',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 4,
                'transaksi_id' => 3,
                'nomor_invoice' => 'INV/2024/003',
                'tanggal_invoice' => date('Y-m-d', strtotime('-2 weeks')),
                'tanggal_jatuh_tempo' => date('Y-m-d', strtotime('-2 days')),
                'subtotal' => 8500000.00,
                'ppn' => 850000.00,
                'total_tagihan' => 9350000.00,
                'status_pembayaran' => 'jatuh_tempo',
                'metode_pembayaran' => null,
                'tanggal_pembayaran' => null,
                'catatan' => 'Invoice jatuh tempo untuk layanan EnviPro',
                'file_invoice' => 'invoice_003.pdf',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('invoice')->insertBatch($data);
    }
}