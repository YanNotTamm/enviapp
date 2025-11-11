# 🔧 Troubleshooting Email Sending

## Error: "Failed to send test email"

### Perbaikan yang Sudah Dilakukan:

1. ✅ **Added Debug Logging**
   - Log setiap attempt send email
   - Log detailed error messages
   - Log stack trace untuk exceptions

2. ✅ **Fixed SMTPCrypto for Port 465**
   - Port 465 dengan SSL → SMTPCrypto = '' (empty string)
   - Port 587 dengan TLS → SMTPCrypto = 'tls'

3. ✅ **Enhanced Error Response**
   - API sekarang return error detail
   - Include path ke log file

---

## Cara Debug

### Step 1: Test Lagi dengan Error Detail

Akses:
```
https://dev.envirometrolestari.com/api/test/send-email?to=your-email@example.com
```

Response sekarang akan include error detail:
```json
{
  "status": "error",
  "message": "Failed to send test email",
  "data": {
    "error_detail": "...",
    "check_logs": "backend/writable/logs/log-2025-11-10.php"
  }
}
```

### Step 2: Cek Logs

Logs location:
```
backend/writable/logs/log-{date}.php
deployment-ready/backend/writable/logs/log-{date}.php
```

Look for:
- `Attempting to send email to:`
- `Email send failed:`
- `Email send exception:`

### Step 3: Common Issues & Solutions

#### Issue 1: SMTP Authentication Failed
```
Error: SMTP Error: Could not authenticate
```

**Solution:**
- Verify username: `noreply@envirometrolestari.com`
- Verify password: `Notreppenvi25`
- Check email account is active
- Try login manually to email account

#### Issue 2: Connection Timeout
```
Error: SMTP Error: Could not connect to SMTP host
```

**Solution:**
- Check hostname: `mail.envirometrolestari.com`
- Check port 465 not blocked by firewall
- Test connection: `telnet mail.envirometrolestari.com 465`
- Check server can reach mail server

#### Issue 3: SSL/TLS Error
```
Error: stream_socket_enable_crypto(): SSL operation failed
```

**Solution:**
- For port 465: SMTPCrypto should be '' (empty)
- For port 587: SMTPCrypto should be 'tls'
- Check PHP OpenSSL extension enabled
- Verify SSL certificate valid

#### Issue 4: From Address Rejected
```
Error: SMTP Error: The following From address failed
```

**Solution:**
- Verify from email matches SMTP username
- Check SPF record allows sending from server IP
- Use authenticated email address

---

## Alternative Test Methods

### Method 1: PHP mail() Function Test

Create file: `test-php-mail.php`
```php
<?php
$to = 'your-email@example.com';
$subject = 'Test PHP mail()';
$message = 'This is a test email';
$headers = 'From: noreply@envirometrolestari.com';

if (mail($to, $subject, $message, $headers)) {
    echo 'Email sent via PHP mail()';
} else {
    echo 'Failed to send via PHP mail()';
}
?>
```

### Method 2: Direct SMTP Test (Telnet)

```bash
telnet mail.envirometrolestari.com 465
```

Expected: Connection established

### Method 3: OpenSSL Test

```bash
openssl s_client -connect mail.envirometrolestari.com:465 -crlf
```

Expected: SSL handshake successful

---

## Configuration Verification

### Current Configuration:
```
Host: mail.envirometrolestari.com
Port: 465
Security: SSL (implicit)
Username: noreply@envirometrolestari.com
Password: Notreppenvi25
From: noreply@envirometrolestari.com
Name: EnviliApps - Envirometro Lestari Indonesia
```

### Verify in .env:
```bash
# Check deployment-ready/.env.production
email.SMTPHost = mail.envirometrolestari.com
email.SMTPPort = 465
email.SMTPCrypto = ssl
email.SMTPUser = noreply@envirometrolestari.com
email.SMTPPass = Notreppenvi25
```

### Verify Email.php loads correctly:
```php
// In Email.php constructor
$this->SMTPPort = 465
$this->SMTPCrypto = '' // Empty for port 465!
```

---

## Next Steps

### 1. Test dengan Error Detail Baru
```
https://dev.envirometrolestari.com/api/test/send-email?to=your-email@example.com
```

### 2. Copy Error Detail
Copy semua error message dari response

### 3. Cek Logs
```
deployment-ready/backend/writable/logs/log-{today}.php
```

### 4. Identify Issue
Match error dengan common issues di atas

### 5. Apply Solution
Implement solution sesuai issue yang ditemukan

---

## PHP Requirements

Pastikan PHP extensions enabled:
```
- openssl
- sockets
- mbstring
```

Check dengan:
```php
<?php
phpinfo();
// Look for: openssl, sockets, mbstring
?>
```

---

## Server Requirements

### Firewall Rules:
- Allow outbound connection to port 465
- Allow connection to mail.envirometrolestari.com

### DNS Resolution:
```bash
nslookup mail.envirometrolestari.com
```
Should resolve to valid IP

### Network Test:
```bash
ping mail.envirometrolestari.com
telnet mail.envirometrolestari.com 465
```

---

## Alternative: Use Port 587 with TLS

If port 465 doesn't work, try port 587:

### Update .env.production:
```env
email.SMTPPort = 587
email.SMTPCrypto = tls
```

### Restart and Test:
```
https://dev.envirometrolestari.com/api/test/send-email?to=your-email@example.com
```

---

## Contact Hosting Support

If all else fails, contact hosting support dengan info:
- Email account: noreply@envirometrolestari.com
- SMTP host: mail.envirometrolestari.com
- Port: 465 (SSL) or 587 (TLS)
- Error message dari logs

Ask them to verify:
1. Email account is active
2. SMTP is enabled
3. Port 465/587 is open
4. Server IP is not blacklisted

---

## Status

🔄 **Waiting for test results with new error details**

Next: Run test and share error detail untuk diagnosis lebih lanjut
 