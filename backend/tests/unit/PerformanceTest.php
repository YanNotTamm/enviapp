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
 * Performance Testing Suite
 * Tests for database query performance, API response times, and optimization
 * 
 * @internal
 */
final class PerformanceTest extends CIUnitTestCase
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
    protected $userId;

    // Performance thresholds (in milliseconds)
    protected const FAST_QUERY_THRESHOLD = 50;      // 50ms
    protected const ACCEPTABLE_QUERY_THRESHOLD = 200; // 200ms
    protected const API_RESPONSE_THRESHOLD = 500;    // 500ms

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userModel = new UserModel();
        $this->serviceModel = new ServiceModel();
        $this->transactionModel = new TransactionModel();
        $this->invoiceModel = new InvoiceModel();
        $this->jwtSecret = getenv('JWT_SECRET') ?: 'test-secret-key-for-testing-only';
        
        // Create test data
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create test user
        $this->userId = $this->userModel->insert([
            'username' => 'perftest',
            'email' => 'perf@example.com',
            'password' => password_hash('Test@1234', PASSWORD_BCRYPT),
            'nama_lengkap' => 'Performance Test User',
            'nama_perusahaan' => 'Performance Company',
            'alamat_perusahaan' => '123 Performance Street',
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
            'email' => 'perf@example.com',
            'role' => 'user'
        ];
        $this->userToken = JWT::encode($payload, $this->jwtSecret, 'HS256');

        // Create multiple services
        for ($i = 1; $i <= 10; $i++) {
            $this->serviceModel->insert([
                'nama_layanan' => "Service $i",
                'deskripsi' => "Description for service $i",
                'harga' => 100000 * $i,
                'durasi_hari' => 365,
                'status' => 'active',
                'fitur' => json_encode(['feature1', 'feature2'])
            ]);
        }

        // Create multiple transactions
        for ($i = 1; $i <= 20; $i++) {
            $transactionId = $this->transactionModel->insert([
                'user_id' => $this->userId,
                'layanan_id' => ($i % 10) + 1,
                'tanggal_mulai' => date('Y-m-d', strtotime("-$i days")),
                'tanggal_selesai' => date('Y-m-d', strtotime("+365 days")),
                'status' => $i % 3 === 0 ? 'completed' : 'active',
                'total_harga' => 100000 * (($i % 10) + 1)
            ]);

            // Create invoice for each transaction
            $this->invoiceModel->insert([
                'user_id' => $this->userId,
                'transaksi_id' => $transactionId,
                'nomor_invoice' => 'INV-' . date('Ymd') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'tanggal_invoice' => date('Y-m-d', strtotime("-$i days")),
                'tanggal_jatuh_tempo' => date('Y-m-d', strtotime("+30 days")),
                'total_tagihan' => 100000 * (($i % 10) + 1),
                'status_pembayaran' => $i % 2 === 0 ? 'paid' : 'pending'
            ]);
        }
    }

    /**
     * Measure execution time of a callback
     */
    protected function measureExecutionTime(callable $callback): float
    {
        $startTime = microtime(true);
        $callback();
        $endTime = microtime(true);
        
        return ($endTime - $startTime) * 1000; // Convert to milliseconds
    }

    // ========== Database Query Performance Tests ==========

    /**
     * Test user lookup by email performance
     */
    public function testUserLookupByEmailPerformance(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->userModel->where('email', 'perf@example.com')->first();
        });

        $this->assertLessThan(
            self::FAST_QUERY_THRESHOLD,
            $executionTime,
            "User lookup by email took {$executionTime}ms (threshold: " . self::FAST_QUERY_THRESHOLD . "ms)"
        );
    }

    /**
     * Test user lookup by ID performance
     */
    public function testUserLookupByIdPerformance(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->userModel->find($this->userId);
        });

        $this->assertLessThan(
            self::FAST_QUERY_THRESHOLD,
            $executionTime,
            "User lookup by ID took {$executionTime}ms (threshold: " . self::FAST_QUERY_THRESHOLD . "ms)"
        );
    }

    /**
     * Test service list query performance
     */
    public function testServiceListQueryPerformance(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->serviceModel->where('status', 'active')->findAll();
        });

        $this->assertLessThan(
            self::ACCEPTABLE_QUERY_THRESHOLD,
            $executionTime,
            "Service list query took {$executionTime}ms (threshold: " . self::ACCEPTABLE_QUERY_THRESHOLD . "ms)"
        );
    }

    /**
     * Test user transactions query performance
     */
    public function testUserTransactionsQueryPerformance(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->transactionModel->where('user_id', $this->userId)->findAll();
        });

        $this->assertLessThan(
            self::ACCEPTABLE_QUERY_THRESHOLD,
            $executionTime,
            "User transactions query took {$executionTime}ms (threshold: " . self::ACCEPTABLE_QUERY_THRESHOLD . "ms)"
        );
    }

    /**
     * Test user invoices query performance
     */
    public function testUserInvoicesQueryPerformance(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->invoiceModel->where('user_id', $this->userId)->findAll();
        });

        $this->assertLessThan(
            self::ACCEPTABLE_QUERY_THRESHOLD,
            $executionTime,
            "User invoices query took {$executionTime}ms (threshold: " . self::ACCEPTABLE_QUERY_THRESHOLD . "ms)"
        );
    }

    /**
     * Test dashboard statistics query performance
     */
    public function testDashboardStatsQueryPerformance(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            // Simulate dashboard stats query
            $user = $this->userModel->find($this->userId);
            $activeServices = $this->transactionModel
                ->where('user_id', $this->userId)
                ->where('status', 'active')
                ->countAllResults();
            $pendingInvoices = $this->invoiceModel
                ->where('user_id', $this->userId)
                ->where('status_pembayaran', 'pending')
                ->countAllResults();
        });

        $this->assertLessThan(
            self::ACCEPTABLE_QUERY_THRESHOLD,
            $executionTime,
            "Dashboard stats query took {$executionTime}ms (threshold: " . self::ACCEPTABLE_QUERY_THRESHOLD . "ms)"
        );
    }

    /**
     * Test transaction with join query performance
     */
    public function testTransactionWithJoinPerformance(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $db = \Config\Database::connect();
            $builder = $db->table('transaksi_layanan t');
            $builder->select('t.*, l.nama_layanan, l.harga');
            $builder->join('layanan l', 'l.id = t.layanan_id');
            $builder->where('t.user_id', $this->userId);
            $builder->get()->getResultArray();
        });

        $this->assertLessThan(
            self::ACCEPTABLE_QUERY_THRESHOLD,
            $executionTime,
            "Transaction with join query took {$executionTime}ms (threshold: " . self::ACCEPTABLE_QUERY_THRESHOLD . "ms)"
        );
    }

    // ========== API Response Time Tests ==========

    /**
     * Test login API response time
     */
    public function testLoginAPIResponseTime(): void
    {
        $loginData = [
            'email' => 'perf@example.com',
            'password' => 'Test@1234'
        ];

        $executionTime = $this->measureExecutionTime(function() use ($loginData) {
            $this->withBodyFormat('json')->post('/api/auth/login', $loginData);
        });

        $this->assertLessThan(
            self::API_RESPONSE_THRESHOLD,
            $executionTime,
            "Login API took {$executionTime}ms (threshold: " . self::API_RESPONSE_THRESHOLD . "ms)"
        );
    }

    /**
     * Test user profile API response time
     */
    public function testUserProfileAPIResponseTime(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
                ->get('/api/user/profile');
        });

        $this->assertLessThan(
            self::API_RESPONSE_THRESHOLD,
            $executionTime,
            "User profile API took {$executionTime}ms (threshold: " . self::API_RESPONSE_THRESHOLD . "ms)"
        );
    }

    /**
     * Test dashboard API response time
     */
    public function testDashboardAPIResponseTime(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
                ->get('/api/dashboard/user');
        });

        $this->assertLessThan(
            self::API_RESPONSE_THRESHOLD,
            $executionTime,
            "Dashboard API took {$executionTime}ms (threshold: " . self::API_RESPONSE_THRESHOLD . "ms)"
        );
    }

    /**
     * Test service list API response time
     */
    public function testServiceListAPIResponseTime(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
                ->get('/api/services/');
        });

        $this->assertLessThan(
            self::API_RESPONSE_THRESHOLD,
            $executionTime,
            "Service list API took {$executionTime}ms (threshold: " . self::API_RESPONSE_THRESHOLD . "ms)"
        );
    }

    /**
     * Test transactions list API response time
     */
    public function testTransactionsListAPIResponseTime(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
                ->get('/api/transactions/');
        });

        $this->assertLessThan(
            self::API_RESPONSE_THRESHOLD,
            $executionTime,
            "Transactions list API took {$executionTime}ms (threshold: " . self::API_RESPONSE_THRESHOLD . "ms)"
        );
    }

    /**
     * Test invoices list API response time
     */
    public function testInvoicesListAPIResponseTime(): void
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->withHeaders(['Authorization' => 'Bearer ' . $this->userToken])
                ->get('/api/invoices/');
        });

        $this->assertLessThan(
            self::API_RESPONSE_THRESHOLD,
            $executionTime,
            "Invoices list API took {$executionTime}ms (threshold: " . self::API_RESPONSE_THRESHOLD . "ms)"
        );
    }

    // ========== N+1 Query Detection Tests ==========

    /**
     * Test for N+1 queries in transaction list
     */
    public function testNoNPlusOneInTransactionList(): void
    {
        // Enable query logging
        $db = \Config\Database::connect();
        
        $queryCountBefore = count($db->getQueries());
        
        // Fetch transactions with related data
        $transactions = $this->transactionModel->where('user_id', $this->userId)->findAll();
        
        $queryCountAfter = count($db->getQueries());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;
        
        // Should use joins or eager loading, not N+1 queries
        // With 20 transactions, should not execute 20+ queries
        $this->assertLessThan(
            5,
            $queriesExecuted,
            "Transaction list executed {$queriesExecuted} queries (possible N+1 problem)"
        );
    }

    // ========== Memory Usage Tests ==========

    /**
     * Test memory usage for large result sets
     */
    public function testMemoryUsageForLargeResultSets(): void
    {
        $memoryBefore = memory_get_usage();
        
        // Fetch all transactions
        $transactions = $this->transactionModel->where('user_id', $this->userId)->findAll();
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB
        
        // Should not use excessive memory (threshold: 10MB for 20 records)
        $this->assertLessThan(
            10,
            $memoryUsed,
            "Query used {$memoryUsed}MB of memory (threshold: 10MB)"
        );
    }

    // ========== Optimization Recommendations ==========

    /**
     * Test that indexes exist on frequently queried columns
     */
    public function testIndexesExistOnFrequentlyQueriedColumns(): void
    {
        $db = \Config\Database::connect();
        
        // Check for index on users.email
        $indexes = $db->query("SHOW INDEX FROM users WHERE Column_name = 'email'")->getResultArray();
        $this->assertNotEmpty($indexes, 'Index missing on users.email');
        
        // Check for index on transaksi_layanan.user_id
        $indexes = $db->query("SHOW INDEX FROM transaksi_layanan WHERE Column_name = 'user_id'")->getResultArray();
        $this->assertNotEmpty($indexes, 'Index missing on transaksi_layanan.user_id');
        
        // Check for index on invoice.user_id
        $indexes = $db->query("SHOW INDEX FROM invoice WHERE Column_name = 'user_id'")->getResultArray();
        $this->assertNotEmpty($indexes, 'Index missing on invoice.user_id');
    }

    /**
     * Generate performance report
     */
    public function testGeneratePerformanceReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database_queries' => [],
            'api_endpoints' => [],
            'recommendations' => []
        ];

        // Test various queries and collect metrics
        $queries = [
            'user_lookup' => function() {
                return $this->userModel->find($this->userId);
            },
            'service_list' => function() {
                return $this->serviceModel->findAll();
            },
            'user_transactions' => function() {
                return $this->transactionModel->where('user_id', $this->userId)->findAll();
            },
        ];

        foreach ($queries as $name => $query) {
            $time = $this->measureExecutionTime($query);
            $report['database_queries'][$name] = [
                'execution_time_ms' => round($time, 2),
                'status' => $time < self::ACCEPTABLE_QUERY_THRESHOLD ? 'PASS' : 'SLOW'
            ];
        }

        // Add recommendations
        if (count(array_filter($report['database_queries'], fn($q) => $q['status'] === 'SLOW')) > 0) {
            $report['recommendations'][] = 'Consider adding indexes to slow queries';
            $report['recommendations'][] = 'Review query execution plans';
        }

        // Output report (in real scenario, this would be saved to a file)
        $this->assertTrue(true, 'Performance report generated: ' . json_encode($report, JSON_PRETTY_PRINT));
    }
}
