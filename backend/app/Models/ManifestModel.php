<?php

namespace App\Models;

use CodeIgniter\Model;

class ManifestModel extends Model
{
    protected $table            = 'manifest_elektronik';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'riwayat_pengangkutan_id',
        'nomor_manifest',
        'tanggal_manifest',
        'jenis_limbah',
        'kode_limbah',
        'jumlah_limbah_kg',
        'asal_limbah',
        'tujuan_pengolahan',
        'metode_pengolahan',
        'penyedia_jasa',
        'dokumen_pendukung',
        'status_manifest',
        'tanggal_persetujuan',
        'disetujui_oleh',
        'catatan_persetujuan'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Get user manifests
     */
    public function getUserManifests($userId, $status = null)
    {
        $builder = $this->where('user_id', $userId);
        
        if ($status) {
            $builder->where('status_manifest', $status);
        }
        
        return $builder->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Get manifest by ID with details
     */
    public function getManifestById($id)
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table)
            ->select('manifest_elektronik.*, users.nama_lengkap, users.nama_perusahaan, riwayat_pengangkutan.tanggal_pengangkutan, riwayat_pengangkutan.lokasi_pengangkutan')
            ->join('users', 'users.id = manifest_elektronik.user_id')
            ->join('riwayat_pengangkutan', 'riwayat_pengangkutan.id = manifest_elektronik.riwayat_pengangkutan_id')
            ->where('manifest_elektronik.id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get all manifests (for admin)
     */
    public function getAllManifests($status = null, $limit = null, $offset = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table)
            ->select('manifest_elektronik.*, users.nama_lengkap, users.nama_perusahaan')
            ->join('users', 'users.id = manifest_elektronik.user_id')
            ->orderBy('manifest_elektronik.created_at', 'DESC');
        
        if ($status) {
            $builder->where('manifest_elektronik.status_manifest', $status);
        }
        
        if ($limit) {
            $builder->limit($limit, $offset ?? 0);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get manifests pending approval
     */
    public function getPendingManifests()
    {
        return $this->where('status_manifest', 'diajukan')
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }

    /**
     * Check if manifest number exists
     */
    public function isManifestNumberExists($manifestNumber, $excludeId = null)
    {
        $builder = $this->where('nomor_manifest', $manifestNumber);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
}
