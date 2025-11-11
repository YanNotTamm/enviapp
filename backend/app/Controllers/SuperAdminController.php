<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\ServiceModel;
use App\Models\UserModel;
use App\Models\TransactionModel;
use App\Models\InvoiceModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class SuperAdminController extends ResourceController
{
    use ResponseTrait;
    
    protected $serviceModel;
    protected $userModel;
    protected $transactionModel;
    protected $invoiceModel;
    protected $jwtSecret;
    protected $db;
    
    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
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
     * Check if user has superadmin role
     */
    private function isSuperAdmin($user)
    {
        return $user && $user->role === 'superadmin';
    }

    
    /**
     * Get all services
     * List all services (active and inactive)
     */
    public function getServices()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isSuperAdmin($user)) {
            return $this->failForbidden('Access denied. Superadmin role required.');
        }
        
        try {
            // Get query parameters for filtering
            $type = $this->request->getGet('type');
            $status = $this->request->getGet('status');
            $search = $this->request->getGet('search');
            
            $builder = $this->db->table('layanan');
            
            // Apply filters
            if ($type) {
                $builder->where('tipe_layanan', $type);
            }
            
            if ($status === 'active') {
                $builder->where('is_active', true);
            } elseif ($status === 'inactive') {
                $builder->where('is_active', false);
            }
            
            if ($search) {
                $builder->groupStart()
                       ->like('nama_layanan', $search)
                       ->orLike('kode_layanan', $search)
                       ->orLike('deskripsi', $search)
                       ->groupEnd();
            }
            
            $services = $builder->orderBy('tipe_layanan', 'ASC')
                              ->orderBy('nama_layanan', 'ASC')
                              ->get()
                              ->getResultArray();
            
            // Get usage statistics for each service
            foreach ($services as &$service) {
                $service['usage_stats'] = [
                    'total_subscriptions' => $this->db->table('transaksi_layanan')
                        ->where('layanan_id', $service['id'])
                        ->countAllResults(),
                    
                    'active_subscriptions' => $this->db->table('transaksi_layanan')
                        ->where('layanan_id', $service['id'])
                        ->where('status', 'aktif')
                        ->countAllResults(),
                    
                    'total_revenue' => $this->db->table('transaksi_layanan')
                        ->where('layanan_id', $service['id'])
                        ->selectSum('total_harga')
                        ->get()
                        ->getRow()->total_harga ?? 0
                ];
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'services' => $services,
                    'count' => count($services)
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch services: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create new service
     * Create a new service in the catalog
     */
    public function createService()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isSuperAdmin($user)) {
            return $this->failForbidden('Access denied. Superadmin role required.');
        }
        
        try {
            $data = $this->request->getJSON(true);
            
            // Validation rules
            $rules = [
                'kode_layanan' => 'required|min_length[3]|max_length[20]',
                'nama_layanan' => 'required|min_length[3]|max_length[100]',
                'deskripsi' => 'required',
                'harga' => 'required|numeric',
                'satuan' => 'required|in_list[bulan,tahun,sekali]',
                'tipe_layanan' => 'required|in_list[registrasi,perizinan,konsultasi,pelatihan,pengangkutan,manifest]',
                'durasi_hari' => 'permit_empty|numeric',
                'envipoin_reward' => 'permit_empty|numeric',
                'is_active' => 'permit_empty|in_list[0,1,true,false]'
            ];
            
            if (!$this->validate($rules)) {
                return $this->fail([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $this->validator->getErrors()
                ], 400);
            }
            
            // Check if service code already exists
            if ($this->serviceModel->isCodeExists($data['kode_layanan'])) {
                return $this->fail([
                    'status' => 'error',
                    'message' => 'Service code already exists',
                    'errors' => ['kode_layanan' => 'This service code is already in use']
                ], 400);
            }
            
            // Prepare data
            $serviceData = [
                'kode_layanan' => $data['kode_layanan'],
                'nama_layanan' => $data['nama_layanan'],
                'deskripsi' => $data['deskripsi'],
                'harga' => $data['harga'],
                'satuan' => $data['satuan'],
                'tipe_layanan' => $data['tipe_layanan'],
                'durasi_hari' => $data['durasi_hari'] ?? null,
                'envipoin_reward' => $data['envipoin_reward'] ?? 0,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true
            ];
            
            // Insert service
            $serviceId = $this->serviceModel->insert($serviceData);
            
            if (!$serviceId) {
                return $this->fail('Failed to create service', 500);
            }
            
            // Get created service
            $service = $this->serviceModel->find($serviceId);
            
            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Service created successfully',
                'data' => $service
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to create service: ' . $e->getMessage(), 500);
        }
    }

    
    /**
     * Update service
     * Update an existing service
     */
    public function updateService($id)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isSuperAdmin($user)) {
            return $this->failForbidden('Access denied. Superadmin role required.');
        }
        
        try {
            // Check if service exists
            $service = $this->serviceModel->find($id);
            
            if (!$service) {
                return $this->failNotFound('Service not found');
            }
            
            $data = $this->request->getJSON(true);
            
            // Validation rules
            $rules = [
                'kode_layanan' => 'permit_empty|min_length[3]|max_length[20]',
                'nama_layanan' => 'permit_empty|min_length[3]|max_length[100]',
                'deskripsi' => 'permit_empty',
                'harga' => 'permit_empty|numeric',
                'satuan' => 'permit_empty|in_list[bulan,tahun,sekali]',
                'tipe_layanan' => 'permit_empty|in_list[registrasi,perizinan,konsultasi,pelatihan,pengangkutan,manifest]',
                'durasi_hari' => 'permit_empty|numeric',
                'envipoin_reward' => 'permit_empty|numeric',
                'is_active' => 'permit_empty|in_list[0,1,true,false]'
            ];
            
            if (!$this->validate($rules)) {
                return $this->fail([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $this->validator->getErrors()
                ], 400);
            }
            
            // Check if service code already exists (if being changed)
            if (isset($data['kode_layanan']) && $data['kode_layanan'] !== $service['kode_layanan']) {
                if ($this->serviceModel->isCodeExists($data['kode_layanan'], $id)) {
                    return $this->fail([
                        'status' => 'error',
                        'message' => 'Service code already exists',
                        'errors' => ['kode_layanan' => 'This service code is already in use']
                    ], 400);
                }
            }
            
            // Prepare update data
            $updateData = [];
            $allowedFields = ['kode_layanan', 'nama_layanan', 'deskripsi', 'harga', 'satuan', 
                            'tipe_layanan', 'durasi_hari', 'envipoin_reward', 'is_active'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'is_active') {
                        $updateData[$field] = (bool) $data[$field];
                    } else {
                        $updateData[$field] = $data[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                return $this->fail('No valid fields to update', 400);
            }
            
            // Update service
            $this->serviceModel->update($id, $updateData);
            
            // Get updated service
            $updatedService = $this->serviceModel->find($id);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Service updated successfully',
                'data' => $updatedService
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update service: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete service
     * Delete a service (soft delete by setting is_active to false)
     */
    public function deleteService($id)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isSuperAdmin($user)) {
            return $this->failForbidden('Access denied. Superadmin role required.');
        }
        
        try {
            // Check if service exists
            $service = $this->serviceModel->find($id);
            
            if (!$service) {
                return $this->failNotFound('Service not found');
            }
            
            // Check if service has active subscriptions
            $activeSubscriptions = $this->db->table('transaksi_layanan')
                ->where('layanan_id', $id)
                ->where('status', 'aktif')
                ->countAllResults();
            
            if ($activeSubscriptions > 0) {
                return $this->fail([
                    'status' => 'error',
                    'message' => 'Cannot delete service with active subscriptions',
                    'data' => [
                        'active_subscriptions' => $activeSubscriptions
                    ]
                ], 400);
            }
            
            // Get permanent delete flag from query parameter
            $permanent = $this->request->getGet('permanent') === 'true';
            
            if ($permanent) {
                // Permanent delete
                $this->serviceModel->delete($id);
                $message = 'Service permanently deleted';
            } else {
                // Soft delete by setting is_active to false
                $this->serviceModel->update($id, ['is_active' => false]);
                $message = 'Service deactivated successfully';
            }
            
            return $this->respond([
                'status' => 'success',
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete service: ' . $e->getMessage(), 500);
        }
    }

    
    /**
     * Get system statistics
     * Get comprehensive system-wide statistics
     */
    public function getSystemStats()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        if (!$this->isSuperAdmin($user)) {
            return $this->failForbidden('Access denied. Superadmin role required.');
        }
        
        try {
            // User statistics
            $userStats = [
                'total_users' => $this->db->table('users')->countAllResults(),
                'verified_users' => $this->db->table('users')
                    ->where('email_verified', true)
                    ->countAllResults(),
                'unverified_users' => $this->db->table('users')
                    ->where('email_verified', false)
                    ->countAllResults(),
                'users_by_role' => [
                    'user' => $this->db->table('users')
                        ->where('role', 'user')
                        ->countAllResults(),
                    'admin_keuangan' => $this->db->table('users')
                        ->where('role', 'admin_keuangan')
                        ->countAllResults(),
                    'superadmin' => $this->db->table('users')
                        ->where('role', 'superadmin')
                        ->countAllResults()
                ],
                'active_users' => $this->db->table('users')
                    ->where('email_verified', true)
                    ->where('masa_berlaku >=', date('Y-m-d'))
                    ->countAllResults(),
                'new_users_this_month' => $this->db->table('users')
                    ->where('created_at >=', date('Y-m-01 00:00:00'))
                    ->countAllResults()
            ];
            
            // Service statistics
            $serviceStats = [
                'total_services' => $this->db->table('layanan')->countAllResults(),
                'active_services' => $this->db->table('layanan')
                    ->where('is_active', true)
                    ->countAllResults(),
                'inactive_services' => $this->db->table('layanan')
                    ->where('is_active', false)
                    ->countAllResults(),
                'services_by_type' => []
            ];
            
            // Get service counts by type
            $serviceTypes = $this->db->table('layanan')
                ->select('tipe_layanan, COUNT(*) as count')
                ->groupBy('tipe_layanan')
                ->get()
                ->getResultArray();
            
            foreach ($serviceTypes as $type) {
                $serviceStats['services_by_type'][$type['tipe_layanan']] = (int) $type['count'];
            }
            
            // Transaction statistics
            $transactionStats = [
                'total_transactions' => $this->db->table('transaksi_layanan')->countAllResults(),
                'active_transactions' => $this->db->table('transaksi_layanan')
                    ->where('status', 'aktif')
                    ->countAllResults(),
                'completed_transactions' => $this->db->table('transaksi_layanan')
                    ->where('status', 'selesai')
                    ->countAllResults(),
                'cancelled_transactions' => $this->db->table('transaksi_layanan')
                    ->where('status', 'dibatalkan')
                    ->countAllResults(),
                'total_revenue' => $this->db->table('transaksi_layanan')
                    ->selectSum('total_harga')
                    ->get()
                    ->getRow()->total_harga ?? 0,
                'revenue_this_month' => $this->db->table('transaksi_layanan')
                    ->where('tanggal_pesan >=', date('Y-m-01'))
                    ->selectSum('total_harga')
                    ->get()
                    ->getRow()->total_harga ?? 0,
                'transactions_this_month' => $this->db->table('transaksi_layanan')
                    ->where('tanggal_pesan >=', date('Y-m-01'))
                    ->countAllResults()
            ];
            
            // Invoice statistics
            $invoiceStats = [
                'total_invoices' => $this->db->table('invoice')->countAllResults(),
                'paid_invoices' => $this->db->table('invoice')
                    ->where('status_pembayaran', 'lunas')
                    ->countAllResults(),
                'unpaid_invoices' => $this->db->table('invoice')
                    ->where('status_pembayaran', 'belum_bayar')
                    ->countAllResults(),
                'overdue_invoices' => $this->db->table('invoice')
                    ->where('status_pembayaran', 'belum_bayar')
                    ->where('tanggal_jatuh_tempo <', date('Y-m-d'))
                    ->countAllResults(),
                'total_billed' => $this->db->table('invoice')
                    ->selectSum('total_tagihan')
                    ->get()
                    ->getRow()->total_tagihan ?? 0,
                'total_paid' => $this->db->table('invoice')
                    ->where('status_pembayaran', 'lunas')
                    ->selectSum('total_tagihan')
                    ->get()
                    ->getRow()->total_tagihan ?? 0,
                'total_outstanding' => $this->db->table('invoice')
                    ->where('status_pembayaran', 'belum_bayar')
                    ->selectSum('total_tagihan')
                    ->get()
                    ->getRow()->total_tagihan ?? 0
            ];
            
            // Waste collection statistics (if table exists)
            $wasteStats = [
                'total_collections' => 0,
                'completed_collections' => 0,
                'pending_collections' => 0,
                'total_waste_collected_kg' => 0
            ];
            
            if ($this->db->tableExists('riwayat_pengangkutan')) {
                $wasteStats = [
                    'total_collections' => $this->db->table('riwayat_pengangkutan')->countAllResults(),
                    'completed_collections' => $this->db->table('riwayat_pengangkutan')
                        ->where('status', 'selesai')
                        ->countAllResults(),
                    'pending_collections' => $this->db->table('riwayat_pengangkutan')
                        ->where('status', 'dijadwalkan')
                        ->countAllResults(),
                    'total_waste_collected_kg' => $this->db->table('riwayat_pengangkutan')
                        ->where('status', 'selesai')
                        ->selectSum('berat_kg')
                        ->get()
                        ->getRow()->berat_kg ?? 0
                ];
            }
            
            // Manifest statistics (if table exists)
            $manifestStats = [
                'total_manifests' => 0,
                'approved_manifests' => 0,
                'pending_manifests' => 0
            ];
            
            if ($this->db->tableExists('manifest_elektronik')) {
                $manifestStats = [
                    'total_manifests' => $this->db->table('manifest_elektronik')->countAllResults(),
                    'approved_manifests' => $this->db->table('manifest_elektronik')
                        ->where('status', 'disetujui')
                        ->countAllResults(),
                    'pending_manifests' => $this->db->table('manifest_elektronik')
                        ->where('status', 'pending')
                        ->countAllResults()
                ];
            }
            
            // Recent activity
            $recentActivity = [
                'recent_users' => $this->db->table('users')
                    ->select('id, username, email, nama_lengkap, created_at')
                    ->orderBy('created_at', 'DESC')
                    ->limit(5)
                    ->get()
                    ->getResultArray(),
                
                'recent_transactions' => $this->db->table('transaksi_layanan')
                    ->select('transaksi_layanan.*, layanan.nama_layanan, users.nama_lengkap')
                    ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
                    ->join('users', 'users.id = transaksi_layanan.user_id')
                    ->orderBy('transaksi_layanan.created_at', 'DESC')
                    ->limit(5)
                    ->get()
                    ->getResultArray(),
                
                'recent_invoices' => $this->db->table('invoice')
                    ->select('invoice.*, users.nama_lengkap')
                    ->join('users', 'users.id = invoice.user_id')
                    ->orderBy('invoice.created_at', 'DESC')
                    ->limit(5)
                    ->get()
                    ->getResultArray()
            ];
            
            // Top services by revenue
            $topServices = $this->db->table('transaksi_layanan')
                ->select('layanan.nama_layanan, layanan.tipe_layanan, COUNT(*) as subscription_count, SUM(transaksi_layanan.total_harga) as total_revenue')
                ->join('layanan', 'layanan.id = transaksi_layanan.layanan_id')
                ->groupBy('transaksi_layanan.layanan_id')
                ->orderBy('total_revenue', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'users' => $userStats,
                    'services' => $serviceStats,
                    'transactions' => $transactionStats,
                    'invoices' => $invoiceStats,
                    'waste_collections' => $wasteStats,
                    'manifests' => $manifestStats,
                    'recent_activity' => $recentActivity,
                    'top_services' => $topServices,
                    'generated_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch system statistics: ' . $e->getMessage(), 500);
        }
    }
}
