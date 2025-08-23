# 🚀 Unified Account System Implementation Guide

## 🎯 **What We're Building**

A unified authentication system where users can:
- **Sign up** with either local email/password OR Google OAuth
- **Log in** with either method (or both)
- **Link accounts** after creation
- **Switch between** login methods seamlessly

## 📋 **Phase 1: Database Setup (COMPLETED)**

Run the database migration script:
```bash
php setup_unified_account_system.php
```

This adds:
- `google_id` - Google's unique user identifier
- `account_type` - 'local', 'google', or 'unified'
- `google_email_verified` - Email verification status
- `last_oauth_login` - Last OAuth login timestamp
- `oauth_provider` - Which OAuth provider was used
- `account_links` table - For future multi-provider support

## 🔧 **Phase 2: Backend Implementation**

### **2.1 Update User Model**

Add methods to handle unified accounts:

```php
// In User_model.php
public function get_by_google_id($google_id) {
    return $this->db->get_where('users', ['google_id' => $google_id])->row_array();
}

public function get_by_email_or_google($email, $google_id = null) {
    $this->db->group_start();
    $this->db->where('email', $email);
    if ($google_id) {
        $this->db->or_where('google_id', $google_id);
    }
    $this->db->group_end();
    return $this->db->get('users')->row_array();
}

public function link_google_account($user_id, $google_id, $google_email) {
    $data = [
        'google_id' => $google_id,
        'account_type' => 'unified',
        'google_email_verified' => true,
        'oauth_provider' => 'google'
    ];
    return $this->update($user_id, $data);
}
```

### **2.2 Update Auth Controller**

#### **Modified Login Method:**
```php
public function login() {
    // ... existing validation ...
    
    $user = $this->User_model->get_by_email($email);
    
    if ($user && password_verify($password, $user['password'])) {
        // Check if user also has Google account
        $has_google = !empty($user['google_id']);
        
        // Update account type if needed
        if ($has_google && $user['account_type'] === 'local') {
            $this->User_model->update($user['user_id'], ['account_type' => 'unified']);
        }
        
        // ... rest of login logic ...
    }
}
```

#### **Modified Google OAuth Method:**
```php
public function google_oauth() {
    // ... existing validation ...
    
    $email = $data->email;
    $google_id = $data->sub; // Google's user ID
    
    // Check if user exists by email OR Google ID
    $existing_user = $this->User_model->get_by_email_or_google($email, $google_id);
    
    if ($existing_user) {
        // User exists - handle different scenarios
        if ($existing_user['google_id'] === $google_id) {
            // User has this Google account linked - normal login
            $this->handle_google_login($existing_user, $google_id);
        } elseif ($existing_user['email'] === $email && empty($existing_user['google_id'])) {
            // User has local account with same email - link Google account
            $this->link_existing_account($existing_user, $google_id, $data);
        } else {
            // Conflict - different Google account with same email
            $this->output->set_status_header(409)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false, 
                    'message' => 'Email already associated with different Google account'
                ]));
            return;
        }
    } else {
        // New user - create account
        $this->create_google_user($data);
    }
}

private function link_existing_account($user, $google_id, $oauth_data) {
    // Link Google account to existing local account
    $this->User_model->link_google_account($user['user_id'], $google_id, $user['email']);
    
    // Update last OAuth login
    $this->User_model->update($user['user_id'], [
        'last_oauth_login' => date('Y-m-d H:i:s')
    ]);
    
    // Generate JWT and return success
    $this->handle_google_login($user, $google_id);
}

private function handle_google_login($user, $google_id) {
    // Update last OAuth login
    $this->User_model->update($user['user_id'], [
        'last_oauth_login' => date('Y-m-d H:i:s')
    ]);
    
    // Generate JWT token and return success
    // ... existing JWT logic ...
}
```

### **2.3 Add Account Management Endpoints**

