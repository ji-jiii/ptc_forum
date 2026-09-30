<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['studentId'] ?? '');

    if (!empty($studentId)) {
        try {
            // Set user status to offline
            $stmt = $pdo->prepare("UPDATE users SET is_online = 0 WHERE student_id = ?");
            $stmt->execute([$studentId]);

            echo json_encode(["status" => "success", "message" => "Logged out successfully."]);
            exit();
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Database error during logout."]);
            exit();
        }
    }
}

echo json_encode(["status" => "error", "message" => "Invalid request."]);
?>