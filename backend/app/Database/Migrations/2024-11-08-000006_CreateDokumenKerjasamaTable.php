<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDokumenKerjasamaTable extends Migration
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
            'nama_dokumen' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
            ],
            'jenis_dokumen' => [
                'type'       => 'ENUM',
                'constraint' => ['kontrak', 'perjanjian_kerjasama', 'sop', 'dokumen_teknis', 'lainnya'],
            ],
            'nomor_dokumen' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'tanggal_dokumen' => [
                'type' => 'DATE',
            ],
            'tanggal_berlaku_mulai' => [
                'type' => 'DATE',
            ],
            'tanggal_berlaku_selesai' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'file_dokumen' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'deskripsi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['aktif', 'kadaluarsa', 'akan_kadaluarsa', 'draft'],
                'default'    => 'aktif',
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
        $this->forge->addKey('jenis_dokumen');
        $this->forge->addKey('status');
        $this->forge->addKey('tanggal_berlaku_selesai');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('dokumen_kerjasama');
    }

    public function down()
    {
        $this->forge->dropTable('dokumen_kerjasama');
    }
}