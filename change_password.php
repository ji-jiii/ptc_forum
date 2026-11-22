<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Support both JSON body and FormData / POST body
$currentPassword = $data['currentPassword'] ?? ($_POST['currentPassword'] ?? '');
$newPassword = $data['newPassword'] ?? ($_POST['newPassword'] ?? '');
$studentId = $data['studentId'] ?? ($_POST['studentId'] ?? '');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

// Fallback lookup by session student_id or submitted studentId when user_id session key is not set
if (!$userId) {
    $lookupStudentId = $_SESSION['student_id'] ?? $studentId;
    if (!empty($lookupStudentId)) {
        $lookupStmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ? LIMIT 1");
        $lookupStmt->execute([$lookupStudentId]);
        $foundUser = $lookupStmt->fetch(PDO::FETCH_ASSOC);
        if ($foundUser) {
            $userId = $foundUser['id'];
            $_SESSION['user_id'] = $userId;
        }
    }
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

if ($currentPassword === '' || strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Invalid password input. New password must be at least 6 characters.']);
    exit;
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || empty($user['password']) || !password_verify($currentPassword, $user['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

$hashedNew = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$update->execute([$hashedNew, $userId]);

echo json_encode(['status' => 'success', 'success' => true, 'message' => 'Password updated successfully.']);
?>
