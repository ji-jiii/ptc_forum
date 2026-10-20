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
    // Select all unique users who have exchanged messages with the current student
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.student_id, u.full_name, u.course, u.role, u.is_online
        FROM users u
        WHERE u.student_id != ? 
          AND u.student_id IN (
              SELECT 
                  CASE 
                      WHEN sender_id = ? THEN receiver_id 
                      ELSE sender_id 
                  END 
              FROM messages 
              WHERE sender_id = ? OR receiver_id = ?
          )
    ");
    $stmt->execute([$studentId, $studentId, $studentId, $studentId]);
    $recentChats = $stmt->fetchAll();

    echo json_encode($recentChats);
} catch (\Exception $e) {
    echo json_encode([]);
}
?>
