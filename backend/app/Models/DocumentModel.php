<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table            = 'dokumen_kerjasama';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'transaksi_id',
        'jenis_dokumen',
        'nama_dokumen',
        'file_path',
        'file_size',
        'file_type',
        'tanggal_upload',
        'tanggal_berlaku',
        'tanggal_kadaluarsa',
        'status_dokumen',
        'catatan'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Get user documents
     */
    public function getUserDocuments($userId, $jenisDokumen = null)
    {
        $builder = $this->where('user_id', $userId);
        
        if ($jenisDokumen) {
            $builder->where('jenis_dokumen', $jenisDokumen);
        }
        
        return $builder->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Get document by ID
     */
    public function getDocumentById($id)
    {
        return $this->find($id);
    }

    /**
     * Get documents by transaction
     */
    public function getDocumentsByTransaction($transactionId)
    {
        return $this->where('transaksi_id', $transactionId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments()
    {
        return $this->where('tanggal_kadaluarsa <', date('Y-m-d'))
                    ->where('status_dokumen', 'aktif')
                    ->findAll();
    }

    /**
     * Get expiring documents (within specified days)
     */
    public function getExpiringDocuments($days = 30)
    {
        $expiryDate = date('Y-m-d', strtotime("+{$days} days"));
        return $this->where('tanggal_kadaluarsa <=', $expiryDate)
                    ->where('tanggal_kadaluarsa >=', date('Y-m-d'))
                    ->where('status_dokumen', 'aktif')
                    ->findAll();
    }
}
