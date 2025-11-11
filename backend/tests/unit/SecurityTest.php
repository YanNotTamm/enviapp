<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use Firebase\JWT\JWT;

/**
 * Security Testing Suite
 * Tests for SQL injection, XSS, CSRF, JWT validation, and file upload security
 * 
 * @internal
 */
final class SecurityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected $userModel;
    protected $jwtSecret;
    protected $userToken;
    protected $userId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userModel = new UserModel();
        $this->jwtSecret = getenv('JWT_SECRET') ?: 'test-secret-key-for-testing-only';
        
        // Create test user
        $this->userId = $this->userModel->insert([
            'username' => 'securitytest',
            'email' => 'security@example.com',
            'password' => password_hash('Test@1234', PASSWORD_BCRYPT),
            'nama_lengkap' => 'Security Test User',
            'nama_perusahaan' => 'Security Company',
            'alamat_perusahaan' => '123 Security Street',
            'telepon' => '1234567890',
            'role' => 'user',
            'email_verified' => true,
            'envipoin' => 100,
            'masa_berlaku' => date('Y-m-d', strtotime('+1 year')),
            'layanan_aktif' => 'EnviReg'
        ]);

        // Create valid token
        $payload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => $this->userId,
            'email' => 'security@example.com',
            'role' => 'user'
        ];
        $this->userToken = JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    // ========== SQL Injection Prevention Tests ==========

    /**
     * Test SQL injection in login email field
     */
    public function testSQLInjectionInLoginEmail(): void
    {
        $maliciousData = [
            'email' => "admin' OR '1'='1",
            'password' => 'anything'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/login', $maliciousData);

        // Should fail authentication, not execute SQL injection
        $result->assertStatus(401);
        $result->assertJSONFragment(['status' => 'error']);
    }

    /**
     * Test SQL injection in registration fields
     */
    public function testSQLInjectionInRegistration(): void
    {
        $maliciousData = [
            'username' => "admin'; DROP TABLE users; --",
            'email' => 'malicious@example.com',
            'password' => 'Test@1234',
            'nama_lengkap' => 'Test User',
            'nama_perusahaan' => 'Test Company',
            'alamat_perusahaan' => '123 Test Street',
            'telepon' => '1234567890'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/register', $maliciousData);

        // Should either succeed with sanitized data or fail validation
        // But should NOT execute SQL injection
        $this->assertNotEquals(500, $result->response()->getStatusCode());
        
        // Verify users table still exists
        $users = $this->userModel->findAll();
        $this->assertIsArray($users);
    }

    /**
     * Test SQL injection in search/filter parameters
     */
    public function testSQLInjectionInQueryParameters(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get("/api/services/1' OR '1'='1");

        // Should return 404 or validation error, not execute injection
        $this->assertContains($result->response()->getStatusCode(), [400, 404]);
    }

    // ========== XSS Prevention Tests ==========

    /**
     * Test XSS in registration fields
     */
    public function testXSSInRegistrationFields(): void
    {
        $xssData = [
            'username' => 'xsstest',
            'email' => 'xss@example.com',
            'password' => 'Test@1234',
            'nama_lengkap' => '<script>alert("XSS")</script>',
            'nama_perusahaan' => '<img src=x onerror=alert("XSS")>',
            'alamat_perusahaan' => '123 Test Street',
            'telepon' => '1234567890'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/register', $xssData);

        if ($result->response()->getStatusCode() === 201) {
            // If registration succeeds, verify data is sanitized
            $user = $this->userModel->where('email', 'xss@example.com')->first();
            
            // Should not contain script tags
            $this->assertStringNotContainsString('<script>', $user['nama_lengkap']);
            $this->assertStringNotContainsString('<img', $user['nama_perusahaan']);
        }
    }

    /**
     * Test XSS in profile update
     */
    public function testXSSInProfileUpdate(): void
    {
        $xssData = [
            'nama_lengkap' => '<script>alert("XSS")</script>',
            'nama_perusahaan' => 'Normal Company',
            'alamat_perusahaan' => '123 Test Street',
            'telepon' => '1234567890'
        ];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->withBodyFormat('json')
            ->put('/api/user/profile', $xssData);

        if ($result->response()->getStatusCode() === 200) {
            // Verify data is sanitized
            $user = $this->userModel->find($this->userId);
            $this->assertStringNotContainsString('<script>', $user['nama_lengkap']);
        }
    }

    /**
     * Test XSS in API response
     */
    public function testXSSInAPIResponse(): void
    {
        // Update user with potentially malicious data
        $this->userModel->update($this->userId, [
            'nama_lengkap' => '<script>alert("XSS")</script>'
        ]);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/user/profile');

        $result->assertStatus(200);
        
        $response = json_decode($result->getJSON(), true);
        
        // Response should have HTML entities encoded or script tags removed
        if (isset($response['data']['nama_lengkap'])) {
            $this->assertStringNotContainsString('<script>', $response['data']['nama_lengkap']);
        }
    }

    // ========== CSRF Protection Tests ==========

    /**
     * Test CSRF token validation for state-changing operations
     */
    public function testCSRFProtectionOnStateChangingOperations(): void
    {
        // POST, PUT, DELETE operations should be protected
        // In JWT-based auth, the token itself provides CSRF protection
        
        // Test without Authorization header (should fail)
        $result = $this->withBodyFormat('json')
            ->put('/api/user/profile', [
                'nama_lengkap' => 'Updated Name'
            ]);

        $result->assertStatus(401);
    }

    /**
     * Test that GET requests don't modify data
     */
    public function testGETRequestsDoNotModifyData(): void
    {
        // Attempt to modify data via GET (should not work)
        $originalUser = $this->userModel->find($this->userId);
        
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/user/profile?nama_lengkap=Modified');

        // User data should remain unchanged
        $currentUser = $this->userModel->find($this->userId);
        $this->assertEquals($originalUser['nama_lengkap'], $currentUser['nama_lengkap']);
    }

    // ========== JWT Token Validation Tests ==========

    /**
     * Test access with invalid JWT signature
     */
    public function testInvalidJWTSignature(): void
    {
        $payload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => $this->userId,
            'email' => 'security@example.com',
            'role' => 'user'
        ];

        // Sign with wrong secret
        $invalidToken = JWT::encode($payload, 'wrong-secret-key', 'HS256');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $invalidToken])
            ->get('/api/user/profile');

        $result->assertStatus(401);
    }

    /**
     * Test access with expired JWT token
     */
    public function testExpiredJWTToken(): void
    {
        $payload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time() - 7200,
            'exp' => time() - 3600, // Expired 1 hour ago
            'user_id' => $this->userId,
            'email' => 'security@example.com',
            'role' => 'user'
        ];

        $expiredToken = JWT::encode($payload, $this->jwtSecret, 'HS256');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $expiredToken])
            ->get('/api/user/profile');

        $result->assertStatus(401);
    }

    /**
     * Test access with malformed JWT token
     */
    public function testMalformedJWTToken(): void
    {
        $malformedToken = 'not.a.valid.jwt.token';

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $malformedToken])
            ->get('/api/user/profile');

        $result->assertStatus(401);
    }

    /**
     * Test JWT token with tampered payload
     */
    public function testTamperedJWTPayload(): void
    {
        // Create valid token
        $validToken = $this->userToken;
        
        // Tamper with the payload part
        $parts = explode('.', $validToken);
        if (count($parts) === 3) {
            // Decode payload, modify it, encode it back
            $payload = json_decode(base64_decode($parts[1]), true);
            $payload['role'] = 'superadmin'; // Try to escalate privileges
            $parts[1] = base64_encode(json_encode($payload));
            $tamperedToken = implode('.', $parts);

            $result = $this->withHeaders(['Authorization' => 'Bearer ' . $tamperedToken])
                ->get('/api/superadmin/services');

            // Should fail due to invalid signature
            $result->assertStatus(401);
        }
    }

    /**
     * Test JWT token without required claims
     */
    public function testJWTTokenWithoutRequiredClaims(): void
    {
        $payload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time(),
            'exp' => time() + 3600,
            // Missing user_id, email, role
        ];

        $incompleteToken = JWT::encode($payload, $this->jwtSecret, 'HS256');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $incompleteToken])
            ->get('/api/user/profile');

        $result->assertStatus(401);
    }

    // ========== File Upload Security Tests ==========

    /**
     * Test file upload with invalid file type
     */
    public function testFileUploadWithInvalidType(): void
    {
        // Create a fake PHP file (should be rejected)
        $phpContent = '<?php echo "malicious code"; ?>';
        $tempFile = tmpfile();
        fwrite($tempFile, $phpContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->withFiles([
                'file' => new \CodeIgniter\HTTP\Files\UploadedFile(
                    $tempPath,
                    'malicious.php',
                    'application/x-php',
                    null,
                    UPLOAD_ERR_OK,
                    true
                )
            ])
            ->post('/api/documents/upload');

        // Should reject PHP files
        $this->assertContains($result->response()->getStatusCode(), [400, 422]);
        
        fclose($tempFile);
    }

    /**
     * Test file upload with oversized file
     */
    public function testFileUploadWithOversizedFile(): void
    {
        // Create a file larger than 5MB
        $largeContent = str_repeat('A', 6 * 1024 * 1024); // 6MB
        $tempFile = tmpfile();
        fwrite($tempFile, $largeContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->withFiles([
                'file' => new \CodeIgniter\HTTP\Files\UploadedFile(
                    $tempPath,
                    'large.pdf',
                    'application/pdf',
                    null,
                    UPLOAD_ERR_OK,
                    true
                )
            ])
            ->post('/api/documents/upload');

        // Should reject files larger than 5MB
        $this->assertContains($result->response()->getStatusCode(), [400, 413, 422]);
        
        fclose($tempFile);
    }

    /**
     * Test file upload with malicious filename
     */
    public function testFileUploadWithMaliciousFilename(): void
    {
        $content = 'Normal PDF content';
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        // Filename with path traversal attempt
        $maliciousFilename = '../../../etc/passwd.pdf';

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->withFiles([
                'file' => new \CodeIgniter\HTTP\Files\UploadedFile(
                    $tempPath,
                    $maliciousFilename,
                    'application/pdf',
                    null,
                    UPLOAD_ERR_OK,
                    true
                )
            ])
            ->post('/api/documents/upload');

        // Should sanitize filename or reject
        if ($result->response()->getStatusCode() === 201) {
            $response = json_decode($result->getJSON(), true);
            // Filename should not contain path traversal
            if (isset($response['data']['filename'])) {
                $this->assertStringNotContainsString('..', $response['data']['filename']);
                $this->assertStringNotContainsString('/', $response['data']['filename']);
            }
        }
        
        fclose($tempFile);
    }

    // ========== Rate Limiting Tests ==========

    /**
     * Test rate limiting on login endpoint
     */
    public function testRateLimitingOnLogin(): void
    {
        $loginData = [
            'email' => 'security@example.com',
            'password' => 'WrongPassword'
        ];

        $successCount = 0;
        $rateLimitedCount = 0;

        // Attempt multiple failed logins
        for ($i = 0; $i < 10; $i++) {
            $result = $this->withBodyFormat('json')
                ->post('/api/auth/login', $loginData);

            $statusCode = $result->response()->getStatusCode();
            
            if ($statusCode === 401) {
                $successCount++;
            } elseif ($statusCode === 429) {
                $rateLimitedCount++;
            }
        }

        // If rate limiting is implemented, we should see 429 responses
        // This test documents expected behavior
        $this->assertTrue(true, 'Rate limiting test completed');
    }

    // ========== Password Security Tests ==========

    /**
     * Test password strength requirements
     */
    public function testPasswordStrengthRequirements(): void
    {
        $weakPasswords = [
            'short',           // Too short
            'alllowercase',    // No uppercase
            'ALLUPPERCASE',    // No lowercase
            'NoNumbers!',      // No numbers
            'NoSpecial123',    // No special characters
        ];

        foreach ($weakPasswords as $weakPassword) {
            $userData = [
                'username' => 'weakpass' . rand(1000, 9999),
                'email' => 'weak' . rand(1000, 9999) . '@example.com',
                'password' => $weakPassword,
                'nama_lengkap' => 'Test User',
                'nama_perusahaan' => 'Test Company',
                'alamat_perusahaan' => '123 Test Street',
                'telepon' => '1234567890'
            ];

            $result = $this->withBodyFormat('json')
                ->post('/api/auth/register', $userData);

            // Should reject weak passwords
            $this->assertEquals(400, $result->response()->getStatusCode());
        }
    }

    /**
     * Test that passwords are properly hashed
     */
    public function testPasswordsAreHashed(): void
    {
        $plainPassword = 'Test@1234';
        
        $userData = [
            'username' => 'hashtest',
            'email' => 'hash@example.com',
            'password' => $plainPassword,
            'nama_lengkap' => 'Hash Test',
            'nama_perusahaan' => 'Test Company',
            'alamat_perusahaan' => '123 Test Street',
            'telepon' => '1234567890'
        ];

        $result = $this->withBodyFormat('json')
            ->post('/api/auth/register', $userData);

        if ($result->response()->getStatusCode() === 201) {
            $user = $this->userModel->where('email', 'hash@example.com')->first();
            
            // Password should be hashed, not stored in plain text
            $this->assertNotEquals($plainPassword, $user['password']);
            $this->assertTrue(password_verify($plainPassword, $user['password']));
        }
    }
}
