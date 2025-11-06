# SendGrid Email Configuration Guide

## Overview

Your SCMS system is configured to use SendGrid for email notifications. This guide will help you set up and troubleshoot SendGrid email delivery.

## Required Environment Variables

In your Railway project, you need to set these environment variables:

### Required:
```
SENDGRID_API_KEY=your_sendgrid_api_key_here
```

### Optional (with fallbacks):
```
SENDGRID_FROM_EMAIL=noreply@yourdomain.com
SENDGRID_FROM_NAME=SCMS System
```

**Note:** If `SENDGRID_FROM_EMAIL` is not set, the system will use `SMTP_USER` as a fallback.

## Getting Your SendGrid API Key

1. **Sign up/Login to SendGrid:**
   - Go to https://sendgrid.com
   - Create an account or log in

2. **Create an API Key:**
   - Navigate to Settings → API Keys
   - Click "Create API Key"
   - Give it a name (e.g., "SCMS Production")
   - Select "Full Access" or "Restricted Access" with "Mail Send" permissions
   - Copy the API key immediately (you won't be able to see it again)

3. **Verify Sender Identity:**
   - Go to Settings → Sender Authentication
   - Verify a Single Sender or set up Domain Authentication
   - Use the verified email address in `SENDGRID_FROM_EMAIL`

## Setting Up in Railway

1. Go to your Railway project dashboard
2. Click on your service
3. Go to the "Variables" tab
4. Add the following variables:

```
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SENDGRID_FROM_EMAIL=noreply@yourdomain.com
SENDGRID_FROM_NAME=SCMS System
```

5. Click "Deploy" or the service will auto-redeploy

## How It Works

The email system checks for SendGrid API key first. If found, it uses SendGrid API to send emails. The system:

1. ✅ Uses SendGrid v3 Mail Send API
2. ✅ Has 30-second timeout for requests
3. ✅ Has 10-second connection timeout
4. ✅ Properly handles SendGrid's 202 Accepted response
5. ✅ Logs detailed error messages for debugging

## Testing SendGrid Configuration

### Method 1: Using API Endpoint
```
GET /api/auth/test-email?to=your-email@example.com
```

### Method 2: Using Debug Endpoint
```
GET /api/auth/debug-email
```

### Method 3: Check Railway Logs
- Go to Railway dashboard → Your service → Logs
- Look for SendGrid-related messages
- Check for any error messages

## Common SendGrid Issues

### 1. **401 Unauthorized**
**Cause:** Invalid or expired API key
**Solution:**
- Verify the API key is correct in Railway variables
- Check if the API key has "Mail Send" permissions
- Generate a new API key if needed

### 2. **403 Forbidden**
**Cause:** Sender email not verified
**Solution:**
- Verify the sender email in SendGrid dashboard
- Use a verified email address in `SENDGRID_FROM_EMAIL`
- Complete domain authentication if using a custom domain

### 3. **400 Bad Request**
**Cause:** Invalid email format or missing required fields
**Solution:**
- Check that recipient email is valid
- Verify `SENDGRID_FROM_EMAIL` is set correctly
- Check Railway logs for specific error details

### 4. **Timeout Errors**
**Cause:** Network issues or SendGrid API slow response
**Solution:**
- Already fixed with 30s timeout
- Check Railway network connectivity
- Verify SendGrid service status

### 5. **Emails Not Delivered**
**Cause:** Various reasons (spam, invalid recipient, etc.)
**Solution:**
- Check SendGrid Activity Feed in dashboard
- Verify recipient email is valid
- Check spam folder
- Review SendGrid suppression list

## SendGrid Free Tier Limits

- **100 emails/day** on free tier
- Upgrade plan if you need more

## Monitoring Email Delivery

1. **SendGrid Dashboard:**
   - Go to Activity Feed to see email status
   - Check delivery, bounces, and spam reports

2. **Railway Logs:**
   - Check application logs for SendGrid API responses
   - Look for error messages with HTTP status codes

## Error Logging

The system now logs detailed error information:
- HTTP status code
- cURL error codes (if any)
- SendGrid API response body
- Connection timeout information

Check logs at: Railway Dashboard → Your Service → Logs

## Troubleshooting Steps

1. ✅ Verify `SENDGRID_API_KEY` is set in Railway
2. ✅ Check API key has correct permissions
3. ✅ Verify sender email is authenticated in SendGrid
4. ✅ Test using `/api/auth/test-email` endpoint
5. ✅ Check Railway logs for specific error messages
6. ✅ Verify SendGrid account is active (not suspended)
7. ✅ Check SendGrid Activity Feed for delivery status

## Support

If issues persist:
- Check SendGrid status: https://status.sendgrid.com
- Review SendGrid documentation: https://docs.sendgrid.com
- Check Railway service logs for detailed error messages
- Verify all environment variables are correctly set

