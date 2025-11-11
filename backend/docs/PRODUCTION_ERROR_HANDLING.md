# Production Error Handling

This document describes the error handling configuration for production environments.

## Overview

The Envindo system implements secure error handling in production to:
- Hide sensitive information from error messages
- Provide user-friendly error responses
- Log detailed error information for debugging
- Prevent information disclosure vulnerabilities

## Configuration

### Environment Settings

In production, the following settings are configured in `.env.production`:

```env
CI_ENVIRONMENT = production
logger.threshold = 3
app.showDebugBacktrace = false
app.logErrors = true
```

### Error Handler

The system uses a custom `ProductionExceptionHandler` that:

1. **Hides Sensitive Data**: Removes passwords, tokens, and credentials from error traces
2. **Provides Safe Messages**: Returns generic, user-friendly error messages
3. **Logs Full Details**: Logs complete error information to files for debugging
4. **Handles API vs Web**: Provides JSON responses for API requests, HTML for web requests

## Sensitive Data Protection

The following data is automatically hidden from error traces:

- `password`, `passwd`, `password_confirmation`
- `old_password`, `new_password`
- `JWT_SECRET`, `encryption.key`
- `database.default.password`
- `email.SMTPPass`
- `secret`, `token`, `api_key`
- `access_token`, `refresh_token`

## Error Response Formats

### API Requests (JSON)

```json
{
  "status": "error",
  "message": "User-friendly error message",
  "code": 500,
  "error_code": "OPTIONAL_ERROR_CODE"
}
```

### Web Requests (HTML)

A styled HTML error page is displayed with:
- HTTP status code
- User-friendly error message
- Link to homepage

## HTTP Status Codes

The system handles the following status codes with specific messages:

| Code | Message |
|------|---------|
| 400  | Bad Request. Please check your input and try again. |
| 401  | Authentication required. Please log in to continue. |
| 403  | Access denied. You do not have permission to access this resource. |
| 404  | The requested resource was not found. |
| 405  | Method not allowed. |
| 422  | Validation failed. Please check your input. |
| 429  | Too many requests. Please try again later. |
| 500  | An internal server error occurred. Please try again later. |
| 502  | Bad Gateway. The server is temporarily unavailable. |
| 503  | Service temporarily unavailable. Please try again later. |

## Error Logging

### Log Levels

Errors are logged based on severity:

- **500+ errors**: Logged as ERROR level
- **400-499 errors**: Logged as WARNING level
- **Other errors**: Logged as INFO level

### Log Format

```
Exception: ExceptionClass | Message: Error message | File: /path/to/file.php | Line: 123 | Status: 500
```

### Log Location

Logs are written to: `writable/logs/log-YYYY-MM-DD.log`

## Monitoring Recommendations

### 1. Log Monitoring

Set up monitoring for:
- 500 errors (server errors)
- Repeated 401/403 errors (potential security issues)
- High frequency of 429 errors (rate limiting triggers)

### 2. Error Alerting

Configure alerts for:
- Critical errors (500, 502, 503)
- Unusual error patterns
- Error rate spikes

### 3. Log Rotation

Implement log rotation to prevent disk space issues:
- Rotate logs daily or weekly
- Compress old logs
- Archive logs for compliance
- Delete logs older than retention period

## Testing Error Handling

### Test in Development

Before deploying, test error handling:

1. Set `CI_ENVIRONMENT = production` in development
2. Trigger various errors (404, 500, validation errors)
3. Verify error messages don't expose sensitive data
4. Check logs contain full error details
5. Test both API and web error responses

### Test Commands

```bash
# Test 404 error
curl http://localhost:8080/api/nonexistent

# Test validation error
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"invalid"}'

# Test authentication error
curl http://localhost:8080/api/dashboard \
  -H "Authorization: Bearer invalid_token"
```

## Security Considerations

### 1. Never Expose

- Database credentials
- API keys and secrets
- File paths and directory structure
- Stack traces in production
- Internal error messages

### 2. Always Log

- Full exception details
- Request context (URL, method, IP)
- User context (if authenticated)
- Timestamp and severity

### 3. Rate Limiting

Implement rate limiting to prevent:
- Brute force attacks
- API abuse
- DoS attacks

## Troubleshooting

### Errors Not Being Logged

1. Check `writable/logs/` directory exists and is writable
2. Verify `logger.threshold` is set correctly
3. Check `app.logErrors = true` in .env
4. Verify file permissions on writable directory

### Sensitive Data Still Visible

1. Verify `CI_ENVIRONMENT = production`
2. Check `sensitiveDataInTrace` array in `Config/Exceptions.php`
3. Add additional sensitive field names if needed
4. Clear cache: `php spark cache:clear`

### Custom Error Pages Not Showing

1. Verify `ProductionExceptionHandler` is being used
2. Check error handler logic in `Config/Exceptions.php`
3. Test with different error types
4. Check web server error handling configuration

## Maintenance

### Regular Tasks

1. **Review Logs**: Check logs weekly for patterns
2. **Update Sensitive Data List**: Add new sensitive fields as needed
3. **Test Error Handling**: Test after major updates
4. **Monitor Disk Space**: Ensure logs don't fill disk
5. **Update Error Messages**: Keep messages user-friendly and helpful

### After Deployment

1. Verify error handling is working
2. Check logs are being written
3. Test error responses (API and web)
4. Monitor error rates
5. Set up alerting if not already configured

## Additional Resources

- [CodeIgniter Error Handling](https://codeigniter.com/user_guide/general/errors.html)
- [PSR-3 Logger Interface](https://www.php-fig.org/psr/psr-3/)
- [OWASP Error Handling](https://owasp.org/www-community/Improper_Error_Handling)
