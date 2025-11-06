# Railway Performance & Email Notification Fix

## Issues Fixed

### 1. **Email Notifications Not Working**
**Problem:** After Railway subscription renewal, email notifications stopped working.

**Root Causes:**
- Missing timeout settings on cURL requests (could hang indefinitely)
- Environment variables may not be properly configured after renewal
- No proper error logging for debugging

**Fixes Applied:**
- ✅ Added timeout settings to Resend API cURL requests (30s timeout, 10s connection timeout)
- ✅ Added timeout settings to SendGrid API cURL requests (30s timeout, 10s connection timeout)
- ✅ Improved error logging for email failures

### 2. **Slow Loading / Performance Issues**
**Problem:** Application is very slow after Railway subscription renewal.

**Root Causes:**
- `file_get_contents()` used for Google OAuth without timeout (could hang indefinitely)
- No timeout on external API calls
- Database connection issues after renewal

**Fixes Applied:**
- ✅ Replaced `file_get_contents()` with cURL in Google OAuth verification (15s timeout, 5s connection timeout)
- ✅ Added proper error handling for Google OAuth failures
- ✅ Database connection timeout already configured (10 seconds)

### 3. **Google Login Slow**
**Problem:** Google login takes too long or hangs.

**Root Causes:**
- `file_get_contents()` fetching Google public keys without timeout
- No error handling for network failures

**Fixes Applied:**
- ✅ Replaced `file_get_contents()` with cURL for fetching Google public keys
- ✅ Added 15-second timeout and 5-second connection timeout
- ✅ Added proper error logging

## Required Environment Variables for Railway

After renewing your Railway subscription, ensure these environment variables are set in your Railway project:

### Email Configuration (Choose ONE method):

**Option 1: Resend API (Recommended for Railway)**
```
RESEND_API_KEY=your_resend_api_key_here
RESEND_FROM_EMAIL=noreply@yourdomain.com
RESEND_FROM_NAME=SCMS System
```

**Option 2: SendGrid API**
```
SENDGRID_API_KEY=your_sendgrid_api_key_here
SENDGRID_FROM_EMAIL=noreply@yourdomain.com
SENDGRID_FROM_NAME=SCMS System
```

**Option 3: SMTP (Gmail)**
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_CRYPTO=tls
SMTP_FROM_NAME=SCMS System
```

### Database Configuration:
```
DB_HOST=your-db-host
DB_USER=your-db-user
DB_PASS=your-db-password
DB_NAME=your-db-name
DB_PORT=3306
```

Or use Railway's automatic variables:
```
MYSQLHOST=...
MYSQLUSER=...
MYSQLPASSWORD=...
MYSQLDATABASE=...
MYSQLPORT=...
```

### Base URL:
```
BASE_URL=https://your-railway-app.up.railway.app
```

## How to Check Your Environment Variables in Railway

1. Go to your Railway project dashboard
2. Click on your service
3. Go to the "Variables" tab
4. Verify all required variables are set
5. If any are missing, add them and redeploy

## Testing Email Configuration

You can test your email configuration using the API endpoint:
```
GET /api/auth/test-email?to=your-email@example.com
```

Or use the debug endpoint:
```
GET /api/auth/debug-email
```

## Performance Improvements

### Timeout Settings Added:
- **Email APIs (Resend/SendGrid):** 30s timeout, 10s connection timeout
- **Google OAuth:** 15s timeout, 5s connection timeout
- **Database:** 10s connection timeout (already configured)

### Error Handling:
- All external API calls now have proper timeout handling
- Better error logging for debugging
- Graceful failure handling

## Troubleshooting Steps

1. **Check Railway Logs:**
   - Go to Railway dashboard → Your service → Logs
   - Look for email-related errors
   - Check for timeout errors

2. **Verify Environment Variables:**
   - Ensure all required variables are set
   - Check for typos in variable names
   - Verify API keys are valid

3. **Test Email Configuration:**
   - Use the test endpoints mentioned above
   - Check logs for specific error messages

4. **Check Database Connection:**
   - Verify database credentials are correct
   - Check if database is accessible from Railway

5. **Monitor Performance:**
   - Check Railway metrics for resource usage
   - Look for memory or CPU issues
   - Verify database connection pool settings

## Common Issues After Railway Renewal

1. **Environment Variables Reset:**
   - Sometimes variables need to be re-added
   - Check if variables are still set after renewal

2. **Database Connection Issues:**
   - Database credentials might have changed
   - Check Railway database service status

3. **API Key Expiration:**
   - Resend/SendGrid keys might have expired
   - Regenerate and update keys

4. **Service Restart Required:**
   - After adding/updating variables, redeploy the service
   - Railway may need to restart to pick up changes

## Next Steps

1. ✅ Verify all environment variables are set in Railway
2. ✅ Test email configuration using test endpoints
3. ✅ Monitor logs for any remaining errors
4. ✅ Test Google login functionality
5. ✅ Monitor application performance

## Files Modified

1. `application/helpers/email_notification_helper.php`
   - Added timeout settings to Resend API calls
   - Added timeout settings to SendGrid API calls
   - Improved error handling

2. `application/controllers/api/Auth.php`
   - Replaced `file_get_contents()` with cURL for Google OAuth
   - Added timeout settings (15s timeout, 5s connection timeout)
   - Improved error logging

## Support

If issues persist after applying these fixes:
1. Check Railway service logs
2. Verify all environment variables
3. Test individual components (email, database, OAuth)
4. Check Railway service status and resource usage

