# Deployment Checklist

This comprehensive checklist ensures a smooth and secure deployment of the Envindo system to production.

## Pre-Deployment Tasks

### 1. Code Preparation

- [ ] All features tested in development environment
- [ ] All unit tests passing
- [ ] Code reviewed and approved
- [ ] No debug code or console.log statements
- [ ] No commented-out code blocks
- [ ] Version number updated in package.json and documentation
- [ ] CHANGELOG.md updated with release notes
- [ ] Git repository is clean (no uncommitted changes)
- [ ] Create release tag in Git: `git tag -a v1.0.0 -m "Release v1.0.0"`

### 2. Security Review

- [ ] All dependencies updated to latest secure versions
- [ ] Security audit completed: `npm audit` and `composer audit`
- [ ] No hardcoded credentials in code
- [ ] All sensitive data moved to environment variables
- [ ] JWT_SECRET generated and secured
- [ ] Encryption key generated and secured
- [ ] CORS origins properly configured (no wildcards)
- [ ] Rate limiting configured
- [ ] Input validation implemented on all endpoints
- [ ] SQL injection prevention verified
- [ ] XSS prevention verified
- [ ] CSRF protection enabled
- [ ] File upload security implemented

### 3. Environment Configuration

#### Backend Configuration

- [ ] Create `backend/.env.production` from template
- [ ] Set `CI_ENVIRONMENT = production`
- [ ] Configure production database credentials
- [ ] Generate new `JWT_SECRET`: `openssl rand -hex 32`
- [ ] Generate new `encryption.key`: `php spark key:generate`
- [ ] Set production `app.baseURL` with HTTPS
- [ ] Enable `app.forceGlobalSecureRequests = true`
- [ ] Configure production email settings (SMTP)
- [ ] Set `logger.threshold = 3` (WARNING level)
- [ ] Set `FRONTEND_URL` to production frontend domain
- [ ] Verify all required environment variables are set

#### Frontend Configuration

- [ ] Create `frontend/.env.production` from template
- [ ] Set `REACT_APP_API_URL` to production API endpoint
- [ ] Set `REACT_APP_ENV = production`
- [ ] Verify API URL uses HTTPS
- [ ] Configure analytics/monitoring IDs (if applicable)
- [ ] Disable source maps: `GENERATE_SOURCEMAP=false`

### 4. Database Preparation

- [ ] Database backup created
- [ ] Production database created
- [ ] Database user created with minimal privileges
- [ ] Database connection tested from application server
- [ ] Migration files reviewed and tested
- [ ] Seed data prepared (if needed)
- [ ] Database indexes verified
- [ ] Database performance optimized

### 5. Server Preparation

#### Backend Server

- [ ] PHP 8.1+ installed
- [ ] Required PHP extensions installed:
  - [ ] intl
  - [ ] mbstring
  - [ ] mysqli
  - [ ] json
  - [ ] xml
  - [ ] curl
  - [ ] gd (for image processing)
- [ ] Composer installed
- [ ] Web server configured (Apache/Nginx)
- [ ] SSL certificate installed and configured
- [ ] Firewall rules configured
- [ ] Server timezone set correctly
- [ ] PHP memory_limit set appropriately (256M+)
- [ ] PHP upload_max_filesize set (10M+)
- [ ] PHP post_max_size set (10M+)

#### Frontend Server

- [ ] Node.js 16+ installed (for building)
- [ ] Web server configured (Nginx/Apache)
- [ ] SSL certificate installed and configured
- [ ] CDN configured (if applicable)
- [ ] Gzip/Brotli compression enabled
- [ ] Cache headers configured

### 6. Build Preparation

- [ ] Run production build locally to test
- [ ] Backend: `composer install --no-dev --optimize-autoloader`
- [ ] Frontend: `npm run build`
- [ ] Verify build completes without errors
- [ ] Test production build locally
- [ ] Check bundle sizes are acceptable
- [ ] Verify all assets are included

## Deployment Steps

### Phase 1: Database Deployment

