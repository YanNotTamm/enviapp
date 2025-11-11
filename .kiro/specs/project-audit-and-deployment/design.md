# Design Document

## Overview

This design document outlines the comprehensive audit and fixes for the Envindo full-stack application. The project consists of:

- **Backend**: CodeIgniter 4 (PHP 8.1+) REST API with JWT authentication
- **Frontend**: React 19 with TypeScript, using React Router and Axios
- **Database**: MySQL with CodeIgniter migrations
- **Authentication**: JWT-based stateless authentication with role-based access control

The audit identifies security vulnerabilities, missing implementations, configuration issues, and deployment requirements. The design provides solutions for each identified issue.

## Architecture

### Current Architecture

```
┌─────────────────┐         HTTP/REST API        ┌──────────────────┐
│                 │◄──────────────────────────────┤                  │
│  React Frontend │         (Port 3000)           │  CodeIgniter 4   │
│   (TypeScript)  │                               │     Backend      │
│                 ├──────────────────────────────►│   (Port 8080)    │
└─────────────────┘      JSON + JWT Token         └────────┬─────────┘
                                                           │
                                                           │ MySQLi
                                                           │
                                                    ┌──────▼─────────┐
                                                    │                │
                                                    │  MySQL Database│
                                                    │  (envipoin_db) │
                                                    │                │
                                                    └────────────────┘
```

### Security Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                     Security Layers                          │
├──────────────────────────────────────────────────────────────┤
│  1. CORS Filter (Frontend Origin Validation)                 │
│  2. JWT Authentication Filter (Token Validation)             │
│  3. Role-Based Access Control (RBAC)                         │
│  4. Input Validation & Sanitization                          │
│  5. SQL Injection Prevention (Parameterized Queries)         │
│  6. XSS Prevention (Output Encoding)                         │
│  7. CSRF Protection (Token-based)                            │
└──────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Security Enhancements

#### 1.1 JWT Secret Management

**Issue**: JWT secret is hardcoded with weak fallback value
**Solution**: 
- Generate strong JWT secret and store in `.env`
- Remove hardcoded fallback
- Add validation on application startup

```php
// In AuthController and JWTAuthFilter
$jwtSecret = getenv('JWT_SECRET');
if (!$jwtSecret) {
    throw new \RuntimeException('JWT_SECRET must be set in environment');
}
```

**Environment Variable**:
```
JWT_SECRET=<generated-256-bit-key>
```

#### 1.2 Input Validation Enhancement

**Current State**: Basic validation exists but needs strengthening
**Improvements**:
- Add comprehensive validation rules for all endpoints
- Implement custom validation rules for business logic
- Add rate limiting for authentication endpoints
- Sanitize all user inputs before processing

#### 1.3 Password Security

**Current State**: Using PASSWORD_BCRYPT (good)
**Enhancements**:
- Add password strength requirements
- Implement password history to prevent reuse
- Add account lockout after failed attempts

#### 1.4 CORS Configuration

**Current State**: CORS configured in Config/Cors.php
**Required Changes**:
- Enable CORS middleware in Filters.php
- Add environment-specific origins
- Implement preflight request handling

### 2. Missing Controller Implementations

#### 2.1 DashboardController

**Purpose**: Provide dashboard data for different user roles

**Methods**:
- `userDashboard()`: User statistics, active services, recent transactions
- `adminDashboard()`: Financial overview, pending invoices, user statistics
- `superadminDashboard()`: System-wide statistics, all users, all transactions

**Data Sources**:
- UserModel::getDashboardStats()
- Transaction aggregations
- Invoice summaries
- Service usage statistics

#### 2.2 ServiceController

**Purpose**: Manage service catalog and subscriptions

**Methods**:
- `index()`: List all available services
- `show($id)`: Get service details
- `subscribe()`: Subscribe user to a service
- `myServices()`: Get user's active services

**Database Tables**:
- `layanan` (services catalog)
- `transaksi_layanan` (service subscriptions)

#### 2.3 TransactionController

**Purpose**: Handle service transactions

**Methods**:
- `index()`: List user transactions
- `show($id)`: Get transaction details
- `create()`: Create new transaction
- `updateStatus($id)`: Update transaction status (admin only)

#### 2.4 InvoiceController

