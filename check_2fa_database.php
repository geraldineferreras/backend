<?php
try {
    $pdo = new PDO('mysql:host=localhost:3308;dbname=scms_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Checking 2FA Database Setup...\n\n";
    
    // Check users table for 2FA fields
    echo "📋 Users table 2FA fields:\n";
    $stmt = $pdo->query('DESCRIBE users');
    $two_factor_fields = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (strpos($row['Field'], 'two_factor') !== false || $row['Field'] === 'updated_at') {
            $two_factor_fields[] = $row['Field'];
            echo "✅ {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Default']}\n";
        }
    }
    
    if (empty($two_factor_fields)) {
        echo "❌ No 2FA fields found in users table!\n";
    }
    
    // Check backup_codes table
    echo "\n📋 Checking backup_codes table:\n";
    $stmt = $pdo->query('SHOW TABLES LIKE "backup_codes"');
    if ($stmt->rowCount() > 0) {
        echo "✅ backup_codes table exists\n";
        
        // Show backup_codes structure
        $stmt = $pdo->query('DESCRIBE backup_codes');
        echo "📋 backup_codes table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Default']}\n";
        }
    } else {
        echo "❌ backup_codes table missing!\n";
    }
    
    echo "\n🎯 Summary:\n";
    echo "2FA fields in users table: " . count($two_factor_fields) . "\n";
    echo "backup_codes table: " . ($stmt->rowCount() > 0 ? "✅ Exists" : "❌ Missing") . "\n";
    
    if (count($two_factor_fields) >= 3 && $stmt->rowCount() > 0) {
        echo "\n🎉 2FA Database is fully set up and ready!\n";
    } else {
        echo "\n⚠️ 2FA Database setup incomplete. Run setup_2fa_system.php again.\n";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
