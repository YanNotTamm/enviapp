<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RiwayatPengangkutanSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user_id' => 3,
                'tanggal_pengangkutan' => date('Y-m-d', strtotime('-1 month')),
                'jenis_limbah' => 'Limbah Minyak Bekas',
                'berat_kg' => 150.50,
                'volume_m3' => 0.85,
                'lokasi_pengangkutan' => 'Gudang Limbah PT Mitra Sejahtera, Jl. Sudirman No. 123',
                'metode_pengangkutan' => 'Truk Tangki',
                'kendaraan_yang_digunakan' => 'Truk Tangki 5 ton',
                'driver_name' => 'Bambang Sutrisno',
                'nomor_manifest' => 'MF/2024/001',
                'dokumentasi' => 'dokumentasi_001.jpg',
                'catatan' => 'Pengangkutan berhasil tanpa masalah',
                'status' => 'selesai',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 3,
                'tanggal_pengangkutan' => date('Y-m-d', strtotime('-2 weeks')),
                'jenis_limbah' => 'Limbah Kimia Cair',
                'berat_kg' => 85.25,
                'volume_m3' => 0.45,
                'lokasi_pengangkutan' => 'Area Produksi PT Mitra Sejahtera, Jl. Sudirman No. 123',
                'metode_pengangkutan' => 'Truk Box',
                'kendaraan_yang_digunakan' => 'Truk Box 3.5 ton',
                'driver_name' => 'Sugiono Wibowo',
                'nomor_manifest' => 'MF/2024/002',
                'dokumentasi' => 'dokumentasi_002.jpg',
                'catatan' => 'Pengangkutan limbah kimia dengan prosedur khusus',
                'status' => 'selesai',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 4,
                'tanggal_pengangkutan' => date('Y-m-d', strtotime('-3 weeks')),
                'jenis_limbah' => 'Limbah Padat B3',
                'berat_kg' => 250.75,
                'volume_m3' => 1.20,
                'lokasi_pengangkutan' => 'Tempat Penyimpanan Limbah PT Industri Makmur, Jl. Gatot Subroto No. 456',
                'metode_pengangkutan' => 'Truk Dump',
                'kendaraan_yang_digunakan' => 'Truk Dump 8 ton',
                'driver_name' => 'Ahmad Rahman',
                'nomor_manifest' => 'MF/2024/003',
                'dokumentasi' => 'dokumentasi_003.jpg',
                'catatan' => 'Volume besar, menggunakan truk dump',
                'status' => 'selesai',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 weeks')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 4,
                'tanggal_pengangkutan' => date('Y-m-d', strtotime('-1 week')),
                'jenis_limbah' => 'Limbah Medis',
                'berat_kg' => 45.30,
                'volume_m3' => 0.25,
                'lokasi_pengangkutan' => 'Klinik Perusahaan PT Industri Makmur, Jl. Gatot Subroto No. 456',
                'metode_pengangkutan' => 'Mobil Box Khusus Medis',
                'kendaraan_yang_digunakan' => 'Inova Medis Box',
                'driver_name' => 'Rudi Hartono',
                'nomor_manifest' => 'MF/2024/004',
                'dokumentasi' => 'dokumentasi_004.jpg',
                'catatan' => 'Limbah medis dengan penanganan khusus sesuai SOP',
                'status' => 'selesai',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 3,
                'tanggal_pengangkutan' => date('Y-m-d', strtotime('+2 days')),
                'jenis_limbah' => 'Limbah Minyak Bekas',
                'berat_kg' => 120.00,
                'volume_m3' => 0.70,
                'lokasi_pengangkutan' => 'Gudang Limbah PT Mitra Sejahtera, Jl. Sudirman No. 123',
                'metode_pengangkutan' => 'Truk Tangki',
                'kendaraan_yang_digunakan' => 'Truk Tangki 5 ton',
                'driver_name' => 'Bambang Sutrisno',
                'nomor_manifest' => 'MF/2024/005',
                'dokumentasi' => null,
                'catatan' => 'Pengangkutan terjadwal',
                'status' => 'terjadwal',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('riwayat_pengangkutan')->insertBatch($data);
    }
}