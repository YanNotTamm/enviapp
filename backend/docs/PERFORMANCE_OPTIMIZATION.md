# Performance Optimization Guide

## Overview

This document provides guidelines and best practices for optimizing the performance of the Envindo application, covering database queries, API responses, and frontend load times.

## Performance Targets

- **Database Queries**: < 200ms for most queries, < 50ms for simple lookups
- **API Response Times**: < 500ms for most endpoints
- **Frontend Load Time**: < 3 seconds for initial page load
- **Time to Interactive**: < 5 seconds

## Database Optimization

### 1. Indexes

Ensure the following indexes exist for optimal query performance:

```sql
-- Users table
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_verification_token ON users(verification_token);
CREATE INDEX idx_users_reset_token ON users(reset_token);
CREATE INDEX idx_users_role ON users(role);

-- Transactions table
CREATE INDEX idx_transaksi_user_id ON transaksi_layanan(user_id);
CREATE INDEX idx_transaksi_layanan_id ON transaksi_layanan(layanan_id);
CREATE INDEX idx_transaksi_status ON transaksi_layanan(status);
CREATE INDEX idx_transaksi_dates ON transaksi_layanan(tanggal_mulai, tanggal_selesai);

-- Invoices table
CREATE INDEX idx_invoice_user_id ON invoice(user_id);
CREATE INDEX idx_invoice_transaksi_id ON invoice(transaksi_id);
CREATE INDEX idx_invoice_status ON invoice(status_pembayaran);
CREATE INDEX idx_invoice_dates ON invoice(tanggal_invoice, tanggal_jatuh_tempo);

-- Documents table
CREATE INDEX idx_documents_user_id ON dokumen(user_id);
CREATE INDEX idx_documents_type ON dokumen(jenis_dokumen);

-- Waste collection table
CREATE INDEX idx_waste_user_id ON pengangkutan_limbah(user_id);
CREATE INDEX idx_waste_status ON pengangkutan_limbah(status);

-- Manifests table
CREATE INDEX idx_manifest_user_id ON manifest_elektronik(user_id);
CREATE INDEX idx_manifest_status ON manifest_elektronik(status);
```

### 2. Query Optimization

#### Use Joins Instead of Multiple Queries

**Bad:**
```php
$transactions = $this->transactionModel->where('user_id', $userId)->findAll();
foreach ($transactions as &$transaction) {
    $transaction['service'] = $this->serviceModel->find($transaction['layanan_id']);
}
```

**Good:**
```php
$db = \Config\Database::connect();
$builder = $db->table('transaksi_layanan t');
$builder->select('t.*, l.nama_layanan, l.harga, l.deskripsi');
$builder->join('layanan l', 'l.id = t.layanan_id');
$builder->where('t.user_id', $userId);
$transactions = $builder->get()->getResultArray();
```

#### Use Query Builder Efficiently

**Bad:**
```php
$allUsers = $this->userModel->findAll();
$activeUsers = array_filter($allUsers, fn($u) => $u['status'] === 'active');
```

**Good:**
```php
$activeUsers = $this->userModel->where('status', 'active')->findAll();
```

#### Limit Result Sets

Always use pagination for large datasets:

```php
$perPage = 20;
$page = $this->request->getGet('page') ?? 1;

$transactions = $this->transactionModel
    ->where('user_id', $userId)
    ->orderBy('created_at', 'DESC')
    ->paginate($perPage, 'default', $page);
```

### 3. Database Connection Pooling

Configure persistent connections in `app/Config/Database.php`:

```php
public array $default = [
    // ... other settings
    'pConnect' => true,  // Enable persistent connections
    'DBDebug'  => false, // Disable in production
    'cacheOn'  => true,  // Enable query caching
];
```

### 4. Query Caching

For frequently accessed, rarely changing data:

```php
$cache = \Config\Services::cache();
$cacheKey = 'active_services';

$services = $cache->get($cacheKey);
if ($services === null) {
    $services = $this->serviceModel->where('status', 'active')->findAll();
    $cache->save($cacheKey, $services, 3600); // Cache for 1 hour
}
```

