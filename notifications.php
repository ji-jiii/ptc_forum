<?php
header('Content-Type: application/json');
require_once 'config.php';

$studentId = $_GET['studentId'] ?? '';

if (!empty($studentId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$studentId]);
        $notifications = $stmt->fetchAll();
        echo json_encode($notifications);
        exit();
    } catch (Exception $e) {
        echo json_encode([]);
        exit();
    }
}

echo json_encode([]);
?>