**Purpose**: Manage invoices and payments

**Methods**:
- `index()`: List user invoices
- `show($id)`: Get invoice details
- `markAsPaid($id)`: Mark invoice as paid (admin)
- `download($id)`: Generate PDF invoice

#### 2.5 DocumentController

**Purpose**: Handle document uploads and management

**Methods**:
- `index()`: List user documents
- `show($id)`: Get document details
- `upload()`: Upload new document
- `update($id)`: Update document metadata
- `delete($id)`: Delete document

**Security Considerations**:
- Validate file types (whitelist: PDF, DOC, DOCX, JPG, PNG)
- Limit file sizes (max 5MB)
- Sanitize filenames
- Store files outside web root
- Generate unique filenames to prevent overwrites

#### 2.6 WasteCollectionController

**Purpose**: Manage waste collection schedules

**Methods**:
- `index()`: List collection history
- `show($id)`: Get collection details
- `schedule()`: Schedule new collection
- `complete($id)`: Mark collection as complete

#### 2.7 ManifestController

**Purpose**: Electronic manifest management

**Methods**:
- `index()`: List manifests
- `show($id)`: Get manifest details
- `create()`: Create new manifest
- `approve($id)`: Approve manifest (superadmin only)

#### 2.8 AdminController

**Purpose**: Admin user and transaction management

**Methods**:
- `getUsers()`: List all users
- `getUser($id)`: Get user details
- `updateUserStatus($id)`: Update user status
- `getAllTransactions()`: List all transactions
- `getAllInvoices()`: List all invoices

#### 2.9 SuperAdminController

**Purpose**: System administration

**Methods**:
- `getServices()`: List all services
- `createService()`: Create new service
- `updateService($id)`: Update service
- `deleteService($id)`: Delete service
- `getSystemStats()`: Get system statistics

### 3. Frontend Components

#### 3.1 Registration Component

**Purpose**: User registration form

**Features**:
- Form validation using react-hook-form + yup
- Real-time validation feedback
- Password strength indicator
- Terms and conditions acceptance
- Success/error messaging

**Fields**:
- Username
- Email
- Password
- Confirm Password
- Nama Lengkap
- Nama Perusahaan
- Alamat Perusahaan
- Telepon

#### 3.2 Enhanced Dashboard Component

**Current State**: Basic dashboard exists
**Enhancements**:
- Display user statistics (envipoin, active services)
- Show recent transactions
- Display pending invoices
- Quick action buttons
- Service status indicators
- Responsive card layout

#### 3.3 Navigation Component

**Purpose**: Main navigation menu

**Features**:
- Role-based menu items
- Active route highlighting
- User profile dropdown
- Logout functionality
- Mobile responsive menu

#### 3.4 Service Management Components

**Components**:
- ServiceList: Display available services
- ServiceCard: Individual service display
- ServiceSubscription: Subscribe to service form
- MyServices: User's active services

#### 3.5 Transaction Components

**Components**:
- TransactionList: Display transactions with filters
- TransactionDetail: Detailed transaction view
- TransactionStatus: Status badge component

#### 3.6 Invoice Components

**Components**:
- InvoiceList: Display invoices with status
- InvoiceDetail: Detailed invoice view
- PaymentButton: Initiate payment

### 4. Database Schema Fixes

#### 4.1 Users Table Issues

**Issue**: `layanan_aktif` field type mismatch
- Migration defines as INT
- Model/Controller use as VARCHAR (e.g., 'EnviReg')

**Solution**: Change to VARCHAR in migration
```php
'layanan_aktif' => [
    'type'       => 'VARCHAR',
    'constraint' => '50',
    'default'    => 'EnviReg',
],
```

#### 4.2 Missing email_verified_at Field

**Issue**: Used in AuthController but not in migration
**Solution**: Add to migration
```php
'email_verified_at' => [
    'type' => 'DATETIME',
    'null' => true,
],
```

#### 4.3 Missing Indexes

**Performance Issue**: No indexes on frequently queried fields
**Solution**: Add indexes
- users.email (unique index exists)
- users.verification_token
- users.reset_token
- transaksi_layanan.user_id
- invoice.user_id
- invoice.status_pembayaran

### 5. Configuration Improvements

#### 5.1 Environment Configuration