## API Optimization

### 1. Response Compression

Enable gzip compression in `.htaccess` or web server configuration:

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

### 2. Reduce Payload Size

Only return necessary fields:

```php
// Instead of returning all user fields
$user = $this->userModel->find($userId);

// Return only needed fields
$user = $this->userModel->select('id, username, email, nama_lengkap, role, envipoin')
    ->find($userId);
```

### 3. Implement Caching Headers

```php
return $this->response
    ->setJSON($data)
    ->setHeader('Cache-Control', 'public, max-age=3600')
    ->setHeader('ETag', md5(json_encode($data)));
```

### 4. Batch API Requests

Instead of multiple API calls, create batch endpoints:

```php
// GET /api/dashboard/summary
public function summary()
{
    $userId = $this->request->user_id;
    
    return $this->respond([
        'status' => 'success',
        'data' => [
            'user' => $this->getUserData($userId),
            'stats' => $this->getStats($userId),
            'recent_transactions' => $this->getRecentTransactions($userId, 5),
            'pending_invoices' => $this->getPendingInvoices($userId)
        ]
    ]);
}
```

### 5. Async Processing for Heavy Operations

For operations like email sending, PDF generation:

```php
// Queue the job instead of processing synchronously
$this->queueService->push('send_invoice_email', [
    'invoice_id' => $invoiceId,
    'user_id' => $userId
]);

return $this->respond([
    'status' => 'success',
    'message' => 'Invoice will be sent shortly'
]);
```

## Frontend Optimization

### 1. Code Splitting

Split large bundles in React:

```typescript
// Use React.lazy for route-based code splitting
const Dashboard = React.lazy(() => import('./components/Dashboard'));
const Services = React.lazy(() => import('./components/Services/ServiceList'));

function App() {
  return (
    <Suspense fallback={<LoadingSpinner />}>
      <Routes>
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/services" element={<Services />} />
      </Routes>
    </Suspense>
  );
}
```

### 2. Optimize Images

- Use appropriate image formats (WebP for photos, SVG for icons)
- Implement lazy loading for images
- Compress images before upload

```typescript
<img 
  src={imageUrl} 
  loading="lazy" 
  alt="Description"
  width="300"
  height="200"
/>
```

### 3. Memoization

Use React.memo and useMemo for expensive computations:

```typescript
const ServiceCard = React.memo(({ service }) => {
  return (
    <div className="service-card">
      <h3>{service.nama_layanan}</h3>
      <p>{service.deskripsi}</p>
    </div>
  );
});

function ServiceList({ services }) {
  const sortedServices = useMemo(() => {
    return services.sort((a, b) => a.harga - b.harga);
  }, [services]);

  return sortedServices.map(service => (
    <ServiceCard key={service.id} service={service} />
  ));
}
```

### 4. Debounce Search Inputs

```typescript
import { useState, useCallback } from 'react';
import { debounce } from 'lodash';

function SearchComponent() {
  const [searchTerm, setSearchTerm] = useState('');

  const debouncedSearch = useCallback(
    debounce((term: string) => {
      // Perform API call
      fetchSearchResults(term);
    }, 300),
    []
  );

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setSearchTerm(value);
    debouncedSearch(value);
  };

  return <input value={searchTerm} onChange={handleChange} />;
}
```

### 5. Optimize Bundle Size

```bash
# Analyze bundle size
npm run build
npx source-map-explorer 'build/static/js/*.js'

# Remove unused dependencies
npm prune

# Use production builds
NODE_ENV=production npm run build
```

### 6. Implement Virtual Scrolling

For long lists, use virtual scrolling:

