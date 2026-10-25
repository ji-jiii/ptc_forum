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
    // Fallback: Select all users except current student so they appear in chat menu instantly
    $stmt = $pdo->prepare("
        SELECT student_id, full_name, course, role, is_online, 
               0 AS unread_count 
        FROM users 
        WHERE student_id != ?
    ");
    $stmt->execute([$studentId]);
    $recentChats = $stmt->fetchAll();

    $formattedChats = [];
    foreach ($recentChats as $chat) {
        $formattedChats[] = [
            'student_id' => $chat['student_id'],
            'full_name' => $chat['full_name'],
            'course' => $chat['course'] ?? 'Member',
            'role' => $chat['role'] ?? 'Student',
            'is_online' => $chat['is_online'],
            'unread_count' => 0,
            'has_unread' => false,
            'is_new' => true
        ];
    }

    echo json_encode($formattedChats);
} catch (\Exception $e) {
    echo json_encode([]);
}
?>
