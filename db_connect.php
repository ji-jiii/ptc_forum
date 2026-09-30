<?php
// Check if running on Render with DATABASE_URL, otherwise fallback to local XAMPP MySQL
$databaseUrl = getenv('DATABASE_URL');

try {
    if ($databaseUrl) {
        // --- RENDER & NEON (PostgreSQL) ---
        $options = parse_url($databaseUrl);
        $host = $options['host'] ?? '';
        $port = $options['port'] ?? 5432;
        $dbname = ltrim($options['path'] ?? '', '/');
        $user = $options['user'] ?? '';
        $pass = $options['pass'] ?? '';

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } else {
        // --- LOCAL XAMPP (MySQL) ---[cite: 8]
        $host = 'localhost';
        $dbname = 'ptc_forum'; 
        $username = 'root';
        $password = ''; 

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}
?>
