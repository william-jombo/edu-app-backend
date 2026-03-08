<?php
// backend/api/admin/announcements.php
// Admin-only announcements management

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

// Get admin info from session
session_start();
$adminId = $_SESSION['user_id'] ?? null;
$adminRole = $_SESSION['role'] ?? null;

// ✅ DETAILED DEBUG LOGGING
error_log("=== ANNOUNCEMENTS API DEBUG ===");
error_log("Admin ID: " . ($adminId ?? 'NULL'));
error_log("Admin Role: " . ($adminRole ?? 'NULL'));
error_log("Request Method: " . $method);
error_log("Session Data: " . print_r($_SESSION, true));

// Verify admin access
if (!$adminId || $adminRole !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Admin access required. Please log in as admin.',
        'debug' => [
            'adminId' => $adminId,
            'adminRole' => $adminRole,
            'sessionExists' => isset($_SESSION),
            'sessionData' => $_SESSION
        ]
    ]);
    exit();
}

switch ($method) {
    case 'GET':
        handleGet($conn, $adminId);
        break;
    case 'POST':
        handlePost($conn, $adminId);
        break;
    case 'PUT':
        handlePut($conn, $adminId);
        break;
    case 'DELETE':
        handleDelete($conn, $adminId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($conn, $adminId) {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        try {
            $query = "SELECT a.*, 
                      u.email as created_by_email,
                      (SELECT COUNT(*) FROM announcement_reads WHERE announcement_id = a.id) as read_count,
                      (SELECT COUNT(DISTINCT s.id) 
                       FROM students s 
                       WHERE a.target_audience IN ('students', 'both')) as total_students,
                      (SELECT COUNT(DISTINCT t.id) 
                       FROM teachers t 
                       WHERE a.target_audience IN ('teachers', 'both')) as total_teachers
                      FROM announcements a
                      LEFT JOIN users u ON a.created_by = u.id
                      WHERE a.status != 'archived'
                      ORDER BY a.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($announcements as &$announcement) {
                $totalAudience = 0;
                if ($announcement['target_audience'] === 'students') {
                    $totalAudience = $announcement['total_students'];
                } elseif ($announcement['target_audience'] === 'teachers') {
                    $totalAudience = $announcement['total_teachers'];
                } else {
                    $totalAudience = $announcement['total_students'] + $announcement['total_teachers'];
                }
                
                $announcement['total_audience'] = $totalAudience;
                $announcement['read_percentage'] = $totalAudience > 0 
                    ? round(($announcement['read_count'] / $totalAudience) * 100, 1) 
                    : 0;
            }
            
            echo json_encode(['success' => true, 'data' => $announcements]);
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
            $query = "SELECT a.*, u.email as created_by_email
                      FROM announcements a
                      LEFT JOIN users u ON a.created_by = u.id
                      WHERE a.id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($announcement) {
                $readsQuery = "SELECT ar.read_at, u.email, u.role
                              FROM announcement_reads ar
                              JOIN users u ON ar.user_id = u.id
                              WHERE ar.announcement_id = :id
                              ORDER BY ar.read_at DESC";
                $readsStmt = $conn->prepare($readsQuery);
                $readsStmt->bindParam(':id', $id);
                $readsStmt->execute();
                
                $announcement['reads'] = $readsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $announcement]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Announcement not found']);
            }
        } catch (Exception $e) {
            error_log("Error in handleGet single: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } elseif ($action === 'stats') {
        try {
            $statsQuery = "SELECT 
                           COUNT(*) as total_announcements,
                           COUNT(CASE WHEN status = 'active' THEN 1 END) as active_announcements,
                           COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_announcements,
                           COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_announcements,
                           COUNT(CASE WHEN target_audience = 'students' THEN 1 END) as student_announcements,
                           COUNT(CASE WHEN target_audience = 'teachers' THEN 1 END) as teacher_announcements,
                           COUNT(CASE WHEN target_audience = 'both' THEN 1 END) as everyone_announcements
                           FROM announcements
                           WHERE status != 'archived'";
            
            $stmt = $conn->prepare($statsQuery);
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

function handlePost($conn, $adminId) {
    try {
        $rawInput = file_get_contents('php://input');
        error_log("=== CREATE ANNOUNCEMENT REQUEST ===");
        error_log("Raw input: " . $rawInput);
        
        $data = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }
        
        error_log("Decoded data: " . print_r($data, true));
        
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $targetAudience = $data['target_audience'] ?? 'both';
        $priority = $data['priority'] ?? 'normal';
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
        
        if (!in_array($targetAudience, ['students', 'teachers', 'both'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid target audience: ' . $targetAudience]);
            return;
        }
        
        if (!in_array($priority, ['low', 'normal', 'high', 'urgent'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid priority: ' . $priority]);
            return;
        }
        
        if (!in_array($status, ['active', 'draft'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status: ' . $status]);
            return;
        }
        
        error_log("Validation passed. Inserting into database...");
        error_log("Admin ID: " . $adminId);
        
        $query = "INSERT INTO announcements (title, content, target_audience, priority, status, created_by)
                  VALUES (:title, :content, :target_audience, :priority, :status, :created_by)
                  RETURNING id, created_at";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':target_audience', $targetAudience);
        $stmt->bindParam(':priority', $priority);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':created_by', $adminId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Announcement created successfully. ID: " . $result['id']);
            echo json_encode([
                'success' => true, 
                'message' => 'Announcement created successfully', 
                'data' => $result
            ]);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Database error: " . print_r($errorInfo, true));
            throw new Exception('Failed to create announcement: ' . $errorInfo[2]);
        }
    } catch (Exception $e) {
        error_log("=== ERROR IN CREATE ANNOUNCEMENT ===");
        error_log("Error message: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
}

function handlePut($conn, $adminId) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID required']);
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
    if (isset($data['target_audience'])) {
        if (!in_array($data['target_audience'], ['students', 'teachers', 'both'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid target audience']);
            return;
        }
        $updates[] = "target_audience = :target_audience";
        $params[':target_audience'] = $data['target_audience'];
    }
    if (isset($data['priority'])) {
        if (!in_array($data['priority'], ['low', 'normal', 'high', 'urgent'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid priority']);
            return;
        }
        $updates[] = "priority = :priority";
        $params[':priority'] = $data['priority'];
    }
    if (isset($data['status'])) {
        if (!in_array($data['status'], ['active', 'draft', 'archived'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            return;
        }
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
        $query = "UPDATE announcements SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Announcement updated successfully']);
        } else {
            throw new Exception('Failed to update announcement');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleDelete($conn, $adminId) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID required']);
        return;
    }
    
    $action = $_GET['delete_action'] ?? 'soft';
    
    try {
        if ($action === 'hard') {
            $query = "DELETE FROM announcements WHERE id = :id";
        } else {
            $query = "UPDATE announcements SET status = 'archived', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => $action === 'hard' ? 'Announcement permanently deleted' : 'Announcement archived'
            ]);
        } else {
            throw new Exception('Failed to delete announcement');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>