<?php
// Check draft posts for classroom DZ6ENU
$url = 'http://localhost/scms_new/index.php/api/teacher/classroom/DZ6ENU/stream/drafts';

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'GET'
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Draft Posts Response:\n";
echo $result . "\n";

// Also check the database directly
$host = 'localhost';
$dbname = 'scms_new';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all posts for DZ6ENU
    $stmt = $pdo->prepare("SELECT id, title, content, is_draft, is_scheduled, created_at FROM classroom_stream WHERE class_code = ? ORDER BY created_at DESC");
    $stmt->execute(['DZ6ENU']);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nAll Posts in Database for DZ6ENU:\n";
    foreach ($posts as $post) {
        $status = $post['is_draft'] ? 'DRAFT' : 'PUBLISHED';
        echo "ID: {$post['id']}, Status: {$status}, Title: {$post['title']}, Content: {$post['content']}\n";
    }
    
} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?> 