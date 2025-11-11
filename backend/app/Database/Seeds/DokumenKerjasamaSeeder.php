<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DokumenKerjasamaSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user_id' => 3,
                'nama_dokumen' => 'Perjanjian Kerjasama Pengelolaan Limbah B3',
                'jenis_dokumen' => 'perjanjian_kerjasama',
                'nomor_dokumen' => 'PKS/ENV/2024/001',
                'tanggal_dokumen' => date('Y-m-d', strtotime('-3 months')),
                'tanggal_berlaku_mulai' => date('Y-m-d', strtotime('-3 months')),
                'tanggal_berlaku_selesai' => date('Y-m-d', strtotime('+9 months')),
                'file_dokumen' => 'pks_enviro_2024_001.pdf',
                'deskripsi' => 'Perjanjian kerjasama untuk pengelolaan limbah B3 PT Mitra Sejahtera',
                'status' => 'aktif',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 months')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 3,
                'nama_dokumen' => 'Standard Operating Procedure Pengangkutan Limbah',
                'jenis_dokumen' => 'sop',
                'nomor_dokumen' => 'SOP/ENV/2024/002',
                'tanggal_dokumen' => date('Y-m-d', strtotime('-1 month')),
                'tanggal_berlaku_mulai' => date('Y-m-d', strtotime('-1 month')),
                'tanggal_berlaku_selesai' => date('Y-m-d', strtotime('+11 months')),
                'file_dokumen' => 'sop_pengangkutan_limbah_2024.pdf',
                'deskripsi' => 'SOP untuk prosedur pengangkutan limbah B3 secara aman',
                'status' => 'aktif',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 4,
                'nama_dokumen' => 'Kontrak Pengelolaan Limbah Industri',
                'jenis_dokumen' => 'kontrak',
                'nomor_dokumen' => 'KON/ENV/2024/003',
                'tanggal_dokumen' => date('Y-m-d', strtotime('-2 months')),
                'tanggal_berlaku_mulai' => date('Y-m-d', strtotime('-2 months')),
                'tanggal_berlaku_selesai' => date('Y-m-d', strtotime('+10 months')),
                'file_dokumen' => 'kontrak_industri_2024_003.pdf',
                'deskripsi' => 'Kontrak pengelolaan limbah untuk industri manufaktur PT Industri Makmur',
                'status' => 'aktif',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 months')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('dokumen_kerjasama')->insertBatch($data);
    }
}