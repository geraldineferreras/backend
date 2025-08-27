<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test BSIS Sections API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .result { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>🧪 Test BSIS Sections API</h1>
    
    <div class="test-section">
        <h3>Test 1: BSIS Year 2 Sections</h3>
        <p>Testing: <code>/api/student/programs/BSIS/years/2/sections</code></p>
        <button onclick="testBSISYear2()">Test BSIS Year 2</button>
        <div id="result1" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test 2: BSCS Year 1 Sections</h3>
        <p>Testing: <code>/api/student/programs/BSCS/years/1/sections</code></p>
        <button onclick="testBSCSYear1()">Test BSCS Year 1</button>
        <div id="result2" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test 3: BSIT Year 4 Sections (Control Test)</h3>
        <p>Testing: <code>/api/student/programs/BSIT/years/4/sections</code></p>
        <button onclick="testBSITYear4()">Test BSIT Year 4</button>
        <div id="result3" class="result"></div>
    </div>

    <script>
        const baseUrl = 'http://localhost/scms_new_backup/index.php';
        
        async function testBSISYear2() {
            const resultDiv = document.getElementById('result1');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch(`${baseUrl}/api/student/programs/BSIS/years/2/sections`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4>✅ SUCCESS!</h4>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <p><strong>Sections Found:</strong> ${data.data.length}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>❌ FAILED</h4>
                            <p><strong>Error:</strong> ${data.message}</p>
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
        
        async function testBSCSYear1() {
            const resultDiv = document.getElementById('result2');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch(`${baseUrl}/api/student/programs/BSCS/years/1/sections`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4>✅ SUCCESS!</h4>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <p><strong>Sections Found:</strong> ${data.data.length}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>❌ FAILED</h4>
                            <p><strong>Error:</strong> ${data.message}</p>
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
        
        async function testBSITYear4() {
            const resultDiv = document.getElementById('result3');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch(`${baseUrl}/api/student/programs/BSIT/years/4/sections`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h4>✅ SUCCESS!</h4>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <p><strong>Sections Found:</strong> ${data.data.length}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h4>❌ FAILED</h4>
                            <p><strong>Error:</strong> ${data.message}</p>
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
    </script>
</body>
</html>
