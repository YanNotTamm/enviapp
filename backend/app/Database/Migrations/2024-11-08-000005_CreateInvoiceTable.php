<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoiceTable extends Migration
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
            'transaksi_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nomor_invoice' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
            ],
            'tanggal_invoice' => [
                'type' => 'DATE',
            ],
            'tanggal_jatuh_tempo' => [
                'type' => 'DATE',
            ],
            'subtotal' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'ppn' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => '0.00',
            ],
            'total_tagihan' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'status_pembayaran' => [
                'type'       => 'ENUM',
                'constraint' => ['belum_bayar', 'lunas', 'jatuh_tempo', 'partial'],
                'default'    => 'belum_bayar',
            ],
            'metode_pembayaran' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'tanggal_pembayaran' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'file_invoice' => [
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
        $this->forge->addKey('transaksi_id');
        $this->forge->addKey('status_pembayaran');
        $this->forge->addKey('tanggal_jatuh_tempo');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('transaksi_id', 'transaksi_layanan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('invoice');
    }

    public function down()
    {
        $this->forge->dropTable('invoice');
    }
}