**Backend .env**:
```env
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080'
app.forceGlobalSecureRequests = false

database.default.hostname = localhost
database.default.database = envipoin_db
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi
database.default.port = 3306

JWT_SECRET = <generated-secure-key>
JWT_EXPIRATION = 86400

encryption.key = <generated-encryption-key>

# Email Configuration (for verification and password reset)
email.fromEmail = noreply@envindo.com
email.fromName = Envindo System
email.SMTPHost = smtp.gmail.com
email.SMTPUser = your-email@gmail.com
email.SMTPPass = your-app-password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

**Frontend .env**:
```env
REACT_APP_API_URL=http://localhost:8080/api
REACT_APP_ENV=development
```

#### 5.2 CORS Middleware Activation

**File**: `app/Config/Filters.php`

Add CORS filter to globals:
```php
public array $globals = [
    'before' => [
        'cors',
    ],
    'after' => [],
];

public array $aliases = [
    'cors' => \CodeIgniter\Filters\Cors::class,
    'jwt-auth' => \App\Filters\JWTAuthFilter::class,
];
```

#### 5.3 Frontend Proxy Configuration

**For Development**: Add proxy to package.json
```json
{
  "proxy": "http://localhost:8080"
}
```

**For Production**: Use environment variables

### 6. Error Handling

#### 6.1 Backend Error Response Format

**Standardized Format**:
```json
{
  "status": "error",
  "message": "Human-readable error message",
  "errors": {
    "field_name": ["Validation error 1", "Validation error 2"]
  },
  "code": "ERROR_CODE"
}
```

#### 6.2 Frontend Error Handling

**Implementation**:
- Axios interceptors for global error handling
- Toast notifications for user feedback
- Error boundary components for React errors
- Retry logic for network failures

### 7. Authentication Flow

#### 7.1 Registration Flow

```
User → Registration Form → Validation → API Call → Database Insert
                                                    ↓
                                            Generate Verification Token
                                                    ↓
                                            Send Verification Email
                                                    ↓
                                            Return Success Response
```

#### 7.2 Login Flow

```
User → Login Form → API Call → Verify Credentials → Check Email Verified
                                                    ↓
                                            Generate JWT Token
                                                    ↓
                                            Return Token + User Data
                                                    ↓
                                            Store Token in LocalStorage
                                                    ↓
                                            Set Axios Default Header
                                                    ↓
                                            Redirect to Dashboard
```

#### 7.3 Email Verification Flow

```
User Clicks Email Link → Extract Token → API Call → Verify Token
                                                    ↓
                                            Update email_verified = true
                                                    ↓
                                            Clear verification_token
                                                    ↓
                                            Return Success
                                                    ↓
                                            Redirect to Login
```

#### 7.4 Password Reset Flow

```
User → Forgot Password Form → API Call → Generate Reset Token
                                        ↓
                                Send Reset Email
                                        ↓
User Clicks Email Link → Reset Password Form → API Call → Verify Token
                                                        ↓
                                                Check Expiration
                                                        ↓
                                                Update Password
                                                        ↓
                                                Clear Reset Token
                                                        ↓
                                                Return Success
```

## Data Models

### User Model Extensions

**Additional Methods Needed**:
- `lockAccount($userId)`: Lock account after failed attempts
- `unlockAccount($userId)`: Unlock account
- `getFailedLoginAttempts($userId)`: Get failed login count
- `resetFailedLoginAttempts($userId)`: Reset counter

### Service Model (New)

```php
class ServiceModel extends Model
{
    protected $table = 'layanan';
    protected $allowedFields = [
        'nama_layanan',
        'deskripsi',
        'harga',
        'durasi_hari',
        'status',
        'fitur'
    ];
    
    public function getActiveServices();
    public function getServiceById($id);
    public function getUserServices($userId);
}
```

### Transaction Model (New)

```php
class TransactionModel extends Model
{
    protected $table = 'transaksi_layanan';
    protected $allowedFields = [
        'user_id',
        'layanan_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'total_harga'
    ];
    
