<?php
/**
 * Task Submission Example
 * 
 * This file demonstrates how to submit tasks to teachers using PHP
 * with both multipart form data (file uploads) and JSON requests.
 */

// Configuration
$api_base_url = 'http://localhost/scms_new_backup/api';
$jwt_token = 'your_jwt_token_here'; // Replace with actual token
$task_id = 1; // Replace with actual task ID

/**
 * Submit task with file upload using multipart form data
 */
function submitTaskWithFile($task_id, $submission_content, $class_code, $file_path, $token) {
    global $api_base_url;
    
    // Check if file exists
    if (!file_exists($file_path)) {
        echo "Error: File not found: $file_path\n";
        return false;
    }
    
    // Prepare multipart form data
    $post_data = [
        'submission_content' => $submission_content,
        'class_code' => $class_code,
        'attachment' => new CURLFile($file_path)
    ];
    
    // Initialize cURL
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => "$api_base_url/tasks/$task_id/submit",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token"
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        echo "cURL Error: $error\n";
        return false;
    }
    
    // Parse response
    $data = json_decode($response, true);
    
    echo "HTTP Code: $http_code\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    return $data['success'] ?? false;
}

/**
 * Submit task with JSON data (no file upload)
 */
function submitTaskWithJSON($task_id, $submission_content, $class_code, $attachment_url = null, $attachment_type = null, $token) {
    global $api_base_url;
    
    // Prepare JSON data
    $post_data = [
        'submission_content' => $submission_content,
        'class_code' => $class_code
    ];
    
    // Add attachment if provided
    if ($attachment_url && $attachment_type) {
        $post_data['attachment_url'] = $attachment_url;
        $post_data['attachment_type'] = $attachment_type;
    }
    
    // Initialize cURL
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => "$api_base_url/tasks/$task_id/submit",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($post_data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        echo "cURL Error: $error\n";
        return false;
    }
    
    // Parse response
    $data = json_decode($response, true);
    
    echo "HTTP Code: $http_code\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    return $data['success'] ?? false;
}

/**
 * Submit task using HTML form processing
 */
function processFormSubmission() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    $task_id = $_POST['task_id'] ?? null;
    $submission_content = $_POST['submission_content'] ?? '';
    $class_code = $_POST['class_code'] ?? '';
    $token = $_POST['token'] ?? '';
    
    if (!$task_id || !$class_code || !$token) {
        echo "Error: Missing required fields\n";
        return;
    }
    
    // Check if file was uploaded
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file_path = $_FILES['attachment']['tmp_name'];
        $result = submitTaskWithFile($task_id, $submission_content, $class_code, $file_path, $token);
    } else {
        // No file upload, use JSON method
        $result = submitTaskWithJSON($task_id, $submission_content, $class_code, null, null, $token);
    }
    
    if ($result) {
        echo "Task submitted successfully!\n";
    } else {
        echo "Failed to submit task.\n";
    }
}

// Process form submission if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    processFormSubmission();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Submission Example (PHP)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        input[type="file"] {
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        button {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Task Submission Example (PHP)</h1>
        <p>This form demonstrates how to submit tasks using PHP with file uploads.</p>
        
        <div class="note">
            <strong>Note:</strong> This example shows both client-side form submission and server-side PHP processing.
            The PHP code above demonstrates how to handle the submission programmatically.
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="task_id">Task ID:</label>
                <input type="text" id="task_id" name="task_id" value="1" required>
            </div>
            
            <div class="form-group">
                <label for="submission_content">Submission Content:</label>
                <textarea id="submission_content" name="submission_content" placeholder="Enter your submission content here..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="class_code">Class Code:</label>
                <input type="text" id="class_code" name="class_code" placeholder="e.g., MATH101" required>
            </div>
            
            <div class="form-group">
                <label for="attachment">Attachment (Optional):</label>
                <input type="file" id="attachment" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.mp4,.mp3">
            </div>
            
            <div class="form-group">
                <label for="token">JWT Token:</label>
                <input type="text" id="token" name="token" placeholder="Enter your JWT token" required>
            </div>
            
            <button type="submit">Submit Task</button>
        </form>
        
        <h2>PHP Code Examples</h2>
        <p>Here are examples of how to submit tasks programmatically using PHP:</p>
        
        <h3>1. Submit with File Upload</h3>
        <pre><code>
// Submit task with file upload
$result = submitTaskWithFile(
    task_id: 1,
    submission_content: "My assignment submission",
    class_code: "MATH101",
    file_path: "/path/to/file.pdf",
    token: "your_jwt_token"
);
        </code></pre>
        
        <h3>2. Submit with JSON (No File)</h3>
        <pre><code>
// Submit task with JSON data
$result = submitTaskWithJSON(
    task_id: 1,
    submission_content: "My assignment submission",
    class_code: "MATH101",
    attachment_url: "https://drive.google.com/file/d/123456/view",
    attachment_type: "google_drive",
    token: "your_jwt_token"
);
        </code></pre>
        
        <h3>3. Submit with External Link</h3>
        <pre><code>
// Submit task with external link
$result = submitTaskWithJSON(
    task_id: 1,
    submission_content: "Check my Google Drive document",
    class_code: "ENG101",
    attachment_url: "https://drive.google.com/file/d/1234567890abcdef/view?usp=sharing",
    attachment_type: "google_drive",
    token: "your_jwt_token"
);
        </code></pre>
    </div>
</body>
</html> 