1. **Backup Current Database** (if updating existing system)
   ```bash
   mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
   ```
   - [ ] Backup completed and verified
   - [ ] Backup stored in secure location

2. **Deploy Database Schema**
   ```bash
   php spark migrate
   ```
   - [ ] Migrations executed successfully
   - [ ] Database schema verified
   - [ ] Indexes created

3. **Seed Initial Data** (if new deployment)
   ```bash
   php spark db:seed InitialDataSeeder
   ```
   - [ ] Seed data inserted
   - [ ] Default admin user created
   - [ ] Initial services created

### Phase 2: Backend Deployment

1. **Upload Backend Files**
   - [ ] Upload all backend files to server
   - [ ] Exclude development files (.git, tests, .env.example)
   - [ ] Verify file permissions (755 for directories, 644 for files)

2. **Install Dependencies**
   ```bash
   cd backend
   composer install --no-dev --optimize-autoloader --no-interaction
   ```
   - [ ] Dependencies installed successfully
   - [ ] No errors or warnings

3. **Configure Environment**
   - [ ] Copy `.env.production` to `.env`
   - [ ] Verify all environment variables are set
   - [ ] Test database connection

4. **Set File Permissions**
   ```bash
   chmod -R 755 writable
   chmod -R 755 public
   chown -R www-data:www-data writable
   ```
   - [ ] Writable directory permissions set
   - [ ] Public directory permissions set
   - [ ] Owner set correctly

5. **Clear Cache**
   ```bash
   php spark cache:clear
   ```
   - [ ] Cache cleared

6. **Configure Web Server**
   - [ ] Virtual host configured
   - [ ] Document root set to `public/`
   - [ ] URL rewriting enabled
   - [ ] SSL configured
   - [ ] HTTPS redirect enabled
   - [ ] Security headers configured

### Phase 3: Frontend Deployment

1. **Build Frontend**
   ```bash
   cd frontend
   npm ci
   npm run build
   ```
   - [ ] Build completed successfully
   - [ ] Build directory created

2. **Upload Frontend Files**
   - [ ] Upload `build/` directory contents to web server
   - [ ] Verify all files uploaded correctly
   - [ ] Set proper file permissions

3. **Configure Web Server**
   - [ ] Virtual host configured
   - [ ] Document root set to build directory
   - [ ] SPA routing configured (fallback to index.html)
   - [ ] SSL configured
   - [ ] HTTPS redirect enabled
   - [ ] Gzip compression enabled
   - [ ] Cache headers configured for static assets

### Phase 4: Email Configuration

1. **Configure Email Service**
   - [ ] SMTP credentials verified
   - [ ] Test email sent successfully
   - [ ] Email templates reviewed
   - [ ] Sender domain verified (SPF/DKIM)

2. **Test Email Functionality**
   - [ ] Registration email sent
   - [ ] Email verification works
   - [ ] Password reset email sent
   - [ ] Password reset works

## Post-Deployment Verification

### 1. Smoke Tests

#### Backend API Tests

- [ ] Health check endpoint responds: `GET /api/health`
- [ ] Registration endpoint works: `POST /api/auth/register`
- [ ] Login endpoint works: `POST /api/auth/login`
- [ ] Protected endpoint requires authentication: `GET /api/dashboard`
- [ ] CORS headers present in responses
- [ ] Error responses are user-friendly (no stack traces)

#### Frontend Tests

- [ ] Homepage loads correctly
- [ ] Registration page works
- [ ] Login page works
- [ ] Dashboard loads after login
- [ ] Navigation works
- [ ] API calls succeed
- [ ] Error handling works
- [ ] Responsive design works on mobile

### 2. Security Verification

- [ ] HTTPS enforced (HTTP redirects to HTTPS)
- [ ] SSL certificate valid
- [ ] Security headers present:
  - [ ] Strict-Transport-Security
  - [ ] X-Content-Type-Options
  - [ ] X-Frame-Options
  - [ ] X-XSS-Protection
