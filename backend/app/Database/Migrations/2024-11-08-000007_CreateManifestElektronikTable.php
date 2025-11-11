<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateManifestElektronikTable extends Migration
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
            'riwayat_pengangkutan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nomor_manifest' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
            ],
            'tanggal_manifest' => [
                'type' => 'DATE',
            ],
            'jenis_limbah' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'kode_limbah' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'jumlah_limbah_kg' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'asal_limbah' => [
                'type' => 'TEXT',
            ],
            'tujuan_pengolahan' => [
                'type' => 'TEXT',
            ],
            'metode_pengolahan' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
            ],
            'penyedia_jasa' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
            ],
            'dokumen_pendukung' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'status_manifest' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'diajukan', 'disetujui', 'ditolak', 'selesai'],
                'default'    => 'draft',
            ],
            'tanggal_persetujuan' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'disetujui_oleh' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'catatan_persetujuan' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('riwayat_pengangkutan_id');
        $this->forge->addKey('status_manifest');
        $this->forge->addKey('tanggal_manifest');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('riwayat_pengangkutan_id', 'riwayat_pengangkutan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('manifest_elektronik');
    }

    public function down()
    {
        $this->forge->dropTable('manifest_elektronik');
    }
}