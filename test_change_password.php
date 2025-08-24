<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Change Password API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="password"] { width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .result { margin-top: 20px; padding: 15px; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
    </style>
</head>
<body>
    <h1>Test Change Password API</h1>
    
    <div class="form-group">
        <label for="currentPassword">Current Password:</label>
        <input type="password" id="currentPassword" placeholder="Enter current password">
    </div>
    
    <div class="form-group">
        <label for="newPassword">New Password:</label>
        <input type="password" id="newPassword" placeholder="Enter new password (min 8 chars, letters + numbers)">
    </div>
    
    <div class="form-group">
        <label for="confirmPassword">Confirm New Password:</label>
        <input type="password" id="confirmPassword" placeholder="Confirm new password">
    </div>
    
    <button onclick="testChangePassword()">Test Change Password</button>
    
    <div id="result"></div>

    <script>
        async function testChangePassword() {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const resultDiv = document.getElementById('result');
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                resultDiv.innerHTML = '<div class="error">Please fill in all fields</div>';
                return;
            }
            
            if (newPassword !== confirmPassword) {
                resultDiv.innerHTML = '<div class="error">New password and confirm password do not match</div>';
                return;
            }
            
            if (newPassword.length < 8) {
                resultDiv.innerHTML = '<div class="error">New password must be at least 8 characters long</div>';
                return;
            }
            
            if (!/(?=.*[A-Za-z])(?=.*\d)/.test(newPassword)) {
                resultDiv.innerHTML = '<div class="error">New password must contain both letters and numbers</div>';
                return;
            }
            
            try {
                const response = await fetch('/api/auth/change-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword,
                        confirm_password: confirmPassword
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    resultDiv.innerHTML = `<div class="success">
                        <strong>Success!</strong><br>
                        Message: ${data.message}<br>
                        User Role: ${data.user_role || 'N/A'}
                    </div>`;
                } else {
                    resultDiv.innerHTML = `<div class="error">
                        <strong>Error ${response.status}:</strong><br>
                        Message: ${data.message}
                    </div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">
                    <strong>Network Error:</strong><br>
                    ${error.message}
                </div>`;
            }
        }
    </script>
    
    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
        <h3>API Endpoint Information:</h3>
        <p><strong>URL:</strong> <code>/api/auth/change-password</code></p>
        <p><strong>Method:</strong> <code>POST</code></p>
        <p><strong>Required Fields:</strong></p>
        <ul>
            <li><code>current_password</code> - User's current password</li>
            <li><code>new_password</code> - New password (min 8 chars, must contain letters + numbers)</li>
            <li><code>confirm_password</code> - Confirmation of new password</li>
        </ul>
        <p><strong>Authentication:</strong> User must be logged in (session required)</p>
        <p><strong>Supported Roles:</strong> Teachers, Students, and Admins</p>
    </div>
</body>
</html>
