<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Forgot Password - SCMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .result {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .info { border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>
    <h1>🔐 Test Forgot Password Functionality - SCMS</h1>
    
    <div class="test-section">
        <h3>📋 Prerequisites</h3>
        <p>Before testing, make sure:</p>
        <ul>
            <li>✅ Database table <code>password_reset_tokens</code> is created</li>
            <li>✅ Email configuration is set up in CodeIgniter</li>
            <li>✅ User exists in the database</li>
        </ul>
        <button class="btn-primary" onclick="createTable()">Create Password Reset Table</button>
        <button class="btn-primary" onclick="checkTable()">Check Table Exists</button>
    </div>

    <div class="test-section">
        <h3>1. 🔑 Test Forgot Password Request</h3>
        <input type="email" id="forgotEmail" placeholder="Enter email address" value="admin@example.com">
        <button class="btn-primary" onclick="testForgotPassword()">Send Reset Email</button>
        <p><small>This will generate a reset token and send an email</small></p>
    </div>

    <div class="test-section">
        <h3>2. 🔄 Test Password Reset</h3>
        <input type="text" id="resetToken" placeholder="Enter reset token from email">
        <input type="password" id="newPassword" placeholder="Enter new password" value="newpassword123">
        <button class="btn-success" onclick="testResetPassword()">Reset Password</button>
        <p><small>Use the token received in the email to reset password</small></p>
    </div>

    <div class="test-section">
        <h3>3. 🔍 Check Database</h3>
        <button class="btn-primary" onclick="checkTokens()">Check Reset Tokens</button>
        <button class="btn-primary" onclick="checkUsers()">Check Users</button>
    </div>

    <div class="test-section">
        <h3>4. 🧪 Test Login with New Password</h3>
        <input type="email" id="loginEmail" placeholder="Email" value="admin@example.com">
        <input type="password" id="loginPassword" placeholder="Password" value="newpassword123">
        <button class="btn-success" onclick="testLogin()">Test Login</button>
        <p><small>Verify the password was actually changed</small></p>
    </div>

    <div id="results"></div>

    <script>
        let currentToken = null;

        function log(message, type = 'info') {
            const results = document.getElementById('results');
            const div = document.createElement('div');
            div.className = `result ${type}`;
            div.textContent = new Date().toLocaleTimeString() + ': ' + message;
            results.appendChild(div);
            results.scrollTop = results.scrollHeight;
        }

        async function createTable() {
            try {
                log('Creating password_reset_tokens table...');
                const response = await fetch('create_password_reset_tokens_table.sql');
                const sql = await response.text();
                
                // For now, just log the SQL - you'll need to run this in your database
                log('SQL to create table:\n' + sql, 'info');
                log('Please run this SQL in your database manually', 'info');
                
            } catch (error) {
                log('Error creating table: ' + error.message, 'error');
            }
        }

        async function checkTable() {
            try {
                log('Checking if password_reset_tokens table exists...');
                // This would require a backend endpoint to check table existence
                log('Table check not implemented - please check manually in database', 'info');
                
            } catch (error) {
                log('Error checking table: ' + error.message, 'error');
            }
        }

        async function testForgotPassword() {
            const email = document.getElementById('forgotEmail').value;
            
            if (!email) {
                log('Please enter an email address', 'error');
                return;
            }

            try {
                log('Sending forgot password request for: ' + email);
                
                const response = await fetch('/scms_new_backup/index.php/api/auth/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                });

                const data = await response.json();
                
                if (data.status) {
                    log('✅ Forgot password request successful: ' + data.message, 'success');
                    log('📧 Check your email for the reset link', 'info');
                } else {
                    log('❌ Forgot password request failed: ' + data.message, 'error');
                }
                
            } catch (error) {
                log('❌ Network error: ' + error.message, 'error');
            }
        }

        async function testResetPassword() {
            const token = document.getElementById('resetToken').value;
            const newPassword = document.getElementById('newPassword').value;
            
            if (!token || !newPassword) {
                log('Please enter both token and new password', 'error');
                return;
            }

            try {
                log('Resetting password with token: ' + token.substring(0, 10) + '...');
                
                const response = await fetch('/scms_new_backup/index.php/api/auth/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        token: token, 
                        new_password: newPassword 
                    })
                });

                const data = await response.json();
                
                if (data.status) {
                    log('✅ Password reset successful: ' + data.message, 'success');
                    log('🔑 You can now login with the new password', 'info');
                } else {
                    log('❌ Password reset failed: ' + data.message, 'error');
                }
                
            } catch (error) {
                log('❌ Network error: ' + error.message, 'error');
            }
        }

        async function checkTokens() {
            try {
                log('Checking password reset tokens in database...');
                // This would require a backend endpoint to check tokens
                log('Token check not implemented - please check manually in database', 'info');
                
            } catch (error) {
                log('Error checking tokens: ' + error.message, 'error');
            }
        }

        async function checkUsers() {
            try {
                log('Checking users in database...');
                // This would require a backend endpoint to check users
                log('User check not implemented - please check manually in database', 'info');
                
            } catch (error) {
                log('Error checking users: ' + error.message, 'error');
            }
        }

        async function testLogin() {
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            if (!email || !password) {
                log('Please enter both email and password', 'error');
                return;
            }

            try {
                log('Testing login with new password...');
                
                const response = await fetch('/scms_new_backup/index.php/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        email: email, 
                        password: password 
                    })
                });

                const data = await response.json();
                
                if (data.status) {
                    log('✅ Login successful with new password!', 'success');
                    log('User: ' + data.data.full_name + ' (' + data.data.role + ')', 'info');
                    currentToken = data.data.token;
                } else {
                    log('❌ Login failed: ' + data.message, 'error');
                }
                
            } catch (error) {
                log('❌ Network error: ' + error.message, 'error');
            }
        }

        // Auto-fill token from URL if present
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            if (token) {
                document.getElementById('resetToken').value = token;
                log('🔗 Token loaded from URL: ' + token.substring(0, 10) + '...', 'info');
            }
        };
    </script>
</body>
</html>
