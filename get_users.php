<?php
header('Content-Type: application/json');
$database_url = getenv('DATABASE_URL') ?: 'postgres://user:password@host/dbname?sslmode=require';

try {
    $parsed_url = parse_url($database_url);
    $dsn = "pgsql:host=" . ($parsed_url['host'] ?? 'localhost') . ";port=" . ($parsed_url['port'] ?? '5432') . ";dbname=" . ltrim($parsed_url['path'] ?? '', '/') . ";sslmode=require";
    $pdo = new PDO($dsn, $parsed_url['user'] ?? '', $parsed_url['pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->query("SELECT student_id, full_name, role, course, email, is_online FROM users ORDER BY full_name ASC");
    $users = $stmt->fetchAll();
    echo json_encode($users);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
