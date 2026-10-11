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

$action = $_REQUEST['action'] ?? '';

// FETCH MESSAGES BETWEEN TWO USERS
if ($action === 'fetch') {
    $user1 = $_GET['user1'] ?? '';
    $user2 = $_GET['user2'] ?? '';

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$user1, $user2, $user2, $user1]);
        $messages = $stmt->fetchAll();
        
        echo json_encode($messages);
    } catch (\Exception $e) {
        echo json_encode([]);
    }
    exit;
}

// SEND A NEW MESSAGE
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $senderId = $_POST['senderId'] ?? '';
    $receiverId = $_POST['receiverId'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if (empty($senderId) || empty($receiverId) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Empty fields']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, message, created_at) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$senderId, $receiverId, $message]);
        
        echo json_encode(['status' => 'success']);
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
