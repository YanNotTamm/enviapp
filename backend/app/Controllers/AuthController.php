<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use App\Helpers\ValidationHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\EmailHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends ResourceController
{
    use ResponseTrait;
    
    protected $userModel;
    protected $jwtSecret;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->jwtSecret = getenv('JWT_SECRET');
        
        if (!$this->jwtSecret) {
            throw new \RuntimeException('JWT_SECRET must be set in environment variables');
        }
    }
    
    /**
     * User registration
     */
    public function register()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]|valid_username|no_sql_injection|no_xss',
            'email' => 'required|valid_email|is_unique[users.email]|max_length[100]',
            'password' => 'required|min_length[8]|max_length[255]|strong_password',
            'nama_perusahaan' => 'required|min_length[3]|max_length[100]|no_xss',
            'alamat_perusahaan' => 'required|min_length[10]|max_length[255]|no_xss',
            'no_telp' => 'required|min_length[10]|max_length[20]|valid_phone',
            'role' => 'permit_empty|in_list[user,admin_keuangan]'
        ];
        
        $validationMessages = [
            'password' => [
                'strong_password' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
            ],
            'username' => [
                'valid_username' => 'Username can only contain letters, numbers, and underscores.'
            ],
            'no_telp' => [
                'valid_phone' => 'Please enter a valid phone number.'
            ]
        ];
        
        if (!$this->validate($rules, $validationMessages)) {
            return ResponseHelper::validationError($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        // Sanitize all input data
        $data = ValidationHelper::sanitizeArray($data);
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Set nama_lengkap from username if not provided
        if (!isset($data['nama_lengkap']) || empty($data['nama_lengkap'])) {
            $data['nama_lengkap'] = $data['username'];
        }
        
        // Map no_telp to telepon for database compatibility
        if (isset($data['no_telp'])) {
            $data['telepon'] = $data['no_telp'];
            unset($data['no_telp']);
        }
        
        // Set default values
        $data['role'] = $data['role'] ?? 'user';
        
        // Email verification enabled
        $data['email_verified'] = false;
        $data['verification_token'] = bin2hex(random_bytes(32));
        $data['envipoin'] = 0;
        $data['masa_berlaku'] = date('Y-m-d', strtotime('+1 year'));
        $data['layanan_aktif'] = 'EnviReg';
        
        try {
            $userId = $this->userModel->insert($data);
            
            if ($userId) {
                // Send verification email
                $emailSent = false;
                try {
                    $emailSent = $this->sendVerificationEmail(
                        $data['email'], 
                        $data['username'], 
                        $data['verification_token']
                    );
                } catch (\Exception $e) {
                    log_message('error', 'Email verification could not be sent: ' . $e->getMessage());
                }
                
                $message = 'Registration successful. Please check your email for verification link.';
                if (!$emailSent) {
                    $message = 'Registration successful, but verification email could not be sent. Please contact support.';
                }
                
                return ResponseHelper::created([
                    'user_id' => $userId,
                    'email' => $data['email'],
                    'email_sent' => $emailSent
                ], $message);
            }
            
            return ResponseHelper::serverError('Registration failed');
        } catch (\Exception $e) {
            return ResponseHelper::serverError('Registration failed', $e);
        }
    }
    
    /**
     * User login
     */
    public function login()
    {
        $rules = [
            'email' => 'required|valid_email|max_length[100]',
            'password' => 'required|max_length[255]'
        ];
        
        if (!$this->validate($rules)) {
            return ResponseHelper::validationError($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        // Sanitize input
        $data['email'] = ValidationHelper::sanitizeString($data['email'] ?? '');
        $data['password'] = $data['password'] ?? ''; // Don't sanitize password as it may contain special chars
        
        // Find user by email
        $user = $this->userModel->where('email', $data['email'])->first();
        
        if (!$user) {
            return ResponseHelper::unauthorized('Invalid credentials');
        }
        
        // Verify password
        if (!password_verify($data['password'], $user['password'])) {
            return ResponseHelper::unauthorized('Invalid credentials');
        }
        
        // Check if email is verified
        if (!$user['email_verified']) {
            return ResponseHelper::unauthorized('Please verify your email address first');
        }
        
        // Generate JWT token
        $payload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24), // 24 hours
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');
        
        return ResponseHelper::success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'nama_lengkap' => $user['nama_lengkap'],
                'nama_perusahaan' => $user['nama_perusahaan'],
                'role' => $user['role'],
                'envipoin' => $user['envipoin'],
                'layanan_aktif' => $user['layanan_aktif']
            ]
        ], 'Login successful');
    }
    
    /**
     * Email verification
     */
    public function verifyEmail($token = null)
    {
        if (!$token) {
            return ResponseHelper::error('Verification token is required', null, 400, 'MISSING_TOKEN');
        }
        
        $user = $this->userModel->where('verification_token', $token)->first();
        
        if (!$user) {
            return ResponseHelper::error('Invalid verification token', null, 400, 'INVALID_TOKEN');
        }
        
        // Update user verification status
        $this->userModel->update($user['id'], [
            'email_verified' => true,
            'verification_token' => null,
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);
        
        return ResponseHelper::success(null, 'Email verified successfully. You can now login.');
    }
    
    /**
     * Forgot password
     */
    public function forgotPassword()
    {
        $rules = [
            'email' => 'required|valid_email|max_length[100]'
        ];
        
        if (!$this->validate($rules)) {
            return ResponseHelper::validationError($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        // Sanitize email
        $data['email'] = ValidationHelper::sanitizeString($data['email'] ?? '');
        
        $user = $this->userModel->where('email', $data['email'])->first();
        
        if (!$user) {
            return ResponseHelper::notFound('User not found');
        }
        
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Update user with reset token
        $this->userModel->update($user['id'], [
            'reset_token' => $resetToken,
            'reset_expires' => $resetExpires
        ]);
        
        // Send reset email
        $emailSent = $this->sendResetEmail(
            $user['email'],
            $user['username'],
            $resetToken
        );
        
        if (!$emailSent) {
            log_message('error', 'Failed to send password reset email to: ' . $user['email']);
        }
        
        // Always return success to prevent email enumeration
        return ResponseHelper::success(null, 'If an account exists with that email, a password reset link has been sent.');
    }
    
    /**
     * Reset password
     */
    public function resetPassword()
    {
        $rules = [
            'token' => 'required|max_length[64]|alpha_numeric',
            'password' => 'required|min_length[8]|max_length[255]|strong_password'
        ];
        
        $validationMessages = [
            'password' => [
                'strong_password' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
            ]
        ];
        
        if (!$this->validate($rules, $validationMessages)) {
            return ResponseHelper::validationError($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        // Sanitize token
        $data['token'] = ValidationHelper::sanitizeString($data['token'] ?? '');
        
        $user = $this->userModel->where('reset_token', $data['token'])->first();
        
        if (!$user) {
            return ResponseHelper::error('Invalid reset token', null, 400, 'INVALID_TOKEN');
        }
        
        // Check if token is expired
        if (strtotime($user['reset_expires']) < time()) {
            return ResponseHelper::error('Reset token has expired', null, 400, 'TOKEN_EXPIRED');
        }
        
        // Update password
        $this->userModel->update($user['id'], [
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'reset_token' => null,
            'reset_expires' => null
        ]);
        
        return ResponseHelper::success(null, 'Password reset successful');
    }
    
    /**
     * Get current user profile
     */
    public function profile()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        
        if (!$authHeader) {
            return ResponseHelper::unauthorized('Token required');
        }
        
        try {
            $token = str_replace('Bearer ', '', $authHeader);
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            $user = $this->userModel->find($decoded->user_id);
            
            if (!$user) {
                return ResponseHelper::notFound('User not found');
            }
            
            // Remove sensitive data
            unset($user['password']);
            unset($user['verification_token']);
            unset($user['reset_token']);
            unset($user['reset_expires']);
            
            // Encode output for safe display
            $user = ValidationHelper::encodeOutput($user);
            
            return ResponseHelper::success($user);
        } catch (\Exception $e) {
            return ResponseHelper::unauthorized('Invalid token');
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        
        if (!$authHeader) {
            return ResponseHelper::unauthorized('Token required');
        }
        
        try {
            $token = str_replace('Bearer ', '', $authHeader);
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            $rules = [
                'nama_lengkap' => 'permit_empty|min_length[3]|max_length[100]|no_xss',
                'nama_perusahaan' => 'permit_empty|min_length[3]|max_length[100]|no_xss',
                'alamat_perusahaan' => 'permit_empty|min_length[10]|max_length[255]|no_xss',
                'no_telp' => 'permit_empty|min_length[10]|max_length[20]|valid_phone'
            ];
            
            if (!$this->validate($rules)) {
                return ResponseHelper::validationError($this->validator->getErrors());
            }
            
            $data = $this->request->getJSON(true);
            
            // Sanitize input data
            $data = ValidationHelper::sanitizeArray($data);
            
            // Map no_telp to telepon for database compatibility
            if (isset($data['no_telp'])) {
                $data['telepon'] = $data['no_telp'];
                unset($data['no_telp']);
            }
            
            // Remove any potentially harmful fields
            unset($data['id']);
            unset($data['email']);
            unset($data['password']);
            unset($data['role']);
            unset($data['email_verified']);
            unset($data['verification_token']);
            unset($data['reset_token']);
            unset($data['envipoin']);
            
            $this->userModel->update($decoded->user_id, $data);
            
            return ResponseHelper::success(null, 'Profile updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::unauthorized('Invalid token');
        }
    }
    
    /**
     * Logout (optional - mainly for client-side token removal)
     */
    public function logout()
    {
        // In a stateless JWT system, logout is mainly handled client-side
        // But we can add token blacklisting here if needed
        
        return ResponseHelper::success(null, 'Logout successful');
    }
    
    /**
     * Send verification email
     * 
     * @param string $email
     * @param string $username
     * @param string $token
     * @return bool
     */
    private function sendVerificationEmail(string $email, string $username, string $token): bool
    {
        try {
            $frontendUrl = getenv('FRONTEND_URL') ?: 'http://localhost:3000';
            $verificationUrl = $frontendUrl . '/verify-email/' . $token;
            
            // Load email template
            $message = view('emails/verification', [
                'username' => $username,
                'verificationUrl' => $verificationUrl,
                'token' => $token
            ]);
            
            return EmailHelper::send(
                $email,
                'Verify Your Email Address - Envindo',
                $message
            );
        } catch (\Exception $e) {
            log_message('error', 'Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send password reset email
     * 
     * @param string $email
     * @param string $username
     * @param string $token
     * @return bool
     */
    private function sendResetEmail(string $email, string $username, string $token): bool
    {
        try {
            $frontendUrl = getenv('FRONTEND_URL') ?: 'http://localhost:3000';
            $resetUrl = $frontendUrl . '/reset-password/' . $token;
            
            // Load email template
            $message = view('emails/password_reset', [
                'username' => $username,
                'resetUrl' => $resetUrl,
                'token' => $token
            ]);
            
            return EmailHelper::send(
                $email,
                'Password Reset Request - Envindo',
                $message
            );
        } catch (\Exception $e) {
            log_message('error', 'Failed to send password reset email: ' . $e->getMessage());
            return false;
        }
    }
}
