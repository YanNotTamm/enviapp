<?php

namespace App\Helpers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * ResponseHelper
 * 
 * Provides standardized response formats for API endpoints
 */
class ResponseHelper
{
    /**
     * Success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code (default: 200)
     * @return ResponseInterface
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): ResponseInterface
    {
        $response = [
            'status' => 'success',
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->setJSON($response)->setStatusCode($statusCode);
    }
    
    /**
     * Error response
     * 
     * @param string $message Error message
     * @param array|null $errors Detailed validation errors
     * @param int $statusCode HTTP status code (default: 400)
     * @param string|null $code Error code for client-side handling
     * @return ResponseInterface
     */
    public static function error(string $message, ?array $errors = null, int $statusCode = 400, ?string $code = null): ResponseInterface
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        if ($errors !== null && !empty($errors)) {
            $response['errors'] = $errors;
        }
        
        if ($code !== null) {
            $response['code'] = $code;
        }
        
        // Log server errors (5xx)
        if ($statusCode >= 500) {
            log_message('error', "API Error [{$statusCode}]: {$message}" . ($errors ? ' | Details: ' . json_encode($errors) : ''));
        }
        
        return response()->setJSON($response)->setStatusCode($statusCode);
    }
    
    /**
     * Validation error response
     * 
     * @param array $errors Validation errors
     * @param string $message Main error message
     * @return ResponseInterface
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): ResponseInterface
    {
        return self::error($message, $errors, 400, 'VALIDATION_ERROR');
    }
    
    /**
     * Unauthorized error response
     * 
     * @param string $message Error message
     * @return ResponseInterface
     */
    public static function unauthorized(string $message = 'Unauthorized'): ResponseInterface
    {
        return self::error($message, null, 401, 'UNAUTHORIZED');
    }
    
    /**
     * Forbidden error response
     * 
     * @param string $message Error message
     * @return ResponseInterface
     */
    public static function forbidden(string $message = 'Access denied'): ResponseInterface
    {
        return self::error($message, null, 403, 'FORBIDDEN');
    }
    
    /**
     * Not found error response
     * 
     * @param string $message Error message
     * @return ResponseInterface
     */
    public static function notFound(string $message = 'Resource not found'): ResponseInterface
    {
        return self::error($message, null, 404, 'NOT_FOUND');
    }
    
    /**
     * Server error response
     * 
     * @param string $message Error message (generic for security)
     * @param \Exception|null $exception Exception object for logging
     * @return ResponseInterface
     */
    public static function serverError(string $message = 'Internal server error', ?\Exception $exception = null): ResponseInterface
    {
        // Log the actual exception details
        if ($exception !== null) {
            log_message('error', 'Server Error: ' . $exception->getMessage() . ' | File: ' . $exception->getFile() . ' | Line: ' . $exception->getLine());
            log_message('error', 'Stack Trace: ' . $exception->getTraceAsString());
        }
        
        // Return generic message to client (don't expose internal details)
        return self::error($message, null, 500, 'SERVER_ERROR');
    }
    
    /**
     * Created response (for resource creation)
     * 
     * @param mixed $data Created resource data
     * @param string $message Success message
     * @return ResponseInterface
     */
    public static function created($data = null, string $message = 'Resource created successfully'): ResponseInterface
    {
        return self::success($data, $message, 201);
    }
    
    /**
     * No content response (for successful deletion)
     * 
     * @param string $message Success message
     * @return ResponseInterface
     */
    public static function noContent(string $message = 'Resource deleted successfully'): ResponseInterface
    {
        return self::success(null, $message, 204);
    }
}