```php
// Link Google account to existing local account
public function link_google_account() {
    $user_data = require_auth($this);
    if (!$user_data) return;
    
    $data = json_decode(file_get_contents('php://input'));
    $google_id = $data->google_id ?? null;
    $google_email = $data->google_email ?? null;
    
    if (empty($google_id) || empty($google_email)) {
        $this->output->set_status_header(400)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false, 
                'message' => 'Google ID and email are required'
            ]));
        return;
    }
    
    // Check if Google account is already linked to another user
    $existing_google_user = $this->User_model->get_by_google_id($google_id);
    if ($existing_google_user && $existing_google_user['user_id'] !== $user_data['user_id']) {
        $this->output->set_status_header(409)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false, 
                'message' => 'Google account already linked to another user'
            ]));
        return;
    }
    
    // Link the account
    $success = $this->User_model->link_google_account(
        $user_data['user_id'], 
        $google_id, 
        $google_email
    );
    
    if ($success) {
        $this->output->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true, 
                'message' => 'Google account linked successfully'
            ]));
    } else {
        $this->output->set_status_header(500)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false, 
                'message' => 'Failed to link Google account'
            ]));
    }
}

// Unlink Google account
public function unlink_google_account() {
    $user_data = require_auth($this);
    if (!$user_data) return;
    
    $user = $this->User_model->get_by_id($user_data['user_id']);
    
    if (empty($user['google_id'])) {
        $this->output->set_status_header(400)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false, 
                'message' => 'No Google account linked'
            ]));
        return;
    }
    
    // Check if user has local password
    if (empty($user['password'])) {
        $this->output->set_status_header(400)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false, 
                'message' => 'Cannot unlink Google account without local password'
            ]));
        return;
    }
    
    // Unlink Google account
    $success = $this->User_model->update($user_data['user_id'], [
        'google_id' => null,
        'account_type' => 'local',
        'google_email_verified' => false,
        'oauth_provider' => null
    ]);
    
    if ($success) {
        $this->output->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true, 
                'message' => 'Google account unlinked successfully'
            ]));
    } else {
        $this->output->set_status_header(500)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false, 
                'message' => 'Failed to unlink Google account'
            ]));
    }
}
```

## 🎨 **Phase 3: Frontend Updates**

### **3.1 Login Form Logic**

```javascript
// Detect account type and show appropriate options
const checkAccountType = async (email) => {
  try {
    const response = await fetch('/api/auth/check-account-type', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    
    const data = await response.json();
    
    if (data.status) {
      const { account_type, has_password, has_google } = data.data;
      
      if (account_type === 'unified') {
        // Show both options
        showUnifiedLoginOptions();
      } else if (account_type === 'google') {
        // Show Google OAuth only
        showGoogleOnlyLogin();
      } else {
        // Show password login only
        showPasswordOnlyLogin();
      }
    }
  } catch (error) {
    console.error('Error checking account type:', error);
  }
};
```

### **3.2 Account Settings Page**

```javascript
// Show account linking options
const renderAccountLinking = () => {
  const { account_type, has_password, has_google } = userAccount;
  
  if (account_type === 'unified') {
    return (
      <div>
        <h3>Linked Accounts</h3>
        <p>✅ Local Password</p>
        <p>✅ Google Account</p>
        <button onClick={unlinkGoogle}>Unlink Google Account</button>
      </div>
    );
  } else if (account_type === 'local' && !has_google) {
    return (
      <div>
        <h3>Link Additional Login Method</h3>
        <button onClick={linkGoogle}>Link Google Account</button>
      </div>
    );
  } else if (account_type === 'google' && !has_password) {
    return (
      <div>
        <h3>Set Local Password</h3>
        <button onClick={setLocalPassword}>Set Password</button>
      </div>
    );
  }
};
```

## 🧪 **Phase 4: Testing**

### **Test Scenarios:**

1. **New User - Google OAuth**
   - Sign up with Google
   - Verify account type is 'google'

2. **New User - Local Account**
   - Sign up with email/password
   - Verify account type is 'local'

3. **Existing Local User - Link Google**
   - Login with local account
   - Link Google account
   - Verify account type becomes 'unified'

4. **Existing Google User - Add Password**
   - Login with Google
   - Set local password
   - Verify account type becomes 'unified'

5. **Unified Account - Both Login Methods**
   - Login with either method
   - Verify both methods work

## 🚀 **Implementation Order**

1. ✅ **Run database migration** (`setup_unified_account_system.php`)
2. 🔧 **Update User Model** with new methods
3. 🔧 **Modify Auth Controller** for unified logic
4. 🔧 **Add account management endpoints**
5. 🎨 **Update frontend** to show appropriate options
6. 🧪 **Test all scenarios**

## 💡 **Key Benefits**

- **No duplicate accounts** - One email = one account
- **Flexible login** - Users choose their preferred method
- **Seamless experience** - Can switch between methods
- **Future-proof** - Easy to add more OAuth providers
- **Professional** - Matches industry standards

**Ready to start implementing? Let me know which phase you'd like to tackle first!** 🎯
