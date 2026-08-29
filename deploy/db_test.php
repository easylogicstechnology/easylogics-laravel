<?php
// READ-ONLY Database Connection Test for Hostinger
// Upload this to: public_html/new/db_test.php
// Open: https://societynew.in/new/db_test.php
// DELETE this file after testing!

header('Content-Type: text/plain; charset=utf-8');

echo "=== Hostinger DB Connection Test ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$host = '127.0.0.1';
$port = 3306;
$dbname = 'societynew';
$username = 'societynew';
$password = 'tv3vMrowdYBJI65FBl1F';

echo "DB Host: $host\n";
echo "DB Port: $port\n";
echo "DB Name: $dbname\n";
echo "DB User: $username\n";
echo "DB Pass: ***SET***\n\n";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "CONNECTION: SUCCESS\n\n";

    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "MySQL Version: $version\n";

    $tables = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$dbname'"
    )->fetchColumn();
    echo "Total Tables: $tables\n\n";

    $stmt = $pdo->query(
        "SELECT table_name FROM information_schema.tables WHERE table_schema = '$dbname' ORDER BY table_name LIMIT 10"
    );
    echo "Sample Tables:\n";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "  - $row[0]\n";
    }

    // Test users table
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "\nusers table: $userCount rows\n";

    echo "\n=== RESULT: PHP " . PHP_VERSION . " -> MySQL $dbname = SUCCESS ===\n";

} catch (PDOException $e) {
    echo "CONNECTION: FAILED\n";
    echo "SQLSTATE: " . $e->getCode() . "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n=== RESULT: FAILED ===\n";
}
