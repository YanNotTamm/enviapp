# ✅ SOLUSI: Email Authentication Error

## Masalah yang Ditemukan

### Error Message:
```
Failed to authenticate password. Error: 535-5.7.8 Username and Password not accepted
```

### Root Cause:
System mencoba connect ke `smtp.gmail.com` (default) padahal seharusnya ke `mail.envirometrolestari.com`

**Penyebab:** Environment variables dari `.env.production` tidak ter-load dengan benar di production environment.

---

## Solusi yang Diterapkan

### 1. Fallback Configuration

Updated `Email.php` untuk include fallback values:

```php
// Try $_ENV first, then getenv(), then fallback to hardcoded values
$this->SMTPHost = $_ENV['email.SMTPHost'] 
    ?? getenv('email.SMTPHost') 
    ?: 'mail.envirometrolestari.com';
```

### 2. Hardcoded Defaults

Jika environment variables tidak tersedia, gunakan konfigurasi yang benar:

```php
Host: mail.envirometrolestari.com
Port: 465
User: noreply@envirometrolestari.com
Pass: Notreppenvi25
From: EnviliApps - Envirometro Lestari Indonesia
```

---

## Testing

### Test 1: Verify Configuration Loaded

Akses:
```
https://dev.envirometrolestari.com/api/test/email
```

Expected response:
```json
{
  "success": true,
  "config": {
    "host": "mail.envirometrolestari.com",  // NOT smtp.gmail.com!
    "port": 465,
    "user": "noreply@envirometrolestari.com"
  }
}
```

### Test 2: Send Test Email

Akses:
```
https://dev.envirometrolestari.com/api/test/send-email?to=your-email@example.com
```

Expected: Email terkirim tanpa authentication error

---

## Verification Checklist

### ✅ Configuration Check:

1. **SMTP Host**
   - ❌ Wrong: `smtp.gmail.com`
   - ✅ Correct: `mail.envirometrolestari.com`

2. **SMTP Port**
   - ❌ Wrong: `587`
   - ✅ Correct: `465`

3. **SMTP User**
   - ❌ Wrong: empty or gmail address
   - ✅ Correct: `noreply@envirometrolestari.com`

4. **SMTP Crypto**
   - ❌ Wrong: `tls` or `ssl`
   - ✅ Correct: `''` (empty string for port 465)

---

## Why This Happened

### CodeIgniter Environment Loading

In production, CodeIgniter may not automatically load `.env` file. Reasons:

1. **Performance**: `.env` parsing adds overhead
2. **Security**: Production should use server environment variables
3. **Caching**: Config may be cached

### Solutions:

**Option 1: Use Fallback (IMPLEMENTED)**
- Hardcode defaults in Email.php
- Works immediately
- Easy to maintain

**Option 2: Server Environment Variables**
- Set in Apache/Nginx config
- More secure
- Requires server access

**Option 3: Force .env Loading**
- Modify bootstrap
- May impact performance
- Not recommended for production

---

## File Changes

### backend/app/Config/Email.php

**Before:**
```php
$this->SMTPHost = getenv('email.SMTPHost') ?: 'smtp.gmail.com';
```

**After:**
```php
$this->SMTPHost = $_ENV['email.SMTPHost'] 
    ?? getenv('email.SMTPHost') 
    ?: 'mail.envirometrolestari.com';
```

**Benefits:**
- ✅ Try $_ENV first (faster)
- ✅ Fallback to getenv()
- ✅ Hardcoded default as last resort
- ✅ Always uses correct configuration

---

## Next Steps

### 1. Wait for Sync (1-2 minutes)

File sudah di-copy ke `deployment-ready/`

### 2. Test Configuration

```
https://dev.envirometrolestari.com/api/test/email
```

Verify response shows:
- host: `mail.envirometrolestari.com`
- port: `465`
- user: `noreply@envirometrolestari.com`

### 3. Send Test Email

```
https://dev.envirometrolestari.com/api/test/send-email?to=your-email@example.com
```

Should succeed without authentication error

### 4. Register User

Test full flow:
1. Register new user
2. Check email inbox
3. Click verification link
4. Login

---

## Expected Results

### Before Fix:
```
❌ Connecting to: smtp.gmail.com:587
❌ Error: Username and Password not accepted
```

### After Fix:
```
✅ Connecting to: mail.envirometrolestari.com:465
✅ Authentication successful
✅ Email sent
```

---

## Troubleshooting

### Still Getting Gmail Error?

**Clear PHP OpCache:**
```php
<?php
opcache_reset();
echo "OpCache cleared";
?>
```

**Restart PHP-FPM:**
```bash
sudo systemctl restart php-fpm
# or
sudo service php7.4-fpm restart
```

### Still Not Working?

**Check which config is loaded:**

Add to TestController:
```php
public function debugEmail() {
    $email = \Config\Services::email();
    return $this->respond([
        'host' => $email->SMTPHost,
        'port' => $email->SMTPPort,
        'user' => $email->SMTPUser,
        'crypto' => $email->SMTPCrypto
    ]);
}
```

Access: `/api/test/debug-email`

---

## Status

✅ **FIXED** - Fallback configuration implemented
🔄 **TESTING** - Waiting for sync and test results

Next: Test email sending dengan konfigurasi baru
 