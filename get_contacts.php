<?php
header('Content-Type: application/json');

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
    echo json_encode([]);
    exit;
}

try {
    // Automatically set users to offline if their last heartbeat activity is older than 2 minutes
    $pdo->exec("UPDATE users SET is_online = 0 WHERE last_activity < NOW() - INTERVAL '2 minutes'");

    $stmt = $pdo->query("
        SELECT student_id, full_name, course, role, is_online 
        FROM users 
        ORDER BY full_name ASC
    ");
    $contacts = $stmt->fetchAll();

    $formattedContacts = [];
    foreach ($contacts as $contact) {
        $formattedContacts[] = [
            'student_id' => $contact['student_id'],
            'full_name' => $contact['full_name'],
            'course' => $contact['course'] ?? 'Member',
            'role' => $contact['role'] ?? 'Student',
            'is_online' => (int)($contact['is_online'] ?? 0)
        ];
    }
    
    echo json_encode($formattedContacts);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
