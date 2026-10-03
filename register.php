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
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName    = trim($_POST['fullName'] ?? '');
    $studentId   = trim($_POST['studentId'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $role        = trim($_POST['role'] ?? 'Student');
    $course      = trim($_POST['course'] ?? '');
    $password    = $_POST['password'] ?? '';

    try {
        // Check if ID exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
        $checkStmt->execute([$studentId]);
        if ($checkStmt->fetch()) {
            echo json_encode(["status" => "error", "message" => "ID Number is already registered."]);
            exit();
        }

        // Handle Image Upload
        $imagePath = null;
        if (isset($_FILES['student_id_image']) && $_FILES['student_id_image']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            $ext = pathinfo($_FILES['student_id_image']['name'], PATHINFO_EXTENSION);
            $newFileName = "id_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            $targetPath = "uploads/" . $newFileName;

            if (move_uploaded_file($_FILES['student_id_image']['tmp_name'], $targetPath)) {
                $imagePath = $targetPath;
            }
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert user with role
        $stmt = $pdo->prepare("INSERT INTO users (full_name, student_id, phone_number, role, course, id_image_path, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$fullName, $studentId, $phoneNumber, $role, $course, $imagePath, $hashedPassword])) {
            echo json_encode(["status" => "success", "message" => "Account created successfully as " . $role . "!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database save error."]);
        }
    } catch (\Exception $e) {
        echo json_encode(["status" => "error", "message" => "Registration error: " . $e->getMessage()]);
    }
    exit();
}
?>
