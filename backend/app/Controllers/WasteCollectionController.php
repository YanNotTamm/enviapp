<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\WasteCollectionModel;
use App\Helpers\ValidationHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class WasteCollectionController extends ResourceController
{
    use ResponseTrait;
    
    protected $wasteCollectionModel;
    protected $jwtSecret;
    
    public function __construct()
    {
        $this->wasteCollectionModel = new WasteCollectionModel();
        $this->jwtSecret = getenv('JWT_SECRET');
        
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
     * List collection history
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
            
            $collections = $this->wasteCollectionModel->getUserCollections($user->user_id, $status);
            
            return $this->respond([
                'status' => 'success',
                'data' => $collections
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch collection history: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get collection details by ID
     */
    public function show($id = null)
    {
        if (!$id) {
            return $this->fail('Collection ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            $collection = $this->wasteCollectionModel->getCollectionById($id);
            
            if (!$collection) {
                return $this->failNotFound('Collection not found');
            }
            
            // Check if user owns this collection or is admin
            if ($collection['user_id'] != $user->user_id && !in_array($user->role, ['admin_keuangan', 'superadmin'])) {
                return $this->failForbidden('Access denied');
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => $collection
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch collection details: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Schedule new waste collection
     */
    public function schedule()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        $rules = [
            'tanggal_pengangkutan' => 'required|valid_date',
            'jenis_limbah' => 'required|max_length[100]|no_xss',
            'berat_kg' => 'required|decimal|greater_than[0]',
            'volume_m3' => 'permit_empty|decimal|greater_than[0]',
            'lokasi_pengangkutan' => 'required|no_xss',
            'metode_pengangkutan' => 'required|max_length[50]|no_xss',
            'kendaraan_yang_digunakan' => 'permit_empty|max_length[100]|no_xss',
            'driver_name' => 'permit_empty|max_length[100]|no_xss',
            'catatan' => 'permit_empty|max_length[500]|no_xss'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }
        
        $data = $this->request->getJSON(true);
        
        // Sanitize input
        $data = ValidationHelper::sanitizeArray($data);
        
        try {
            // Generate unique manifest number
            do {
                $nomorManifest = 'MNF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            } while ($this->wasteCollectionModel->isManifestExists($nomorManifest));
            
            // Create collection schedule
            $collectionData = [
                'user_id' => $user->user_id,
                'tanggal_pengangkutan' => $data['tanggal_pengangkutan'],
                'jenis_limbah' => $data['jenis_limbah'],
                'berat_kg' => $data['berat_kg'],
                'volume_m3' => $data['volume_m3'] ?? null,
                'lokasi_pengangkutan' => $data['lokasi_pengangkutan'],
                'metode_pengangkutan' => $data['metode_pengangkutan'],
                'kendaraan_yang_digunakan' => $data['kendaraan_yang_digunakan'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'nomor_manifest' => $nomorManifest,
                'catatan' => $data['catatan'] ?? null,
                'status' => 'terjadwal'
            ];
            
            $collectionId = $this->wasteCollectionModel->insert($collectionData);
            
            if (!$collectionId) {
                return $this->fail('Failed to schedule collection', 500);
            }
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Waste collection scheduled successfully',
                'data' => [
                    'collection_id' => $collectionId,
                    'nomor_manifest' => $nomorManifest
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->fail('Failed to schedule collection: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Mark collection as complete
     */
    public function complete($id = null)
    {
        if (!$id) {
            return $this->fail('Collection ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        // Check if user is admin (only admin can mark as complete)
        if (!in_array($user->role, ['admin_keuangan', 'superadmin'])) {
            return $this->failForbidden('Access denied. Admin role required.');
        }
        
        $rules = [
            'dokumentasi' => 'permit_empty|max_length[255]',
            'catatan' => 'permit_empty|max_length[500]|no_xss'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }
        
        try {
            $collection = $this->wasteCollectionModel->find($id);
            
            if (!$collection) {
                return $this->failNotFound('Collection not found');
            }
            
            if ($collection['status'] === 'selesai') {
                return $this->fail('Collection is already marked as complete', 400);
            }
            
            $data = $this->request->getJSON(true);
            
            // Sanitize input
            if (isset($data['catatan'])) {
                $data['catatan'] = ValidationHelper::sanitizeString($data['catatan']);
            }
            
            // Update collection status
            $updateData = [
                'status' => 'selesai'
            ];
            
            if (isset($data['dokumentasi'])) {
                $updateData['dokumentasi'] = $data['dokumentasi'];
            }
            
            if (isset($data['catatan'])) {
                $updateData['catatan'] = $data['catatan'];
            }
            
            $updated = $this->wasteCollectionModel->update($id, $updateData);
            
            if (!$updated) {
                return $this->fail('Failed to mark collection as complete', 500);
            }
            
            // Award envipoin to user (e.g., 10 points per kg)
            $enviroinReward = (int) ($collection['berat_kg'] * 10);
            
            $db = \Config\Database::connect();
            $db->table('users')
                ->where('id', $collection['user_id'])
                ->set('envipoin', 'envipoin + ' . $enviroinReward, false)
                ->update();
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Collection marked as complete successfully',
                'data' => [
                    'envipoin_awarded' => $enviroinReward
                ]
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to mark collection as complete: ' . $e->getMessage(), 500);
        }
    }
}
