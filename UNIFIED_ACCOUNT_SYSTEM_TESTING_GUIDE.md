# 🚀 Unified Account System Testing Guide

## 🎯 **What We've Implemented**

Your backend now supports a **unified authentication system** where users can:
- **Sign up** with either local email/password OR Google OAuth
- **Log in** with either method (or both)
- **Link accounts** after creation
- **Switch between** login methods seamlessly

## 📋 **Database Status**

✅ **Database Migration**: Completed successfully
✅ **Users Table**: Updated with new fields
✅ **Account Links Table**: Created for future multi-provider support

## 🔧 **New API Endpoints**

### **1. Enhanced Google OAuth Login**
```
POST /api/auth/google
Content-Type: application/json

{
    "email": "user@gmail.com",
    "name": "John Doe",
    "google_id": "google_unique_id_123"
}
```

**Response includes:**
- `account_type`: 'local', 'google', or 'unified'
- `google_id`: Google's unique identifier
- `google_email_verified`: Email verification status
- `oauth_provider`: Which OAuth provider was used

### **2. Link Google Account to Existing Local Account**
```
POST /api/auth/link-google
Content-Type: application/json

{
    "email": "existing@example.com",
    "google_id": "google_unique_id_123"
}
```

### **3. Unlink Google Account**
```
POST /api/auth/unlink-google
Content-Type: application/json

{
    "email": "user@example.com"
}
```

### **4. Get Account Status**
```
POST /api/auth/account-status
Content-Type: application/json

{
    "email": "user@example.com"
}
```

## 🧪 **Testing Scenarios**

### **Scenario 1: New User - Google OAuth First**
1. **Send Google OAuth request** with new email
2. **Expected Result**: User created with `account_type: 'google'`
3. **Verify**: User can log in with Google OAuth

### **Scenario 2: Existing Local User - Link Google Account**
1. **User exists** with local password
2. **Send link-google request** with Google credentials
3. **Expected Result**: `account_type` changes to `'unified'`
4. **Verify**: User can log in with either method

### **Scenario 3: Existing Google User - Try Local Login**
1. **User exists** with Google OAuth
2. **Try local login** with same email
3. **Expected Result**: Login fails (no local password)
4. **Action**: User needs to set local password first

### **Scenario 4: Unified Account - Both Methods Work**
1. **User has both** local password and Google OAuth
2. **Test local login** → Should work
3. **Test Google OAuth** → Should work
4. **Verify**: `account_type` is `'unified'`

## 📱 **Frontend Integration Points**

### **Login Form Logic**
```javascript
// Check account status first
const accountStatus = await fetch('/api/auth/account-status', {
    method: 'POST',
    body: JSON.stringify({ email: userEmail })
});

const status = await accountStatus.json();

if (status.data.account_type === 'local') {
    // Show password field
    showPasswordField();
} else if (status.data.account_type === 'google') {
    // Show Google OAuth button
    showGoogleOAuthButton();
} else if (status.data.account_type === 'unified') {
    // Show both options
    showBothOptions();
}
```

### **Account Management UI**
```javascript
// Link Google account
const linkGoogle = async (email, googleId) => {
    const response = await fetch('/api/auth/link-google', {
        method: 'POST',
        body: JSON.stringify({ email, google_id: googleId })
    });
    return response.json();
};

// Unlink Google account
const unlinkGoogle = async (email) => {
    const response = await fetch('/api/auth/unlink-google', {
        method: 'POST',
        body: JSON.stringify({ email })
    });
    return response.json();
};
```

## 🔍 **Testing with Postman**

### **Test 1: Google OAuth with New Email**
```http
POST http://localhost/scms_new_backup/index.php/api/auth/google
Content-Type: application/json

{
    "email": "testuser@gmail.com",
    "name": "Test User",
    "google_id": "test_google_id_123"
}
```

### **Test 2: Check Account Status**
```http
POST http://localhost/scms_new_backup/index.php/api/auth/account-status
Content-Type: application/json

{
    "email": "testuser@gmail.com"
}
```

### **Test 3: Link Google Account**
```http
POST http://localhost/scms_new_backup/index.php/api/auth/link-google
Content-Type: application/json

{
    "email": "existing@example.com",
    "google_id": "new_google_id_456"
}
```

## 🎉 **What This Enables**

1. **Seamless User Experience**: Users can choose their preferred login method
2. **Account Recovery**: Google OAuth users can still use forgot password
3. **Flexible Authentication**: Switch between methods as needed
4. **Future Expansion**: Easy to add more OAuth providers (Facebook, GitHub, etc.)

## 🚀 **Next Steps**

1. **Test the endpoints** with Postman
2. **Update your frontend** to use the new account status logic
3. **Implement account management UI** for linking/unlinking
4. **Test real user scenarios** with both login methods

## 💡 **Security Features**

- **No duplicate accounts**: One email = one account
- **Account linking validation**: Prevents linking to already-linked Google accounts
- **Password requirement**: Users must have local password before unlinking Google
- **Audit logging**: All authentication events are logged

Your unified account system is now ready! 🎯
