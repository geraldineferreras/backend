# Email Notification Troubleshooting Guide

## Issue: Email Notifications Not Working

Based on the analysis of your SCMS system, **Railway's hobby plan is NOT the cause** of your email notification issues. Railway hobby plan does not restrict outbound SMTP connections.

## Root Cause Analysis

Your system has a complete email notification setup, but there are several potential issues:

### 1. Gmail App Password Issues
- **Problem**: Gmail app passwords can expire or be revoked
- **Solution**: Generate a new Gmail app password

### 2. Email Configuration Issues
- **Problem**: CodeIgniter email library not properly configured
- **Solution**: Updated email configuration with Railway optimization

### 3. Environment Variables
- **Problem**: Railway deployment might not have proper environment variables
- **Solution**: Set up Railway environment variables

## Solutions Implemented

### 1. Updated Email Configuration (`application/config/email.php`)
- Added environment variable support for Railway deployment
- Increased SMTP timeout to 60 seconds
- Added Railway-optimized settings

### 2. Enhanced Email Helper (`application/helpers/email_notification_helper.php`)
- Added comprehensive error logging
- Improved debugging information
- Added email clearing to prevent conflicts

### 3. Railway Email Test Script (`railway_email_test.php`)
- Comprehensive email testing tool
- Environment variable checking
- Network connectivity testing

## Step-by-Step Fix Process

### Step 1: Set Railway Environment Variables

In your Railway dashboard, add these environment variables:

```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_USER=scmswebsitee@gmail.com
SMTP_PASS=your_gmail_app_password_here
SMTP_CRYPTO=ssl
SMTP_FROM_NAME=SCMS System
```

### Step 2: Generate New Gmail App Password

1. Go to your Google Account settings
2. Navigate to Security → 2-Step Verification → App passwords
3. Generate a new app password for "Mail"
4. Use this password in the `SMTP_PASS` environment variable

### Step 3: Test Email Functionality

1. Deploy your updated code to Railway
2. Run the test script: `https://your-app.railway.app/railway_email_test.php`
3. Check the output for any errors

### Step 4: Monitor Logs

Check Railway logs for email-related errors:
```bash
railway logs
```

Look for messages like:
- "Email notification sent successfully"
- "Email notification failed"
- SMTP connection errors

## Alternative Solutions

### Option 1: Use Different SMTP Provider
If Gmail continues to have issues, consider:
- SendGrid (free tier available)
- Mailgun (free tier available)
- AWS SES (pay-per-use)

### Option 2: Use Railway's Email Service
Railway offers email services that might work better:
- Check Railway's marketplace for email add-ons

### Option 3: Implement Queue System
For better reliability, implement an email queue:
- Store emails in database
- Process them with a background job
- Retry failed emails

## Testing Commands

### Test Email Configuration
```bash
# Run the Railway email test
curl https://your-app.railway.app/railway_email_test.php
```

### Test Notification Creation
```bash
# Create a test notification
curl -X POST https://your-app.railway.app/api/notifications/create-test \
  -H "Content-Type: application/json" \
  -d '{"user_id":"STU001","title":"Test","message":"Test notification"}'
```

## Common Issues and Solutions

### Issue: "SMTP connection failed"
**Solution**: Check Gmail app password and ensure 2FA is enabled

### Issue: "Email sent but not received"
**Solution**: Check spam folder, verify recipient email address

### Issue: "Timeout errors"
**Solution**: Increase SMTP timeout (already done in updated config)

### Issue: "Authentication failed"
**Solution**: Regenerate Gmail app password

## Monitoring and Debugging

### Enable SMTP Debug Mode
Set environment variable:
```
SMTP_DEBUG=true
```

### Check Railway Logs
```bash
railway logs --follow
```

### Test Email Endpoints
Use the existing test endpoints:
- `/api/auth/test-email-sending`
- `/api/notifications/create-test`

## Next Steps

1. **Deploy the updated code** to Railway
2. **Set environment variables** in Railway dashboard
3. **Run the email test script** to verify functionality
4. **Monitor logs** for any remaining issues
5. **Test with real notifications** in your application

## Contact Information

If issues persist after following this guide:
1. Check Railway support documentation
2. Review Gmail SMTP settings
3. Consider alternative email providers

---

**Note**: Railway hobby plan does not restrict email functionality. The issue is likely with Gmail SMTP configuration or app password authentication.
