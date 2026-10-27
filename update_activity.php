<?php
// update_activity.php
session_start();
header('Content-Type: application/json');

$database_url = getenv('DATABASE_URL') ?: 'postgres://user:password@host/dbname?sslmode=require';

try {
    $parsed_url = parse_url($database_url);
    $dsn = "pgsql:host=" . ($parsed_url['host'] ?? 'localhost') . ";port=" . ($parsed_url['port'] ?? '5432') . ";dbname=" . ltrim($parsed_url['path'] ?? '', '/') . ";sslmode=require";
    $pdo = new PDO($dsn, $parsed_url['user'] ?? '', $parsed_url['pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['studentId'])) {
        $studentId = trim($_POST['studentId']);

        // Race condition safeguard: If the session was destroyed during logout, 
        // reject any incoming heartbeat pings so they can't flip is_online back to 1.
        if (isset($_SESSION['student_id']) && $_SESSION['student_id'] !== $studentId) {
            echo json_encode(['status' => 'unauthorized']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET last_activity = CURRENT_TIMESTAMP, is_online = 1 WHERE student_id = ?");
        $stmt->execute([$studentId]);
        echo json_encode(['status' => 'success']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error']);
}
?>
