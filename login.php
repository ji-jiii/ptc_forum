<?php
session_start();
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
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['studentId'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($studentId) || empty($password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, full_name, course, password_hash, role FROM users WHERE student_id = ? LIMIT 1");
        $stmt->execute([$studentId]);
        $user = $stmt->fetch();

        if ($user) {
            if (!empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['student_id'] = $studentId;
                $_SESSION['role'] = $user['role'] ?? 'Student';

                // PostgreSQL boolean/integer compatibility: use TRUE or 1 depending on column type
                $updateStmt = $pdo->prepare("UPDATE users SET is_online = 1, last_activity = CURRENT_TIMESTAMP WHERE student_id = ?");
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
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "Invalid password."]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Student ID not found."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Authentication error: " . $e->getMessage()]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method not allowed."]);
?>
