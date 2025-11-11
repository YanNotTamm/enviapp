<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table            = 'transaksi_layanan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'layanan_id',
        'kode_transaksi',
        'tanggal_pesan',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah',
        'total_harga',
        'status',
        'catatan',
        'bukti_pembayaran'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Get user transactions with service details
     */
    public function getUserTransactions($userId, $status = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table)
            ->select('transaksi_layanan.*, layanan.nama_layanan, layanan.tipe_layanan, layanan.satuan')
            ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
            ->where('transaksi_layanan.user_id', $userId);
        
        if ($status) {
            $builder->where('transaksi_layanan.status', $status);
        }
        
        return $builder->orderBy('transaksi_layanan.created_at', 'DESC')
                      ->get()
                      ->getResultArray();
    }

    /**
     * Get transaction by ID with details
     */
    public function getTransactionById($id)
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table)
            ->select('transaksi_layanan.*, layanan.*, users.nama_lengkap, users.nama_perusahaan, users.email')
            ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
            ->join('users', 'users.id = transaksi_layanan.user_id')
            ->where('transaksi_layanan.id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Create new transaction
     */
    public function createTransaction($data)
    {
        return $this->insert($data);
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Get all transactions (for admin)
     */
    public function getAllTransactions($limit = null, $offset = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table)
            ->select('transaksi_layanan.*, layanan.nama_layanan, users.nama_lengkap, users.nama_perusahaan')
            ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
            ->join('users', 'users.id = transaksi_layanan.user_id')
            ->orderBy('transaksi_layanan.created_at', 'DESC');
        
        if ($limit) {
            $builder->limit($limit, $offset ?? 0);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get transactions by status
     */
    public function getTransactionsByStatus($status)
    {
        return $this->where('status', $status)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Check if transaction code exists
     */
    public function isCodeExists($code)
    {
        return $this->where('kode_transaksi', $code)->countAllResults() > 0;
    }
}
