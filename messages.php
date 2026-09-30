<?php
header('Content-Type: application/json');
require_once 'config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// FETCH MESSAGES
if ($action === 'fetch') {
    $user1 = $_GET['user1'] ?? '';
    $user2 = $_GET['user2'] ?? '';

    if (empty($user1) || empty($user2)) {
        echo json_encode([]);
        exit();
    }

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
    } catch (Exception $e) {
        echo json_encode([]);
    }
    exit();
}

// SEND MESSAGE
if ($action === 'send') {
    $senderId = $_POST['senderId'] ?? '';
    $receiverId = $_POST['receiverId'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if (!empty($senderId) && !empty($receiverId) && !empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$senderId, $receiverId, $message]);

            // Add notification for receiver
            $notifStmt = $pdo->prepare("INSERT INTO notifications (student_id, title) VALUES (?, ?)");
            $notifStmt->execute([$receiverId, "New message received from $senderId"]);

            echo json_encode(["status" => "success"]);
            exit();
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit();
        }
    }
}

echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
?>