    public function getUserTransactions($userId);
    public function getTransactionById($id);
    public function createTransaction($data);
    public function updateTransactionStatus($id, $status);
}
```

### Invoice Model (New)

```php
class InvoiceModel extends Model
{
    protected $table = 'invoice';
    protected $allowedFields = [
        'user_id',
        'transaksi_id',
        'nomor_invoice',
        'tanggal_invoice',
        'tanggal_jatuh_tempo',
        'total_tagihan',
        'status_pembayaran',
        'tanggal_pembayaran'
    ];
    
    public function getUserInvoices($userId);
    public function getPendingInvoices($userId);
    public function markAsPaid($id);
    public function generateInvoiceNumber();
}
```

## Error Handling

### Backend Error Handling Strategy

1. **Validation Errors**: Return 400 with field-specific errors
2. **Authentication Errors**: Return 401 with clear message
3. **Authorization Errors**: Return 403 with permission message
4. **Not Found Errors**: Return 404 with resource type
5. **Server Errors**: Return 500 with generic message (log details)

### Frontend Error Handling Strategy

1. **Network Errors**: Show retry option
2. **Validation Errors**: Display inline with form fields
3. **Authentication Errors**: Redirect to login
4. **Authorization Errors**: Show access denied page
5. **Server Errors**: Show generic error message

## Testing Strategy

### Backend Testing

1. **Unit Tests**:
   - Model methods
   - Validation rules
   - Helper functions

2. **Integration Tests**:
   - API endpoints
   - Authentication flow
   - Database operations

3. **Security Tests**:
   - SQL injection attempts
   - XSS attempts
   - CSRF protection
   - JWT token validation

### Frontend Testing

1. **Component Tests**:
   - Form validation
   - User interactions
   - Conditional rendering

2. **Integration Tests**:
   - API integration
   - Authentication flow
   - Navigation

3. **E2E Tests** (Optional):
   - Complete user flows
   - Registration to dashboard
   - Service subscription flow

## Deployment Configuration

### Development Environment

**Backend**:
- PHP built-in server: `php spark serve --port=8080`
- Or Apache/Nginx with virtual host
- MySQL database on localhost

**Frontend**:
- React development server: `npm start`
- Runs on port 3000
- Hot reload enabled

### Production Environment

**Backend**:
- Apache/Nginx web server
- PHP-FPM for performance
- MySQL database (separate server recommended)
- SSL/TLS certificate
- Environment variables via server configuration

**Frontend**:
- Build optimized bundle: `npm run build`
- Serve via Nginx/Apache
- CDN for static assets (optional)
- Environment-specific API URLs

**Server Requirements**:
- PHP 8.1 or higher
- MySQL 5.7 or higher
- Node.js 16+ (for building frontend)
- Composer
- SSL certificate for HTTPS

### Deployment Steps

1. **Backend Deployment**:
   - Clone repository
   - Run `composer install --no-dev`
   - Configure `.env` with production values
   - Run migrations: `php spark migrate`
   - Set proper file permissions
   - Configure web server

2. **Frontend Deployment**:
   - Clone repository
   - Run `npm install`
   - Create production `.env` file
   - Build: `npm run build`
   - Copy `build/` folder to web server
   - Configure web server for SPA routing

3. **Database Setup**:
   - Create database
   - Create database user with appropriate permissions
   - Run migrations
   - Seed initial data (if needed)

### Security Checklist for Production

- [ ] Change all default passwords
- [ ] Generate strong JWT secret
- [ ] Enable HTTPS only
- [ ] Set secure cookie flags
- [ ] Disable debug mode
- [ ] Configure proper CORS origins
- [ ] Set up database backups
- [ ] Configure error logging
- [ ] Implement rate limiting
- [ ] Set up monitoring and alerts
- [ ] Review file upload permissions
- [ ] Enable SQL query logging (for audit)
- [ ] Configure firewall rules
- [ ] Set up intrusion detection

## Documentation Requirements

### API Documentation

Create comprehensive API documentation including:
- Base URL and versioning
- Authentication requirements
- All endpoints with methods
- Request/response examples
- Error codes and messages
- Rate limiting information

### Setup Documentation

Create setup guides for:
- Development environment setup
- Database configuration
- Running migrations
- Starting development servers
- Building for production
- Deployment procedures

### User Documentation

Create user guides for:
- Registration and login
- Email verification
- Password reset
- Using dashboard features
- Managing services
- Viewing transactions and invoices
