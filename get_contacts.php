<?php
header('Content-Type: application/json');

// Connect to Neon PostgreSQL using Render's environment variable
$database_url = getenv('DATABASE_URL') ?: 'postgres://user:password@host/dbname?sslmode=require';

try {
    $parsed_url = parse_url($database_url);
    $host = $parsed_url['host'] ?? 'localhost';
    $port = $parsed_url['port'] ?? '5432';
    $dbname = ltrim($parsed_url['path'] ?? '', '/');
    $user = $parsed_url['user'] ?? '';
    $pass = $parsed_url['pass'] ?? '';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    echo json_encode([]);
    exit;
}

try {
    // Dynamically calculate online status based on last 5 minutes of activity
    $stmt = $pdo->query("
        SELECT student_id, full_name, course, role,
               CASE 
                   WHEN last_activity >= NOW() - INTERVAL '5 minutes' THEN 1 
                   ELSE 0 
               END AS is_online
        FROM users 
        ORDER BY full_name ASC
    ");
    $contacts = $stmt->fetchAll();
    
    echo json_encode($contacts);
} catch (Exception $e) {
    // Fallback if last_activity column doesn't exist yet
    try {
        $stmt = $pdo->query("SELECT student_id, full_name, course, role, is_online FROM users ORDER BY full_name ASC");
        echo json_encode($stmt->fetchAll());
    } catch (Exception $ex) {
        echo json_encode([]);
    }
}
?>
