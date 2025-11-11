<?php

namespace App\Helpers;

use Config\Email as EmailConfig;

class EmailHelper
{
    /**
     * Test email connection
     * 
     * @return array
     */
    public static function testConnection(): array
    {
        try {
            $emailConfig = new EmailConfig();
            
            // Check if required email settings are configured
            if (empty($emailConfig->SMTPUser) || $emailConfig->SMTPUser === 'your-email@gmail.com') {
                return [
                    'success' => false,
                    'message' => 'Email not configured. Please update email settings in .env file.'
                ];
            }
            
            if (empty($emailConfig->SMTPPass) || $emailConfig->SMTPPass === 'your-app-password') {
                return [
                    'success' => false,
                    'message' => 'Email password not configured. Please update email.SMTPPass in .env file.'
                ];
            }
            
            $email = \Config\Services::email();
            
            // Try to initialize the email service
            $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName);
            
            return [
                'success' => true,
                'message' => 'Email configuration is valid.',
                'config' => [
                    'host' => $emailConfig->SMTPHost,
                    'port' => $emailConfig->SMTPPort,
                    'user' => $emailConfig->SMTPUser,
                    'from' => $emailConfig->fromEmail,
                    'crypto' => $emailConfig->SMTPCrypto
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Email configuration error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send email
     * 
     * @param string $to
     * @param string $subject
     * @param string $message
     * @return bool
     */
    public static function send(string $to, string $subject, string $message): bool
    {
        try {
            $email = \Config\Services::email();
            
            $email->setTo($to);
            $email->setSubject($subject);
            $email->setMessage($message);
            
            log_message('info', 'Attempting to send email to: ' . $to);
            
            if ($email->send()) {
                log_message('info', 'Email sent successfully to: ' . $to);
                return true;
            }
            
            // Get detailed error information
            $debugInfo = $email->printDebugger(['headers', 'subject', 'body']);
            log_message('error', 'Email send failed: ' . $debugInfo);
            
            return false;
        } catch (\Exception $e) {
            log_message('error', 'Email send exception: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Get last email error
     * 
     * @return string
     */
    public static function getLastError(): string
    {
        try {
            $email = \Config\Services::email();
            return $email->printDebugger(['headers']);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
