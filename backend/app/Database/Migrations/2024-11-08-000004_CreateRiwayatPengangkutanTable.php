<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRiwayatPengangkutanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tanggal_pengangkutan' => [
                'type' => 'DATE',
            ],
            'jenis_limbah' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'berat_kg' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'volume_m3' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'lokasi_pengangkutan' => [
                'type' => 'TEXT',
            ],
            'metode_pengangkutan' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'kendaraan_yang_digunakan' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'driver_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'nomor_manifest' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
            ],
            'dokumentasi' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['terjadwal', 'dalam_perjalanan', 'selesai', 'dibatalkan'],
                'default'    => 'terjadwal',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('tanggal_pengangkutan');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('riwayat_pengangkutan');
    }

    public function down()
    {
        $this->forge->dropTable('riwayat_pengangkutan');
    }
}