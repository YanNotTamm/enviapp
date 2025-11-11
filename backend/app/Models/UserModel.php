<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username',
        'email',
        'password',
        'role',
        'nama_lengkap',
        'nama_perusahaan',
        'alamat_perusahaan',
        'telepon',
        'email_verified',
        'verification_token',
        'reset_token',
        'reset_expires',
        'envipoin',
        'masa_berlaku',
        'layanan_aktif',
        'email_verified_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField    = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Find user by username
     */
    public function findByUsername($username)
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Find user by verification token
     */
    public function findByVerificationToken($token)
    {
        return $this->where('verification_token', $token)->first();
    }

    /**
     * Find user by reset token
     */
    public function findByResetToken($token)
    {
        return $this->where('reset_token', $token)->first();
    }

    /**
     * Update user Envipoin
     */
    public function updateEnvipoin($userId, $points)
    {
        $user = $this->find($userId);
        if ($user) {
            $newPoints = $user['envipoin'] + $points;
            return $this->update($userId, ['envipoin' => $newPoints]);
        }
        return false;
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($role)
    {
        return $this->where('role', $role)->findAll();
    }

    /**
     * Get active users (with active service)
     */
    public function getActiveUsers()
    {
        return $this->where('masa_berlaku >=', date('Y-m-d'))
                   ->where('email_verified', true)
                   ->findAll();
    }

    /**
     * Get users with expiring services
     */
    public function getExpiringUsers($days = 30)
    {
        $expiryDate = date('Y-m-d', strtotime("+{$days} days"));
        return $this->where('masa_berlaku <=', $expiryDate)
                   ->where('masa_berlaku >=', date('Y-m-d'))
                   ->where('email_verified', true)
                   ->findAll();
    }

    /**
     * Verify user credentials
     */
    public function verifyCredentials($email, $password)
    {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return null;
    }

    /**
     * Check if user has active service
     */
    public function hasActiveService($userId)
    {
        $user = $this->find($userId);
        return $user && $user['masa_berlaku'] >= date('Y-m-d') && $user['email_verified'];
    }

    /**
     * Get user dashboard statistics
     */
    public function getDashboardStats($userId)
    {
        $db = \Config\Database::connect();
        
        $stats = [];
        
        // Get user info
        $user = $this->find($userId);
        $stats['user'] = $user;
        
        // Get active services count
        $stats['active_services'] = $db->table('transaksi_layanan')
            ->where('user_id', $userId)
            ->where('status', 'aktif')
            ->countAllResults();
        
        // Get total transactions
        $stats['total_transactions'] = $db->table('transaksi_layanan')
            ->where('user_id', $userId)
            ->countAllResults();
        
        // Get total waste collected
        $stats['total_waste_kg'] = $db->table('riwayat_pengangkutan')
            ->where('user_id', $userId)
            ->selectSum('berat_kg')
            ->get()
            ->getRow()->berat_kg ?? 0;
        
        // Get pending invoices
        $stats['pending_invoices'] = $db->table('invoice')
            ->where('user_id', $userId)
            ->where('status_pembayaran', 'belum_bayar')
            ->countAllResults();
        
        // Get recent transactions (last 5)
        $stats['recent_transactions'] = $db->table('transaksi_layanan')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
        
        return $stats;
    }
}