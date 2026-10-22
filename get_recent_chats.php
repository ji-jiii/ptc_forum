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
    // Select users who have exchanged messages and calculate their unread message count for the badge
    $stmt = $pdo->prepare("
        SELECT 
            u.student_id, 
            u.full_name, 
            u.course, 
            u.role, 
            u.is_online,
            (
                SELECT COUNT(*) 
                FROM messages m 
                WHERE m.sender_id = u.student_id 
                  AND m.receiver_id = ? 
                  AND (m.is_read = 0 OR m.is_read IS NULL OR m.is_read = false)
            ) AS unread_count
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
    $rawChats = $stmt->fetchAll();

    // Format fields so the frontend filter (c.has_unread || c.unread_count > 0) catches them cleanly
    $recentChats = [];
    foreach ($rawChats as $chat) {
        $unreadCount = intval($chat['unread_count'] ?? 0);
        $chat['has_unread'] = ($unreadCount > 0);
        $chat['unread_count'] = $unreadCount;
        $recentChats[] = $chat;
    }

    echo json_encode($recentChats);
} catch (\Exception $e) {
    echo json_encode([]);
}
?>
```[cite: 4]

### What this update does:
1. **Tracks Unread Messages:** It runs a subquery to count how many messages from each contact have been sent to the logged-in student where `is_read` is false or unread.
2. **Feeds the Badge Counter:** It populates `unread_count` and `has_unread` dynamically, allowing your frontend `renderRecentChats` function to show the badge count accurately and trigger the notifications banner right inside your message menu box.
