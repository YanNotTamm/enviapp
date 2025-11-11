<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\ServiceModel;
use App\Models\UserModel;
use App\Helpers\ValidationHelper;
use App\Helpers\ResponseHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ServiceController extends ResourceController
{
    use ResponseTrait;
    
    protected $serviceModel;
    protected $userModel;
    protected $jwtSecret;
    protected $db;
    
    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
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
     * List all available services
     */
    public function index()
    {
        try {
            $services = $this->serviceModel->getActiveServices();
            
            return ResponseHelper::success($services);
        } catch (\Exception $e) {
            return ResponseHelper::serverError('Failed to fetch services', $e);
        }
    }
    
    /**
     * Get service details by ID
     */
    public function show($id = null)
    {
        if (!$id) {
            return ResponseHelper::error('Service ID is required', null, 400, 'MISSING_ID');
        }
        
        try {
            $service = $this->serviceModel->getServiceById($id);
            
            if (!$service) {
                return ResponseHelper::notFound('Service not found');
            }
            
            return ResponseHelper::success($service);
        } catch (\Exception $e) {
            return ResponseHelper::serverError('Failed to fetch service details', $e);
        }
    }
    
    /**
     * Subscribe user to a service
     */
    public function subscribe()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        $rules = [
            'layanan_id' => 'required|integer|is_not_unique[layanan.id]',
            'jumlah' => 'permit_empty|integer|greater_than[0]',
            'tanggal_mulai' => 'permit_empty|valid_date',
            'catatan' => 'permit_empty|max_length[500]|no_xss'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }
        
        $data = $this->request->getJSON(true);
        
        // Sanitize input
        $data = ValidationHelper::sanitizeArray($data);
        
        try {
            // Get service details
            $service = $this->serviceModel->find($data['layanan_id']);
            
            if (!$service || !$service['is_active']) {
                return $this->fail('Service not available', 400);
            }
            
            // Calculate dates and price
            $jumlah = $data['jumlah'] ?? 1;
            $tanggalMulai = $data['tanggal_mulai'] ?? date('Y-m-d');
            $tanggalSelesai = date('Y-m-d', strtotime($tanggalMulai . ' + ' . $service['durasi_hari'] . ' days'));
            $totalHarga = $service['harga'] * $jumlah;
            
            // Generate transaction code
            $kodeTransaksi = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Create transaction
            $transactionData = [
                'user_id' => $user->user_id,
                'layanan_id' => $data['layanan_id'],
                'kode_transaksi' => $kodeTransaksi,
                'tanggal_pesan' => date('Y-m-d H:i:s'),
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'jumlah' => $jumlah,
                'total_harga' => $totalHarga,
                'status' => 'pending',
                'catatan' => $data['catatan'] ?? null
            ];
            
            $transactionId = $this->db->table('transaksi_layanan')->insert($transactionData);
            
            if (!$transactionId) {
                return $this->fail('Failed to create subscription', 500);
            }
            
            // Create invoice
            $nomorInvoice = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $tanggalJatuhTempo = date('Y-m-d', strtotime('+7 days'));
            
            $invoiceData = [
                'user_id' => $user->user_id,
                'transaksi_id' => $this->db->insertID(),
                'nomor_invoice' => $nomorInvoice,
                'tanggal_invoice' => date('Y-m-d'),
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'total_tagihan' => $totalHarga,
                'status_pembayaran' => 'belum_bayar'
            ];
            
            $this->db->table('invoice')->insert($invoiceData);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Service subscription created successfully',
                'data' => [
                    'transaction_id' => $this->db->insertID(),
                    'kode_transaksi' => $kodeTransaksi,
                    'nomor_invoice' => $nomorInvoice,
                    'total_harga' => $totalHarga,
                    'tanggal_jatuh_tempo' => $tanggalJatuhTempo
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->fail('Failed to create subscription: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get user's active services
     */
    public function myServices()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            $services = $this->serviceModel->getUserServices($user->user_id);
            
            return $this->respond([
                'status' => 'success',
                'data' => $services
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch user services: ' . $e->getMessage(), 500);
        }
    }
}
