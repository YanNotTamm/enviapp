<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Helpers\EmailHelper;
use App\Helpers\ResponseHelper;

class TestController extends ResourceController
{
    use ResponseTrait;
    
    /**
     * Test email configuration
     */
    public function testEmail()
    {
        $result = EmailHelper::testConnection();
        
        if ($result['success']) {
            return ResponseHelper::success($result, 'Email configuration is valid');
        }
        
        return ResponseHelper::error($result['message'], null, 500);
    }
    
    /**
     * Send test email
     */
    public function sendTestEmail()
    {
        $to = $this->request->getGet('to');
        
        if (!$to) {
            return ResponseHelper::error('Email address required. Use: ?to=your-email@example.com', null, 400);
        }
        
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ResponseHelper::error('Invalid email address', null, 400);
        }
        
        $subject = 'Test Email from Envindo System';
        $message = '
            <h1>Test Email</h1>
            <p>This is a test email from Envindo Waste Management System.</p>
            <p>If you received this email, your email configuration is working correctly!</p>
            <p>Sent at: ' . date('Y-m-d H:i:s') . '</p>
        ';
        
        $sent = EmailHelper::send($to, $subject, $message);
        
        if ($sent) {
            return ResponseHelper::success([
                'to' => $to,
                'sent_at' => date('Y-m-d H:i:s')
            ], 'Test email sent successfully');
        }
        
        // Get detailed error
        $errorDetail = EmailHelper::getLastError();
        
        return ResponseHelper::error('Failed to send test email', [
            'error_detail' => $errorDetail,
            'check_logs' => 'backend/writable/logs/log-' . date('Y-m-d') . '.php'
        ], 500);
    }
    
    /**
     * Get system info
     */
    public function info()
    {
        return ResponseHelper::success([
            'environment' => ENVIRONMENT,
            'php_version' => PHP_VERSION,
            'ci_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'base_url' => base_url(),
            'email_configured' => !empty(getenv('email.SMTPUser')) && getenv('email.SMTPUser') !== 'your-email@gmail.com'
        ]);
    }
}
 