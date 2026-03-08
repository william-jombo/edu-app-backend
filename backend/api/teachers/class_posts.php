<?php
// backend/api/teacher/class_posts.php
// Teacher class communications/posts management

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

// Get teacher info from session
session_start();
$teacherUserId = $_SESSION['user_id'] ?? null;
$teacherRole = $_SESSION['role'] ?? null;

// Get teacher ID from users table
$teacherId = null;
if ($teacherUserId && $teacherRole === 'teacher') {
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $teacherUserId);
    $stmt->execute();
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    $teacherId = $teacher['id'] ?? null;
}

// Verify teacher access
if (!$teacherId) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Teacher access required',
        'debug' => [
            'userId' => $teacherUserId,
            'role' => $teacherRole,
            'teacherId' => $teacherId
        ]
    ]);
    exit();
}

switch ($method) {
    case 'GET':
        handleGet($conn, $teacherId);
        break;
    case 'POST':
        handlePost($conn, $teacherId);
        break;
    case 'PUT':
        handlePut($conn, $teacherId);
        break;
    case 'DELETE':
        handleDelete($conn, $teacherId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($conn, $teacherId) {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        $classId = $_GET['class_id'] ?? null;
        
        try {
            $query = "SELECT cp.*, 
                      c.class_name,
                      s.subject_name,
                      t.firstname || ' ' || t.lastname as teacher_name,
                      (SELECT COUNT(*) FROM class_post_comments WHERE post_id = cp.id AND status = 'active') as comment_count,
                      (SELECT COUNT(*) FROM class_post_reads WHERE post_id = cp.id) as read_count,
                      (SELECT COUNT(DISTINCT ce.student_id) FROM class_enrollments ce WHERE ce.class_id = cp.class_id AND ce.status = 'active') as total_students
                      FROM class_posts cp
                      LEFT JOIN classes c ON cp.class_id = c.id
                      LEFT JOIN subjects s ON cp.subject_id = s.id
                      LEFT JOIN teachers t ON cp.teacher_id = t.id
                      WHERE cp.teacher_id = :teacher_id";
            
            if ($classId) {
                $query .= " AND cp.class_id = :class_id";
            }
            
            $query .= " ORDER BY cp.pinned DESC, cp.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':teacher_id', $teacherId);
            if ($classId) {
                $stmt->bindParam(':class_id', $classId);
            }
            $stmt->execute();
            
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($posts as &$post) {
                $post['read_percentage'] = $post['total_students'] > 0 
                    ? round(($post['read_count'] / $post['total_students']) * 100, 1) 
                    : 0;
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
                      t.firstname || ' ' || t.lastname as teacher_name
                      FROM class_posts cp
                      LEFT JOIN classes c ON cp.class_id = c.id
                      LEFT JOIN subjects s ON cp.subject_id = s.id
                      LEFT JOIN teachers t ON cp.teacher_id = t.id
                      WHERE cp.id = :id AND cp.teacher_id = :teacher_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':teacher_id', $teacherId);
            $stmt->execute();
            
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post) {
                // Get comments
                $commentsQuery = "SELECT cpc.*, 
                                  st.firstname || ' ' || st.lastname as student_name,
                                  st.profile_pic as student_profile_pic,
                                  t.firstname || ' ' || t.lastname as teacher_name,
                                  t.profile_pic as teacher_profile_pic
                                  FROM class_post_comments cpc
                                  LEFT JOIN students st ON cpc.student_id = st.id
                                  LEFT JOIN teachers t ON cpc.teacher_id = t.id
                                  WHERE cpc.post_id = :post_id AND cpc.status = 'active'
                                  ORDER BY cpc.created_at ASC";
                
                $commentsStmt = $conn->prepare($commentsQuery);
                $commentsStmt->bindParam(':post_id', $id);
                $commentsStmt->execute();
                
                $post['comments'] = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get read status
                $readsQuery = "SELECT cpr.read_at, s.firstname || ' ' || s.lastname as student_name, s.profile_pic
                              FROM class_post_reads cpr
                              JOIN students s ON cpr.student_id = s.id
                              WHERE cpr.post_id = :post_id
                              ORDER BY cpr.read_at DESC";
                
                $readsStmt = $conn->prepare($readsQuery);
                $readsStmt->bindParam(':post_id', $id);
                $readsStmt->execute();
                
                $post['reads'] = $readsStmt->fetchAll(PDO::FETCH_ASSOC);
                
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
        
    } elseif ($action === 'my_classes') {
        // Get classes where teacher is assigned
        try {
            $query = "SELECT DISTINCT c.id, c.class_name, c.grade_level,
                      (SELECT COUNT(*) FROM class_enrollments WHERE class_id = c.id AND status = 'active') as student_count
                      FROM classes c
                      JOIN teacher_assignments ta ON c.id = ta.class_id
                      WHERE ta.teacher_id = :teacher_id AND ta.status = 'active'
                      ORDER BY c.class_name";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':teacher_id', $teacherId);
            $stmt->execute();
            
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $classes]);
        } catch (Exception $e) {
            error_log("Error in handleGet my_classes: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } elseif ($action === 'stats') {
        try {
            $statsQuery = "SELECT 
                           COUNT(*) as total_posts,
                           COUNT(CASE WHEN status = 'active' THEN 1 END) as active_posts,
                           COUNT(CASE WHEN pinned = true THEN 1 END) as pinned_posts,
                           SUM((SELECT COUNT(*) FROM class_post_comments WHERE post_id = class_posts.id)) as total_comments
                           FROM class_posts
                           WHERE teacher_id = :teacher_id";
            
            $stmt = $conn->prepare($statsQuery);
            $stmt->bindParam(':teacher_id', $teacherId);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            error_log("Error in handleGet stats: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

function handlePost($conn, $teacherId) {
    try {
        $rawInput = file_get_contents('php://input');
        error_log("=== CREATE CLASS POST REQUEST ===");
        error_log("Raw input: " . $rawInput);
        
        $data = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }
        
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $classId = $data['class_id'] ?? null;
        
        // FIX: Handle subject_id properly - convert empty string to null
        $subjectId = !empty($data['subject_id']) ? $data['subject_id'] : null;
        
        $postType = $data['post_type'] ?? 'general';
        $allowComments = $data['allow_comments'] ?? true;
        $pinned = $data['pinned'] ?? false;
        $status = $data['status'] ?? 'active';
        
        // Validation
        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Title is required']);
            return;
        }
        
        if (empty($content)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Content is required']);
            return;
        }
        
        if (!$classId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Class is required']);
            return;
        }
        
        // Verify teacher has access to this class
        $verifyQuery = "SELECT id FROM teacher_assignments 
                       WHERE teacher_id = :teacher_id AND class_id = :class_id AND status = 'active'";
        $verifyStmt = $conn->prepare($verifyQuery);
        $verifyStmt->bindParam(':teacher_id', $teacherId);
        $verifyStmt->bindParam(':class_id', $classId);
        $verifyStmt->execute();
        
        if (!$verifyStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have access to this class']);
            return;
        }
        
        if (!in_array($postType, ['general', 'material', 'reminder', 'question'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid post type']);
            return;
        }
        
        $query = "INSERT INTO class_posts 
                  (teacher_id, class_id, subject_id, title, content, post_type, allow_comments, pinned, status)
                  VALUES (:teacher_id, :class_id, :subject_id, :title, :content, :post_type, :allow_comments, :pinned, :status)
                  RETURNING id, created_at";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId);
        $stmt->bindParam(':class_id', $classId);
        
        // FIX: Bind as integer or null
        $stmt->bindParam(':subject_id', $subjectId, $subjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':post_type', $postType);
        $stmt->bindParam(':allow_comments', $allowComments, PDO::PARAM_BOOL);
        $stmt->bindParam(':pinned', $pinned, PDO::PARAM_BOOL);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Class post created successfully. ID: " . $result['id']);
            echo json_encode([
                'success' => true, 
                'message' => 'Post created successfully', 
                'data' => $result
            ]);
        } else {
            throw new Exception('Failed to create post');
        }
    } catch (Exception $e) {
        error_log("=== ERROR IN CREATE CLASS POST ===");
        error_log("Error message: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
}

function handlePut($conn, $teacherId) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID required']);
        return;
    }
    
    // Verify ownership
    $verifyQuery = "SELECT id FROM class_posts WHERE id = :id AND teacher_id = :teacher_id";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bindParam(':id', $id);
    $verifyStmt->bindParam(':teacher_id', $teacherId);
    $verifyStmt->execute();
    
    if (!$verifyStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $updates = [];
    $params = [':id' => $id];
    
    if (isset($data['title'])) {
        $updates[] = "title = :title";
        $params[':title'] = $data['title'];
    }
    if (isset($data['content'])) {
        $updates[] = "content = :content";
        $params[':content'] = $data['content'];
    }
    if (isset($data['post_type'])) {
        $updates[] = "post_type = :post_type";
        $params[':post_type'] = $data['post_type'];
    }
    if (isset($data['allow_comments'])) {
        $updates[] = "allow_comments = :allow_comments";
        $params[':allow_comments'] = $data['allow_comments'];
    }
    if (isset($data['pinned'])) {
        $updates[] = "pinned = :pinned";
        $params[':pinned'] = $data['pinned'];
    }
    if (isset($data['status'])) {
        $updates[] = "status = :status";
        $params[':status'] = $data['status'];
    }
    
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
    
    if (count($updates) === 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }
    
    try {
        $query = "UPDATE class_posts SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
        } else {
            throw new Exception('Failed to update post');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleDelete($conn, $teacherId) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID required']);
        return;
    }
    
    // Verify ownership
    $verifyQuery = "SELECT id FROM class_posts WHERE id = :id AND teacher_id = :teacher_id";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bindParam(':id', $id);
    $verifyStmt->bindParam(':teacher_id', $teacherId);
    $verifyStmt->execute();
    
    if (!$verifyStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $action = $_GET['delete_action'] ?? 'soft';
    
    try {
        if ($action === 'hard') {
            $query = "DELETE FROM class_posts WHERE id = :id";
        } else {
            $query = "UPDATE class_posts SET status = 'archived', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => $action === 'hard' ? 'Post permanently deleted' : 'Post archived'
            ]);
        } else {
            throw new Exception('Failed to delete post');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>