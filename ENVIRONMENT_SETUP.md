# Environment Setup Guide

## Overview
This project uses environment variables to securely store sensitive configuration like API keys and secrets. This prevents sensitive information from being committed to version control.

## Required Environment Variables

### Google OAuth Configuration
- `GOOGLE_CLIENT_ID` - Your Google OAuth Client ID
- `GOOGLE_CLIENT_SECRET` - Your Google OAuth Client Secret  
- `GOOGLE_PROJECT_ID` - Your Google Cloud Project ID

### JWT Configuration
- `JWT_SECRET_KEY` - Secret key for JWT token signing
- `JWT_EXPIRATION_TIME` - JWT token expiration time in seconds (default: 86400)

### API Configuration
- `API_BASE_URL` - Base URL for your API endpoints

## Setup Instructions

### Option 1: Create a .env file (Recommended)
1. Create a `.env` file in your project root
2. Add your environment variables:
   ```
   GOOGLE_CLIENT_ID=your_actual_client_id
   GOOGLE_CLIENT_SECRET=your_actual_client_secret
   GOOGLE_PROJECT_ID=your_actual_project_id
   JWT_SECRET_KEY=your_actual_jwt_secret
   JWT_EXPIRATION_TIME=86400
   API_BASE_URL=http://localhost/scms_new_backup/index.php/api
   ```

### Option 2: Set system environment variables
Set these variables in your system environment or web server configuration.

### Option 3: Use the fallback values
The configuration files include fallback values for development, but these should NOT be used in production.

## Security Notes
- Never commit the `.env` file to version control
- The `.env` file is already added to `.gitignore`
- Use strong, unique secrets in production
- Rotate secrets regularly

## File Locations
- Main config: `application/config/google_oauth.php`
- Environment config: `environment_config.php`
- Template: `config_template.php`
