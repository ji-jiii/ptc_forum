<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Select users and their online status
    $stmt = $pdo->query("SELECT student_id, full_name, role, course, is_online FROM users ORDER BY full_name ASC");
    $contacts = $stmt->fetchAll();

    echo json_encode($contacts);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve contacts']);
}
?>