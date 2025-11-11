<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\ServiceModel;
use App\Models\TransactionModel;
use App\Models\InvoiceModel;
use Firebase\JWT\JWT;

/**
 * @internal
 */
final class APIEndpointsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected $userModel;
    protected $serviceModel;
    protected $transactionModel;
    protected $invoiceModel;
    protected $jwtSecret;
    protected $userToken;
    protected $adminToken;
    protected $superadminToken;
    protected $userId;
    protected $adminId;
    protected $superadminId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userModel = new UserModel();
        $this->serviceModel = new ServiceModel();
        $this->transactionModel = new TransactionModel();
        $this->invoiceModel = new InvoiceModel();
        $this->jwtSecret = getenv('JWT_SECRET') ?: 'test-secret-key-for-testing-only';
        
        // Create test users with different roles
        $this->createTestUsers();
        $this->createTestTokens();
        $this->createTestData();
    }

    protected function createTestUsers(): void
    {
        // Regular user
        $this->userId = $this->userModel->insert([
            'username' => 'testuser',
            'email' => 'user@example.com',
            'password' => password_hash('Test@1234', PASSWORD_BCRYPT),
            'nama_lengkap' => 'Test User',
            'nama_perusahaan' => 'Test Company',
            'alamat_perusahaan' => '123 Test Street',
            'telepon' => '1234567890',
            'role' => 'user',
            'email_verified' => true,
            'envipoin' => 100,
            'masa_berlaku' => date('Y-m-d', strtotime('+1 year')),
            'layanan_aktif' => 'EnviReg'
        ]);

        // Admin user
        $this->adminId = $this->userModel->insert([
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => password_hash('Admin@1234', PASSWORD_BCRYPT),
            'nama_lengkap' => 'Admin User',
            'nama_perusahaan' => 'Admin Company',
            'alamat_perusahaan' => '456 Admin Street',
            'telepon' => '0987654321',
            'role' => 'admin_keuangan',
            'email_verified' => true,
            'envipoin' => 0,
            'masa_berlaku' => date('Y-m-d', strtotime('+1 year')),
            'layanan_aktif' => 'EnviReg'
        ]);

        // Superadmin user
        $this->superadminId = $this->userModel->insert([
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => password_hash('Super@1234', PASSWORD_BCRYPT),
            'nama_lengkap' => 'Super Admin',
            'nama_perusahaan' => 'Super Company',
            'alamat_perusahaan' => '789 Super Street',
            'telepon' => '5555555555',
            'role' => 'superadmin',
            'email_verified' => true,
            'envipoin' => 0,
            'masa_berlaku' => date('Y-m-d', strtotime('+1 year')),
            'layanan_aktif' => 'EnviReg'
        ]);
    }

    protected function createTestTokens(): void
    {
        // User token
        $userPayload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => $this->userId,
            'email' => 'user@example.com',
            'role' => 'user'
        ];
        $this->userToken = JWT::encode($userPayload, $this->jwtSecret, 'HS256');

        // Admin token
        $adminPayload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => $this->adminId,
            'email' => 'admin@example.com',
            'role' => 'admin_keuangan'
        ];
        $this->adminToken = JWT::encode($adminPayload, $this->jwtSecret, 'HS256');

        // Superadmin token
        $superadminPayload = [
            'iss' => 'envindo-api',
            'aud' => 'envindo-app',
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => $this->superadminId,
            'email' => 'superadmin@example.com',
            'role' => 'superadmin'
        ];
        $this->superadminToken = JWT::encode($superadminPayload, $this->jwtSecret, 'HS256');
    }

    protected function createTestData(): void
    {
        // Create test service
        $this->serviceModel->insert([
            'nama_layanan' => 'Test Service',
            'deskripsi' => 'Test service description',
            'harga' => 100000,
            'durasi_hari' => 365,
            'status' => 'active',
            'fitur' => json_encode(['feature1', 'feature2'])
        ]);

        // Create test transaction
        $transactionId = $this->transactionModel->insert([
            'user_id' => $this->userId,
            'layanan_id' => 1,
            'tanggal_mulai' => date('Y-m-d'),
            'tanggal_selesai' => date('Y-m-d', strtotime('+1 year')),
            'status' => 'active',
            'total_harga' => 100000
        ]);

        // Create test invoice
        $this->invoiceModel->insert([
            'user_id' => $this->userId,
            'transaksi_id' => $transactionId,
            'nomor_invoice' => 'INV-' . date('Ymd') . '-001',
            'tanggal_invoice' => date('Y-m-d'),
            'tanggal_jatuh_tempo' => date('Y-m-d', strtotime('+30 days')),
            'total_tagihan' => 100000,
            'status_pembayaran' => 'pending'
        ]);
    }

    // ========== Dashboard Endpoint Tests ==========

    public function testUserDashboardAccess(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/dashboard/user');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('envipoin', $response['data']);
    }

    public function testAdminDashboardAccess(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminToken])
            ->get('/api/dashboard/admin');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    public function testUserCannotAccessAdminDashboard(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/dashboard/admin');

        $result->assertStatus(403);
    }

    public function testSuperadminDashboardAccess(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->superadminToken])
            ->get('/api/dashboard/superadmin');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    // ========== Service Endpoint Tests ==========

    public function testListServices(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/services/');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    public function testShowService(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/services/1');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('Test Service', $response['data']['nama_layanan']);
    }

    public function testShowNonexistentService(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/services/9999');

        $result->assertStatus(404);
    }

    public function testSubscribeToService(): void
    {
        $subscriptionData = [
            'layanan_id' => 1,
            'tanggal_mulai' => date('Y-m-d')
        ];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->withBodyFormat('json')
            ->post('/api/services/subscribe', $subscriptionData);

        $result->assertStatus(201);
        $result->assertJSONFragment(['status' => 'success']);
    }

    public function testGetMyServices(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/services/my-services');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    // ========== Transaction Endpoint Tests ==========

    public function testListUserTransactions(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/transactions/');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    public function testShowTransaction(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/transactions/1');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals($this->userId, $response['data']['user_id']);
    }

    public function testCreateTransaction(): void
    {
        $transactionData = [
            'layanan_id' => 1,
            'tanggal_mulai' => date('Y-m-d'),
            'tanggal_selesai' => date('Y-m-d', strtotime('+1 year'))
        ];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->withBodyFormat('json')
            ->post('/api/transactions/create', $transactionData);

        $result->assertStatus(201);
        $result->assertJSONFragment(['status' => 'success']);
    }

    public function testAdminCanUpdateTransactionStatus(): void
    {
        $statusData = [
            'status' => 'completed'
        ];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminToken])
            ->withBodyFormat('json')
            ->put('/api/transactions/1/status', $statusData);

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    public function testUserCannotUpdateTransactionStatus(): void
    {
        $statusData = [
            'status' => 'completed'
        ];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->withBodyFormat('json')
            ->put('/api/transactions/1/status', $statusData);

        $result->assertStatus(403);
    }

    // ========== Invoice Endpoint Tests ==========

    public function testListUserInvoices(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/invoices/');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    public function testShowInvoice(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/invoices/1');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals($this->userId, $response['data']['user_id']);
    }

    public function testAdminCanMarkInvoiceAsPaid(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminToken])
            ->withBodyFormat('json')
            ->put('/api/invoices/1/pay', []);

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    public function testUserCannotMarkInvoiceAsPaid(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->withBodyFormat('json')
            ->put('/api/invoices/1/pay', []);

        $result->assertStatus(403);
    }

    // ========== Role-Based Access Control Tests ==========

    public function testUserCannotAccessAdminEndpoints(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/admin/users');

        $result->assertStatus(403);
    }

    public function testAdminCanAccessAdminEndpoints(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminToken])
            ->get('/api/admin/users');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    public function testUserCannotAccessSuperadminEndpoints(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/superadmin/services');

        $result->assertStatus(403);
    }

    public function testAdminCannotAccessSuperadminEndpoints(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminToken])
            ->get('/api/superadmin/services');

        $result->assertStatus(403);
    }

    public function testSuperadminCanAccessSuperadminEndpoints(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->superadminToken])
            ->get('/api/superadmin/services');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    // ========== Error Response Tests ==========

    public function testUnauthorizedAccessReturnsStandardError(): void
    {
        $result = $this->get('/api/dashboard/user');

        $result->assertStatus(401);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('message', $response);
    }

    public function testNotFoundReturnsStandardError(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->get('/api/services/9999');

        $result->assertStatus(404);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
    }

    public function testValidationErrorReturnsStandardFormat(): void
    {
        $invalidData = [
            'layanan_id' => 'invalid', // Should be integer
        ];

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
            ->withBodyFormat('json')
            ->post('/api/services/subscribe', $invalidData);

        $result->assertStatus(400);
        
        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('message', $response);
    }
}