<?php
// db_connect.php
$connectionString = getenv('DATABASE_URL') ?: 'postgres://your_neon_connection_string_here?sslmode=require';

try {
    // Parse the Neon PostgreSQL connection string into PDO format
    $options = parse_url($connectionString);
    $dsn = "pgsql:host={$options['host']};port={$options['port']};dbname=" . ltrim($options['path'], '/');
    
    $pdo = new PDO($dsn, $options['user'], $options['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
?>
