<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Connect directly to Neon PostgreSQL using DATABASE_URL without requiring db.php
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
    http_response_code(500);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Support both JSON body and FormData / POST body
$currentPassword = $data['currentPassword'] ?? ($_POST['currentPassword'] ?? '');
$newPassword = $data['newPassword'] ?? ($_POST['newPassword'] ?? '');
$studentId = trim($data['studentId'] ?? ($_POST['studentId'] ?? ''));

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

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || empty($user['password_hash']) || !password_verify($currentPassword, $user['password_hash'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

$hashedNew = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$update->execute([$hashedNew, $userId]);

echo json_encode(['status' => 'success', 'success' => true, 'message' => 'Password updated successfully.']);
?>
