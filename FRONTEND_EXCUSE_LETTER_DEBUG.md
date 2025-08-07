# Frontend Excuse Letter Debug Guide

Since the API is working in Postman but not in your frontend, here's how to debug and fix the issue.

## Step 1: Check Your Frontend Code

### Common Frontend Issues:

1. **Wrong field names** - Make sure you're using the correct field names
2. **Missing authentication** - Ensure JWT token is properly sent
3. **Wrong content type** - Check if you're sending JSON vs FormData
4. **CORS issues** - Check browser console for CORS errors

## Step 2: Debug Your Frontend Request

Add this debugging code to your frontend:

```javascript
// Debug function to test excuse letter submission
async function debugExcuseLetterSubmission() {
    const token = localStorage.getItem('token'); // or however you store your token
    const classId = '5'; // Use the class_id that works in Postman
    const dateAbsent = '2025-09-12';
    const reason = 'Test from frontend';
    
    console.log('Debug Info:');
    console.log('Token:', token);
    console.log('Class ID:', classId);
    console.log('Date Absent:', dateAbsent);
    console.log('Reason:', reason);
    
    try {
        const response = await fetch('http://localhost/scms_new_backup/index.php/api/excuse-letters/submit', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                class_id: classId,
                date_absent: dateAbsent,
                reason: reason
            })
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (response.ok) {
            console.log('✅ Success!');
        } else {
            console.log('❌ Error:', data);
        }
        
    } catch (error) {
        console.error('❌ Network error:', error);
    }
}

// Call this function in your browser console
debugExcuseLetterSubmission();
```

## Step 3: Check Browser Console

Open your browser's Developer Tools (F12) and check:

1. **Console tab** - Look for JavaScript errors
2. **Network tab** - Check the actual request being sent
3. **Application tab** - Verify your JWT token is stored correctly

## Step 4: Common Frontend Fixes

### Fix 1: Correct Field Names
Make sure you're using the exact field names the API expects:

```javascript
// ✅ Correct field names
{
    "class_id": "5",
    "date_absent": "2025-09-12", 
    "reason": "Medical appointment"
}

// ❌ Wrong field names
{
    "classId": "5",
    "absenceDate": "2025-09-12",
    "excuseReason": "Medical appointment"
}
```

### Fix 2: Proper Authentication
Ensure your JWT token is sent correctly:

```javascript
// ✅ Correct way
headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
}

// ❌ Wrong ways
headers: {
    'Authorization': token, // Missing 'Bearer '
    'Authorization': `Bearer${token}`, // Missing space
    'token': token // Wrong header name
}
```

### Fix 3: Correct Content Type
For JSON requests:

```javascript
// ✅ Correct for JSON
headers: {
    'Content-Type': 'application/json'
},
body: JSON.stringify(data)

// ❌ Wrong for JSON
headers: {
    'Content-Type': 'application/x-www-form-urlencoded'
},
body: new URLSearchParams(data)
```

## Step 5: Test with Simple HTML

Create a simple test file to isolate the issue:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Excuse Letter Test</title>
</head>
<body>
    <h1>Excuse Letter Frontend Test</h1>
    
    <div>
        <label>JWT Token:</label>
        <input type="text" id="token" placeholder="Enter your JWT token" style="width: 400px;">
    </div>
    
    <div>
        <label>Class ID:</label>
        <input type="text" id="classId" value="5">
    </div>
    
    <div>
        <label>Date Absent:</label>
        <input type="date" id="dateAbsent" value="2025-09-12">
    </div>
    
    <div>
        <label>Reason:</label>
        <textarea id="reason" rows="3">Test from frontend</textarea>
    </div>
    
    <button onclick="testSubmit()">Test Submit</button>
    
    <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;"></div>
    
    <script>
        async function testSubmit() {
            const token = document.getElementById('token').value;
            const classId = document.getElementById('classId').value;
            const dateAbsent = document.getElementById('dateAbsent').value;
            const reason = document.getElementById('reason').value;
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = 'Testing...';
            
            try {
                const response = await fetch('http://localhost/scms_new_backup/index.php/api/excuse-letters/submit', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        class_id: classId,
                        date_absent: dateAbsent,
                        reason: reason
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    resultDiv.innerHTML = `<div style="color: green;">✅ Success!<br>${JSON.stringify(data, null, 2)}</div>`;
                } else {
                    resultDiv.innerHTML = `<div style="color: red;">❌ Error (${response.status}):<br>${JSON.stringify(data, null, 2)}</div>`;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `<div style="color: red;">❌ Network Error:<br>${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
```

## Step 6: Check Your React/Angular/Vue Code

If you're using a framework, make sure your code looks like this:

### React Example:
```javascript
const submitExcuseLetter = async (classId, dateAbsent, reason) => {
    try {
        const token = localStorage.getItem('token');
        
        const response = await fetch('/api/excuse-letters/submit', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                class_id: classId,
                date_absent: dateAbsent,
                reason: reason
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            console.log('Success:', data);
            return data;
        } else {
            throw new Error(data.message || 'Submission failed');
        }
        
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
};
```

### Axios Example:
```javascript
const submitExcuseLetter = async (classId, dateAbsent, reason) => {
    try {
        const token = localStorage.getItem('token');
        
        const response = await axios.post('/api/excuse-letters/submit', {
            class_id: classId,
            date_absent: dateAbsent,
            reason: reason
        }, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        console.log('Success:', response.data);
        return response.data;
        
    } catch (error) {
        console.error('Error:', error.response?.data || error.message);
        throw error;
    }
};
```

## Step 7: Debugging Checklist

- [ ] Check browser console for JavaScript errors
- [ ] Verify JWT token is valid and properly formatted
- [ ] Confirm field names match API expectations
- [ ] Check network tab for actual request/response
- [ ] Verify CORS is not blocking the request
- [ ] Test with simple HTML file to isolate the issue
- [ ] Compare request with working Postman request

## Common Issues and Solutions

### Issue 1: CORS Error
**Solution**: Make sure your API has CORS headers or use a proxy

### Issue 2: 401 Unauthorized
**Solution**: Check your JWT token format and expiration

### Issue 3: 400 Bad Request
**Solution**: Verify field names and data types match API expectations

### Issue 4: Network Error
**Solution**: Check if your API URL is correct and accessible

## Next Steps

1. Run the debug function in your browser console
2. Check the browser's Network tab to see the actual request
3. Compare the request with your working Postman request
4. Use the simple HTML test file to isolate the issue
5. Update your frontend code based on the findings
