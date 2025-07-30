<?php
// Test script for section update with student assignments
$url = 'http://localhost/scms_new/index.php/api/admin/sections/15';

$data = [
    'section_name' => 'BSIT 4Z',
    'program' => 'Bachelor of Science in Information Technology',
    'year_level' => '1st Year',
    'semester' => '1st',
    'academic_year' => '2022-2023',
    'adviser_id' => 'TEACH002',
    'student_ids' => ['STU685651BF9DDCF988', '2021302596']
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

echo "Response: " . $result . "\n";
?> 