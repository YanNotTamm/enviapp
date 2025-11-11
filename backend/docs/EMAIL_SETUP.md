# Email Configuration Guide

## Overview

The Envindo system uses email for:
- User email verification during registration
- Password reset requests

## Configuration

### 1. Update Environment Variables

Edit `backend/.env` and configure the following email settings:

```env
# Email Configuration
email.fromEmail = noreply@envindo.com
email.fromName = Envindo System
email.SMTPHost = smtp.gmail.com
email.SMTPUser = your-email@gmail.com
email.SMTPPass = your-app-password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

### 2. Gmail Setup (if using Gmail)

If you're using Gmail as your SMTP server:

1. Enable 2-Factor Authentication on your Google account
2. Generate an App Password:
   - Go to Google Account Settings
   - Security → 2-Step Verification → App passwords
   - Generate a new app password for "Mail"
   - Use this password in `email.SMTPPass`

**Important:** Never use your actual Gmail password. Always use an App Password.

### 3. Other SMTP Providers

For other email providers, update the SMTP settings accordingly:

**SendGrid:**
```env
email.SMTPHost = smtp.sendgrid.net
email.SMTPUser = apikey
email.SMTPPass = your-sendgrid-api-key
email.SMTPPort = 587
email.SMTPCrypto = tls
```

**Mailgun:**
```env
email.SMTPHost = smtp.mailgun.org
email.SMTPUser = your-mailgun-username
email.SMTPPass = your-mailgun-password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

**AWS SES:**
```env
email.SMTPHost = email-smtp.us-east-1.amazonaws.com
email.SMTPUser = your-ses-smtp-username
email.SMTPPass = your-ses-smtp-password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

## Email Templates

Email templates are located in `backend/app/Views/emails/`:

- `verification.php` - Email verification template
- `password_reset.php` - Password reset template

You can customize these templates to match your branding.

## Testing Email Configuration

### Method 1: Using EmailHelper

```php
use App\Helpers\EmailHelper;

// Test connection
$result = EmailHelper::testConnection();
print_r($result);

// Send test email
$sent = EmailHelper::send(
    'test@example.com',
    'Test Email',
    '<h1>This is a test email</h1>'
);
```

### Method 2: Test Registration Flow

1. Start the backend server: `php spark serve`
2. Register a new user via API
3. Check the email inbox for verification email
4. Click the verification link

### Method 3: Test Password Reset Flow

1. Use the forgot password endpoint
2. Check email for reset link
3. Click the reset link and set new password

## Troubleshooting

### Email Not Sending

1. **Check SMTP credentials:**
   - Verify `email.SMTPUser` and `email.SMTPPass` are correct
   - For Gmail, ensure you're using an App Password

2. **Check firewall/network:**
   - Ensure port 587 (or 465 for SSL) is not blocked
   - Try using a different port if needed

3. **Check logs:**
   - View CodeIgniter logs in `backend/writable/logs/`
   - Look for email-related errors

4. **Test SMTP connection:**
   - Use telnet to test SMTP connection:
     ```bash
     telnet smtp.gmail.com 587
     ```

### Email Goes to Spam

1. Configure SPF, DKIM, and DMARC records for your domain
2. Use a verified sender email address
3. Avoid spam trigger words in subject/content
4. Use a reputable SMTP service

### SSL/TLS Errors

If you get SSL/TLS errors:

1. Try changing `email.SMTPCrypto`:
   - Use `tls` for port 587
   - Use `ssl` for port 465
   - Use empty string `''` for no encryption (not recommended)

2. Update PHP OpenSSL extension if needed

## Frontend Integration

The frontend needs to handle email verification and password reset routes:

**Email Verification Route:**
```
/verify-email/:token
```

**Password Reset Route:**
```
/reset-password/:token
```

These routes should call the corresponding backend API endpoints:
- `GET /api/auth/verify-email/:token`
- `POST /api/auth/reset-password` (with token and new password)

## Security Considerations

1. **Never commit credentials:** Keep `.env` file out of version control
2. **Use App Passwords:** For Gmail, always use App Passwords, not account passwords
3. **Token Expiration:** Verification and reset tokens expire automatically
4. **Rate Limiting:** Consider implementing rate limiting on email endpoints
5. **Email Enumeration:** The system prevents email enumeration by always returning success messages

## Production Recommendations

1. Use a dedicated email service (SendGrid, Mailgun, AWS SES)
2. Set up proper SPF/DKIM/DMARC records
3. Monitor email delivery rates
4. Implement email queue for better performance
5. Set up email bounce handling
6. Use a professional sender email address
7. Enable email logging for audit purposes
