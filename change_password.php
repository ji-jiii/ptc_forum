<?php
session_start();
header('Content-Type: application/json');
require 'db.php'; // Your PDO database connection ($pdo)

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$currentPassword = $data['currentPassword'] ?? '';
$newPassword = $data['newPassword'] ?? '';
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($currentPassword, $user['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

$hashedNew = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$update->execute([$hashedNew, $userId]);

echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
?>
