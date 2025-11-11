<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransaksiLayananTable extends Migration
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
            'layanan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kode_transaksi' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
            ],
            'tanggal_pesan' => [
                'type' => 'DATETIME',
            ],
            'tanggal_mulai' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'tanggal_selesai' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'jumlah' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'total_harga' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'diproses', 'aktif', 'selesai', 'dibatalkan'],
                'default'    => 'pending',
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'bukti_pembayaran' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
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
        $this->forge->addKey('layanan_id');
        $this->forge->addKey('status');
        $this->forge->addKey('tanggal_pesan');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('layanan_id', 'layanan', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('transaksi_layanan');
    }

    public function down()
    {
        $this->forge->dropTable('transaksi_layanan');
    }
}