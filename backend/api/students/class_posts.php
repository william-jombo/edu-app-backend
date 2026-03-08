<?php
// backend/api/students/class_posts.php
// Student class communications/posts access and commenting

require_once '../../config/database.php';

// ✅ DYNAMIC CORS HEADERS FROM ENV
$allowedOrigins = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5173';
$allowedOriginsArray = explode(',', $allowedOrigins);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOriginsArray)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

// Get student info from session
session_start();
$studentUserId = $_SESSION['user_id'] ?? null;
$studentRole = $_SESSION['role'] ?? null;

// Get student ID from users table
$studentId = null;
$studentClassId = null;
if ($studentUserId && $studentRole === 'student') {
    $stmt = $conn->prepare("SELECT id, class_id FROM students WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $studentUserId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $studentId = $student['id'] ?? null;
    $studentClassId = $student['class_id'] ?? null;
}

// Verify student access
if (!$studentId) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Student access required',
        'debug' => [
            'userId' => $studentUserId,
            'role' => $studentRole
        ]
    ]);
    exit();
}

switch ($method) {
    case 'GET':
        handleGet($conn, $studentId, $studentClassId);
        break;
    case 'POST':
        handlePost($conn, $studentId, $studentClassId);
        break;
    case 'PUT':
        handlePut($conn, $studentId);
        break;
    case 'DELETE':
        handleDelete($conn, $studentId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($conn, $studentId, $studentClassId) {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        try {
            $query = "SELECT cp.*, 
                      c.class_name,
                      s.subject_name,
                      t.firstname || ' ' || t.lastname as teacher_name,
                      t.profile_pic as teacher_profile_pic,
                      (SELECT COUNT(*) FROM class_post_comments WHERE post_id = cp.id AND status = 'active') as comment_count,
                      cpr.read_at,
                      CASE WHEN cpr.id IS NOT NULL THEN true ELSE false END as is_read
                      FROM class_posts cp
                      LEFT JOIN classes c ON cp.class_id = c.id
                      LEFT JOIN subjects s ON cp.subject_id = s.id
                      LEFT JOIN teachers t ON cp.teacher_id = t.id
                      LEFT JOIN class_post_reads cpr ON cp.id = cpr.post_id AND cpr.student_id = :student_id
                      WHERE cp.status = 'active' 
                      AND cp.class_id = :class_id
                      ORDER BY 
                        cp.pinned DESC,
                        CASE WHEN cpr.id IS NULL THEN 0 ELSE 1 END,
                        cp.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':class_id', $studentClassId);
            $stmt->execute();
            
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($posts as &$post) {
                $post['is_new'] = !$post['is_read'];
                $post['formatted_date'] = date('M d, Y h:i A', strtotime($post['created_at']));
            }
            
            echo json_encode(['success' => true, 'data' => $posts]);
        } catch (Exception $e) {
            error_log("Error in handleGet list: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } elseif ($action === 'single') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required']);
            return;
        }
        
        try {
            $query = "SELECT cp.*, 
                      c.class_name,
                      s.subject_name,
                      t.firstname || ' ' || t.lastname as teacher_name,
                      t.profile_pic as teacher_profile_pic,
                      cpr.read_at,
                      CASE WHEN cpr.id IS NOT NULL THEN true ELSE false END as is_read
                      FROM class_posts cp
                      LEFT JOIN classes c ON cp.class_id = c.id
                      LEFT JOIN subjects s ON cp.subject_id = s.id
                      LEFT JOIN teachers t ON cp.teacher_id = t.id
                      LEFT JOIN class_post_reads cpr ON cp.id = cpr.post_id AND cpr.student_id = :student_id
                      WHERE cp.id = :id 
                      AND cp.status = 'active'
                      AND cp.class_id = :class_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':class_id', $studentClassId);
            $stmt->execute();
            
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post) {
                $post['formatted_date'] = date('M d, Y h:i A', strtotime($post['created_at']));
                
                // Get comments
                $commentsQuery = "SELECT cpc.*, 
                                  st.firstname || ' ' || st.lastname as student_name,
                                  st.profile_pic as student_profile_pic,
                                  t.firstname || ' ' || t.lastname as teacher_name,
                                  t.profile_pic as teacher_profile_pic,
                                  CASE WHEN cpc.student_id = :student_id THEN true ELSE false END as is_mine
                                  FROM class_post_comments cpc
                                  LEFT JOIN students st ON cpc.student_id = st.id
                                  LEFT JOIN teachers t ON cpc.teacher_id = t.id
                                  WHERE cpc.post_id = :post_id AND cpc.status = 'active'
                                  ORDER BY cpc.created_at ASC";
                
                $commentsStmt = $conn->prepare($commentsQuery);
                $commentsStmt->bindParam(':post_id', $id);
                $commentsStmt->bindParam(':student_id', $studentId);
                $commentsStmt->execute();
                
                $post['comments'] = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $post]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Post not found']);
            }
        } catch (Exception $e) {
            error_log("Error in handleGet single: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } elseif ($action === 'unread_count') {
        try {
            $query = "SELECT COUNT(*) as unread_count
                      FROM class_posts cp
                      LEFT JOIN class_post_reads cpr ON cp.id = cpr.post_id AND cpr.student_id = :student_id
                      WHERE cp.status = 'active' 
                      AND cp.class_id = :class_id
                      AND cpr.id IS NULL";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':class_id', $studentClassId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'unread_count' => (int)$result['unread_count']]);
        } catch (Exception $e) {
            error_log("Error in handleGet unread_count: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

function handlePost($conn, $studentId, $studentClassId) {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'mark_read') {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $postId = $data['post_id'] ?? null;
            
            if (!$postId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Post ID required']);
                return;
            }
            
            // Verify post exists and belongs to student's class
            $verifyQuery = "SELECT id FROM class_posts 
                           WHERE id = :id 
                           AND status = 'active'
                           AND class_id = :class_id";
            $verifyStmt = $conn->prepare($verifyQuery);
            $verifyStmt->bindParam(':id', $postId);
            $verifyStmt->bindParam(':class_id', $studentClassId);
            $verifyStmt->execute();
            
            if (!$verifyStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Post not found']);
                return;
            }
            
            $query = "INSERT INTO class_post_reads (post_id, student_id)
                      VALUES (:post_id, :student_id)
                      ON CONFLICT (post_id, student_id) DO NOTHING
                      RETURNING read_at";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':post_id', $postId);
            $stmt->bindParam(':student_id', $studentId);
            
            if ($stmt->execute()) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Marked as read',
                    'read_at' => $result['read_at'] ?? null
                ]);
            } else {
                throw new Exception('Failed to mark as read');
            }
        } catch (Exception $e) {
            error_log("Error in handlePost mark_read: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } elseif ($action === 'add_comment') {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $postId = $data['post_id'] ?? null;
            $comment = trim($data['comment'] ?? '');
            $parentCommentId = $data['parent_comment_id'] ?? null;
            
            if (!$postId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Post ID required']);
                return;
            }
            
            if (empty($comment)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
                return;
            }
            
            // Verify post exists, belongs to student's class, and allows comments
            $verifyQuery = "SELECT id, allow_comments FROM class_posts 
                           WHERE id = :id 
                           AND status = 'active'
                           AND class_id = :class_id";
            $verifyStmt = $conn->prepare($verifyQuery);
            $verifyStmt->bindParam(':id', $postId);
            $verifyStmt->bindParam(':class_id', $studentClassId);
            $verifyStmt->execute();
            
            $post = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$post) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Post not found']);
                return;
            }
            
            if (!$post['allow_comments']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Comments are not allowed on this post']);
                return;
            }
            
            $query = "INSERT INTO class_post_comments (post_id, student_id, comment, parent_comment_id)
                      VALUES (:post_id, :student_id, :comment, :parent_comment_id)
                      RETURNING id, created_at";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':post_id', $postId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':comment', $comment);
            $stmt->bindParam(':parent_comment_id', $parentCommentId);
            
            if ($stmt->execute()) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Comment added successfully',
                    'data' => $result
                ]);
            } else {
                throw new Exception('Failed to add comment');
            }
        } catch (Exception $e) {
            error_log("Error in handlePost add_comment: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

function handlePut($conn, $studentId) {
    // Update student's own comment
    $data = json_decode(file_get_contents('php://input'), true);
    $commentId = $data['comment_id'] ?? null;
    $comment = trim($data['comment'] ?? '');
    
    if (!$commentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Comment ID required']);
        return;
    }
    
    if (empty($comment)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        return;
    }
    
    // Verify ownership
    $verifyQuery = "SELECT id FROM class_post_comments 
                   WHERE id = :id AND student_id = :student_id AND status = 'active'";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bindParam(':id', $commentId);
    $verifyStmt->bindParam(':student_id', $studentId);
    $verifyStmt->execute();
    
    if (!$verifyStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    try {
        $query = "UPDATE class_post_comments 
                  SET comment = :comment, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':comment', $comment);
        $stmt->bindParam(':id', $commentId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Comment updated successfully']);
        } else {
            throw new Exception('Failed to update comment');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleDelete($conn, $studentId) {
    // Delete student's own comment
    $commentId = $_GET['comment_id'] ?? null;
    
    if (!$commentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Comment ID required']);
        return;
    }
    
    // Verify ownership
    $verifyQuery = "SELECT id FROM class_post_comments 
                   WHERE id = :id AND student_id = :student_id";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bindParam(':id', $commentId);
    $verifyStmt->bindParam(':student_id', $studentId);
    $verifyStmt->execute();
    
    if (!$verifyStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    try {
        $query = "UPDATE class_post_comments 
                  SET status = 'deleted', updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $commentId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
        } else {
            throw new Exception('Failed to delete comment');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>