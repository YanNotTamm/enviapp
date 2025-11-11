<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ManifestElektronikSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user_id' => 3,
                'riwayat_pengangkutan_id' => 1,
                'nomor_manifest' => 'MNF/2024/001',
                'tanggal_manifest' => date('Y-m-d', strtotime('-1 month')),
                'jenis_limbah' => 'Limbah Minyak Bekas',
                'kode_limbah' => 'B3-001-MB',
                'jumlah_limbah_kg' => 150.50,
                'asal_limbah' => 'Area Perbaikan Mesin PT Mitra Sejahtera',
                'tujuan_pengolahan' => 'Pabrik Pengolahan Minyak Bekas, Cikarang',
                'metode_pengolahan' => 'Re-refining dan Pemurnian',
                'penyedia_jasa' => 'PT Solusi Limbah Indonesia',
                'dokumen_pendukung' => 'surat_izin_pengangkutan_001.pdf',
                'status_manifest' => 'selesai',
                'tanggal_persetujuan' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
                'disetujui_oleh' => 'Supervisor Limbah',
                'catatan_persetujuan' => 'Manifest disetujui, pengangkutan sesuai prosedur',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 3,
                'riwayat_pengangkutan_id' => 2,
                'nomor_manifest' => 'MNF/2024/002',
                'tanggal_manifest' => date('Y-m-d', strtotime('-2 weeks')),
                'jenis_limbah' => 'Limbah Kimia Cair',
                'kode_limbah' => 'B3-002-KC',
                'jumlah_limbah_kg' => 85.25,
                'asal_limbah' => 'Laboratorium dan Area Produksi PT Mitra Sejahtera',
                'tujuan_pengolahan' => 'Fasilitas Pengolahan Limbah Kimia, Bogor',
                'metode_pengolahan' => 'Neutralisasi dan Stabilisasi',
                'penyedia_jasa' => 'PT Kimia Sehat Indonesia',
                'dokumen_pendukung' => 'surat_izin_pengangkutan_002.pdf',
                'status_manifest' => 'selesai',
                'tanggal_persetujuan' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'disetujui_oleh' => 'Kepala Divisi HSE',
                'catatan_persetujuan' => 'Pengangkutan limbah kimia sesuai regulasi',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 4,
                'riwayat_pengangkutan_id' => 3,
                'nomor_manifest' => 'MNF/2024/003',
                'tanggal_manifest' => date('Y-m-d', strtotime('-3 weeks')),
                'jenis_limbah' => 'Limbah Padat B3',
                'kode_limbah' => 'B3-003-PB',
                'jumlah_limbah_kg' => 250.75,
                'asal_limbah' => 'Area Produksi PT Industri Makmur',
                'tujuan_pengolahan' => 'TPA Khusus Limbah B3, Tangerang',
                'metode_pengolahan' => 'Secure Landfill dan Stabilisasi',
                'penyedia_jasa' => 'PT Pengelolaan Limbah Nusantara',
                'dokumen_pendukung' => 'surat_izin_pengangkutan_003.pdf',
                'status_manifest' => 'selesai',
                'tanggal_persetujuan' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
                'disetujui_oleh' => 'Manager Operasional',
                'catatan_persetujuan' => 'Manifest lengkap, pengangkutan sesuai jadwal',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('manifest_elektronik')->insertBatch($data);
    }
}