<?php
header('Content-Type: application/json');

// Connect to Neon PostgreSQL using Render's environment variable
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
    // Directly select the is_online column (0 or 1) without any time cooldowns
    $stmt = $pdo->query("
        SELECT student_id, full_name, course, role, is_online 
        FROM users 
        ORDER BY full_name ASC
    ");
    $contacts = $stmt->fetchAll();

    // Ensure is_online is cleanly cast to an integer for the frontend
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
