<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\InvoiceModel;
use App\Helpers\ValidationHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class InvoiceController extends ResourceController
{
    use ResponseTrait;
    
    protected $invoiceModel;
    protected $jwtSecret;
    protected $db;
    
    public function __construct()
    {
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
     * List user invoices
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
            
            $invoices = $this->invoiceModel->getUserInvoices($user->user_id, $status);
            
            return $this->respond([
                'status' => 'success',
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch invoices: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get invoice details by ID
     */
    public function show($id = null)
    {
        if (!$id) {
            return $this->fail('Invoice ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            $invoice = $this->invoiceModel->getInvoiceById($id);
            
            if (!$invoice) {
                return $this->failNotFound('Invoice not found');
            }
            
            // Check if user owns this invoice or is admin
            if ($invoice['user_id'] != $user->user_id && !in_array($user->role, ['admin_keuangan', 'superadmin'])) {
                return $this->failForbidden('Access denied');
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch invoice details: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Mark invoice as paid (admin only)
     */
    public function markAsPaid($id = null)
    {
        if (!$id) {
            return $this->fail('Invoice ID is required', 400);
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
            'metode_pembayaran' => 'permit_empty|max_length[50]|no_xss',
            'bukti_pembayaran' => 'permit_empty|max_length[255]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }
        
        $data = $this->request->getJSON(true);
        
        // Sanitize input
        if (isset($data['metode_pembayaran'])) {
            $data['metode_pembayaran'] = ValidationHelper::sanitizeString($data['metode_pembayaran']);
        }
        
        try {
            $invoice = $this->invoiceModel->find($id);
            
            if (!$invoice) {
                return $this->failNotFound('Invoice not found');
            }
            
            if ($invoice['status_pembayaran'] === 'lunas') {
                return $this->fail('Invoice is already paid', 400);
            }
            
            // Mark invoice as paid
            $updated = $this->invoiceModel->markAsPaid(
                $id,
                $data['metode_pembayaran'] ?? null,
                $data['bukti_pembayaran'] ?? null
            );
            
            if (!$updated) {
                return $this->fail('Failed to update invoice status', 500);
            }
            
            // Update related transaction status to 'diproses' if still pending
            $transaction = $this->db->table('transaksi_layanan')
                ->where('id', $invoice['transaksi_id'])
                ->get()
                ->getRowArray();
            
            if ($transaction && $transaction['status'] === 'pending') {
                $this->db->table('transaksi_layanan')
                    ->where('id', $invoice['transaksi_id'])
                    ->update(['status' => 'diproses']);
            }
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Invoice marked as paid successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to mark invoice as paid: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Download invoice as PDF
     */
    public function download($id = null)
    {
        if (!$id) {
            return $this->fail('Invoice ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            $invoice = $this->invoiceModel->getInvoiceById($id);
            
            if (!$invoice) {
                return $this->failNotFound('Invoice not found');
            }
            
            // Check if user owns this invoice or is admin
            if ($invoice['user_id'] != $user->user_id && !in_array($user->role, ['admin_keuangan', 'superadmin'])) {
                return $this->failForbidden('Access denied');
            }
            
            // Generate PDF (simplified version - would need a PDF library like TCPDF or Dompdf)
            // For now, return HTML that can be converted to PDF on frontend
            $html = $this->generateInvoiceHTML($invoice);
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'invoice_html' => $html,
                    'invoice_number' => $invoice['nomor_invoice'],
                    'message' => 'Invoice HTML generated. Use a PDF library on frontend to convert to PDF.'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to generate invoice: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Generate invoice HTML
     */
    private function generateInvoiceHTML($invoice)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Invoice ' . htmlspecialchars($invoice['nomor_invoice']) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .company-info { margin-bottom: 20px; }
                .invoice-details { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .total { font-weight: bold; font-size: 1.2em; }
                .footer { margin-top: 30px; text-align: center; font-size: 0.9em; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>INVOICE</h1>
                <p>' . htmlspecialchars($invoice['nomor_invoice']) . '</p>
            </div>
            
            <div class="company-info">
                <h3>Bill To:</h3>
                <p><strong>' . htmlspecialchars($invoice['nama_perusahaan']) . '</strong></p>
                <p>' . htmlspecialchars($invoice['nama_lengkap']) . '</p>
                <p>' . htmlspecialchars($invoice['alamat_perusahaan']) . '</p>
                <p>Phone: ' . htmlspecialchars($invoice['telepon']) . '</p>
                <p>Email: ' . htmlspecialchars($invoice['email']) . '</p>
            </div>
            
            <div class="invoice-details">
                <p><strong>Invoice Date:</strong> ' . htmlspecialchars($invoice['tanggal_invoice']) . '</p>
                <p><strong>Due Date:</strong> ' . htmlspecialchars($invoice['tanggal_jatuh_tempo']) . '</p>
                <p><strong>Transaction Code:</strong> ' . htmlspecialchars($invoice['kode_transaksi']) . '</p>
                <p><strong>Status:</strong> ' . htmlspecialchars($invoice['status_pembayaran']) . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . htmlspecialchars($invoice['nama_layanan']) . '</td>
                        <td>' . htmlspecialchars($invoice['tipe_layanan']) . '</td>
                        <td>' . htmlspecialchars($invoice['jumlah']) . '</td>
                        <td>Rp ' . number_format($invoice['total_harga'] / $invoice['jumlah'], 2) . '</td>
                        <td>Rp ' . number_format($invoice['total_harga'], 2) . '</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="total">
                <p>Total Amount: Rp ' . number_format($invoice['total_tagihan'], 2) . '</p>
            </div>
            
            <div class="footer">
                <p>Thank you for your business!</p>
                <p>Envindo Environmental Services</p>
            </div>
        </body>
        </html>
        ';
        
        return $html;
    }
}
