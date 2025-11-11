<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\ManifestModel;
use App\Helpers\ValidationHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ManifestController extends ResourceController
{
    use ResponseTrait;
    
    protected $manifestModel;
    protected $jwtSecret;
    protected $db;
    
    public function __construct()
    {
        $this->manifestModel = new ManifestModel();
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
     * List manifests
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
            
            // If admin, show all manifests, otherwise show only user's manifests
            if (in_array($user->role, ['admin_keuangan', 'superadmin'])) {
                $manifests = $this->manifestModel->getAllManifests($status);
            } else {
                $manifests = $this->manifestModel->getUserManifests($user->user_id, $status);
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => $manifests
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch manifests: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get manifest details by ID
     */
    public function show($id = null)
    {
        if (!$id) {
            return $this->fail('Manifest ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            $manifest = $this->manifestModel->getManifestById($id);
            
            if (!$manifest) {
                return $this->failNotFound('Manifest not found');
            }
            
            // Check if user owns this manifest or is admin
            if ($manifest['user_id'] != $user->user_id && !in_array($user->role, ['admin_keuangan', 'superadmin'])) {
                return $this->failForbidden('Access denied');
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => $manifest
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch manifest details: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create new manifest
     */
    public function create()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        $rules = [
            'riwayat_pengangkutan_id' => 'required|integer|is_not_unique[riwayat_pengangkutan.id]',
            'tanggal_manifest' => 'required|valid_date',
            'jenis_limbah' => 'required|max_length[100]|no_xss',
            'kode_limbah' => 'required|max_length[50]|no_xss',
            'jumlah_limbah_kg' => 'required|decimal|greater_than[0]',
            'asal_limbah' => 'required|no_xss',
            'tujuan_pengolahan' => 'required|no_xss',
            'metode_pengolahan' => 'required|max_length[200]|no_xss',
            'penyedia_jasa' => 'required|max_length[150]|no_xss',
            'dokumen_pendukung' => 'permit_empty|max_length[255]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }
        
        $data = $this->request->getJSON(true);
        
        // Sanitize input
        $data = ValidationHelper::sanitizeArray($data);
        
        try {
            // Verify that the waste collection belongs to the user
            $wasteCollection = $this->db->table('riwayat_pengangkutan')
                ->where('id', $data['riwayat_pengangkutan_id'])
                ->where('user_id', $user->user_id)
                ->get()
                ->getRowArray();
            
            if (!$wasteCollection) {
                return $this->fail('Waste collection not found or access denied', 404);
            }
            
            // Generate unique manifest number
            do {
                $nomorManifest = 'MNFE-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            } while ($this->manifestModel->isManifestNumberExists($nomorManifest));
            
            // Create manifest
            $manifestData = [
                'user_id' => $user->user_id,
                'riwayat_pengangkutan_id' => $data['riwayat_pengangkutan_id'],
                'nomor_manifest' => $nomorManifest,
                'tanggal_manifest' => $data['tanggal_manifest'],
                'jenis_limbah' => $data['jenis_limbah'],
                'kode_limbah' => $data['kode_limbah'],
                'jumlah_limbah_kg' => $data['jumlah_limbah_kg'],
                'asal_limbah' => $data['asal_limbah'],
                'tujuan_pengolahan' => $data['tujuan_pengolahan'],
                'metode_pengolahan' => $data['metode_pengolahan'],
                'penyedia_jasa' => $data['penyedia_jasa'],
                'dokumen_pendukung' => $data['dokumen_pendukung'] ?? null,
                'status_manifest' => 'draft'
            ];
            
            $manifestId = $this->manifestModel->insert($manifestData);
            
            if (!$manifestId) {
                return $this->fail('Failed to create manifest', 500);
            }
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Manifest created successfully',
                'data' => [
                    'manifest_id' => $manifestId,
                    'nomor_manifest' => $nomorManifest
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->fail('Failed to create manifest: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Approve manifest (superadmin only)
     */
    public function approve($id = null)
    {
        if (!$id) {
            return $this->fail('Manifest ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        // Check if user is superadmin
        if ($user->role !== 'superadmin') {
            return $this->failForbidden('Access denied. Super admin role required.');
        }
        
        $rules = [
            'action' => 'required|in_list[approve,reject]',
            'catatan_persetujuan' => 'permit_empty|max_length[500]|no_xss'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }
        
        $data = $this->request->getJSON(true);
        
        // Sanitize input
        if (isset($data['catatan_persetujuan'])) {
            $data['catatan_persetujuan'] = ValidationHelper::sanitizeString($data['catatan_persetujuan']);
        }
        
        try {
            $manifest = $this->manifestModel->find($id);
            
            if (!$manifest) {
                return $this->failNotFound('Manifest not found');
            }
            
            if (!in_array($manifest['status_manifest'], ['draft', 'diajukan'])) {
                return $this->fail('Manifest cannot be approved in current status', 400);
            }
            
            // Update manifest status
            $updateData = [
                'status_manifest' => $data['action'] === 'approve' ? 'disetujui' : 'ditolak',
                'tanggal_persetujuan' => date('Y-m-d H:i:s'),
                'disetujui_oleh' => $user->email,
                'catatan_persetujuan' => $data['catatan_persetujuan'] ?? null
            ];
            
            $updated = $this->manifestModel->update($id, $updateData);
            
            if (!$updated) {
                return $this->fail('Failed to update manifest status', 500);
            }
            
            $message = $data['action'] === 'approve' 
                ? 'Manifest approved successfully' 
                : 'Manifest rejected';
            
            return $this->respond([
                'status' => 'success',
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to process manifest approval: ' . $e->getMessage(), 500);
        }
    }
}
