<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table            = 'layanan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kode_layanan',
        'nama_layanan',
        'deskripsi',
        'harga',
        'satuan',
        'tipe_layanan',
        'durasi_hari',
        'envipoin_reward',
        'is_active'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Get all active services
     */
    public function getActiveServices()
    {
        return $this->where('is_active', true)
                    ->orderBy('tipe_layanan', 'ASC')
                    ->orderBy('nama_layanan', 'ASC')
                    ->findAll();
    }

    /**
     * Get service by ID
     */
    public function getServiceById($id)
    {
        return $this->find($id);
    }

    /**
     * Get services by type
     */
    public function getServicesByType($type)
    {
        return $this->where('tipe_layanan', $type)
                    ->where('is_active', true)
                    ->findAll();
    }

    /**
     * Get user's active services
     */
    public function getUserServices($userId)
    {
        $db = \Config\Database::connect();
        
        return $db->table('transaksi_layanan')
            ->select('transaksi_layanan.*, layanan.*')
            ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
            ->where('transaksi_layanan.user_id', $userId)
            ->where('transaksi_layanan.status', 'aktif')
            ->orderBy('transaksi_layanan.tanggal_mulai', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Check if service code exists
     */
    public function isCodeExists($code, $excludeId = null)
    {
        $builder = $this->where('kode_layanan', $code);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
}
