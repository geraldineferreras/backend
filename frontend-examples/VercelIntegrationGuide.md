# Vercel + Railway Integration Guide

## 🚀 Quick Integration Steps

### 1. Add Notification Hook to Your Existing App

```jsx
// In your existing React app
import { useVercelNotifications } from './hooks/useVercelNotifications';

function App() {
  const token = localStorage.getItem('authToken'); // Your existing auth token
  const { notifications, isConnected, error } = useVercelNotifications(token);
  
  return (
    <div>
      {/* Your existing app content */}
      <NotificationBell notifications={notifications} isConnected={isConnected} />
    </div>
  );
}
```

### 2. Update Your Existing Auth Context

```jsx
// If you have an existing auth context, add notifications
import { useVercelNotifications } from './hooks/useVercelNotifications';

function AuthProvider({ children }) {
  const [token, setToken] = useState(localStorage.getItem('authToken'));
  const notifications = useVercelNotifications(token);
  
  return (
    <AuthContext.Provider value={{ token, setToken, ...notifications }}>
      {children}
    </AuthContext.Provider>
  );
}
```

### 3. Environment Variables Setup

In your Vercel dashboard:
1. Go to Settings → Environment Variables
2. Add: `REACT_APP_API_URL` = `https://scms-backend.up.railway.app`
3. Add: `REACT_APP_DEBUG_NOTIFICATIONS` = `false` (set to `true` for debugging)

### 4. Update Your Existing API Calls

```javascript
// Update your existing API base URL
const API_BASE_URL = process.env.REACT_APP_API_URL || 'https://scms-backend.up.railway.app';

// Your existing API calls will now use Railway backend
const response = await fetch(`${API_BASE_URL}/api/auth/login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(credentials)
});
```

## 🔧 CORS Configuration

Your Railway backend already supports CORS for Vercel domains. The backend will automatically allow:
- `https://your-app.vercel.app` (production)
- `https://your-app-git-branch.vercel.app` (preview branches)

## 📱 Adding to Existing Components

### Option 1: Add to Header/Navbar
```jsx
// In your existing header component
import NotificationBell from './components/NotificationBell';

function Header() {
  const token = useAuth(); // Your existing auth hook
  
  return (
    <header>
      {/* Your existing header content */}
      <NotificationBell authToken={token} />
    </header>
  );
}
```

### Option 2: Add as Floating Component
```jsx
// Add as a floating notification component
import { useVercelNotifications } from './hooks/useVercelNotifications';

function FloatingNotifications() {
  const token = localStorage.getItem('authToken');
  const { notifications } = useVercelNotifications(token);
  
  return (
    <div className="fixed bottom-4 right-4 z-50">
      {notifications.map(notification => (
        <div key={notification.id} className="notification-toast">
          {notification.title}
        </div>
      ))}
    </div>
  );
}
```

## 🎯 Testing the Integration

1. **Deploy to Vercel** with the new code
2. **Login to your app** to get a JWT token
3. **Check browser console** for connection logs
4. **Test notifications** from your Railway backend

## 🔍 Debugging

Enable debug mode by setting environment variable:
```
REACT_APP_DEBUG_NOTIFICATIONS=true
```

This will show detailed logs in the browser console.

## 📦 File Structure for Integration

```
your-vercel-app/
├── src/
│   ├── hooks/
│   │   └── useVercelNotifications.js
│   ├── components/
│   │   └── NotificationBell.jsx
│   └── App.jsx (existing)
├── vercel.json
└── package.json
```

## 🚀 Deployment

1. **Commit your changes**
2. **Push to your connected Git repository**
3. **Vercel will automatically deploy**
4. **Test the live application**

Your notifications will now work seamlessly between Vercel frontend and Railway backend!
