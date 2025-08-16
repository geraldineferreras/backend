# Configuration Setup Guide

## Important: Sensitive Configuration Files

This repository contains template files for configuration. **DO NOT commit the actual configuration files with real credentials.**

## Setup Steps

### 1. Google OAuth Configuration

1. Copy `application/config/google_oauth_template.php` to `application/config/google_oauth.php`
2. Fill in your actual Google OAuth credentials:
   - `client_id`: Your Google OAuth client ID
   - `client_secret`: Your Google OAuth client secret
   - `project_id`: Your Google Cloud project ID

### 2. Environment Configuration

1. Copy `environment_config_template.php` to `environment_config.php`
2. Fill in your actual values:
   - `GOOGLE_CLIENT_ID`: Your Google OAuth client ID
   - `GOOGLE_CLIENT_SECRET`: Your Google OAuth client secret
   - `JWT_SECRET_KEY`: A secure random string for JWT signing

### 3. Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API and Google OAuth2 API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URIs:
   - `http://localhost:3000/auth/callback`
   - `http://localhost/auth/callback`

## Security Notes

- Never commit real credentials to version control
- Keep your configuration files secure
- Use environment variables in production
- Regularly rotate your secrets

## File Structure

```
├── application/config/
│   ├── google_oauth_template.php  ← Template (safe to commit)
│   └── google_oauth.php          ← Real config (DO NOT commit)
├── environment_config_template.php ← Template (safe to commit)
├── environment_config.php         ← Real config (DO NOT commit)
└── CONFIGURATION_SETUP.md        ← This file
```
