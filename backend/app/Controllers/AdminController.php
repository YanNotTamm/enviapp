<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use App\Models\TransactionModel;
use App\Models\InvoiceModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AdminController extends ResourceController
{
    use ResponseTrait;
    
    protected $userModel;
    protected $transactionModel;
    protected $invoiceModel;
    protected $jwtSecret;
    protected $db;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->transactionModel = new TransactionModel();
        $this->invoiceModel = new InvoiceModel();
        $this->jwtSecret = getenv('JWT_SECRET');
        $this->db = \Config\Database::connect();
        
        if (!$this->jwtSecret) {
            throw new \RuntimeException('JWT_SECRET must be set in environment variables');
        }
    }
    
    /**
     * Get authenticated user from JWT token
     */
    private function getAuthenticatedUser()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        
        if (!$authHeader) {
            return null;
        }
        
        try {
            $token = str_replace('Bearer ', '', $authHeader);
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if user has admin role
     */
    private function isAdmin($user)
    {
        return $user && in_array($user->role, ['admin_keuangan', 'superadmin']);
    }
    
    /**
     * Get all users
     * List all users with optional filtering and pagination
     */
    public function getUsers()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isAdmin($user)) {
            return $this->failForbidden('Access denied. Admin role required.');
        }
        
        try {
            // Get query parameters for filtering and pagination
            $role = $this->request->getGet('role');
            $status = $this->request->getGet('status');
            $search = $this->request->getGet('search');
            $limit = $this->request->getGet('limit') ?? 50;
            $offset = $this->request->getGet('offset') ?? 0;
            
            $builder = $this->db->table('users')
                ->select('id, username, email, role, nama_lengkap, nama_perusahaan, alamat_perusahaan, telepon, email_verified, envipoin, masa_berlaku, layanan_aktif, created_at, updated_at');
            
            // Apply filters
            if ($role) {
                $builder->where('role', $role);
            }
            
            if ($status === 'active') {
                $builder->where('email_verified', true)
                       ->where('masa_berlaku >=', date('Y-m-d'));
            } elseif ($status === 'inactive') {
                $builder->groupStart()
                       ->where('email_verified', false)
                       ->orWhere('masa_berlaku <', date('Y-m-d'))
                       ->groupEnd();
            }
            
            if ($search) {
                $builder->groupStart()
                       ->like('username', $search)
                       ->orLike('email', $search)
                       ->orLike('nama_lengkap', $search)
                       ->orLike('nama_perusahaan', $search)
                       ->groupEnd();
            }
            
            // Get total count before pagination
            $total = $builder->countAllResults(false);
            
            // Apply pagination
            $users = $builder->orderBy('created_at', 'DESC')
                           ->limit($limit, $offset)
                           ->get()
                           ->getResultArray();
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'users' => $users,
                    'pagination' => [
                        'total' => $total,
                        'limit' => (int) $limit,
                        'offset' => (int) $offset,
                        'count' => count($users)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch users: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get user details by ID
     * Get detailed information about a specific user
     */
    public function getUser($id)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isAdmin($user)) {
            return $this->failForbidden('Access denied. Admin role required.');
        }
        
        try {
            // Get user details
            $userDetails = $this->userModel->find($id);
            
            if (!$userDetails) {
                return $this->failNotFound('User not found');
            }
            
            // Remove password from response
            unset($userDetails['password']);
            unset($userDetails['verification_token']);
            unset($userDetails['reset_token']);
            
            // Get user statistics
            $stats = [
                'total_transactions' => $this->db->table('transaksi_layanan')
                    ->where('user_id', $id)
                    ->countAllResults(),
                
                'active_transactions' => $this->db->table('transaksi_layanan')
                    ->where('user_id', $id)
                    ->where('status', 'aktif')
                    ->countAllResults(),
                
                'total_invoices' => $this->db->table('invoice')
                    ->where('user_id', $id)
                    ->countAllResults(),
                
                'pending_invoices' => $this->db->table('invoice')
                    ->where('user_id', $id)
                    ->where('status_pembayaran', 'belum_bayar')
                    ->countAllResults(),
                
                'total_spent' => $this->db->table('invoice')
                    ->where('user_id', $id)
                    ->where('status_pembayaran', 'lunas')
                    ->selectSum('total_tagihan')
                    ->get()
                    ->getRow()->total_tagihan ?? 0,
                
                'total_waste_collected' => $this->db->table('riwayat_pengangkutan')
                    ->where('user_id', $id)
                    ->selectSum('berat_kg')
                    ->get()
                    ->getRow()->berat_kg ?? 0
            ];
            
            // Get recent transactions
            $recentTransactions = $this->transactionModel->getUserTransactions($id, null);
            $recentTransactions = array_slice($recentTransactions, 0, 5);
            
            // Get recent invoices
            $recentInvoices = $this->invoiceModel->getUserInvoices($id, null);
            $recentInvoices = array_slice($recentInvoices, 0, 5);
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'user' => $userDetails,
                    'statistics' => $stats,
                    'recent_transactions' => $recentTransactions,
                    'recent_invoices' => $recentInvoices
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch user details: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update user status
     * Update user's email verification, service status, or other status fields
     */
    public function updateUserStatus($id)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isAdmin($user)) {
            return $this->failForbidden('Access denied. Admin role required.');
        }
        
        try {
            // Get user to update
            $targetUser = $this->userModel->find($id);
            
            if (!$targetUser) {
                return $this->failNotFound('User not found');
            }
            
            // Get update data from request
            $data = $this->request->getJSON(true);
            
            // Validate and prepare update data
            $updateData = [];
            
            // Email verification status
            if (isset($data['email_verified'])) {
                $updateData['email_verified'] = (bool) $data['email_verified'];
                if ($updateData['email_verified'] && !$targetUser['email_verified_at']) {
                    $updateData['email_verified_at'] = date('Y-m-d H:i:s');
                }
            }
            
            // Service expiry date
            if (isset($data['masa_berlaku'])) {
                $updateData['masa_berlaku'] = $data['masa_berlaku'];
            }
            
            // Active service
            if (isset($data['layanan_aktif'])) {
                $updateData['layanan_aktif'] = $data['layanan_aktif'];
            }
            
            // Envipoin
            if (isset($data['envipoin'])) {
                $updateData['envipoin'] = (int) $data['envipoin'];
            }
            
            // Role (only superadmin can change roles)
            if (isset($data['role']) && $user->role === 'superadmin') {
                $allowedRoles = ['user', 'admin_keuangan', 'superadmin'];
                if (in_array($data['role'], $allowedRoles)) {
                    $updateData['role'] = $data['role'];
                }
            }
            
            if (empty($updateData)) {
                return $this->fail('No valid fields to update', 400);
            }
            
            // Update user
            $this->userModel->update($id, $updateData);
            
            // Get updated user
            $updatedUser = $this->userModel->find($id);
            unset($updatedUser['password']);
            unset($updatedUser['verification_token']);
            unset($updatedUser['reset_token']);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'User status updated successfully',
                'data' => $updatedUser
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update user status: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get all transactions
     * List all transactions across all users
     */
    public function getAllTransactions()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isAdmin($user)) {
            return $this->failForbidden('Access denied. Admin role required.');
        }
        
        try {
            // Get query parameters for filtering and pagination
            $status = $this->request->getGet('status');
            $userId = $this->request->getGet('user_id');
            $layananId = $this->request->getGet('layanan_id');
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');
            $limit = $this->request->getGet('limit') ?? 50;
            $offset = $this->request->getGet('offset') ?? 0;
            
            $builder = $this->db->table('transaksi_layanan')
                ->select('transaksi_layanan.*, layanan.nama_layanan, layanan.tipe_layanan, users.nama_lengkap, users.nama_perusahaan, users.email')
                ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
                ->join('users', 'users.id = transaksi_layanan.user_id');
            
            // Apply filters
            if ($status) {
                $builder->where('transaksi_layanan.status', $status);
            }
            
            if ($userId) {
                $builder->where('transaksi_layanan.user_id', $userId);
            }
            
            if ($layananId) {
                $builder->where('transaksi_layanan.layanan_id', $layananId);
            }
            
            if ($dateFrom) {
                $builder->where('transaksi_layanan.tanggal_pesan >=', $dateFrom);
            }
            
            if ($dateTo) {
                $builder->where('transaksi_layanan.tanggal_pesan <=', $dateTo);
            }
            
            // Get total count before pagination
            $total = $builder->countAllResults(false);
            
            // Apply pagination
            $transactions = $builder->orderBy('transaksi_layanan.created_at', 'DESC')
                                  ->limit($limit, $offset)
                                  ->get()
                                  ->getResultArray();
            
            // Get summary statistics
            $summaryBuilder = $this->db->table('transaksi_layanan');
            if ($status) $summaryBuilder->where('status', $status);
            if ($userId) $summaryBuilder->where('user_id', $userId);
            if ($layananId) $summaryBuilder->where('layanan_id', $layananId);
            if ($dateFrom) $summaryBuilder->where('tanggal_pesan >=', $dateFrom);
            if ($dateTo) $summaryBuilder->where('tanggal_pesan <=', $dateTo);
            
            $summary = [
                'total_amount' => $summaryBuilder->selectSum('total_harga')->get()->getRow()->total_harga ?? 0,
                'total_count' => $total
            ];
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'transactions' => $transactions,
                    'summary' => $summary,
                    'pagination' => [
                        'total' => $total,
                        'limit' => (int) $limit,
                        'offset' => (int) $offset,
                        'count' => count($transactions)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch transactions: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get all invoices
     * List all invoices across all users
     */
    public function getAllInvoices()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isAdmin($user)) {
            return $this->failForbidden('Access denied. Admin role required.');
        }
        
        try {
            // Get query parameters for filtering and pagination
            $status = $this->request->getGet('status');
            $userId = $this->request->getGet('user_id');
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');
            $overdue = $this->request->getGet('overdue');
            $limit = $this->request->getGet('limit') ?? 50;
            $offset = $this->request->getGet('offset') ?? 0;
            
            $builder = $this->db->table('invoice')
                ->select('invoice.*, users.nama_lengkap, users.nama_perusahaan, users.email, transaksi_layanan.kode_transaksi, layanan.nama_layanan')
                ->join('users', 'users.id = invoice.user_id')
                ->join('transaksi_layanan', 'transaksi_layanan.id = invoice.transaksi_id')
                ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id');
            
            // Apply filters
            if ($status) {
                $builder->where('invoice.status_pembayaran', $status);
            }
            
            if ($userId) {
                $builder->where('invoice.user_id', $userId);
            }
            
            if ($dateFrom) {
                $builder->where('invoice.tanggal_invoice >=', $dateFrom);
            }
            
            if ($dateTo) {
                $builder->where('invoice.tanggal_invoice <=', $dateTo);
            }
            
            if ($overdue === 'true') {
                $builder->where('invoice.status_pembayaran', 'belum_bayar')
                       ->where('invoice.tanggal_jatuh_tempo <', date('Y-m-d'));
            }
            
            // Get total count before pagination
            $total = $builder->countAllResults(false);
            
            // Apply pagination
            $invoices = $builder->orderBy('invoice.created_at', 'DESC')
                              ->limit($limit, $offset)
                              ->get()
                              ->getResultArray();
            
            // Get summary statistics
            $summaryBuilder = $this->db->table('invoice');
            if ($status) $summaryBuilder->where('status_pembayaran', $status);
            if ($userId) $summaryBuilder->where('user_id', $userId);
            if ($dateFrom) $summaryBuilder->where('tanggal_invoice >=', $dateFrom);
            if ($dateTo) $summaryBuilder->where('tanggal_invoice <=', $dateTo);
            if ($overdue === 'true') {
                $summaryBuilder->where('status_pembayaran', 'belum_bayar')
                              ->where('tanggal_jatuh_tempo <', date('Y-m-d'));
            }
            
            $summary = [
                'total_amount' => $summaryBuilder->selectSum('total_tagihan')->get()->getRow()->total_tagihan ?? 0,
                'total_count' => $total,
                'paid_amount' => $this->db->table('invoice')
                    ->where('status_pembayaran', 'lunas')
                    ->selectSum('total_tagihan')
                    ->get()
                    ->getRow()->total_tagihan ?? 0,
                'unpaid_amount' => $this->db->table('invoice')
                    ->where('status_pembayaran', 'belum_bayar')
                    ->selectSum('total_tagihan')
                    ->get()
                    ->getRow()->total_tagihan ?? 0,
                'overdue_count' => $this->db->table('invoice')
                    ->where('status_pembayaran', 'belum_bayar')
                    ->where('tanggal_jatuh_tempo <', date('Y-m-d'))
                    ->countAllResults()
            ];
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'invoices' => $invoices,
                    'summary' => $summary,
                    'pagination' => [
                        'total' => $total,
                        'limit' => (int) $limit,
                        'offset' => (int) $offset,
                        'count' => count($invoices)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch invoices: ' . $e->getMessage(), 500);
        }
    }
}
