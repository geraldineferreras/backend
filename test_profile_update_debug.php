<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Profile Update API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .result { margin-top: 10px; }
        .debug-info { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🐛 Debug Profile Update API</h1>
    
    <div class="debug-info">
        <h3>🔍 Debug Information</h3>
        <p><strong>Issue:</strong> API returns "Invalid JSON data provided"</p>
        <p><strong>Possible Causes:</strong></p>
        <ul>
            <li>Empty request body</li>
            <li>Malformed JSON</li>
            <li>Content-Type header mismatch</li>
            <li>Request method issue</li>
        </ul>
    </div>
    
    <div class="test-section">
        <h3>Test 1: PUT Method with Proper Headers</h3>
        <p>Testing: <code>PUT /api/student/profile/update</code></p>
        <button onclick="testPUTMethod()">Test PUT Method</button>
        <div id="result1" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test 2: POST Method (Fallback)</h3>
        <p>Testing: <code>POST /api/student/profile/update</code></p>
        <button onclick="testPOSTMethod()">Test POST Method</button>
        <div id="result2" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test 3: Check Raw Request Body</h3>
        <p>Testing what the API actually receives</p>
        <button onclick="testRawBody()">Test Raw Body</button>
        <div id="result3" class="result"></div>
    </div>

    <script>
        const baseUrl = 'http://localhost/scms_new_backup/index.php';
        
        const testData = {
            "full_name": "Ferreras, Geraldine P.",
            "email": "2021304995@pampangastateu.edu.ph",
            "address": "Pampanga, Philippines",
            "contact_num": "09123456789",
            "student_num": "2021304995",
            "program": "Bachelor of Science in Information Technology",
            "year_level": "1st year",
            "section_id": 1,
            "password": ""
        };
        
        async function testPUTMethod() {
            const resultDiv = document.getElementById('result1');
            resultDiv.innerHTML = '<p>Testing PUT method...</p>';
            
            try {
                const response = await fetch(`${baseUrl}/api/student/profile/update`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer YOUR_JWT_TOKEN_HERE' // You'll need to add a real token
                    },
                    body: JSON.stringify(testData)
                });
                
                const data = await response.json();
                
                if (data.status) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4>✅ SUCCESS!</h4>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>❌ FAILED</h4>
                            <p><strong>Error:</strong> ${data.message}</p>
                            <p><strong>Status Code:</strong> ${response.status}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4>❌ ERROR</h4>
                        <p><strong>Exception:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
        
        async function testPOSTMethod() {
            const resultDiv = document.getElementById('result2');
            resultDiv.innerHTML = '<p>Testing POST method...</p>';
            
            try {
                const response = await fetch(`${baseUrl}/api/student/profile/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer YOUR_JWT_TOKEN_HERE' // You'll need to add a real token
                    },
                    body: JSON.stringify(testData)
                });
                
                const data = await response.json();
                
                if (data.status) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4>✅ SUCCESS!</h4>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>❌ FAILED</h4>
                            <p><strong>Error:</strong> ${data.message}</p>
                            <p><strong>Status Code:</strong> ${response.status}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4>❌ ERROR</h4>
                        <p><strong>Exception:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
        
        async function testRawBody() {
            const resultDiv = document.getElementById('result3');
            resultDiv.innerHTML = '<p>Testing raw body...</p>';
            
            try {
                // Test with a simple POST to see what happens
                const response = await fetch(`${baseUrl}/api/student/profile/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'text/plain' // Try different content type
                    },
                    body: 'This is a test body'
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="debug-info">
                        <h4>🔍 Raw Body Test Result</h4>
                        <p><strong>Status Code:</strong> ${response.status}</p>
                        <p><strong>Response:</strong></p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <h4>❌ ERROR</h4>
                        <p><strong>Exception:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>
