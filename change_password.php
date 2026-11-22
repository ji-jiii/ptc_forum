<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Fallback if FormData was sent instead of JSON
$currentPassword = $data['currentPassword'] ?? ($_POST['currentPassword'] ?? '');
$newPassword = $data['newPassword'] ?? ($_POST['newPassword'] ?? '');
$userId = $_SESSION['user_id'];

if ($currentPassword === '' || strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Invalid password input.']);
    exit;
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($currentPassword, $user['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

$hashedNew = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$update->execute([$hashedNew, $userId]);

echo json_encode(['status' => 'success', 'success' => true, 'message' => 'Password updated successfully.']);
?>
