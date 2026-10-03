<?php
header('Content-Type: application/json');
$host = 'localhost';
$db   = 'ptc_forum';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// GET ALL POSTS WITH COMMENTS & REPLIES
if ($action === 'get_posts') {
    try {
        $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            // Fetch Comments for this post
            $cStmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at ASC");
            $cStmt->execute([$post['id']]);
            $comments = $cStmt->fetchAll();

            foreach ($comments as &$comment) {
                // Fetch Replies for each comment
                $rStmt = $pdo->prepare("SELECT * FROM comment_replies WHERE comment_id = ? ORDER BY created_at ASC");
                $rStmt->execute([$comment['id']]);
                $comment['replies'] = $rStmt->fetchAll();
            }
            $post['comments'] = $comments;
        }

        echo json_encode($posts);
    } catch (\Exception $e) {
        echo json_encode([]);
    }
    exit;
}

// CREATE A NEW POST
if ($action === 'create_post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['studentId'] ?? '';
    $authorName = $_POST['authorName'] ?? '';
    $course = $_POST['course'] ?? '';
    $content = $_POST['content'] ?? '';
    $imageUrl = null;

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $fileName = time() . '_' . basename($_FILES['post_image']['name']);
        $targetFilePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['post_image']['tmp_name'], $targetFilePath)) {
            $imageUrl = $targetFilePath;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO posts (student_id, author_name, course, content, image_url, likes_count) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$studentId, $authorName, $course, $content, $imageUrl]);
        echo json_encode(['status' => 'success', 'message' => 'Post created successfully!']);
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save post.']);
    }
    exit;
}

// TOGGLE LIKE
if ($action === 'toggle_like' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['postId'] ?? 0;
    try {
        $stmt = $pdo->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->execute([$postId]);
        
        $fetch = $pdo->prepare("SELECT likes_count FROM posts WHERE id = ?");
        $fetch->execute([$postId]);
        $res = $fetch.fetch();
        
        echo json_encode(['status' => 'success', 'likes_count' => $res['likes_count'] ?? 0]);
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// ADD COMMENT
if ($action === 'add_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['postId'] ?? 0;
    $studentId = $_POST['studentId'] ?? '';
    $authorName = $_POST['authorName'] ?? '';
    $content = $_POST['content'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, student_id, author_name, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$postId, $studentId, $authorName, $content]);
        echo json_encode(['status' => 'success']);
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add comment.']);
    }
    exit;
}

// ADD REPLY TO A COMMENT
if ($action === 'add_reply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = $_POST['commentId'] ?? 0;
    $studentId = $_POST['studentId'] ?? '';
    $authorName = $_POST['authorName'] ?? '';
    $content = $_POST['content'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO comment_replies (comment_id, student_id, author_name, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$commentId, $studentId, $authorName, $content]);
        echo json_encode(['status' => 'success']);
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add reply.']);
    }
    exit;
}

// DELETE POST
if ($action === 'delete_post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['postId'] ?? 0;
    $studentId = $_POST['studentId'] ?? '';
    $userRole = $_POST['userRole'] ?? '';

    try {
        if ($userRole === 'Admin') {
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND student_id = ?");
            $stmt->execute([$postId, $studentId]);
        }
        echo json_encode(['status' => 'success']);
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error']);
    }
    exit;
}
