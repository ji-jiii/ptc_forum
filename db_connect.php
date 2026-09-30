<?php
// Check for DATABASE_URL across different environment scopes in Docker/Apache
$databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);
if (!$databaseUrl && function_exists('apache_getenv')) {
    $databaseUrl = apache_getenv('DATABASE_URL');
}

try {
    if (!empty($databaseUrl)) {
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
        // --- LOCAL XAMPP (MySQL) - Only used locally ---
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
    // This outputs the exact database error and environment check for debugging on Render
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'debug_url_exists' => !empty($databaseUrl) ? 'yes' : 'no'
    ]);
    exit;
}
?>