```typescript
import { FixedSizeList } from 'react-window';

function TransactionList({ transactions }) {
  const Row = ({ index, style }) => (
    <div style={style}>
      <TransactionItem transaction={transactions[index]} />
    </div>
  );

  return (
    <FixedSizeList
      height={600}
      itemCount={transactions.length}
      itemSize={80}
      width="100%"
    >
      {Row}
    </FixedSizeList>
  );
}
```

## Monitoring and Profiling

### 1. Enable Query Logging (Development Only)

In `app/Config/Database.php`:

```php
public array $default = [
    'DBDebug' => ENVIRONMENT !== 'production',
];
```

### 2. Use CodeIgniter's Debug Toolbar

Enable in development:

```php
// app/Config/Filters.php
public array $globals = [
    'before' => [],
    'after' => [
        'toolbar', // Enable debug toolbar
    ],
];
```

### 3. Profile Slow Queries

```php
$db = \Config\Database::connect();
$db->query("SET profiling = 1");

// Run your queries
$result = $db->query("SELECT * FROM users WHERE email = ?", [$email]);

// Get profiling info
$profiles = $db->query("SHOW PROFILES")->getResultArray();
```

### 4. Frontend Performance Monitoring

```typescript
// Measure component render time
import { Profiler } from 'react';

function onRenderCallback(
  id: string,
  phase: "mount" | "update",
  actualDuration: number
) {
  console.log(`${id} (${phase}) took ${actualDuration}ms`);
}

<Profiler id="Dashboard" onRender={onRenderCallback}>
  <Dashboard />
</Profiler>
```

## Production Optimizations

### 1. Enable OPcache

In `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 2. Use CDN for Static Assets

Configure frontend to use CDN:

```env
REACT_APP_CDN_URL=https://cdn.example.com
```

### 3. Database Connection Pooling

Use connection pooling at the database server level (MySQL, PostgreSQL).

### 4. Implement Rate Limiting

Protect against abuse:

```php
// app/Filters/RateLimitFilter.php
public function before(RequestInterface $request, $arguments = null)
{
    $cache = \Config\Services::cache();
    $key = 'rate_limit_' . $request->getIPAddress();
    
    $requests = $cache->get($key) ?? 0;
    
    if ($requests >= 100) { // 100 requests per minute
        return Services::response()
            ->setStatusCode(429)
            ->setJSON(['error' => 'Too many requests']);
    }
    
    $cache->save($key, $requests + 1, 60);
}
```

## Performance Checklist

### Database
- [ ] Indexes created on frequently queried columns
- [ ] Queries use joins instead of N+1 patterns
- [ ] Result sets are paginated
- [ ] Query caching enabled for static data
- [ ] Persistent connections enabled

### API
- [ ] Response compression enabled
- [ ] Unnecessary fields removed from responses
- [ ] Caching headers implemented
- [ ] Batch endpoints created for related data
- [ ] Heavy operations moved to background jobs

### Frontend
- [ ] Code splitting implemented
- [ ] Images optimized and lazy loaded
- [ ] Expensive computations memoized
- [ ] Search inputs debounced
- [ ] Production build optimized
- [ ] Virtual scrolling for long lists

### Production
- [ ] OPcache enabled
- [ ] CDN configured for static assets
- [ ] Rate limiting implemented
- [ ] Monitoring and alerting set up
- [ ] Performance tests passing

## Troubleshooting Slow Performance

### Slow Database Queries

1. Enable query logging
2. Identify slow queries (> 200ms)
3. Run EXPLAIN on slow queries
4. Add appropriate indexes
5. Optimize query structure

### Slow API Responses

1. Check database query times
2. Profile controller methods
3. Reduce payload size
4. Implement caching
5. Move heavy operations to background

### Slow Frontend Load

1. Analyze bundle size
2. Implement code splitting
3. Optimize images
4. Enable compression
5. Use CDN for static assets

## Resources

- [CodeIgniter Performance](https://codeigniter.com/user_guide/general/caching.html)
- [React Performance Optimization](https://react.dev/learn/render-and-commit)
- [MySQL Query Optimization](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [Web Performance Best Practices](https://web.dev/performance/)
