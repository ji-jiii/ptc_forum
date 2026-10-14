<?php
// update_activity.php
$database_url = getenv('DATABASE_URL') ?: 'postgres://user:password@host/dbname?sslmode=require';

try {
    $parsed_url = parse_url($database_url);
    $dsn = "pgsql:host=" . ($parsed_url['host'] ?? 'localhost') . ";port=" . ($parsed_url['port'] ?? '5432') . ";dbname=" . ltrim($parsed_url['path'] ?? '', '/') . ";sslmode=require";
    $pdo = new PDO($dsn, $parsed_url['user'] ?? '', $parsed_url['pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['studentId'])) {
        $stmt = $pdo->prepare("UPDATE users SET last_activity = CURRENT_TIMESTAMP, is_online = 1 WHERE student_id = ?");
        $stmt->execute([$_POST['studentId']]);
        echo json_encode(['status' => 'success']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error']);
}
?>
