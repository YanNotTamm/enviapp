<?php

namespace App\Validation;

class CustomRules
{
    /**
     * Validate username format (alphanumeric and underscore only)
     */
    public function valid_username(string $str, ?string &$error = null): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $str)) {
            $error = 'The {field} field must contain only letters, numbers, and underscores.';
            return false;
        }
        return true;
    }
    
    /**
     * Validate phone number format
     */
    public function valid_phone(string $str, ?string &$error = null): bool
    {
        // Remove spaces and hyphens for validation
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $str);
        
        if (!preg_match('/^[\+]?[0-9]{10,15}$/', $cleaned)) {
            $error = 'The {field} field must be a valid phone number.';
            return false;
        }
        return true;
    }
    
    /**
     * Validate strong password
     * Must contain: uppercase, lowercase, number, special character
     */
    public function strong_password(string $str, ?string &$error = null): bool
    {
        $hasUppercase = preg_match('/[A-Z]/', $str);
        $hasLowercase = preg_match('/[a-z]/', $str);
        $hasNumber = preg_match('/[0-9]/', $str);
        $hasSpecial = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $str);
        
        if (!$hasUppercase || !$hasLowercase || !$hasNumber || !$hasSpecial) {
            $error = 'The {field} must contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
            return false;
        }
        return true;
    }
    
    /**
     * Validate no SQL injection patterns
     */
    public function no_sql_injection(string $str, ?string &$error = null): bool
    {
        $sqlPatterns = [
            '/(\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bCREATE\b|\bALTER\b)/i',
            '/(\bUNION\b|\bJOIN\b)/i',
            '/(--|#|\/\*|\*\/)/i',
            '/(\bOR\b|\bAND\b)\s+[\d\w]+\s*=\s*[\d\w]+/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $str)) {
                $error = 'The {field} contains invalid characters.';
                return false;
            }
        }
        return true;
    }
    
    /**
     * Validate no XSS patterns
     */
    public function no_xss(string $str, ?string &$error = null): bool
    {
        $xssPatterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $str)) {
                $error = 'The {field} contains invalid content.';
                return false;
            }
        }
        return true;
    }
    
    /**
     * Validate allowed file extension
     */
    public function allowed_file_ext(string $str, string $params, array $data, ?string &$error = null): bool
    {
        $allowedExts = explode(',', $params);
        $ext = strtolower(pathinfo($str, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExts)) {
            $error = 'The {field} must be one of: ' . implode(', ', $allowedExts);
            return false;
        }
        return true;
    }
    
    /**
     * Validate file size
     */
    public function max_file_size(string $str, string $params, array $data, ?string &$error = null): bool
    {
        // This is used with file upload validation
        // The actual file size check is done in FileUploadHelper
        return true;
    }
}
