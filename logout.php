<?php
session_start(); // Start session so we can destroy it
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['studentId'] ?? '');

    if (!empty($studentId)) {
        try {
            // Set user status to offline in the database
            $stmt = $pdo->prepare("UPDATE users SET is_online = 0 WHERE student_id = ?");
            $stmt->execute([$studentId]);

            // Clear all session variables and destroy the session 
            // This prevents any trailing heartbeat pings from flipping is_online back to 1
            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["use_secure"], $params["httponly"]
                );
            }
            session_destroy();

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
