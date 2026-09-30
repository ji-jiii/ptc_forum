<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ptc_forum";

$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName    = trim($_POST['fullName'] ?? '');
    $studentId   = trim($_POST['studentId'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $role        = trim($_POST['role'] ?? 'Student');
    $course      = trim($_POST['course'] ?? '');
    $password    = $_POST['password'] ?? '';

    // Check if ID exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE student_id = ?");
    $checkStmt->bind_param("s", $studentId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "ID Number is already registered."]);
        exit();
    }

    // Handle Image Upload
    $imagePath = null;
    if (isset($_FILES['student_id_image']) && $_FILES['student_id_image']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
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
    $stmt = $conn->prepare("INSERT INTO users (full_name, student_id, phone_number, role, course, id_image_path, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $fullName, $studentId, $phoneNumber, $role, $course, $imagePath, $hashedPassword);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Account created successfully as " . $role . "!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database save error: " . $stmt->error]);
    }

    $stmt->close();
}
$conn->close();
?>