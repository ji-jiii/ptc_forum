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
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['studentId'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($studentId) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, full_name, course, password_hash, role FROM users WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                $updateStmt = $pdo->prepare("UPDATE users SET is_online = 1 WHERE student_id = ?");
                $updateStmt->execute([$studentId]);

                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful!",
                    "user" => [
                        "fullName" => $user['full_name'],
                        "studentId" => $studentId,
                        "course" => $user['course'],
                        "role" => $user['role']
                    ]
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid password."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Student ID not found."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Authentication error: " . $e->getMessage()]);
    }
    exit();
}
?>
