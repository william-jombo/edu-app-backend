<?php
// backend/api/students/announcements.php
// Student-only announcements access

require_once '../../config/database.php';

// ✅ DYNAMIC CORS HEADERS FROM ENV
$allowedOrigins = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:5173';
$allowedOriginsArray = explode(',', $allowedOrigins);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOriginsArray)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

// Verify student access
if (!$studentUserId || $studentRole !== 'student') {
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
        handleGet($conn, $studentUserId);
        break;
    case 'POST':
        handlePost($conn, $studentUserId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($conn, $studentUserId) {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        try {
            $query = "SELECT a.*, 
                      u.email as created_by_email,
                      ar.read_at,
                      CASE WHEN ar.id IS NOT NULL THEN true ELSE false END as is_read
                      FROM announcements a
                      LEFT JOIN users u ON a.created_by = u.id
                      LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                      WHERE a.status = 'active' 
                      AND (a.target_audience = 'students' OR a.target_audience = 'both')
                      ORDER BY 
                        CASE WHEN ar.id IS NULL THEN 0 ELSE 1 END,
                        a.priority = 'urgent' DESC,
                        a.priority = 'high' DESC,
                        a.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $studentUserId);
            $stmt->execute();
            
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($announcements as &$announcement) {
                $announcement['is_new'] = !$announcement['is_read'];
                $announcement['formatted_date'] = date('M d, Y h:i A', strtotime($announcement['created_at']));
            }
            
            echo json_encode(['success' => true, 'data' => $announcements]);
        } catch (Exception $e) {
            error_log("Error in handleGet list: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } elseif ($action === 'unread_count') {
        try {
            $query = "SELECT COUNT(*) as unread_count
                      FROM announcements a
                      LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                      WHERE a.status = 'active' 
                      AND (a.target_audience = 'students' OR a.target_audience = 'both')
                      AND ar.id IS NULL";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $studentUserId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'unread_count' => (int)$result['unread_count']]);
        } catch (Exception $e) {
            error_log("Error in handleGet unread_count: " . $e->getMessage());
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
            $query = "SELECT a.*, 
                      u.email as created_by_email,
                      ar.read_at,
                      CASE WHEN ar.id IS NOT NULL THEN true ELSE false END as is_read
                      FROM announcements a
                      LEFT JOIN users u ON a.created_by = u.id
                      LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                      WHERE a.id = :id 
                      AND a.status = 'active'
                      AND (a.target_audience = 'students' OR a.target_audience = 'both')";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':user_id', $studentUserId);
            $stmt->execute();
            
            $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($announcement) {
                $announcement['formatted_date'] = date('M d, Y h:i A', strtotime($announcement['created_at']));
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
        
    } elseif ($action === 'recent') {
        $limit = $_GET['limit'] ?? 5;
        
        try {
            $query = "SELECT a.id, a.title, a.priority, a.created_at,
                      CASE WHEN ar.id IS NOT NULL THEN true ELSE false END as is_read
                      FROM announcements a
                      LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                      WHERE a.status = 'active' 
                      AND (a.target_audience = 'students' OR a.target_audience = 'both')
                      ORDER BY a.created_at DESC
                      LIMIT :limit";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $studentUserId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $announcements]);
        } catch (Exception $e) {
            error_log("Error in handleGet recent: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

function handlePost($conn, $studentUserId) {
    $action = $_GET['action'] ?? 'mark_read';
    
    if ($action === 'mark_read') {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $announcementId = $data['announcement_id'] ?? null;
            
            if (!$announcementId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Announcement ID required']);
                return;
            }
            
            $verifyQuery = "SELECT id FROM announcements 
                           WHERE id = :id 
                           AND status = 'active'
                           AND (target_audience = 'students' OR target_audience = 'both')";
            $verifyStmt = $conn->prepare($verifyQuery);
            $verifyStmt->bindParam(':id', $announcementId);
            $verifyStmt->execute();
            
            if (!$verifyStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Announcement not found']);
                return;
            }
            
            $query = "INSERT INTO announcement_reads (announcement_id, user_id)
                      VALUES (:announcement_id, :user_id)
                      ON CONFLICT (announcement_id, user_id) DO NOTHING
                      RETURNING read_at";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':announcement_id', $announcementId);
            $stmt->bindParam(':user_id', $studentUserId);
            
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
        
    } elseif ($action === 'mark_all_read') {
        try {
            $query = "INSERT INTO announcement_reads (announcement_id, user_id)
                      SELECT a.id, :user_id
                      FROM announcements a
                      LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = :user_id
                      WHERE a.status = 'active'
                      AND (a.target_audience = 'students' OR a.target_audience = 'both')
                      AND ar.id IS NULL
                      ON CONFLICT (announcement_id, user_id) DO NOTHING";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $studentUserId);
            
            if ($stmt->execute()) {
                $count = $stmt->rowCount();
                echo json_encode([
                    'success' => true, 
                    'message' => "Marked {$count} announcements as read"
                ]);
            } else {
                throw new Exception('Failed to mark all as read');
            }
        } catch (Exception $e) {
            error_log("Error in handlePost mark_all_read: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>