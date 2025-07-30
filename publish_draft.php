<?php
// Publish a draft post
// Replace {draft_id} with the actual draft ID from the check_draft_posts.php script

$draft_id = 9; // Replace with actual draft ID
$url = "http://localhost/scms_new/index.php/api/teacher/classroom/DZ6ENU/stream/draft/{$draft_id}";

$data = [
    'is_draft' => 0,  // Set to 0 to publish
    'content' => 'Please pass y',
    'title' => 'Your title here'  // Add your title
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'PUT',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Publish Response:\n";
echo $result . "\n";

// Now check if it appears in the main stream
$stream_url = 'http://localhost/scms_new/index.php/api/teacher/classroom/DZ6ENU/stream';
$stream_options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'GET'
    ]
];

$stream_context = stream_context_create($stream_options);
$stream_result = file_get_contents($stream_url, false, $stream_context);

echo "\nMain Stream Response:\n";
echo $stream_result . "\n";
?> 