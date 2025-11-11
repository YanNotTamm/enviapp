<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\TransactionModel;
use App\Models\ServiceModel;
use App\Helpers\ValidationHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TransactionController extends ResourceController
{
    use ResponseTrait;
    
    protected $transactionModel;
    protected $serviceModel;
    protected $jwtSecret;
    protected $db;
    
    public function __construct()
    {
        $this->transactionModel = new TransactionModel();
        $this->serviceModel = new ServiceModel();
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
     * List user transactions
     */
    public function index()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            // Get status filter from query params
            $status = $this->request->getGet('status');
            
            $transactions = $this->transactionModel->getUserTransactions($user->user_id, $status);
            
            return $this->respond([
                'status' => 'success',
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch transactions: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get transaction details by ID
     */
    public function show($id = null)
    {
        if (!$id) {
            return $this->fail('Transaction ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            $transaction = $this->transactionModel->getTransactionById($id);
            
            if (!$transaction) {
                return $this->failNotFound('Transaction not found');
            }
            
            // Check if user owns this transaction or is admin
            if ($transaction['user_id'] != $user->user_id && !in_array($user->role, ['admin_keuangan', 'superadmin'])) {
                return $this->failForbidden('Access denied');
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch transaction details: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create new transaction
     */
    public function create()
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
            
            // Generate unique transaction code
            do {
                $kodeTransaksi = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            } while ($this->transactionModel->isCodeExists($kodeTransaksi));
            
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
            
            $transactionId = $this->transactionModel->createTransaction($transactionData);
            
            if (!$transactionId) {
                return $this->fail('Failed to create transaction', 500);
            }
            
            // Create invoice
            $nomorInvoice = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $tanggalJatuhTempo = date('Y-m-d', strtotime('+7 days'));
            
            $invoiceData = [
                'user_id' => $user->user_id,
                'transaksi_id' => $transactionId,
                'nomor_invoice' => $nomorInvoice,
                'tanggal_invoice' => date('Y-m-d'),
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'total_tagihan' => $totalHarga,
                'status_pembayaran' => 'belum_bayar'
            ];
            
            $this->db->table('invoice')->insert($invoiceData);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Transaction created successfully',
                'data' => [
                    'transaction_id' => $transactionId,
                    'kode_transaksi' => $kodeTransaksi,
                    'nomor_invoice' => $nomorInvoice,
                    'total_harga' => $totalHarga,
                    'tanggal_jatuh_tempo' => $tanggalJatuhTempo
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->fail('Failed to create transaction: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update transaction status (admin only)
     */
    public function updateStatus($id = null)
    {
        if (!$id) {
            return $this->fail('Transaction ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        // Check if user is admin
        if (!in_array($user->role, ['admin_keuangan', 'superadmin'])) {
            return $this->failForbidden('Access denied. Admin role required.');
        }
        
        $rules = [
            'status' => 'required|in_list[pending,diproses,aktif,selesai,dibatalkan]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }
        
        $data = $this->request->getJSON(true);
        
        try {
            $transaction = $this->transactionModel->find($id);
            
            if (!$transaction) {
                return $this->failNotFound('Transaction not found');
            }
            
            // Update transaction status
            $updated = $this->transactionModel->updateTransactionStatus($id, $data['status']);
            
            if (!$updated) {
                return $this->fail('Failed to update transaction status', 500);
            }
            
            // If status is 'aktif', update user's active service
            if ($data['status'] === 'aktif') {
                $service = $this->serviceModel->find($transaction['layanan_id']);
                if ($service) {
                    $this->db->table('users')
                        ->where('id', $transaction['user_id'])
                        ->update([
                            'layanan_aktif' => $service['tipe_layanan'],
                            'masa_berlaku' => $transaction['tanggal_selesai']
                        ]);
                }
            }
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Transaction status updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update transaction status: ' . $e->getMessage(), 500);
        }
    }
}