- [ ] CORS properly configured
- [ ] JWT authentication working
- [ ] Rate limiting active
- [ ] File upload restrictions working
- [ ] SQL injection prevention verified
- [ ] XSS prevention verified

### 3. Performance Verification

- [ ] Page load times acceptable (< 3 seconds)
- [ ] API response times acceptable (< 500ms)
- [ ] Database queries optimized
- [ ] Static assets cached properly
- [ ] Gzip compression working
- [ ] No memory leaks
- [ ] Server resources within limits

### 4. Monitoring Setup

- [ ] Error logging configured
- [ ] Log files being written to `writable/logs/`
- [ ] Log rotation configured
- [ ] Server monitoring enabled (CPU, memory, disk)
- [ ] Application monitoring enabled (if applicable)
- [ ] Uptime monitoring configured
- [ ] Alert notifications configured
- [ ] Backup monitoring configured

### 5. Backup Verification

- [ ] Database backup scheduled (daily)
- [ ] File backup scheduled (daily)
- [ ] Backup restoration tested
- [ ] Backup retention policy configured
- [ ] Off-site backup configured

### 6. Documentation

- [ ] API documentation accessible
- [ ] Admin documentation provided
- [ ] User documentation provided
- [ ] Deployment documentation updated
- [ ] Runbook created for operations team
- [ ] Contact information updated

## Rollback Procedures

### When to Rollback

Rollback if:
- Critical bugs discovered in production
- Security vulnerabilities found
- Performance degradation
- Data corruption
- Service unavailable for extended period

### Rollback Steps

#### 1. Immediate Actions

- [ ] Notify team of rollback decision
- [ ] Document reason for rollback
- [ ] Preserve logs for analysis

#### 2. Database Rollback

```bash
# Restore database from backup
mysql -u username -p database_name < backup_file.sql
```
- [ ] Database restored from backup
- [ ] Database integrity verified
- [ ] Connections tested

#### 3. Backend Rollback

- [ ] Restore previous backend version from Git tag
- [ ] Restore previous `.env` configuration
- [ ] Run `composer install`
- [ ] Clear cache
- [ ] Restart web server

#### 4. Frontend Rollback

- [ ] Restore previous frontend build
- [ ] Clear CDN cache (if applicable)
- [ ] Verify frontend loads correctly

#### 5. Verification

- [ ] Application accessible
- [ ] Core functionality working
- [ ] No errors in logs
- [ ] Users can access system

#### 6. Post-Rollback

- [ ] Notify users of rollback (if necessary)
- [ ] Analyze root cause
- [ ] Plan fix and redeployment
- [ ] Update deployment procedures

## Post-Deployment Tasks

### Immediate (Within 1 Hour)

- [ ] Monitor error logs
- [ ] Monitor server resources
- [ ] Monitor user activity
- [ ] Verify critical workflows
- [ ] Check email delivery
- [ ] Respond to any issues

### Short-term (Within 24 Hours)

- [ ] Review all logs for errors
- [ ] Check performance metrics
- [ ] Verify backup completed
- [ ] Monitor user feedback
- [ ] Document any issues
- [ ] Update status page

### Medium-term (Within 1 Week)

- [ ] Analyze usage patterns
- [ ] Review performance metrics
- [ ] Optimize slow queries
- [ ] Address user feedback
- [ ] Plan next iteration
- [ ] Update documentation

## Emergency Contacts

| Role | Name | Contact |
|------|------|---------|
| Lead Developer | [Name] | [Email/Phone] |
| DevOps Engineer | [Name] | [Email/Phone] |
| Database Admin | [Name] | [Email/Phone] |
| System Admin | [Name] | [Email/Phone] |
| Project Manager | [Name] | [Email/Phone] |

## Deployment Sign-off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Developer | | | |
| QA Lead | | | |
| DevOps | | | |
| Project Manager | | | |

## Notes

Use this section to document any deployment-specific notes, issues encountered, or deviations from the standard process:

```
[Add notes here]
```

---

**Version:** 1.0.0  
**Last Updated:** 2025-01-10  
**Next Review:** Before next deployment
