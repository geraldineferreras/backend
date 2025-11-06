# Brevo Email Configuration Guide

## Overview

Brevo (formerly Sendinblue) is now supported as an email provider for your SCMS system. This guide will help you set up and configure Brevo for email notifications.

## Why Brevo?

- ✅ **Free Tier**: 300 emails/day (9,000/month)
- ✅ **Good Deliverability**: Reliable email delivery
- ✅ **Easy Setup**: Simple API integration
- ✅ **No Credit Card Required**: Free tier available

## Getting Your Brevo API Key

1. **Sign up/Login to Brevo:**
   - Go to https://www.brevo.com
   - Create an account or log in

2. **Create an API Key:**
   - Navigate to Settings → API Keys (or SMTP & API)
   - Click "Generate a new API key"
   - Give it a name (e.g., "SCMS Production")
   - Copy the API key immediately (you won't be able to see it again)
   - Format: `xkeysib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

3. **Verify Sender Email:**
   - Go to Senders & IP → Senders
   - Add and verify your sender email address
   - You'll receive a verification email
   - Click the verification link in the email

## Setting Up in Railway

1. Go to your Railway project dashboard
2. Click on your service
3. Go to the "Variables" tab
4. Add the following variables:

```
BREVO_API_KEY=xkeysib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
BREVO_FROM_EMAIL=scmswebsitee@gmail.com
BREVO_FROM_NAME=SCMS System
```

5. Click "Deploy" or the service will auto-redeploy

**Note:** If `BREVO_FROM_EMAIL` is not set, it will fallback to `SMTP_USER` environment variable.

## Email Provider Priority

The system checks email providers in this order:
1. **Resend** (if `RESEND_API_KEY` is set)
2. **SendGrid** (if `SENDGRID_API_KEY` is set)
3. **Brevo** (if `BREVO_API_KEY` is set) ← **NEW**
4. **SMTP/PHPMailer** (fallback if no API keys are set)

## How It Works

The email system automatically uses Brevo if:
- `BREVO_API_KEY` is set in environment variables
- Resend and SendGrid are not configured (or fail)

The system:
- ✅ Uses Brevo v3 SMTP Email API
- ✅ Has 30-second timeout for requests
- ✅ Has 10-second connection timeout
- ✅ Properly handles Brevo's 201 Created response
- ✅ Logs detailed error messages for debugging

## Testing Brevo Configuration

### Method 1: Using API Endpoint
```
GET /api/auth/test-email?to=your-email@example.com
```

The response will show:
```json
{
    "status": true,
    "message": "Test email sent successfully",
    "to": "your-email@example.com"
}
```

Or if it fails:
```json
{
    "status": false,
    "message": "Failed to send test email",
    "to": "your-email@example.com",
    "debug": {
        "sendgrid_api_key_set": false,
        "resend_api_key_set": false,
        "brevo_api_key_set": true,
        "smtp_user_set": true,
        "check_railway_logs": "Check Railway logs for detailed error messages"
    }
}
```

### Method 2: Check Railway Logs
- Go to Railway dashboard → Your service → Logs
- Look for Brevo-related messages
- Check for any error messages

## Common Brevo Issues

### 1. **401 Unauthorized**
**Cause:** Invalid or expired API key
**Solution:**
- Verify the API key is correct in Railway variables
- Check if the API key has proper permissions
- Generate a new API key if needed

### 2. **400 Bad Request**
**Cause:** Invalid email format, unverified sender, or missing required fields
**Solution:**
- Verify the sender email in Brevo dashboard
- Check that recipient email is valid
- Verify `BREVO_FROM_EMAIL` is set correctly
- Check Railway logs for specific error details

### 3. **422 Unprocessable Entity**
**Cause:** Sender email not verified
**Solution:**
- Verify the sender email in Brevo: Senders & IP → Senders
- Check your email inbox for verification email
- Complete the verification process

### 4. **Timeout Errors**
**Cause:** Network issues or Brevo API slow response
**Solution:**
- Already fixed with 30s timeout
- Check Railway network connectivity
- Verify Brevo service status

### 5. **Emails Not Delivered**
**Cause:** Various reasons (spam, invalid recipient, etc.)
**Solution:**
- Check Brevo Statistics dashboard
- Verify recipient email is valid
- Check spam folder
- Review Brevo suppression list

## Brevo Free Tier Limits

- **300 emails/day** (9,000/month)
- Upgrade plan if you need more

## Monitoring Email Delivery

1. **Brevo Dashboard:**
   - Go to Statistics to see email status
   - Check delivery, bounces, and spam reports
   - View email logs and events

2. **Railway Logs:**
   - Check application logs for Brevo API responses
   - Look for error messages with HTTP status codes

## Error Logging

The system logs detailed error information:
- HTTP status code
- cURL error codes (if any)
- Brevo API response body
- Connection timeout information

Check logs at: Railway Dashboard → Your Service → Logs

## Troubleshooting Steps

1. ✅ Verify `BREVO_API_KEY` is set in Railway
2. ✅ Check API key format is correct (starts with `xkeysib-`)
3. ✅ Verify sender email is authenticated in Brevo
4. ✅ Test using `/api/auth/test-email` endpoint
5. ✅ Check Railway logs for specific error messages
6. ✅ Verify Brevo account is active (not suspended)
7. ✅ Check Brevo Statistics dashboard for delivery status

## Switching from SendGrid to Brevo

If you want to use Brevo instead of SendGrid:

1. Add `BREVO_API_KEY` to Railway variables
2. (Optional) Remove or keep `SENDGRID_API_KEY` - Brevo will be used if SendGrid fails
3. Redeploy the service
4. Test using the test-email endpoint

## Support

If issues persist:
- Check Brevo status: https://status.brevo.com
- Review Brevo documentation: https://developers.brevo.com
- Check Railway service logs for detailed error messages
- Verify all environment variables are correctly set

## Comparison: Brevo vs SendGrid

| Feature | Brevo | SendGrid |
|---------|-------|----------|
| Free Tier | 300/day (9,000/month) | 100/day |
| API Key Format | `xkeysib-...` | `SG....` |
| Setup Difficulty | Easy | Easy |
| Deliverability | Good | Excellent |
| Best For | Higher volume free tier | Lower volume, premium features |

