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
    // Select all unique users involved in any message exchange with the current user
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.student_id, u.full_name, u.course, u.role, u.is_online,
               (SELECT COUNT(*) FROM messages WHERE sender_id = u.student_id AND receiver_id = ? AND (is_read = 0 OR is_read = FALSE)) AS unread_count
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
    $stmt->execute([$studentId, $studentId, $studentId, $studentId, $studentId]);
    $recentChats = $stmt->fetchAll();

    // Map each record to include flags required by frontend badge filters
    foreach ($recentChats as &$chat) {
        $chat['unread_count'] = (int)($chat['unread_count'] ?? 0);
        $chat['has_unread'] = $chat['unread_count'] > 0;
        // Include chat thread even if it's outbound so names appear correctly in menu
        $chat['is_new'] = true; 
    }

    echo json_encode($recentChats);
} catch (\Exception $e) {
    echo json_encode([]);
}
?>
