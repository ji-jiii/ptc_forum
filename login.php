<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['studentId'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($studentId) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
        exit();
    }

    try {
        // Query user by Student ID
        $stmt = $pdo->prepare("SELECT id, full_name, course, password_hash, role FROM users WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify hashed password
            if (password_verify($password, $user['password_hash'])) {
                
                // Update user online status
                $updateStmt = $pdo->prepare("UPDATE users SET is_online = 1 WHERE student_id = ?");
                $updateStmt->execute([$studentId]);

                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful!",
                    "user" => [
                        "fullName" => $user['full_name'],
                        "studentId" => $studentId,
                        "course" => $user['course'],
                        "role" => $user['role']
                    ]
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid password."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Student ID not found."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Authentication error: " . $e->getMessage()]);
    }
    exit();
}
?>