<?php
header('Content-Type: text/plain');

$keys = [
    'ENVIRONMENT', 'BASE_URL', 'DATABASE_URL',
    'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASS', 'DB_NAME',
    'MYSQL_URL', 'MYSQL_PUBLIC_URL',
    'MYSQLHOST', 'MYSQLPORT', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLDATABASE',
    'RAILWAY_PRIVATE_DOMAIN', 'RAILWAY_PUBLIC_DOMAIN', 'RAILWAY_TCP_PROXY_DOMAIN', 'RAILWAY_TCP_PROXY_PORT'
];

echo "Runtime environment variables (getenv)\n";
echo str_repeat('=', 50) . "\n\n";
foreach ($keys as $k) {
    $v = getenv($k);
    echo sprintf("%-24s = %s\n", $k, ($v === false ? '(not set)' : $v));
}

echo "\n\n_SERVER overrides\n";
echo str_repeat('=', 50) . "\n\n";
foreach ($keys as $k) {
    $v = isset($_SERVER[$k]) ? $_SERVER[$k] : '(not set)';
    echo sprintf("%-24s = %s\n", $k, $v);
}

echo "\n\n_ENV overrides\n";
echo str_repeat('=', 50) . "\n\n";
foreach ($keys as $k) {
    $v = isset($_ENV[$k]) ? $_ENV[$k] : '(not set)';
    echo sprintf("%-24s = %s\n", $k, $v);
}

?>


