<?php
header('Content-Type: application/json');

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

$studentId = $_GET['studentId'] ?? '';

if (!$studentId) {
    echo json_encode([]);
    exit;
}

try {
    // Query to find unique users who have exchanged messages with the current user
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.student_id, u.full_name, u.course, u.role, u.is_online
        FROM users u
        JOIN messages m ON (u.student_id = m.sender_id OR u.student_id = m.receiver_id)
        WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.student_id != ?
    ");
    $stmt->execute([$studentId, $studentId, $studentId]);
    $recentChats = $stmt->fetchAll();

    echo json_encode($recentChats);
} catch (\Exception $e) {
    echo json_encode([]);
}
?>
```[cite: 5]
