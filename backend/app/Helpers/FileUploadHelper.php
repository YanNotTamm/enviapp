<?php

namespace App\Helpers;

class FileUploadHelper
{
    // Allowed file types with their MIME types
    private static $allowedTypes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'jpg' => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/jpg'],
        'png' => ['image/png'],
        'gif' => ['image/gif']
    ];
    
    // Maximum file size in bytes (5MB)
    private static $maxFileSize = 5242880; // 5 * 1024 * 1024
    
    /**
     * Validate uploaded file
     * 
     * @param mixed $file The uploaded file
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validateFile($file)
    {
        if (!$file) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }
        
        // Check if file was uploaded successfully
        if (!$file->isValid()) {
            return ['valid' => false, 'error' => 'File upload failed: ' . $file->getErrorString()];
        }
        
        // Check file size
        if ($file->getSize() > self::$maxFileSize) {
            $maxSizeMB = self::$maxFileSize / 1048576;
            return ['valid' => false, 'error' => "File size exceeds maximum allowed size of {$maxSizeMB}MB"];
        }
        
        // Get file extension
        $extension = strtolower($file->getClientExtension());
        
        // Check if extension is allowed
        if (!isset(self::$allowedTypes[$extension])) {
            return ['valid' => false, 'error' => 'File type not allowed. Allowed types: ' . implode(', ', array_keys(self::$allowedTypes))];
        }
        
        // Verify MIME type matches extension
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::$allowedTypes[$extension])) {
            return ['valid' => false, 'error' => 'File MIME type does not match extension'];
        }
        
        // Additional security: Check file content for common attack patterns
        if (self::containsMaliciousContent($file)) {
            return ['valid' => false, 'error' => 'File contains potentially malicious content'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Generate a safe, unique filename
     * 
     * @param mixed $file The uploaded file
     * @param int $userId User ID for organizing files
     * @return string Safe filename
     */
    public static function generateSafeFilename($file, $userId)
    {
        $extension = strtolower($file->getClientExtension());
        $originalName = pathinfo($file->getClientName(), PATHINFO_FILENAME);
        
        // Sanitize original filename
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalName);
        $safeName = substr($safeName, 0, 50); // Limit length
        
        // Generate unique filename with timestamp and random string
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return "{$userId}_{$timestamp}_{$random}_{$safeName}.{$extension}";
    }
    
    /**
     * Get upload directory path (outside web root for security)
     * 
     * @param string $subdir Optional subdirectory
     * @return string Full path to upload directory
     */
    public static function getUploadPath($subdir = '')
    {
        // Use writable directory which is outside public web root
        $basePath = WRITEPATH . 'uploads';
        
        if ($subdir) {
            $basePath .= DIRECTORY_SEPARATOR . $subdir;
        }
        
        // Create directory if it doesn't exist
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        
        return $basePath;
    }
    
    /**
     * Save uploaded file securely
     * 
     * @param mixed $file The uploaded file
     * @param int $userId User ID
     * @param string $subdir Optional subdirectory
     * @return array ['success' => bool, 'filename' => string|null, 'path' => string|null, 'error' => string|null]
     */
    public static function saveFile($file, $userId, $subdir = 'documents')
    {
        // Validate file first
        $validation = self::validateFile($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'filename' => null,
                'path' => null,
                'error' => $validation['error']
            ];
        }
        
        // Generate safe filename
        $filename = self::generateSafeFilename($file, $userId);
        
        // Get upload path
        $uploadPath = self::getUploadPath($subdir);
        
        try {
            // Move file to secure location
            $file->move($uploadPath, $filename);
            
            // Get relative path for database storage
            $relativePath = $subdir . DIRECTORY_SEPARATOR . $filename;
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $relativePath,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'filename' => null,
                'path' => null,
                'error' => 'Failed to save file: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete uploaded file
     * 
     * @param string $relativePath Relative path to file
     * @return bool Success status
     */
    public static function deleteFile($relativePath)
    {
        $fullPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $relativePath;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * Get full path to file for reading
     * 
     * @param string $relativePath Relative path to file
     * @return string|null Full path if file exists, null otherwise
     */
    public static function getFilePath($relativePath)
    {
        $fullPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $relativePath;
        
        if (file_exists($fullPath)) {
            return $fullPath;
        }
        
        return null;
    }
    
    /**
     * Check file content for malicious patterns
     * 
     * @param mixed $file The uploaded file
     * @return bool True if malicious content detected
     */
    private static function containsMaliciousContent($file)
    {
        // Read first 1KB of file for analysis
        $handle = fopen($file->getTempName(), 'r');
        if (!$handle) {
            return false;
        }
        
        $content = fread($handle, 1024);
        fclose($handle);
        
        // Check for PHP code in uploaded files
        if (preg_match('/<\?php|<\?=|<script/i', $content)) {
            return true;
        }
        
        // Check for executable signatures
        $signatures = [
            "\x4D\x5A", // PE/EXE
            "\x7F\x45\x4C\x46", // ELF
            "#!", // Shell script
        ];
        
        foreach ($signatures as $signature) {
            if (strpos($content, $signature) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get allowed file extensions
     * 
     * @return array List of allowed extensions
     */
    public static function getAllowedExtensions()
    {
        return array_keys(self::$allowedTypes);
    }
    
    /**
     * Get maximum file size in MB
     * 
     * @return float Maximum file size in MB
     */
    public static function getMaxFileSizeMB()
    {
        return self::$maxFileSize / 1048576;
    }
}
