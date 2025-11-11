<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * @internal
 */
final class AuthenticationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected $userModel;
    protected $testUser;
    protected $jwtSecret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new UserModel();
        $this->jwtSecret = getenv('JWT_SECRET') ?: 'test-secret-key-for-testing-only';
        
        // Create a test user
        $this->testUser = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => password_hash('Test@1234', PASSWORD_BCRYPT),
            'nama_lengkap' => 'Test User',
            'nama_perusahaan' => 'Test Company',
            'alamat_perusahaan' => '123 Test Street, Test City',
            'telepon' => '1234567890',
            'role' => 'user',
            'email_verified' => true,
            'envipoin' => 0,
            'masa_berlaku' => date('Y-m-d', strtotime('+1 year')),
            'layanan_aktif' => 'EnviReg'
        ];
    }

    /**
     * Test user registration with valid data
     */
    public function testUserRegistrationSuccess(): void
    {
        $userData = [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'NewUser@123',
            'nama_lengkap' => 'New User',
            'nama_perusahaan' => 'New Company',
            'alamat_perusahaan' => '456 New Street, New City',
            'telepon' => '9876543210'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/register', $userData);

        $result->assertStatus(201);
        $result->assertJSONFragment(['status' => 'success']);
        
        // Verify user was created in database
        $user = $this->userModel->where('email', $userData['email'])->first();
        $this->assertNotNull($user);
        $this->assertEquals($userData['username'], $user['username']);
        $this->assertFalse($user['email_verified']);
        $this->assertNotNull($user['verification_token']);
    }

    /**
     * Test registration with duplicate email
     */
    public function testRegistrationWithDuplicateEmail(): void
    {
        // Insert test user first
        $this->userModel->insert($this->testUser);

        $userData = [
            'username' => 'anotheruser',
            'email' => 'test@example.com', // Same email
            'password' => 'Test@1234',
            'nama_lengkap' => 'Another User',
            'nama_perusahaan' => 'Another Company',
            'alamat_perusahaan' => '789 Another Street',
            'telepon' => '5555555555'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/register', $userData);

        $result->assertStatus(400);
        $result->assertJSONFragment(['status' => 'error']);
    }

    /**
     * Test registration with weak password
     */
    public function testRegistrationWithWeakPassword(): void
    {
        $userData = [
            'username' => 'weakpassuser',
            'email' => 'weak@example.com',
            'password' => 'weak', // Weak password
            'nama_lengkap' => 'Weak Pass User',
            'nama_perusahaan' => 'Weak Company',
            'alamat_perusahaan' => '123 Weak Street',
            'telepon' => '1111111111'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/register', $userData);

        $result->assertStatus(400);
        $result->assertJSONFragment(['status' => 'error']);
    }

    /**
     * Test login with verified account
     */
    public function testLoginWithVerifiedAccount(): void
    {
        // Insert verified test user
        $this->userModel->insert($this->testUser);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'Test@1234'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/login', $loginData);

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('token', $response['data']);
        $this->assertArrayHasKey('user', $response['data']);
        $this->assertEquals('test@example.com', $response['data']['user']['email']);
    }

    /**
     * Test login with unverified account
     */
    public function testLoginWithUnverifiedAccount(): void
    {
        // Insert unverified test user
        $unverifiedUser = $this->testUser;
        $unverifiedUser['email_verified'] = false;
        $unverifiedUser['verification_token'] = bin2hex(random_bytes(32));
        $this->userModel->insert($unverifiedUser);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'Test@1234'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/login', $loginData);

        $result->assertStatus(401);
        $result->assertJSONFragment(['message' => 'Please verify your email address first']);
    }

    /**
     * Test login with invalid credentials
     */
    public function testLoginWithInvalidCredentials(): void
    {
        $this->userModel->insert($this->testUser);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'WrongPassword123'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/login', $loginData);

        $result->assertStatus(401);
        $result->assertJSONFragment(['message' => 'Invalid credentials']);
    }

    /**
     * Test email verification
     */
    public function testEmailVerification(): void
    {
        // Insert unverified user
        $unverifiedUser = $this->testUser;
        $unverifiedUser['email_verified'] = false;
        $unverifiedUser['verification_token'] = 'test-verification-token-123';
        $userId = $this->userModel->insert($unverifiedUser);

        $result = $this->get('/api/auth/verify-email/test-verification-token-123');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        // Verify user is now verified
        $user = $this->userModel->find($userId);
        $this->assertTrue($user['email_verified']);
        $this->assertNull($user['verification_token']);
        $this->assertNotNull($user['email_verified_at']);
    }

    /**
     * Test email verification with invalid token
     */
    public function testEmailVerificationWithInvalidToken(): void
    {
        $result = $this->get('/api/auth/verify-email/invalid-token-xyz');

        $result->assertStatus(400);
        $result->assertJSONFragment(['code' => 'INVALID_TOKEN']);
    }

    /**
     * Test password reset request
     */
    public function testPasswordResetRequest(): void
    {
        $this->userModel->insert($this->testUser);

        $resetData = [
            'email' => 'test@example.com'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/forgot-password', $resetData);

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        // Verify reset token was created
        $user = $this->userModel->where('email', 'test@example.com')->first();
        $this->assertNotNull($user['reset_token']);
        $this->assertNotNull($user['reset_expires']);
    }

    /**
     * Test password reset with valid token
     */
    public function testPasswordResetWithValidToken(): void
    {
        // Insert user with reset token
        $userWithToken = $this->testUser;
        $userWithToken['reset_token'] = 'valid-reset-token-123';
        $userWithToken['reset_expires'] = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $userId = $this->userModel->insert($userWithToken);

        $resetData = [
            'token' => 'valid-reset-token-123',
            'password' => 'NewPassword@123'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/reset-password', $resetData);

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        // Verify password was changed and token cleared
        $user = $this->userModel->find($userId);
        $this->assertNull($user['reset_token']);
        $this->assertNull($user['reset_expires']);
        $this->assertTrue(password_verify('NewPassword@123', $user['password']));
    }

    /**
     * Test password reset with expired token
     */
    public function testPasswordResetWithExpiredToken(): void
    {
        // Insert user with expired reset token
        $userWithToken = $this->testUser;
        $userWithToken['reset_token'] = 'expired-reset-token-123';
        $userWithToken['reset_expires'] = date('Y-m-d H:i:s', strtotime('-1 hour')); // Expired
        $this->userModel->insert($userWithToken);

        $resetData = [
            'token' => 'expired-reset-token-123',
            'password' => 'NewPassword@123'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/reset-password', $resetData);

        $result->assertStatus(400);
        $result->assertJSONFragment(['code' => 'TOKEN_EXPIRED']);
    }

    /**
     * Test JWT token expiration
     */
    public function testJWTTokenExpiration(): void
    {
        // Create an expired token
        $payload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time() - 3600,
            'exp' => time() - 1800, // Expired 30 minutes ago
            'user_id' => 1,
            'email' => 'test@example.com',
            'role' => 'user'
        ];

        $expiredToken = JWT::encode($payload, $this->jwtSecret, 'HS256');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $expiredToken])
            ->get('/api/auth/profile');

        $result->assertStatus(401);
    }

    /**
     * Test accessing protected route with valid token
     */
    public function testAccessProtectedRouteWithValidToken(): void
    {
        // Insert test user
        $userId = $this->userModel->insert($this->testUser);

        // Create valid token
        $payload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => $userId,
            'email' => 'test@example.com',
            'role' => 'user'
        ];

        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('/api/auth/profile');

        $result->assertStatus(200);
        $result->assertJSONFragment(['email' => 'test@example.com']);
    }

    /**
     * Test accessing protected route without token
     */
    public function testAccessProtectedRouteWithoutToken(): void
    {
        $result = $this->get('/api/auth/profile');

        $result->assertStatus(401);
    }
}
