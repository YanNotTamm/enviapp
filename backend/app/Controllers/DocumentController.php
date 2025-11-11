<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\DocumentModel;
use App\Helpers\ValidationHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class DocumentController extends ResourceController
{
    use ResponseTrait;
    
    protected $documentModel;
    protected $jwtSecret;
    protected $uploadPath;
    protected $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    protected $maxSize = 5242880; // 5MB in bytes
    
    public function __construct()
    {
        $this->documentModel = new DocumentModel();
        $this->jwtSecret = getenv('JWT_SECRET');
        $this->uploadPath = WRITEPATH . 'uploads/documents/';
        
        if (!$this->jwtSecret) {
            throw new \RuntimeException('JWT_SECRET must be set in environment variables');
        }
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
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
     * List user documents
     */
    public function index()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            // Get jenis_dokumen filter from query params
            $jenisDokumen = $this->request->getGet('jenis_dokumen');
            
            $documents = $this->documentModel->getUserDocuments($user->user_id, $jenisDokumen);
            
            return $this->respond([
                'status' => 'success',
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch documents: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get document details by ID
     */
    public function show($id = null)
    {
        if (!$id) {
            return $this->fail('Document ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            $document = $this->documentModel->getDocumentById($id);
            
            if (!$document) {
                return $this->failNotFound('Document not found');
            }
            
            // Check if user owns this document or is admin
            if ($document['user_id'] != $user->user_id && !in_array($user->role, ['admin_keuangan', 'superadmin'])) {
                return $this->failForbidden('Access denied');
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => $document
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch document details: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Upload new document
     */
    public function upload()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        // Validate file upload
        $file = $this->request->getFile('file');
        
        if (!$file) {
            return $this->fail('No file uploaded', 400);
        }
        
        if (!$file->isValid()) {
            return $this->fail('Invalid file upload: ' . $file->getErrorString(), 400);
        }
        
        // Validate file type
        $extension = strtolower($file->getExtension());
        if (!in_array($extension, $this->allowedTypes)) {
            return $this->fail('File type not allowed. Allowed types: ' . implode(', ', $this->allowedTypes), 400);
        }
        
        // Validate file size
        if ($file->getSize() > $this->maxSize) {
            return $this->fail('File size exceeds maximum limit of 5MB', 400);
        }
        
        // Validate other fields
        $rules = [
            'jenis_dokumen' => 'required|max_length[50]|no_xss',
            'nama_dokumen' => 'required|max_length[200]|no_xss',
            'transaksi_id' => 'permit_empty|integer',
            'tanggal_berlaku' => 'permit_empty|valid_date',
            'tanggal_kadaluarsa' => 'permit_empty|valid_date',
            'catatan' => 'permit_empty|max_length[500]|no_xss'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }
        
        try {
            // Sanitize filename
            $originalName = $file->getName();
            $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            
            // Generate unique filename
            $newName = uniqid() . '_' . $sanitizedName;
            
            // Move file to upload directory
            $file->move($this->uploadPath, $newName);
            
            // Get form data
            $jenisDokumen = ValidationHelper::sanitizeString($this->request->getPost('jenis_dokumen'));
            $namaDokumen = ValidationHelper::sanitizeString($this->request->getPost('nama_dokumen'));
            $transaksiId = $this->request->getPost('transaksi_id');
            $tanggalBerlaku = $this->request->getPost('tanggal_berlaku');
            $tanggalKadaluarsa = $this->request->getPost('tanggal_kadaluarsa');
            $catatan = ValidationHelper::sanitizeString($this->request->getPost('catatan') ?? '');
            
            // Save document metadata to database
            $documentData = [
                'user_id' => $user->user_id,
                'transaksi_id' => $transaksiId ?: null,
                'jenis_dokumen' => $jenisDokumen,
                'nama_dokumen' => $namaDokumen,
                'file_path' => $newName,
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'tanggal_upload' => date('Y-m-d H:i:s'),
                'tanggal_berlaku' => $tanggalBerlaku ?: null,
                'tanggal_kadaluarsa' => $tanggalKadaluarsa ?: null,
                'status_dokumen' => 'aktif',
                'catatan' => $catatan ?: null
            ];
            
            $documentId = $this->documentModel->insert($documentData);
            
            if (!$documentId) {
                // Delete uploaded file if database insert fails
                unlink($this->uploadPath . $newName);
                return $this->fail('Failed to save document metadata', 500);
            }
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Document uploaded successfully',
                'data' => [
                    'document_id' => $documentId,
                    'file_name' => $newName,
                    'file_size' => $file->getSize()
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->fail('Failed to upload document: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update document metadata
     */
    public function update($id = null)
    {
        if (!$id) {
            return $this->fail('Document ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        $rules = [
            'jenis_dokumen' => 'permit_empty|max_length[50]|no_xss',
            'nama_dokumen' => 'permit_empty|max_length[200]|no_xss',
            'tanggal_berlaku' => 'permit_empty|valid_date',
            'tanggal_kadaluarsa' => 'permit_empty|valid_date',
            'status_dokumen' => 'permit_empty|in_list[aktif,kadaluarsa,ditolak]',
            'catatan' => 'permit_empty|max_length[500]|no_xss'
        ];
        
        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }
        
        try {
            $document = $this->documentModel->find($id);
            
            if (!$document) {
                return $this->failNotFound('Document not found');
            }
            
            // Check if user owns this document or is admin
            if ($document['user_id'] != $user->user_id && !in_array($user->role, ['admin_keuangan', 'superadmin'])) {
                return $this->failForbidden('Access denied');
            }
            
            $data = $this->request->getJSON(true);
            
            // Sanitize input
            $data = ValidationHelper::sanitizeArray($data);
            
            // Remove fields that shouldn't be updated
            unset($data['id']);
            unset($data['user_id']);
            unset($data['file_path']);
            unset($data['file_size']);
            unset($data['file_type']);
            unset($data['tanggal_upload']);
            
            $updated = $this->documentModel->update($id, $data);
            
            if (!$updated) {
                return $this->fail('Failed to update document', 500);
            }
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Document updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to update document: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete document
     */
    public function delete($id = null)
    {
        if (!$id) {
            return $this->fail('Document ID is required', 400);
        }
        
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return $this->failUnauthorized('Invalid or missing token');
        }
        
        try {
            $document = $this->documentModel->find($id);
            
            if (!$document) {
                return $this->failNotFound('Document not found');
            }
            
            // Check if user owns this document or is admin
            if ($document['user_id'] != $user->user_id && !in_array($user->role, ['admin_keuangan', 'superadmin'])) {
                return $this->failForbidden('Access denied');
            }
            
            // Delete file from filesystem
            $filePath = $this->uploadPath . $document['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete from database
            $deleted = $this->documentModel->delete($id);
            
            if (!$deleted) {
                return $this->fail('Failed to delete document', 500);
            }
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->fail('Failed to delete document: ' . $e->getMessage(), 500);
        }
    }
}
