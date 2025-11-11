<?php

namespace App\Models;

use CodeIgniter\Model;

class WasteCollectionModel extends Model
{
    protected $table            = 'riwayat_pengangkutan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'tanggal_pengangkutan',
        'jenis_limbah',
        'berat_kg',
        'volume_m3',
        'lokasi_pengangkutan',
        'metode_pengangkutan',
        'kendaraan_yang_digunakan',
        'driver_name',
        'nomor_manifest',
        'dokumentasi',
        'catatan',
        'status'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Get user's waste collection history
     */
    public function getUserCollections($userId, $status = null)
    {
        $builder = $this->where('user_id', $userId);
        
        if ($status) {
            $builder->where('status', $status);
        }
        
        return $builder->orderBy('tanggal_pengangkutan', 'DESC')->findAll();
    }

    /**
     * Get collection by ID
     */
    public function getCollectionById($id)
    {
        return $this->find($id);
    }

    /**
     * Get scheduled collections
     */
    public function getScheduledCollections($userId = null)
    {
        $builder = $this->where('status', 'terjadwal');
        
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->orderBy('tanggal_pengangkutan', 'ASC')->findAll();
    }

    /**
     * Get collections by date range
     */
    public function getCollectionsByDateRange($userId, $startDate, $endDate)
    {
        return $this->where('user_id', $userId)
                    ->where('tanggal_pengangkutan >=', $startDate)
                    ->where('tanggal_pengangkutan <=', $endDate)
                    ->orderBy('tanggal_pengangkutan', 'DESC')
                    ->findAll();
    }

    /**
     * Get total waste collected by user
     */
    public function getTotalWasteByUser($userId)
    {
        $result = $this->selectSum('berat_kg')
                      ->where('user_id', $userId)
                      ->where('status', 'selesai')
                      ->first();
        
        return $result['berat_kg'] ?? 0;
    }

    /**
     * Check if manifest number exists
     */
    public function isManifestExists($manifestNumber, $excludeId = null)
    {
        $builder = $this->where('nomor_manifest', $manifestNumber);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
}
