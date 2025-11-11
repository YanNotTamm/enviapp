<?php

namespace App\Helpers;

class ValidationHelper
{
    /**
     * Sanitize string input - remove HTML tags and encode special characters
     */
    public static function sanitizeString($input)
    {
        if (is_string($input)) {
            // Remove HTML tags
            $input = strip_tags($input);
            // Encode special characters
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            // Trim whitespace
            $input = trim($input);
        }
        return $input;
    }
    
    /**
     * Sanitize array of inputs
     */
    public static function sanitizeArray($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = self::sanitizeString($value);
            }
        }
        return $sanitized;
    }
    
    /**
     * Validate phone number format
     */
    public static function isValidPhone($phone)
    {
        // Allow digits, spaces, hyphens, parentheses, and plus sign
        return preg_match('/^[\d\s\-\(\)\+]+$/', $phone);
    }
    
    /**
     * Validate username format (alphanumeric and underscore only)
     */
    public static function isValidUsername($username)
    {
        return preg_match('/^[a-zA-Z0-9_]+$/', $username);
    }
    
    /**
     * Sanitize filename for uploads
     */
    public static function sanitizeFilename($filename)
    {
        // Remove any path information
        $filename = basename($filename);
        
        // Get file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove special characters from name
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        
        // Limit length
        $name = substr($name, 0, 100);
        
        return $name . '.' . $ext;
    }
    
    /**
     * Encode output for HTML display
     */
    public static function encodeOutput($data)
    {
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        if (is_array($data)) {
            return array_map([self::class, 'encodeOutput'], $data);
        }
        
        return $data;
    }
}
