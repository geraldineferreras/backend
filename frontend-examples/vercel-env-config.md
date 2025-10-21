# Vercel Environment Configuration

## Environment Variables for Vercel

Add these environment variables to your Vercel project settings:

### Required Variables:
```
REACT_APP_API_URL=https://scms-backend.up.railway.app
```

### Optional Variables:
```
REACT_APP_DEBUG_NOTIFICATIONS=false
REACT_APP_ENABLE_BROWSER_NOTIFICATIONS=true
REACT_APP_NOTIFICATION_SOUND_ENABLED=false
```

## How to Add Environment Variables in Vercel:

1. Go to your Vercel dashboard
2. Select your project
3. Go to Settings → Environment Variables
4. Add each variable above
5. Redeploy your application

## CORS Configuration

Make sure your Railway backend allows requests from your Vercel domain:
- Production: `https://your-vercel-app.vercel.app`
- Preview: `https://your-vercel-app-git-branch.vercel.app`
