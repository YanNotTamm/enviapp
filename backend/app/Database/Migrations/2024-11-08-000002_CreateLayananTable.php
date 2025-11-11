<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLayananTable extends Migration
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
            'kode_layanan' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'unique'     => true,
            ],
            'nama_layanan' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'deskripsi' => [
                'type' => 'TEXT',
            ],
            'harga' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'satuan' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'tipe_layanan' => [
                'type'       => 'ENUM',
                'constraint' => ['EnviReg', 'Envi+', 'EnviPro', 'Legal+', 'UjiPro'],
            ],
            'durasi_hari' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 30,
            ],
            'envipoin_reward' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'BOOLEAN',
                'default'    => true,
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
        $this->forge->addKey('tipe_layanan');
        $this->forge->addKey('is_active');
        $this->forge->createTable('layanan');
    }

    public function down()
    {
        $this->forge->dropTable('layanan');
    }
}