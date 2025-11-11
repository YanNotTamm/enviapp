<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use App\Helpers\ResponseHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class DashboardController extends ResourceController
{
    use ResponseTrait;
    
    protected $userModel;
    protected $jwtSecret;
    protected $db;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
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
     * User Dashboard
     * Display user statistics, active services, and recent activities
     */
    public function userDashboard()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return ResponseHelper::unauthorized('Invalid or missing token');
        }
        
        try {
            // Use UserModel::getDashboardStats() for data
            $stats = $this->userModel->getDashboardStats($user->user_id);
            
            // Flatten structure for frontend compatibility
            return ResponseHelper::success([
                'envipoin' => $stats['user']['envipoin'] ?? 0,
                'active_services' => $stats['active_services'] ?? 0,
                'total_transactions' => $stats['total_transactions'] ?? 0,
                'pending_invoices' => $stats['pending_invoices'] ?? 0,
                'total_waste_kg' => $stats['total_waste_kg'] ?? 0,
                'recent_transactions' => $stats['recent_transactions'] ?? [],
                'active_services_list' => $stats['active_services_list'] ?? [],
                'user_info' => [
                    'id' => $stats['user']['id'],
                    'username' => $stats['user']['username'],
                    'nama_lengkap' => $stats['user']['nama_lengkap'],
                    'nama_perusahaan' => $stats['user']['nama_perusahaan'],
                    'layanan_aktif' => $stats['user']['layanan_aktif'],
                    'masa_berlaku' => $stats['user']['masa_berlaku']
                ]
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::serverError('Failed to fetch dashboard data', $e);
        }
    }
    
    /**
     * Admin Dashboard
     * Display financial overview, pending invoices, and user statistics
     * Requires admin_keuangan or superadmin role
     */
    public function adminDashboard()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return ResponseHelper::unauthorized('Invalid or missing token');
        }
        
        // Check role
        if (!in_array($user->role, ['admin_keuangan', 'superadmin'])) {
            return ResponseHelper::forbidden('Access denied. Admin role required.');
        }
        
        try {
            // Total users
            $totalUsers = $this->db->table('users')
                ->where('email_verified', true)
                ->countAllResults();
            
            // Active users (with active services)
            $activeUsers = $this->db->table('users')
                ->where('email_verified', true)
                ->where('masa_berlaku >=', date('Y-m-d'))
                ->countAllResults();
            
            // Total transactions
            $totalTransactions = $this->db->table('transaksi_layanan')
                ->countAllResults();
            
            // Pending transactions
            $pendingTransactions = $this->db->table('transaksi_layanan')
                ->where('status', 'pending')
                ->countAllResults();
            
            // Total invoices
            $totalInvoices = $this->db->table('invoice')
                ->countAllResults();
            
            // Pending invoices
            $pendingInvoices = $this->db->table('invoice')
                ->where('status_pembayaran', 'belum_bayar')
                ->countAllResults();
            
            // Total revenue (paid invoices)
            $totalRevenue = $this->db->table('invoice')
                ->where('status_pembayaran', 'lunas')
                ->selectSum('total_tagihan')
                ->get()
                ->getRow()->total_tagihan ?? 0;
            
            // Outstanding revenue (unpaid invoices)
            $outstandingRevenue = $this->db->table('invoice')
                ->where('status_pembayaran', 'belum_bayar')
                ->selectSum('total_tagihan')
                ->get()
                ->getRow()->total_tagihan ?? 0;
            
            // Recent transactions (last 10)
            $recentTransactions = $this->db->table('transaksi_layanan')
                ->select('transaksi_layanan.*, users.nama_lengkap, users.nama_perusahaan, layanan.nama_layanan')
                ->join('users', 'users.id = transaksi_layanan.user_id')
                ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
                ->orderBy('transaksi_layanan.created_at', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
            
            // Pending invoices list (last 10)
            $pendingInvoicesList = $this->db->table('invoice')
                ->select('invoice.*, users.nama_lengkap, users.nama_perusahaan')
                ->join('users', 'users.id = invoice.user_id')
                ->where('invoice.status_pembayaran', 'belum_bayar')
                ->orderBy('invoice.tanggal_jatuh_tempo', 'ASC')
                ->limit(10)
                ->get()
                ->getResultArray();
            
            return ResponseHelper::success([
                'user_statistics' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers
                ],
                'transaction_statistics' => [
                    'total_transactions' => $totalTransactions,
                    'pending_transactions' => $pendingTransactions
                ],
                'financial_overview' => [
                    'total_invoices' => $totalInvoices,
                    'pending_invoices' => $pendingInvoices,
                    'total_revenue' => (float) $totalRevenue,
                    'outstanding_revenue' => (float) $outstandingRevenue
                ],
                'recent_transactions' => $recentTransactions,
                'pending_invoices_list' => $pendingInvoicesList
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::serverError('Failed to fetch admin dashboard data', $e);
        }
    }
    
    /**
     * Super Admin Dashboard
     * Display system-wide statistics, all users, and all transactions
     * Requires superadmin role
     */
    public function superadminDashboard()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return ResponseHelper::unauthorized('Invalid or missing token');
        }
        
        // Check role
        if ($user->role !== 'superadmin') {
            return ResponseHelper::forbidden('Access denied. Super admin role required.');
        }
        
        try {
            // Total users by role
            $usersByRole = $this->db->table('users')
                ->select('role, COUNT(*) as count')
                ->groupBy('role')
                ->get()
                ->getResultArray();
            
            // Total services
            $totalServices = $this->db->table('layanan')
                ->countAllResults();
            
            // Active services
            $activeServices = $this->db->table('layanan')
                ->where('is_active', true)
                ->countAllResults();
            
            // Total transactions by status
            $transactionsByStatus = $this->db->table('transaksi_layanan')
                ->select('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->getResultArray();
            
            // Total waste collected
            $totalWasteCollected = $this->db->table('riwayat_pengangkutan')
                ->selectSum('berat_kg')
                ->get()
                ->getRow()->berat_kg ?? 0;
            
            // Total manifests by status
            $manifestsByStatus = $this->db->table('manifest_elektronik')
                ->select('status_manifest, COUNT(*) as count')
                ->groupBy('status_manifest')
                ->get()
                ->getResultArray();
            
            // Total revenue
            $totalRevenue = $this->db->table('invoice')
                ->where('status_pembayaran', 'lunas')
                ->selectSum('total_tagihan')
                ->get()
                ->getRow()->total_tagihan ?? 0;
            
            // Monthly revenue (last 12 months)
            $monthlyRevenue = $this->db->query("
                SELECT 
                    DATE_FORMAT(tanggal_pembayaran, '%Y-%m') as month,
                    SUM(total_tagihan) as revenue
                FROM invoice
                WHERE status_pembayaran = 'lunas'
                    AND tanggal_pembayaran >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(tanggal_pembayaran, '%Y-%m')
                ORDER BY month DESC
            ")->getResultArray();
            
            // Recent user registrations (last 10)
            $recentUsers = $this->db->table('users')
                ->select('id, username, email, nama_lengkap, nama_perusahaan, role, created_at')
                ->orderBy('created_at', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
            
            // System health metrics
            $systemHealth = [
                'database_size_mb' => $this->getDatabaseSize(),
                'total_records' => [
                    'users' => $this->db->table('users')->countAllResults(),
                    'services' => $this->db->table('layanan')->countAllResults(),
                    'transactions' => $this->db->table('transaksi_layanan')->countAllResults(),
                    'invoices' => $this->db->table('invoice')->countAllResults(),
                    'documents' => $this->db->table('dokumen_kerjasama')->countAllResults(),
                    'waste_collections' => $this->db->table('riwayat_pengangkutan')->countAllResults(),
                    'manifests' => $this->db->table('manifest_elektronik')->countAllResults()
                ]
            ];
            
            return ResponseHelper::success([
                'users_by_role' => $usersByRole,
                'service_statistics' => [
                    'total_services' => $totalServices,
                    'active_services' => $activeServices
                ],
                'transactions_by_status' => $transactionsByStatus,
                'waste_statistics' => [
                    'total_waste_kg' => (float) $totalWasteCollected
                ],
                'manifests_by_status' => $manifestsByStatus,
                'financial_overview' => [
                    'total_revenue' => (float) $totalRevenue,
                    'monthly_revenue' => $monthlyRevenue
                ],
                'recent_users' => $recentUsers,
                'system_health' => $systemHealth
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::serverError('Failed to fetch superadmin dashboard data', $e);
        }
    }
    
    /**
     * Get database size in MB
     */
    private function getDatabaseSize()
    {
        try {
            $dbName = $this->db->getDatabase();
            $result = $this->db->query("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ", [$dbName])->getRow();
            
            return $result ? (float) $result->size_mb : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
